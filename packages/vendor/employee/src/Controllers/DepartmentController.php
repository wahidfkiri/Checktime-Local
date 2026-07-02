<?php

namespace Vendor\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CheckTimeService;
use App\Models\Department;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DepartmentController extends Controller
{
    private CheckTimeService $api;

    public function __construct(CheckTimeService $api)
    {
        $this->api = $api;
    }

    public function index(Request $request)
    {
        // Si c'est une requête AJAX pour DataTables
        if ($request->ajax()) {
            $this->checkAndSyncIfNeeded(1);
            return $this->getLocalDepartments($request);
        }
        
        $this->checkAndSyncIfNeeded(1);
        
        return view('employee::departments.index');
    }

    public function store(Request $request)
{
    try {
        // Valider les données d'entrée
        $validated = $request->validate([
            // 'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
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
        $code = Department::max('code');
        $nextCode = $code ? $code + 1 : 1;


        // Envoyer la requête à l'API externe
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->post(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/departments/', [
            'dept_code' => $nextCode,
            'dept_name' => $validated['name'],
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après création
            
            return response()->json([
                'success' => true,
                'message' => 'Département créé avec succès',
                'data' => $responseData
            ]);
        } else {
            $errorMessage = 'Erreur lors de la création';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 401) {
                $errorMessage = 'Non autorisé - Token invalide';
            } elseif ($response->status() === 409) {
                $errorMessage = 'Le code de département existe déjà';
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
            'name' => 'required|string|max:255',
        ]);

        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        // Envoyer la requête à l'API externe pour mettre à jour
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->patch(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/departments/' . $id . '/', [
            'dept_name' => $validated['name'],
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après modification
            
            return response()->json([
                'success' => true,
                'message' => 'Département modifié avec succès',
                'data' => $responseData
            ]);
        } else {
            $errorMessage = 'Erreur lors de la modification';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Département non trouvé';
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
        ])
        ->timeout(30)
        ->delete(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/departments/' . $id . '/');

        if ($response->successful()) {
            //$this->sync($request); // Synchroniser après suppression
            Department::where('department_id', $id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Département supprimé avec succès'
            ]);
        } else {
            $errorMessage = 'Erreur lors de la suppression';
            
            if ($response->status() === 404) {
                $errorMessage = 'Département non trouvé';
            } elseif ($response->status() === 403) {
                $errorMessage = 'Non autorisé à supprimer ce département';
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
        if (Cache::get('departments_syncing_' . $clientId, false)) {
            return false;
        }
        
        $lastSync = Cache::get('departments_last_sync_' . $clientId, 0);
        $syncInterval = 300; // 5 minutes pour les tests, ajustez selon vos besoins
        
        // Si jamais synchronisé ou si ça fait plus de X secondes
        if ($lastSync == 0 || (time() - $lastSync) > $syncInterval) {
            // Marquer comme en cours de synchronisation
            Cache::put('departments_syncing_' . $clientId, true, 300);
            
            // Lancer la synchronisation EN TEMPS RÉEL (pas en arrière-plan)
            $this->syncDepartmentsForClientNow($clientId);
            
            return true;
        }
        
        return false;
    }

    /**
     * Synchronise les départements pour le client spécifié
     */
    private function syncDepartmentsForClientNow(int $clientId): int
    {
        try {
            Log::info("Début de la synchronisation des départements pour le client {$clientId}");

            $token = $this->api->getGeneralToken();

            if (!$token) {
                Log::warning("Aucun token configuré pour le client {$clientId}");
                Cache::forget('departments_syncing_' . $clientId);
                return 0;
            }
            
            // Récupérer toutes les départements de l'API
            $allDepartments = $this->fetchAllDepartmentsFromAPI($token);
            
            if (empty($allDepartments)) {
                Cache::forget('departments_syncing_' . $clientId);
                return 0;
            }
            
            // Synchroniser chaque département
            $syncedCount = 0;
            foreach ($allDepartments as $departmentData) {
                if ($this->syncSingleDepartment($departmentData, $clientId)) {
                    $syncedCount++;
                }
            }
            
            // SUPPRIMER les départements qui n'existent plus dans l'API
            $this->deleteMissingDepartments($allDepartments, $clientId);
            
            // Mettre à jour le cache
            Cache::put('departments_last_sync_' . $clientId, time(), now()->addHours(2));
            Cache::forget('departments_syncing_' . $clientId);
            
            Log::info("Synchronisation terminée pour le client {$clientId}: {$syncedCount} départements");
            
            return $syncedCount;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncDepartmentsForClientNow client {$clientId}: " . $e->getMessage());
            Cache::forget('departments_syncing_' . $clientId);
            return 0;
        }
    }

    /**
     * Récupère TOUTES les départements depuis l'API (avec pagination)
     */
    private function fetchAllDepartmentsFromAPI(string $token): array
    {
        $allDepartments = [];
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
                ->get(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/departments/', [
                    'page' => $page,
                    'limit' => 300
                ]);
                
                if (!$response->successful()) {
                    Log::warning("Échec de récupération des départements - Page {$page}");
                    break;
                }
                
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
                    break;
                }
                
                // Ajouter les départements de cette page
                $allDepartments = array_merge($allDepartments, $data['data']);
                
                // Vérifier s'il y a une page suivante
                $hasMore = isset($data['next']) && !empty($data['next']);
                $page++;
                
                // Petite pause pour éviter de surcharger l'API
                if ($hasMore) {
                    usleep(200000); // 0.2 seconde
                }
            }
            
            Log::info("Récupéré " . count($allDepartments) . " départements depuis l'API");
            
        } catch (\Exception $e) {
            Log::error('Erreur fetchAllDepartmentsFromAPI: ' . $e->getMessage());
        }
        
        return $allDepartments;
    }

    /**
     * Synchronise une seule département
     */
    private function syncSingleDepartment(array $departmentData, int $clientId): bool
    {
        try {
            // Vérifier les données minimales
            if (empty($departmentData['dept_code']) || empty($departmentData['dept_name'])) {
                return false;
            }
            
            $departmentCode = $departmentData['dept_code'];
            
            // Vérifier si la département existe déjà
            $existingDepartment = Department::where('code', $departmentCode)
                               ->where('department_id', $departmentData['id'] ?? 0)
                               ->first();
            
            // Préparer les données
            $departmentAttributes = [
                'name' => $departmentData['dept_name'],
                'department_id' => $departmentData['id'] ?? null,
                'description' => $departmentData['description'] ?? null,
                'parent_code' => $departmentData['parent_dept'] ?? null,
                'external_id' => $departmentData['id'] ?? null,
                'metadata' => json_encode($departmentData),
                'updated_at' => now(),
            ];
            
            if ($existingDepartment) {
                // Mettre à jour le département existant
                $existingDepartment->update($departmentAttributes);
                Log::debug("Département mis à jour: {$departmentCode} (client {$clientId})");
            } else {
                // Créer un nouveau département
                $departmentAttributes['code'] = $departmentCode;
                $departmentAttributes['created_at'] = now();
                
                Department::create($departmentAttributes);
                Log::info("Département créé: {$departmentCode} (client {$clientId})");
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncSingleDepartment {$departmentData['dept_code']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime les departments qui n'existent plus dans l'API
     */
    private function deleteMissingDepartments(array $apiDepartments, int $clientId): void
    {
        try {
            // Extraire les codes de department de l'API
            $apiDepartmentCodes = [];
            foreach ($apiDepartments as $department) {
                if (!empty($department['dept_code'])) {
                    $apiDepartmentCodes[] = $department['dept_code'];
                }
            }
            
            if (empty($apiDepartmentCodes)) {
                return;
            }
            
            // Trouver les departments locaux qui ne sont plus dans l'API
            $departmentsToDelete = Department::whereNotIn('code', $apiDepartmentCodes)->get();
            
            // Supprimer les departments obsolètes
            $deletedCount = 0;
            foreach ($departmentsToDelete as $department) {
                $department->delete();
                $deletedCount++;
                Log::info("Département supprimé: {$department->code} (client {$clientId}) - n'existe plus dans l'API");
            }
            
            if ($deletedCount > 0) {
                Log::info("Supprimé {$deletedCount} départements obsolètes pour le client {$clientId}");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur deleteMissingDepartments client {$clientId}: " . $e->getMessage());
        }
    }

    /**
     * Récupère les données LOCALES pour DataTables
     */
    public function getLocalDepartments(Request $request)
    {
        if ($request->ajax()) {
            $query = Department::query();
            
            // Appliquer les filtres
            $this->applyFilters($query, $request);
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code', function($department) {
                    return $department->code ?? 'N/A';
                })
                ->addColumn('name', function($department) {
                    return $department->name ?? 'N/A';
                })
                ->addColumn('description', function($department) {
                    return $department->description ?? 'N/A';
                })
                ->addColumn('last_sync', function($department) {
                    return $department->updated_at->diffForHumans();
                })
                ->addColumn('actions', function($department) {
                    return '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-warning edit-department-btn" data-id="'.$department->_department_id.'" data-code="'.$department->code.'" data-name="'.$department->name.'">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-department-btn" data-id="'.$department->department_id.'" data-code="'.$department->code.'" data-name="'.$department->name.'">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['code', 'name', 'description', 'last_sync', 'actions'])
                ->make(true);
        }
    }

    /**
     * Applique les filtres
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('code') && !empty($request->code)) {
            $query->where('code', 'LIKE', $request->code . '%');
        }
        
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
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
                Cache::forget('departments_last_sync_' . $clientId);
                Cache::forget('departments_syncing_' . $clientId);
            }
            
            $syncedCount = $this->syncDepartmentsForClientNow($clientId);
            
            return response()->json([
                'success' => true,
                'message' => "Synchronisation terminée avec succès ({$syncedCount} départements)",
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
        $clientId = 1;
        
        $status = [
            'total_departments' => Department::count(),
            'last_sync' => Cache::get('departments_last_sync_' . $clientId) ? 
                date('d/m/Y H:i:s', Cache::get('departments_last_sync_' . $clientId)) : 'Jamais',
            'is_syncing' => Cache::get('departments_syncing_' . $clientId, false),
            'client_name' => config('app.name')
        ];
        
        return response()->json($status);
    }

    /**
     * Vider et resynchroniser toutes les départements du client
     */
    public function resetAndSync(Request $request)
    {
        try {
            $clientId = 1;
            
            Department::truncate();
            Log::info("Tous les départements ont été supprimés");
            
            Cache::forget('departments_last_sync_' . $clientId);
            Cache::forget('departments_syncing_' . $clientId);
            
            $syncedCount = $this->syncDepartmentsForClientNow($clientId);
            
            return response()->json([
                'success' => true,
                'message' => "Base de données vidée et resynchronisée ({$syncedCount} départements)"
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