<?php

namespace Vendor\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CheckTimeService;
use App\Models\Employee;
use App\Models\Zone;
use App\Models\Department;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EmployeeController extends Controller
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
            return $this->getLocalEmployees($request);
        }
        
        // Pour l'affichage normal de la page
        $zones = Zone::all();
        $departments = Department::all();
        
        return view('employee::employees.index', compact('zones', 'departments'));
    }

    public function store(Request $request)
{
    try {
        // Valider les données d'entrée
        $validated = $request->validate([
           // 'emp_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'address' => 'nullable|string|max:500',
        ]);

        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        $this->sync($request);

        // get max code 
    $code = Employee::whereRaw('emp_code REGEXP "^[0-9]+$"')
    ->selectRaw('MAX(CAST(emp_code AS UNSIGNED)) as max_code')
    ->value('max_code');
    $nextCode = $code !== null ? ((int) $code + 1) : 1;


        
        // Préparer les données pour l'API externe
        $apiData = [
            'emp_code' => $nextCode,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contact_tel' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        $apiData['department'] = $validated['department_id'];
        $apiData['area'] = [$validated['area_id']];

        // Envoyer la requête à l'API externe
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->post(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/employees/', $apiData);

        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après création
            
            // Optionnel: Sauvegarder aussi en local
            // Employee::create(array_merge($validated, ['external_id' => $responseData['id']]));
            
            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => $responseData
            ]);
        } else {
            $errorMessage = 'Erreur lors de la création';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 401) {
                $errorMessage = 'Non autorisé - Token invalide';
            } elseif ($response->status() === 409) {
                $errorMessage = 'Le code employé existe déjà';
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

public function update(Request $request, $id)
{
    try {
        // Valider les données d'entrée
        $validated = $request->validate([
           // 'emp_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|integer',
            'department_id' => 'required|integer',
            'address' => 'nullable|string|max:500',
        ]);

        
        $token = $this->api->getGeneralToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token API non configuré'
            ], 401);
        }

        $apiData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contact_tel' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        $apiData['department'] = $validated['department_id'];
        $apiData['area'] = [$validated['area_id']];

        // Envoyer la requête à l'API externe
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])
        ->timeout(30)
        ->patch(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/employees/' . $id . '/', $apiData);

        if ($response->successful()) {
            $responseData = $response->json();
            $this->sync($request); // Synchroniser après modification
            
            return response()->json([
                'success' => true,
                'message' => 'Employé modifié avec succès',
                'data' => $responseData
            ]);
        } else {
            $errorMessage = 'Erreur lors de la modification';
            
            if ($response->status() === 400) {
                $errorMessage = $response->json()['detail'] ?? 'Données invalides';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Employé non trouvé';
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

        // Envoyer la requête à l'API externe
        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json",
        ])
        ->timeout(30)
        ->delete(rtrim($this->api->getBaseUrl(), '/') . '/personnel/api/employees/' . $id . '/');

        if ($response->successful()) {
            $this->sync(new Request()); // Synchroniser après suppression
            Employee::where('employee_id', $id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Employé supprimé avec succès'
            ]);
        } else {
            $errorMessage = 'Erreur lors de la suppression';
            
            if ($response->status() === 404) {
                $errorMessage = 'Employé non trouvé';
            } elseif ($response->status() === 403) {
                $errorMessage = 'Non autorisé à supprimer cet employé';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status()
            ], $response->status());
        }

    } catch (\Exception $e) {
        
        Log::info('Error : ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Synchronisation manuelle
     */
    public function sync(Request $request)
    {
        try {
            Log::info('====== DÉBUT SYNCHRONISATION EMPLOYÉS ======');
            
            $token = $this->api->getGeneralToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token API non configuré'
                ]);
            }

            // Synchroniser les employés
            $synced = $this->syncEmployees($token);
            
            // Mettre à jour le cache
            Cache::put('employees_last_sync_1', time(), now()->addHours(2));
            
            $totalEmployees = Employee::count();
            
            Log::info("====== SYNCHRONISATION TERMINÉE: {$synced} employés synchronisés ======");
            
            return response()->json([
                'success' => true,
                'message' => "Synchronisation terminée: {$totalEmployees} employés en base",
                'total_synced' => $synced,
                'total_in_db' => $totalEmployees
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronise les employés pour un client
     */
    private function syncEmployees($token): int
    {
        $clientId = 1;
        
        Log::info("Synchronisation employés");
        
        try {
            // Récupérer TOUS les employés avec pagination
            $allEmployees = $this->fetchAllEmployeesWithPagination($token);
            
            if (empty($allEmployees)) {
                Log::warning("Client {$clientId}: Aucun employé récupéré");
                return 0;
            }
            
            Log::info("Client {$clientId}: " . count($allEmployees) . " employés récupérés");
            
            // Préparer les données pour upsert
            $batchData = [];
            $employeeCodes = [];
            $now = now();
            
            foreach ($allEmployees as $employeeData) {
                $employeeCode = $employeeData['emp_code'] ?? null;
                $firstName = $employeeData['first_name'] ?? null;
                $lastName = $employeeData['last_name'] ?? null;
                $employeeId = $employeeData['id'] ?? null;
                
                // FIXED: area is an array, need to access first element
                $areaName = $employeeData['area'][0]['area_name'] ?? null;
                
                $deptName = $employeeData['department']['dept_name'] ?? null;
                
                if (!$employeeCode || !$firstName) {
                    Log::warning("Employé ignoré: emp_code ou first_name manquant", ['emp_code' => $employeeCode]);
                    continue;
                }
                
                $employeeCodes[] = (string) $employeeCode;
                $departmentData = $employeeData['department']['id'] ?? null;
                
                // FIXED: Get zone data from first element of area array
                $zoneData = $employeeData['area'][0] ?? null;
                
                // Préparer les données SIMPLES - PAS de zones/départements
                $batchData[] = [
                    'employee_id' => $employeeId,
                    'emp_code' => (string) $employeeCode,
                    'first_name' => $firstName,
                    'last_name' => $lastName ?? $firstName,
                    'email' => $employeeData['email'] ?? null,
                    'phone' => $employeeData['mobile'] ?? $employeeData['contact_tel'] ?? null,
                    'area_name' => $areaName,
                    'dept_name' => $deptName,
                    'area_id' => $zoneData['id'] ?? null,
                    'department_id' => $departmentData ?? null,
                    'status' => $this->determineStatus($employeeData),
                    'metadata' => json_encode($employeeData),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                
                // Traiter par lots de 50 pour éviter les problèmes de mémoire
                if (count($batchData) >= 150) {
                    $this->bulkUpsertEmployees($batchData);
                    $batchData = []; // Réinitialiser le lot
                }
            }
            
            // Traiter le dernier lot
            if (!empty($batchData)) {
                $this->bulkUpsertEmployees($batchData);
            }
            
            Log::info("Client {$clientId}: " . count($employeeCodes) . " employés préparés");
            
            // Supprimer les employés obsolètes
            $deletedCount = $this->cleanupMissingEmployees($employeeCodes, $clientId);
            
            Log::info("Client {$clientId}: Synchronisation terminée, {$deletedCount} employés supprimés");
            
            return count($employeeCodes);
            
        } catch (\Exception $e) {
            Log::error("Client {$clientId} erreur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère tous les employés avec pagination
     */
    private function fetchAllEmployeesWithPagination(string $token): array
    {
        $allEmployees = [];
        $page = 1;
        
        try {
            while (true) {
                Log::debug("Récupération page {$page}");
                
                // Préparer les paramètres
                $params = [
                    'page' => $page,
                    'limit' => 300
                ];
                
                // Utiliser votre méthode get() existante
                $response = $this->api->get('/personnel/api/employees/', $params, $token);
                
                if (!isset($response['data']) || !is_array($response['data'])) {
                    Log::warning("Format de réponse invalide pour la page {$page}");
                    break;
                }
                
                $employees = $response['data'];
                
                if (empty($employees)) {
                    Log::info("Page {$page} vide - fin de pagination");
                    break;
                }
                
                // Ajouter les employés de cette page
                $allEmployees = array_merge($allEmployees, $employees);
                Log::debug("Page {$page}: " . count($employees) . " employés");
                
                // Vérifier s'il y a une page suivante
                $hasNext = isset($response['next']) && !empty($response['next']);
                
                if (!$hasNext) {
                    Log::info("Pas de page suivante - fin de pagination");
                    break;
                }
                
                $page++;
                
                // Petite pause pour éviter de surcharger l'API
                if ($page <= 100) { // Limite de sécurité
                    usleep(100000); // 0.1 seconde
                } else {
                    Log::warning("Limite de pagination atteinte (10 pages)");
                    break;
                }
            }
            
            Log::info("Total récupéré: " . count($allEmployees) . " employés");
            
        } catch (\Exception $e) {
            Log::error('Erreur fetchAllEmployeesWithPagination: ' . $e->getMessage());
        }
        
        return $allEmployees;
    }

    /**
     * Détermine le statut
     */
    private function determineStatus(array $employeeData): string
    {
        // enable_att = false => employé inactif
        if (isset($employeeData['enable_att']) && $employeeData['enable_att'] === false) {
            return 'inactive';
        }
        
        // Par défaut actif
        return 'active';
    }

    /**
     * UPSERT en masse - CORRIGÉ
     */
    private function bulkUpsertEmployees(array $batchData): void
    {
        if (empty($batchData)) {
            return;
        }
        
        try {
            // Utiliser updateOrCreate au lieu de upsert pour éviter les conflits
            foreach ($batchData as $data) {
                try {
                    Employee::updateOrCreate(
                        [
                            'employee_id' => $data['employee_id'],
                            'emp_code' => $data['emp_code']
                        ],
                        [
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'area_name' => $data['area_name'],
                            'dept_name' => $data['dept_name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'],
                            'area_id' => $data['area_id'],
                            'department_id' => $data['department_id'],
                            'status' => $data['status'],
                            'metadata' => $data['metadata'],
                            'updated_at' => $data['updated_at']
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Erreur pour emp_code {$data['emp_code']}: " . $e->getMessage());
                }
            }
            
            Log::info("Batch traité: " . count($batchData) . " employés");
            
        } catch (\Exception $e) {
            Log::error("Erreur bulkUpsertEmployees: " . $e->getMessage());
        }
    }

    /**
     * Nettoie les employés obsolètes
     */
    private function cleanupMissingEmployees(array $apiEmployeeCodes, int $clientId): int
    {
        try {
            if (empty($apiEmployeeCodes)) {
                return 0;
            }
            
            $deleted = Employee::whereNotIn('emp_code', $apiEmployeeCodes)->delete();
            
            if ($deleted > 0) {
                Log::info("Supprimé {$deleted} employés obsolètes");
            }
            
            return $deleted;
            
        } catch (\Exception $e) {
            Log::error("Erreur cleanupMissingEmployees: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les employés locaux pour DataTables
     */
    public function getLocalEmployees(Request $request)
{
    if ($request->ajax()) {
        $query = Employee::query();
        
        // Appliquer les filtres
        $this->applyFilters($query, $request);
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('full_name', function($employee) {
                $firstName = $employee->first_name ?? '';
                $lastName = $employee->last_name ?? '';
                return trim($firstName . ' ' . $lastName) ?: 'N/A';
            })
            ->addColumn('emp_code', function($employee) {
                return $employee->emp_code ?? 'N/A';
            })
            ->addColumn('area_name', function($employee) {
                return $employee->area_name ?? 'N/A';
            })
            ->addColumn('dept_name', function($employee) {
                return $employee->dept_name ?? 'N/A';
            })
            ->addColumn('phone', function($employee) {
                return $employee->phone ?? '';
            })
            ->addColumn('email', function($employee) {
                return $employee->email ?? '';
            })
            ->addColumn('area_id', function($employee) {
                return $employee->area_id ?? '';  // Ajoutez ceci
            })
            ->addColumn('department_id', function($employee) {
                return $employee->department_id ?? '';  // Ajoutez ceci
            })
            ->addColumn('address', function($employee) {
                return $employee->address ?? '';  // Ajoutez ceci si nécessaire
            })
            ->addColumn('status_badge', function($employee) {
                $status = strtolower($employee->status ?? 'active');
                $statusClass = [
                    'active' => 'badge bg-success',
                    'inactive' => 'badge bg-danger',
                    'suspended' => 'badge bg-warning'
                ][$status] ?? 'badge bg-secondary';
                
                return '<span class="' . $statusClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('last_sync', function($employee) {
                return $employee->updated_at ? $employee->updated_at->diffForHumans() : 'N/A';
            })
            ->addColumn('actions', function($employee) {
                // Échapper les caractères spéciaux pour le nom
                $employeeName = htmlspecialchars($employee->first_name, ENT_QUOTES, 'UTF-8');
                
                $html = '<div class="btn-group" role="group" style="gap: 3px;">';
                
                // Bouton Éditer avec TOUTES les données nécessaires
                $html .= '<button type="button" 
                            class="btn btn-sm btn-info edit-employee-btn" 
                            data-id="' . $employee->employee_id . '"
                            data-emp_code="' . htmlspecialchars($employee->emp_code ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-first_name="' . htmlspecialchars($employee->first_name ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-last_name="' . htmlspecialchars($employee->last_name ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-email="' . htmlspecialchars($employee->email ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-phone="' . htmlspecialchars($employee->phone ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-area_id="' . ($employee->area_id ?? '') . '"
                            data-area_name="' . htmlspecialchars($employee->area_name ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-department_id="' . ($employee->department_id ?? '') . '"
                            data-dept_name="' . htmlspecialchars($employee->dept_name ?? '', ENT_QUOTES, 'UTF-8') . '"
                            data-status="' . ($employee->status ?? '') . '"
                            data-address="' . htmlspecialchars($employee->address ?? '', ENT_QUOTES, 'UTF-8') . '"
                            title="Modifier">
                            <i class="bi bi-pencil"></i>
                         </button>';
                
                // Bouton Supprimer
                $html .= '<button type="button" 
                            class="btn btn-sm btn-danger btn-delete-employee" 
                            data-id="' . $employee->employee_id . '"
                            data-name="' . $employeeName . '"
                            title="Supprimer">
                            <i class="bi bi-trash"></i>
                         </button>';
                
                $html .= '</div>';
                
                return $html;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
}

    /**
     * Applique les filtres
     */
    private function applyFilters($query, Request $request): void
    {
        // Note: Le filtre client_id est automatiquement appliqué
        
        if ($request->has('zone_id') && !empty($request->zone_id)) {
            $query->where('area_id', $request->zone_id);
        }
        
        if ($request->has('dept_name') && !empty($request->dept_name)) {
            $query->where('dept_name', 'LIKE', '%' . $request->dept_name . '%');
        }

        if($request->has('area_name') && !empty($request->area_name)) {
            $query->where('area_name', 'LIKE', '%' . $request->area_name . '%');
        }
        
        if ($request->has('emp_code') && !empty($request->emp_code)) {
            $query->where('emp_code', 'LIKE', '%' . $request->emp_code . '%');
        }
        
        if ($request->has('name') && !empty($request->name)) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->name . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $request->name . '%');
            });
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        $query->orderBy('emp_code', 'asc');
    }

    /**
     * Synchronisation forcée
     */
    public function forceSync(Request $request)
    {
        return $this->sync($request);
    }

    /**
     * Reset et resynchronisation
     */
    public function resetAndSync(Request $request)
    {
        try {
            $clientId = 1;
            
            // Compter avant suppression
            $beforeCount = Employee::count();
            
            // Vider les employés
            Employee::truncate();
            Log::info("Employés supprimés (avant: {$beforeCount})");
            
            // Vider le cache
            Cache::forget('employees_last_sync_' . $clientId);
            
            // Resynchroniser
            $token = $this->api->getGeneralToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token API non configuré'
                ]);
            }

            $synced = $this->syncEmployees($token);
            
            // Mettre à jour le cache
            Cache::put('employees_last_sync_' . $clientId, time(), now()->addHours(2));
            
            $afterCount = Employee::count();
            
            return response()->json([
                'success' => true,
                'message' => "Reset et synchronisation terminés: {$afterCount} employés",
                'before' => $beforeCount,
                'after' => $afterCount,
                'synced' => $synced
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur resetAndSync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchroniser les employés avec des credentials explicites (pour installation)
     */
    public function syncWithCredentials(Request $request)
    {
        $apiUrl = $request->input('api_url');
        $apiToken = $request->input('api_token');

        if (!$apiUrl || !$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'URL et token API requis'
            ], 400);
        }

        try {
            Log::info('====== SYNCHRONISATION EMPLOYÉS (DEPUIS INSTALLATION) ======');

            $allEmployees = [];
            $page = 1;

            while (true) {
                $response = Http::withHeaders([
                    "Authorization" => "Token " . $apiToken,
                    "Accept" => "application/json"
                ])
                ->timeout(60)
                ->get(rtrim($apiUrl, '/') . '/personnel/api/employees/', [
                    'page' => $page,
                    'limit' => 300
                ]);

                if ($response->failed()) {
                    Log::warning("Page {$page} échouée: " . $response->status());
                    break;
                }

                $data = $response->json();
                $employees = $data['data'] ?? [];

                if (empty($employees)) break;

                $allEmployees = array_merge($allEmployees, $employees);

                if (empty($data['next'])) break;
                $page++;
                usleep(100000);
            }

            if (empty($allEmployees)) {
                Log::info('Aucun employé trouvé via API');
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion réussie mais aucun employé trouvé.',
                    'total_synced' => 0,
                    'total_in_db' => Employee::count()
                ]);
            }

            Log::info(count($allEmployees) . ' employés récupérés depuis l\'API');

            $batchData = [];
            $employeeCodes = [];
            $now = now();

            foreach ($allEmployees as $employeeData) {
                $employeeCode = $employeeData['emp_code'] ?? null;
                $firstName = $employeeData['first_name'] ?? null;

                if (!$employeeCode || !$firstName) continue;

                $employeeCodes[] = (string) $employeeCode;

                $batchData[] = [
                    'employee_id' => $employeeData['id'] ?? null,
                    'emp_code' => (string) $employeeCode,
                    'first_name' => $firstName,
                    'last_name' => $employeeData['last_name'] ?? $firstName,
                    'email' => $employeeData['email'] ?? null,
                    'phone' => $employeeData['mobile'] ?? $employeeData['contact_tel'] ?? null,
                    'area_name' => $employeeData['area'][0]['area_name'] ?? null,
                    'dept_name' => $employeeData['department']['dept_name'] ?? null,
                    'area_id' => $employeeData['area'][0]['id'] ?? null,
                    'department_id' => $employeeData['department']['id'] ?? null,
                    'status' => (isset($employeeData['enable_att']) && $employeeData['enable_att'] === false) ? 'inactive' : 'active',
                    'metadata' => json_encode($employeeData),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batchData) >= 150) {
                    $this->bulkUpsertEmployees($batchData);
                    $batchData = [];
                }
            }

            if (!empty($batchData)) {
                $this->bulkUpsertEmployees($batchData);
            }

            $this->cleanupMissingEmployees($employeeCodes, 1);

            Cache::put('employees_last_sync_1', time(), now()->addHours(2));

            $totalEmployees = Employee::count();

            Log::info("====== SYNCHRONISATION INSTALLATION TERMINÉE: {$totalEmployees} employés ======");

            return response()->json([
                'success' => true,
                'message' => "Synchronisation terminée: {$totalEmployees} employés en base",
                'total_synced' => count($employeeCodes),
                'total_in_db' => $totalEmployees
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur syncWithCredentials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur synchro employés: ' . $e->getMessage()
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
            'total_employees' => Employee::count(),
            'last_sync' => Cache::get('employees_last_sync_' . $clientId) ? 
                date('d/m/Y H:i:s', Cache::get('employees_last_sync_' . $clientId)) : 'Jamais',
            'client_name' => config('app.name'),
            'employees_by_status' => Employee::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->map(function($item) {
                    return [
                        'status' => $item->status,
                        'count' => $item->count
                    ];
                })
                ->toArray()
        ];
        
        return response()->json($status);
    }
}