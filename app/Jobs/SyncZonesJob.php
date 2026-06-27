<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Zone;
use Carbon\Carbon;

class SyncZonesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;
    public $backoff = [60, 180, 300]; // Retry après 1, 3, 5 minutes
    public $maxExceptions = 3;

    private $forceSync = false;

    /**
     * Crée une nouvelle instance de job
     */
    public function __construct($forceSync = false)
    {
        $this->forceSync = $forceSync;
    }

    /**
     * Exécute le job
     */
    public function handle()
    {
        Log::info('Démarrage du job SyncZonesJob', [
            'force_sync' => $this->forceSync
        ]);

        try {
            if (!$this->shouldSkip()) {
                $this->syncZones();
                $this->markSynced();
            }

            Log::info('SyncZonesJob terminé avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur dans SyncZonesJob: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Pour les retries
        }
    }

    /**
     * Synchronise les zones
     */
    private function syncZones()
    {
        $config = DB::table('access_configs')->first();

        if (!$config || !$config->general_token) {
            Log::warning('Pas de token configuré pour la synchronisation des zones');
            return;
        }

        $page = 1;
        $hasMore = true;
        $totalSynced = 0;
        $batchSize = config('services.zones.batch_size', 100);

        while ($hasMore && $page <= config('services.zones.max_pages', 50)) {
            $response = $this->fetchApiPage($config, $page, $batchSize);

            if (!$response || !isset($response['results'])) {
                break;
            }

            $zones = $response['results'];

            if (empty($zones)) {
                break;
            }

            // Traitement par lot
            $this->processZonesBatch($zones);
            $totalSynced += count($zones);

            // Vérifier s'il y a une page suivante
            $hasMore = isset($response['next']) && !empty($response['next']);
            $page++;

            // Pause courte pour éviter de surcharger l'API
            if ($hasMore) {
                usleep(300000); // 0.3 seconde
            }
        }

        Log::info("Zones synchronisées: {$totalSynced}");

        // Nettoyer les anciennes zones
        $this->cleanupOldZones();

        return $totalSynced;
    }

    /**
     * Récupère une page de l'API
     */
    private function fetchApiPage($config, $page, $limit)
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Token " . $config->general_token,
                "Accept" => "application/json"
            ])
            ->timeout(45)
            ->retry(3, 1000)
            ->get(config('services.checktime.base_url') . '/personnel/api/areas/', [
                'page' => $page,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("API échouée page {$page}: " . $response->status());
            return null;

        } catch (\Exception $e) {
            Log::error("Erreur API zones: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Traite un lot de zones
     */
    private function processZonesBatch(array $zones)
    {
        $batchData = [];
        $now = now();

        foreach ($zones as $zone) {
            if (empty($zone['area_code']) || empty($zone['area_name'])) {
                continue;
            }

            $batchData[] = [
                'code' => $zone['area_code'],
                'name' => $zone['area_name'],
                'description' => $zone['description'] ?? null,
                'parent_code' => $zone['parent_area'] ?? null,
                'external_id' => $zone['id'] ?? null,
                'metadata' => json_encode($zone),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        if (empty($batchData)) {
            return;
        }

        // Upsert en masse
        $this->bulkUpsertZones($batchData);
    }

    /**
     * Upsert en masse optimisé
     */
    private function bulkUpsertZones(array $batchData)
    {
        try {
            Zone::upsert(
                $batchData,
                ['code'],
                ['name', 'description', 'parent_code', 'metadata', 'updated_at']
            );

        } catch (\Exception $e) {
            Log::error("Erreur bulk upsert: " . $e->getMessage());

            // Fallback: upsert un par un
            foreach ($batchData as $data) {
                try {
                    Zone::updateOrCreate(
                        ['code' => $data['code']],
                        $data
                    );
                } catch (\Exception $innerException) {
                    Log::error("Erreur single upsert: " . $innerException->getMessage());
                }
            }
        }
    }

    /**
     * Vérifie si on doit ignorer la synchronisation
     */
    private function shouldSkip()
    {
        if ($this->forceSync) {
            return false;
        }

        $lastSync = cache()->get('zones_sync_timestamp', 0);
        $syncInterval = config('services.zones.sync_interval', 3600); // 1 heure

        return (time() - $lastSync) < $syncInterval;
    }

    /**
     * Marque la synchronisation comme effectuée
     */
    private function markSynced()
    {
        cache()->put('zones_sync_timestamp', time(), now()->addHours(2));
    }

    /**
     * Nettoie les anciennes zones
     */
    private function cleanupOldZones()
    {
        $threshold = now()->subDays(config('services.zones.cleanup_days', 7));

        $deleted = Zone::where('updated_at', '<', $threshold)
            ->whereDoesntHave('employees')
            ->delete();

        if ($deleted > 0) {
            Log::info("Nettoyage: {$deleted} anciennes zones supprimées");
        }
    }

    /**
     * Gestion des échecs
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SyncZonesJob a échoué: ' . $exception->getMessage());
    }
}