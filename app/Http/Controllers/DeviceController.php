<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CheckTimeService;
use App\Models\Device;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DeviceController extends Controller
{
    private CheckTimeService $api;

    public function __construct(CheckTimeService $api)
    {
        $this->api = $api;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $this->checkAndSyncIfNeeded();
            return $this->getLocalDevices($request);
        }
        
        $this->checkAndSyncIfNeeded();
        
        return view('devices.index');
    }

    /**
     * Vérifie et synchronise si nécessaire
     */
    private function checkAndSyncIfNeeded(): bool
    {
        if (Cache::get('devices_syncing', false)) {
            return false;
        }
        
        $lastSync = Cache::get('devices_last_sync', 0);
        $syncInterval = 300;
        
        if ($lastSync == 0 || (time() - $lastSync) > $syncInterval) {
            Cache::put('devices_syncing', true, 300);
            $this->syncDevicesNow();
            return true;
        }
        
        return false;
    }

    /**
     * Synchronise les devices
     */
    private function syncDevicesNow(): int
    {
        try {
            Log::info("Début de la synchronisation des devices");

            if (!$this->api->hasToken()) {
                Log::warning("Aucun token configuré dans CheckTimeService");
                Cache::forget('devices_syncing');
                return 0;
            }

            $token = $this->api->getGeneralToken();
            $allDevices = $this->fetchAllDevicesFromAPI($token);
            
            if (empty($allDevices)) {
                Cache::forget('devices_syncing');
                return 0;
            }
            
            $syncedCount = 0;
            foreach ($allDevices as $deviceData) {
                if ($this->syncSingleDevice($deviceData)) {
                    $syncedCount++;
                }
            }
            
            $this->deleteMissingDevices($allDevices);
            
            Cache::put('devices_last_sync', time(), now()->addHours(2));
            Cache::forget('devices_syncing');
            
            Log::info("Synchronisation terminée: {$syncedCount} devices");
            
            return $syncedCount;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncDevicesNow: " . $e->getMessage());
            Cache::forget('devices_syncing');
            return 0;
        }
    }

    /**
     * Récupère TOUTES les devices depuis l'API (avec pagination)
     */
    private function fetchAllDevicesFromAPI(string $token): array
    {
        $allDevices = [];
        $page = 1;
        $hasMore = true;
        
        try {
            while ($hasMore && $page <= 20) {
                $response = Http::withHeaders([
                    "Authorization" => "Token " . $token,
                    "Accept" => "application/json"
                ])
                ->timeout(30)
                ->get(rtrim($this->api->getBaseUrl(), '/') . '/iclock/api/terminals/', [
                    'page' => $page,
                    'limit' => 100
                ]);
                
                if (!$response->successful()) {
                    Log::warning("Échec de récupération des devices - Page {$page}");
                    break;
                }
                
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
                    break;
                }
                
                $allDevices = array_merge($allDevices, $data['data']);
                $hasMore = isset($data['next']) && !empty($data['next']);
                $page++;
                
                if ($hasMore) {
                    usleep(200000);
                }
            }
            
            Log::info("Récupéré " . count($allDevices) . " devices depuis l'API");
            
        } catch (\Exception $e) {
            Log::error('Erreur fetchAllDevicesFromAPI: ' . $e->getMessage());
        }
        
        return $allDevices;
    }

    /**
     * Synchronise une seule device
     */
    private function syncSingleDevice(array $deviceData): bool
    {
        try {
            if (empty($deviceData['sn']) || empty($deviceData['alias'])) {
                return false;
            }
            
            $deviceCode = $deviceData['sn'];
            $existingDevice = Device::where('device_sn', $deviceCode)->first();
            
            $deviceAttributes = [
                'alias' => $deviceData['alias'],
                'ip' => $deviceData['ip_address'] ?? null,
                'terminal_name' => $deviceData['terminal_name'] ?? null,
                'area_name' => $deviceData['area_name'] ?? null,
                'last_sync' => $deviceData['last_activity'] ?? null,
                'metadata' => json_encode($deviceData),
                'updated_at' => now(),
            ];
            
            if ($existingDevice) {
                $existingDevice->update($deviceAttributes);
                Log::debug("Device mise à jour: {$deviceCode}");
            } else {
                $deviceAttributes['device_sn'] = $deviceCode;
                $deviceAttributes['created_at'] = now();
                Device::create($deviceAttributes);
                Log::info("Device créée: {$deviceCode}");
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncSingleDevice {$deviceData['sn']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime les devices qui n'existent plus dans l'API
     */
    private function deleteMissingDevices(array $apiDevices): void
    {
        try {
            $apiDeviceCodes = [];
            foreach ($apiDevices as $device) {
                if (!empty($device['sn'])) {
                    $apiDeviceCodes[] = $device['sn'];
                }
            }
            
            if (empty($apiDeviceCodes)) {
                return;
            }
            
            $devicesToDelete = Device::whereNotIn('device_sn', $apiDeviceCodes)->get();
            
            $deletedCount = 0;
            foreach ($devicesToDelete as $device) {
                $device->delete();
                $deletedCount++;
                Log::info("Device supprimée: {$device->device_sn} - n'existe plus dans l'API");
            }
            
            if ($deletedCount > 0) {
                Log::info("Supprimé {$deletedCount} devices obsolètes");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur deleteMissingDevices: " . $e->getMessage());
        }
    }

    /**
     * Récupère les données LOCALES pour DataTables
     */
    public function getLocalDevices(Request $request)
    {
        if ($request->ajax()) {
            $query = Device::query();
            $this->applyFilters($query, $request);
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code', function($device) {
                    return $device->device_sn ?? 'N/A';
                })
                ->addColumn('alias', function($device) {
                    return $device->alias ?? 'N/A';
                })
                ->addColumn('ip', function($device) {
                    return $device->ip ?? 'N/A';
                })
                ->addColumn('area_name', function($device) {
                    return $device->area_name ?? 'N/A';
                })
                ->addColumn('terminal_name', function($device) {
                    return $device->terminal_name ?? 'N/A';
                })
                ->addColumn('status', function($device) {
                    if (!$device->last_sync) {
                        return '<span class="badge bg-danger-light text-danger">
                            <i class="bi bi-circle-fill me-1 fs-6"></i> Inactif
                        </span>';
                    }

                    if (\Carbon\Carbon::parse($device->last_sync)->diffInDays(now()) <= 15) {
                        return '<span class="badge bg-success-light text-success">
                            <i class="bi bi-check-circle-fill me-1 fs-6"></i> Actif
                        </span>';
                    } else {
                        return '<span class="badge bg-danger-light text-danger">
                            <i class="bi bi-x-circle-fill me-1 fs-6"></i> Inactif
                        </span>';
                    }
                })
                ->addColumn('last_sync', function($device) {
                    if (!$device->last_sync) {
                        return '<span class="text-muted">Jamais</span>';
                    }
                    
                    $daysAgo = \Carbon\Carbon::parse($device->last_sync)->diffInDays(now());
                    $formattedDate = \Carbon\Carbon::parse($device->last_sync)->format('d/m/Y H:i:s');
                    
                    if ($daysAgo == 0) {
                        return '<span class="text-success">Aujourd\'hui</span><br>
                                <small class="text-muted">' . \Carbon\Carbon::parse($device->last_sync)->format('H:i:s') . '</small>';
                    } elseif ($daysAgo == 1) {
                        return '<span class="text-success">Hier</span><br>
                                <small class="text-muted">' . \Carbon\Carbon::parse($device->last_sync)->format('H:i:s') . '</small>';
                    } elseif ($daysAgo <= 15) {
                        return '<span>' . $formattedDate . '</span><br>
                                <small class="text-success">Il y a ' . $daysAgo . ' jour(s)</small>';
                    } else {
                        return '<span>' . $formattedDate . '</span><br>
                                <small class="text-danger">Il y a ' . $daysAgo . ' jours</small>';
                    }
                })
                ->rawColumns(['status', 'last_sync'])
                ->make(true);
        }
    }

    /**
     * Applique les filtres
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('device_sn') && !empty($request->device_sn)) {
            $query->where('device_sn', 'LIKE', '%' . $request->device_sn . '%');
        }
        
        if ($request->has('alias') && !empty($request->alias)) {
            $query->where('alias', 'LIKE', '%' . $request->alias . '%');
        }

        if($request->has('ip') && !empty($request->ip)) {
            $query->where('ip', 'LIKE', '%' . $request->ip . '%');
        }
        
        if($request->has('area_name') && !empty($request->area_name)) {
            $query->where('area_name', 'LIKE', '%' . $request->area_name . '%');
        }
        
        if($request->has('terminal_name') && !empty($request->terminal_name)) {
            $query->where('terminal_name', 'LIKE', '%' . $request->terminal_name . '%');
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $fifteenDaysAgo = now()->subDays(15);
            
            if ($request->status == 'active') {
                $query->whereNotNull('last_sync')
                      ->where('last_sync', '>=', $fifteenDaysAgo);
            } elseif ($request->status == 'inactive') {
                $query->where(function($q) use ($fifteenDaysAgo) {
                    $q->whereNull('last_sync')
                      ->orWhere('last_sync', '<', $fifteenDaysAgo);
                });
            }
        }
        
        $query->orderBy('device_sn', 'asc');
    }

    /**
     * Synchronisation manuelle via le bouton
     */
    public function sync(Request $request)
    {
        try {
            $force = $request->get('force', false);
            
            if ($force) {
                Cache::forget('devices_last_sync');
                Cache::forget('devices_syncing');
            }
            
            $syncedCount = $this->syncDevicesNow();
            
            return response()->json([
                'success' => true,
                'message' => "Synchronisation terminée avec succès ({$syncedCount} devices)",
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

    /**
     * Statut de synchronisation
     */
    public function syncStatus()
    {
        $status = [
            'total_devices' => Device::count(),
            'last_sync' => Cache::get('devices_last_sync') ? 
                date('d/m/Y H:i:s', Cache::get('devices_last_sync')) : 'Jamais',
            'is_syncing' => Cache::get('devices_syncing', false),
        ];
        
        return response()->json($status);
    }

    /**
     * Vider et resynchroniser tous les appareils
     */
    public function resetAndSync(Request $request)
    {
        try {
            Device::truncate();
            Log::info("Tous les appareils ont été supprimés");
            
            Cache::forget('devices_last_sync');
            Cache::forget('devices_syncing');
            
            $syncedCount = $this->syncDevicesNow();
            
            return response()->json([
                'success' => true,
                'message' => "Base de données vidée et resynchronisée ({$syncedCount} devices)"
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