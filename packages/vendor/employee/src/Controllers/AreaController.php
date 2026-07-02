<?php

namespace Vendor\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CheckTimeService;
use App\Models\Zone;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AreaController extends Controller
{
    private CheckTimeService $api;

    public function __construct(CheckTimeService $api)
    {
        $this->api = $api;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $this->checkAndSyncIfNeeded(1);
            return $this->getLocalZones($request);
        }
        
        $this->checkAndSyncIfNeeded(1);
        
        return view('employee::areas.index');
    }

   public function store(Request $request)
{
    try {
        // Valider les données d'entrée
        $validated = $request->validate([
           // 'code' => 'required|string|max:50',
            'name' => 'required|string|max:100'
        ]);

        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        // synch last area
        $this->sync($request);

        // get max code 
        $code = Zone::max('code');
        $nextCode = $code ? $code + 1 : 1;

        // Préparer les données pour l'API externe
        $apiData = [
            'area_code' => $nextCode,
            'area_name' => $validated['name'],
        ];

        // Ajouter parent_area si fourni
        if (!empty($validated['parent_area'])) {
            $apiData['parent_area'] = $validated['parent_area'];
        }

        // Envoyer la requête à l'API externe
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->post(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/areas/', $apiData);

        // Vérifier la réponse
        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après création
            
            return response()->json([
                'success' => true,
                'message' => 'Zone créée avec succès',
                'data' => $responseData
            ]);
        } else {
            // Gérer les erreurs de l'API
            $errorMessage = 'Erreur lors de la création';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 401) {
                $errorMessage = 'Non autorisé - Token invalide';
            } elseif ($response->status() === 409) {
                $errorMessage = 'Le code de zone existe déjà';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status()
            ], $response->status());
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Connexion à l\'API impossible. Vérifiez votre connexion réseau.'
        ], 503);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne: ' . $e->getMessage()
        ], 500);
    }
}

public function update(Request $request, $id)
{
    try {
        // Valider les données d'entrée
        $validated = $request->validate([
           // 'code' => 'required|string|max:50|unique:zones,code,' . $id,
            'name' => 'required|string|max:255',
        ]);

        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        // Préparer les données pour l'API externe
        $apiData = [
            'area_name' => $validated['name'],
        ];

        // Envoyer la requête à l'API externe pour mettre à jour
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->patch(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/areas/' . $id . '/', $apiData);

        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après création
            
            return response()->json([
                'success' => true,
                'message' => 'Zone modifiée avec succès',
                'data' => $responseData
            ]);
        } else {
            $errorMessage = 'Erreur lors de la modification';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Zone non trouvée';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status()
            ], $response->status());
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne: ' . $e->getMessage()
        ], 500);
    }
}

public function destroy($id)
{
    try {
        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        // Envoyer la requête à l'API externe pour supprimer
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->delete(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/areas/' . $id . '/');

        if ($response->successful()) {
            // Optionnel: Supprimer aussi en local
            Zone::where('area_id', $id)->delete();
         //   $this->sync(new Request()); // Synchroniser après suppression
            
            return response()->json([
                'success' => true,
                'message' => 'Zone supprimée avec succès'
            ]);
        } else {
            $errorMessage = 'Erreur lors de la suppression';
            
            if ($response->status() === 404) {
                $errorMessage = 'Zone non trouvée';
            } elseif ($response->status() === 403) {
                $errorMessage = 'Non autorisé à supprimer cette zone';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status()
            ], $response->status());
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Vérifie et synchronise si nécessaire pour un client spécifique
     */
    private function checkAndSyncIfNeeded(int $clientId): bool
    {
        // Ne pas synchroniser si déjà en cours
        if (Cache::get('zones_syncing_' . $clientId, false)) {
            return false;
        }
        
        $lastSync = Cache::get('zones_last_sync_' . $clientId, 0);
        $syncInterval = 300; // 5 minutes pour les tests, ajustez selon vos besoins
        
        // Si jamais synchronisé ou si ça fait plus de X secondes
        if ($lastSync == 0 || (time() - $lastSync) > $syncInterval) {
            // Marquer comme en cours de synchronisation
            Cache::put('zones_syncing_' . $clientId, true, 300);
            
            // Lancer la synchronisation EN TEMPS RÉEL (pas en arrière-plan)
            $this->syncZonesForClientNow($clientId);
            
            return true;
        }
        
        return false;
    }

    /**
     * Synchronise les zones pour le client spécifié
     */
    private function syncZonesForClientNow(int $clientId): int
    {
        try {
            Log::info("Début de la synchronisation des zones pour le client {$clientId}");
            
            $token = $this->api->getGeneralToken();

            if (!$token) {
                Log::warning("Aucun token configuré pour le client {$clientId}");
                Cache::forget('zones_syncing_' . $clientId);
                return 0;
            }
            
            // Récupérer toutes les zones de l'API
            $allZones = $this->fetchAllZonesFromAPI($token);
            
            if (empty($allZones)) {
                Cache::forget('zones_syncing_' . $clientId);
                return 0;
            }
            
            // Synchroniser chaque zone
            $syncedCount = 0;
            foreach ($allZones as $zoneData) {
                if ($this->syncSingleZone($zoneData, $clientId)) {
                    $syncedCount++;
                }
            }
            
            // SUPPRIMER les zones qui n'existent plus dans l'API
            $this->deleteMissingZones($allZones, $clientId);
            
            // Mettre à jour le cache
            Cache::put('zones_last_sync_' . $clientId, time(), now()->addHours(2));
            Cache::forget('zones_syncing_' . $clientId);
            
            Log::info("Synchronisation terminée pour le client {$clientId}: {$syncedCount} zones");
            
            return $syncedCount;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncZonesForClientNow client {$clientId}: " . $e->getMessage());
            Cache::forget('zones_syncing_' . $clientId);
            return 0;
        }
    }

    /**
     * Récupère TOUTES les zones depuis l'API (avec pagination)
     */
    private function fetchAllZonesFromAPI(string $token): array
    {
        $allZones = [];
        $page = 1;
        $hasMore = true;
        
        try {
            while ($hasMore && $page <= 20) { // Limite de sécurité
                $response = Http::withHeaders([
                    "Authorization" => "Token " . $token,
                    "Accept" => "application/json"
                ])
                ->timeout(60)
                ->retry(3, 1000)
                ->get(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/areas/', [
                    'page' => $page,
                    'limit' => 300
                ]);
                
                if (!$response->successful()) {
                    Log::warning("Échec de récupération des zones - Page {$page}");
                    break;
                }
                
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
                    break;
                }
                
                // Ajouter les zones de cette page
                $allZones = array_merge($allZones, $data['data']);
                
                // Vérifier s'il y a une page suivante
                $hasMore = isset($data['next']) && !empty($data['next']);
                $page++;
                
                // Petite pause pour éviter de surcharger l'API
                if ($hasMore) {
                    usleep(200000); // 0.2 seconde
                }
            }
            
            Log::info("Récupéré " . count($allZones) . " zones depuis l'API");
            
        } catch (\Exception $e) {
            Log::error('Erreur fetchAllZonesFromAPI: ' . $e->getMessage());
        }
        
        return $allZones;
    }

    /**
     * Synchronise une seule zone
     */
    private function syncSingleZone(array $zoneData, int $clientId): bool
    {
        try {
            // Vérifier les données minimales
            if (empty($zoneData['area_code']) || empty($zoneData['area_name'])) {
                return false;
            }
            
            $zoneCode = $zoneData['area_code'];
            
            // Vérifier si la zone existe déjà
            $existingZone = Zone::where('code', $zoneCode)
                               ->where('area_id', $zoneData['id'] ?? 0)
                               ->first();
            
            // Préparer les données
            $zoneAttributes = [
                'name' => $zoneData['area_name'],
                'area_id' => $zoneData['id'] ?? null,
                'description' => $zoneData['description'] ?? null,
                'parent_code' => $zoneData['parent_area'] ?? null,
                'external_id' => $zoneData['id'] ?? null,
                'metadata' => json_encode($zoneData),
                'updated_at' => now(),
            ];
            
            if ($existingZone) {
                // Mettre à jour la zone existante
                $existingZone->update($zoneAttributes);
                Log::debug("Zone mise à jour: {$zoneCode} (client {$clientId})");
            } else {
                // Créer une nouvelle zone
                $zoneAttributes['code'] = $zoneCode;
                // client_id no longer needed (single tenant)
                $zoneAttributes['created_at'] = now();
                
                Zone::create($zoneAttributes);
                Log::info("Zone créée: {$zoneCode} (client {$clientId})");
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncSingleZone {$zoneData['area_code']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime les zones qui n'existent plus dans l'API
     */
    private function deleteMissingZones(array $apiZones, int $clientId): void
    {
        try {
            // Extraire les codes de zone de l'API
            $apiZoneCodes = [];
            foreach ($apiZones as $zone) {
                if (!empty($zone['area_code'])) {
                    $apiZoneCodes[] = $zone['area_code'];
                }
            }
            
            if (empty($apiZoneCodes)) {
                return;
            }
            
            // Trouver les zones locales qui ne sont plus dans l'API
            $zonesToDelete = Zone::whereNotIn('code', $apiZoneCodes)->get();
            
            // Supprimer les zones obsolètes
            $deletedCount = 0;
            foreach ($zonesToDelete as $zone) {
                $zone->delete();
                $deletedCount++;
                Log::info("Zone supprimée: {$zone->code} (client {$clientId}) - n'existe plus dans l'API");
            }
            
            if ($deletedCount > 0) {
                Log::info("Supprimé {$deletedCount} zones obsolètes pour le client {$clientId}");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur deleteMissingZones client {$clientId}: " . $e->getMessage());
        }
    }

    /**
     * Récupère les données LOCALES pour DataTables
     */
    public function getLocalZones(Request $request)
    {
        if ($request->ajax()) {
            $query = Zone::query();
            
            // Appliquer les filtres
            $this->applyFilters($query, $request);
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code', function($zone) {
                    return $zone->code ?? 'N/A';
                })
                ->addColumn('name', function($zone) {
                    return $zone->name ?? 'N/A';
                })
                ->addColumn('description', function($zone) {
                    return $zone->description ?? 'N/A';
                })
                ->addColumn('last_sync', function($zone) {
                    return $zone->updated_at->diffForHumans();
                })
                
            ->addColumn('actions', function($zone) {
                // Échapper les caractères spéciaux pour le nom
                $zoneName = htmlspecialchars($zone->name, ENT_QUOTES, 'UTF-8');
                
                $html = '<div class="btn-group" role="group" style="gap: 3px;">';
                
                // Bouton Éditer
                $html .= '<button type="button" 
                            class="btn btn-sm btn-info edit-zone-btn" 
                            data-id="' . $zone->area_id . '"
                            title="Modifier">
                            <i class="bi bi-pencil"></i>
                         </button>';
                
                
                
                // Bouton Supprimer
                $html .= '<button type="button" 
                            class="btn btn-sm btn-danger btn-delete-zone" 
                            data-id="' . $zone->area_id . '"
                            data-name="' . $zoneName . '"
                            title="Supprimer">
                            <i class="bi bi-trash"></i>
                         </button>';
                
                $html .= '</div>';
                
                return $html;
            })
                ->rawColumns(['code', 'name', 'description', 'last_sync','actions'])
                ->make(true);
        }
    }

    /**
     * Applique les filtres
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('area_code') && !empty($request->area_code)) {
            $query->where('code', 'LIKE', $request->area_code . '%');
        }
        
        if ($request->has('area_name') && !empty($request->area_name)) {
            $query->where('name', 'LIKE', '%' . $request->area_name . '%');
        }
        
        if ($request->has('description') && !empty($request->description)) {
            $query->where('description', 'LIKE', '%' . $request->description . '%');
        }
        
        $query->orderBy('code', 'asc');
    }

    /**
     * Synchronisation manuelle via le bouton
     */
    public function sync(Request $request)
    {
        try {
            $clientId = 1;
            $force = $request->get('force', false);
            
            if ($force) {
                Cache::forget('zones_last_sync_' . $clientId);
                Cache::forget('zones_syncing_' . $clientId);
            }
            
            $syncedCount = $this->syncZonesForClientNow($clientId);
            
            return response()->json([
                'success' => true,
                'message' => "Synchronisation terminée avec succès ({$syncedCount} zones)",
                'count' => $syncedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur sync manuel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncStatus()
    {
        $clientId = 1;
        
        $status = [
            'total_zones' => Zone::count(),
            'last_sync' => Cache::get('zones_last_sync_' . $clientId) ? 
                date('d/m/Y H:i:s', Cache::get('zones_last_sync_' . $clientId)) : 'Jamais',
            'is_syncing' => Cache::get('zones_syncing_' . $clientId, false),
            'client_name' => config('app.name')
        ];
        
        return response()->json($status);
    }

    public function resetAndSync(Request $request)
    {
        try {
            $clientId = 1;
            
            Zone::truncate();
            Log::info("Toutes les zones ont été supprimées");
            
            Cache::forget('zones_last_sync_' . $clientId);
            Cache::forget('zones_syncing_' . $clientId);
            
            $syncedCount = $this->syncZonesForClientNow($clientId);
            
            return response()->json([
                'success' => true,
                'message' => "Base de données vidée et resynchronisée ({$syncedCount} zones)"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur resetAndSync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}