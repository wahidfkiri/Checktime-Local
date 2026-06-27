<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Device; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\CheckTimeService;

class DailyAttendanceController extends Controller
{
    private CheckTimeService $api;

    public function __construct(CheckTimeService $api)
    {
        $this->api = $api;
    }
    /**
     * Afficher la page des pointages avec synchronisation automatique d'aujourd'hui
     */
    public function index(Request $request)
    {
        // Récupérer les devices pour les filtres
        $devices = Device::orderBy('terminal_name')->get();
        
        // Récupérer les employés avec leurs codes pour le filtre
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(function($employee) {
                $fullName = $employee->first_name ?? '';
                if ($employee->last_name) {
                    $fullName .= ' ' . $employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Code: ' . $employee->emp_code;
                
                return [
                    'emp_code' => $employee->emp_code,
                    'full_name' => $fullName
                ];
            });
        
        // Données d'aujourd'hui par défaut
        $todayData = $this->getTodayData();
        
        return view('daily-attendance.index', compact('devices', 'employees', 'todayData'));
    }
    
    /**
     * Récupérer les données d'aujourd'hui depuis l'API
     */
    private function getTodayData()
    {
        try {
            $today = Carbon::today();
            $startTime = $today->startOfDay()->format('Y-m-d H:i:s');
            $endTime = $today->endOfDay()->format('Y-m-d H:i:s');
            
            Log::info("Récupération données pour aujourd'hui: " . $today->format('d-m-Y'));
            
            // Récupérer le token d'authentification
            $accessConfig = DB::table('access_configs')->first();
            
            if (!$accessConfig || empty($accessConfig->general_token)) {
                Log::warning("Token d'accès non configuré");
                return [
                    'success' => false,
                    'message' => 'Token d\'accès non configuré',
                    'data' => []
                ];
            }
            
            $token = $accessConfig->general_token;
            
            // Récupérer tous les devices
            $devices = Device::all();
            
            if ($devices->isEmpty()) {
                Log::warning("Aucun device trouvé");
                return [
                    'success' => false,
                    'message' => 'Aucun device trouvé',
                    'data' => []
                ];
            }
            
            // Collecter toutes les transactions depuis l'API
            $allTransactions = collect();
            
            foreach ($devices as $device) {
                $deviceTransactions = $this->getDeviceTransactionsFromApi(
                    $device,
                    $startTime,
                    $endTime,
                    $token,
                    null
                );
                
                $allTransactions = $allTransactions->merge($deviceTransactions);
            }
            
            Log::info("Total transactions récupérées: " . $allTransactions->count());
            
            if ($allTransactions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Aucune transaction trouvée pour ' . $today->format('d-m-Y'),
                    'data' => []
                ];
            }
            
            // DEBUG: Afficher tous les emp_code de l'API
            $uniqueEmpCodes = $allTransactions->pluck('emp_code')->unique()->values();
            Log::info("Tous les emp_code de l'API (" . $uniqueEmpCodes->count() . "): " . $uniqueEmpCodes->implode(', '));
            
            // Récupérer tous les employés pour la correspondance
            $allEmployees = Employee::whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get();
            
            Log::info("Employés en base (" . $allEmployees->count() . "): " . $allEmployees->pluck('emp_code')->implode(', '));
            
            $employeesByCode = $allEmployees->keyBy('emp_code');
            
            // DEBUG: Vérifier la correspondance
            $matched = [];
            $unmatched = [];
            
            foreach ($uniqueEmpCodes as $apiEmpCode) {
                $cleanEmpCode = trim($apiEmpCode);
                
                if (isset($employeesByCode[$cleanEmpCode])) {
                    $matched[] = $cleanEmpCode;
                } else {
                    $unmatched[] = $cleanEmpCode;
                    Log::warning("emp_code non trouvé en base: '" . $cleanEmpCode . "'");
                }
            }
            
            Log::info("Correspondance: " . count($matched) . " trouvés, " . count($unmatched) . " non trouvés");
            
            // Grouper et transformer les données
            $groupedData = $this->groupAndTransformData($allTransactions);
            
            // Formater pour l'affichage
            $formattedData = $this->formatForDisplay($groupedData, $employeesByCode);
            
            return [
                'success' => true,
                'message' => 'Données récupérées avec succès',
                'date' => $today->format('d/m/Y'),
                'total_attendances' => count($formattedData),
                'matched_employees' => count($matched),
                'unmatched_employees' => count($unmatched),
                'unmatched_codes' => $unmatched,
                'data' => $formattedData
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Récupérer les données pour DataTables directement depuis l'API
     */
    public function getData(Request $request)
    {
        try {
            // Valider les paramètres
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'terminal_sn' => 'nullable|string'
            ]);
            
            // Récupérer les paramètres
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $terminalSn = $request->input('terminal_sn');
            
            // Si aucune date n'est fournie, utiliser aujourd'hui
            if (!$startDate && !$endDate) {
                $today = Carbon::today();
                $startDate = $today->format('Y-m-d');
                $endDate = $today->format('Y-m-d');
            } elseif ($startDate && !$endDate) {
                $endDate = $startDate;
            } elseif (!$startDate && $endDate) {
                $startDate = $endDate;
            }
            
            Log::info("Récupération données pour: " . $startDate . " à " . $endDate . 
                     ", emp_code: " . ($empCode ?: 'all') . 
                     ", terminal_sn: " . ($terminalSn ?: 'all'));
            
            // Convertir en format datetime pour l'API
            $startTime = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endTime = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');
            
            // Récupérer le token d'authentification
            $accessConfig = DB::table('access_configs')->first();
            
            if (!$accessConfig || empty($accessConfig->general_token)) {
                return response()->json(['error' => 'Token d\'accès non configuré'], 400);
            }
            
            $token = $accessConfig->general_token;
            
            // Récupérer les devices selon le filtre
            $devicesQuery = Device::query();
            
            if ($terminalSn && $terminalSn !== 'all') {
                $devicesQuery->where('device_sn', $terminalSn);
            }
            
            $devices = $devicesQuery->get();
            
            if ($devices->isEmpty()) {
                return response()->json(['error' => 'Aucun device trouvé'], 400);
            }
            
            // Récupérer tous les employés pour la correspondance
            $employees = Employee::whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get()
                ->keyBy('emp_code');
            
            // Collecter toutes les transactions depuis l'API avec GESTION DE L'INSTABILITÉ
            $allTransactions = collect();
            
            $allTransactions = $this->getAllTransactionsWithRetry(
    $devices,
    $startTime,
    $endTime,
    $token,
    $empCode
);
            
            Log::info("Total transactions récupérées: " . $allTransactions->count());
            
            if ($allTransactions->isEmpty()) {
                return response()->json([
                    'draw' => $request->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }
            
            // Grouper et transformer les données
            $groupedData = $this->groupAndTransformData($allTransactions);
            
            // Convertir en tableau pour DataTables
            $data = $this->formatForDataTables($groupedData, $employees);
            
            // Pagination manuelle pour DataTables
            $totalRecords = count($data);
            $start = $request->input('start', 0);
            $length = 1500; // 500 lignes par page
            $pageData = array_slice($data, $start, $length);
            
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données pointages: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Récupérer les transactions d'un device avec GESTION D'INSTABILITÉ et RETRY
     */
    private function getDeviceTransactionsFromApiWithRetry($device, $startTime, $endTime, $token, $empCode = null)
    {
        $maxRetries = 3;
        $retryDelay = 2; // secondes
        
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                Log::info("Tentative {$retry} pour device {$device->device_sn}");
                
                $transactions = $this->getDeviceTransactionsFromApi(
                    $device,
                    $startTime,
                    $endTime,
                    $token,
                    $empCode,
                    $retry
                );
                
                // Si on obtient un nombre raisonnable de transactions, on considère que c'est bon
                if ($transactions->count() > 0) {
                    Log::info("Device {$device->device_sn}: " . $transactions->count() . " transactions récupérées");
                    return $transactions;
                }
                
                // Attente avant nouvelle tentative
                if ($retry < $maxRetries - 1) {
                    sleep($retryDelay * ($retry + 1)); // Attente exponentielle
                }
                
            } catch (\Exception $e) {
                Log::error("Erreur tentative {$retry} pour device {$device->device_sn}: " . $e->getMessage());
                if ($retry < $maxRetries - 1) {
                    sleep($retryDelay * ($retry + 1));
                }
            }
        }
        
        Log::warning("Échec après {$maxRetries} tentatives pour device {$device->device_sn}");
        return collect();
    }
    
    /**
     * Récupérer les transactions d'un device depuis l'API - VERSION ROBUSTE
     */
    private function getDeviceTransactionsFromApi($device, $startTime, $endTime, $token, $empCode = null)
{
    $page = 1;
    $limit = 100; // Maximum que l'API peut supporter
    $allTransactions = collect();
    $maxPages = 50; // Limite de sécurité
    
    // Stocker toutes les transactions vues pour éviter les doublons
    $seenTransactions = [];
    
    do {
        try {
            $apiParams = [
                'page' => $page,
                'limit' => $limit,
                'terminal_sn' => $device->device_sn,
                'start_time' => $startTime,
                'end_time' => $endTime,
                '_t' => time() . rand(1000, 9999) // Anti-cache
            ];
            
            if ($empCode && $empCode !== 'all') {
                $apiParams['emp_code'] = $empCode;
            }
            
            Log::info("API Call - Device: {$device->device_sn}, Page: {$page}");
            
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $token,
                'Accept' => 'application/json',
                'Cache-Control' => 'no-cache'
            ])->withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->get('http://54.37.15.111/iclock/api/transactions/', $apiParams);

            
            
            if (!$response->successful()) {
                Log::warning("API Error: " . $response->status());
                break;
            }
            
            $data = $response->json();
            
            // Vérifier la structure
            if (!isset($data['data']) || !is_array($data['data'])) {
                Log::warning("Invalid response structure");
                break;
            }
            
            $transactions = $data['data'];
            $count = count($transactions);
            
            Log::info("Page {$page}: {$count} transactions");
            
            if ($count === 0) {
                Log::info("Empty page - stopping");
                break;
            }
            
            // Filtrer les doublons et ajouter les infos du device
            foreach ($transactions as $transaction) {
                if (empty($transaction['emp_code']) || empty($transaction['punch_time'])) {
                    continue;
                }
                
                // Créer une clé unique
                $key = md5($transaction['emp_code'] . $transaction['punch_time'] . $device->device_sn);
                
                if (!isset($seenTransactions[$key])) {
                    $seenTransactions[$key] = true;
                    
                    // Ajouter les infos du device
                    $transaction['device_sn'] = $device->device_sn;
                    $transaction['device_name'] = $device->terminal_name ?: $device->device_sn;
                    
                    $allTransactions->push($transaction);
                }
            }
            
            // Vérifier s'il y a une page suivante
            $hasNextPage = isset($data['next']) && !empty($data['next']);
            
            if (!$hasNextPage || $page >= $maxPages) {
                Log::info("No next page or max pages reached");
                break;
            }
            
            $page++;
            
            // Petite pause pour ne pas surcharger l'API
            usleep(200000); // 200ms
            
        } catch (\Exception $e) {
            Log::error("Error page {$page}: " . $e->getMessage());
            break;
        }
        
    } while (true);
    
    Log::info("Total for device {$device->device_sn}: {$allTransactions->count()} unique transactions");
    
    return $allTransactions;
}

/**
 * Méthode pour récupérer TOUTES les données sur plusieurs tentatives
 */
private function getAllTransactionsWithRetry($devices, $startTime, $endTime, $token, $empCode = null)
{
    $allTransactions = collect();
    $maxRetries = 3;
    
    foreach ($devices as $device) {
        $deviceTransactions = collect();
        
        // Essayer plusieurs fois pour ce device
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            Log::info("Device {$device->device_sn} - Attempt {$retry}");
            
            $transactions = $this->getDeviceTransactionsFromApi(
                $device,
                $startTime,
                $endTime,
                $token,
                $empCode
            );
            
            $deviceTransactions = $deviceTransactions->merge($transactions);
            
            // Si on a des données, on peut arrêter les retries
            if ($transactions->count() > 0) {
                Log::info("Got {$transactions->count()} transactions - stopping retries");
                break;
            }
            
            // Attendre avant la prochaine tentative
            if ($retry < $maxRetries - 1) {
                sleep(2); // 2 secondes
            }
        }
        
        $allTransactions = $allTransactions->merge($deviceTransactions);
    }
    
    return $allTransactions;
}
    
    /**
     * STRATÉGIE ALTERNATIVE: Récupérer par segments de temps
     */
    private function getDeviceTransactionsByTimeSegments($device, $startTime, $endTime, $token, $empCode = null)
    {
        $allTransactions = collect();
        
        // Diviser la période en segments de 4 heures
        $segmentHours = 4;
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        Log::info("Récupération par segments de {$segmentHours}h pour device {$device->device_sn}");
        
        while ($start < $end) {
            $segmentEnd = (clone $start)->addHours($segmentHours);
            if ($segmentEnd > $end) {
                $segmentEnd = $end;
            }
            
            $segmentStartTime = $start->format('Y-m-d H:i:s');
            $segmentEndTime = $segmentEnd->format('Y-m-d H:i:s');
            
            Log::info("Segment: {$segmentStartTime} à {$segmentEndTime}");
            
            // Récupérer pour ce segment
            $segmentTransactions = $this->getDeviceTransactionsFromApi(
                $device,
                $segmentStartTime,
                $segmentEndTime,
                $token,
                $empCode,
                0
            );
            
            $allTransactions = $allTransactions->merge($segmentTransactions);
            
            // Pause entre segments
            if ($segmentEnd < $end) {
                usleep(500000); // 500ms
            }
            
            $start = $segmentEnd;
        }
        
        Log::info("Total par segments pour device {$device->device_sn}: " . $allTransactions->count());
        
        return $allTransactions;
    }
    
    /**
     * Grouper et transformer les données
     */
    private function groupAndTransformData($transactions)
    {
        $grouped = [];
        
        foreach ($transactions as $transaction) {
            $empCode = $transaction['emp_code'] ?? null;
            if (!$empCode) continue;
            
            $empCode = trim($empCode);
            
            try {
                $punchTime = Carbon::parse($transaction['punch_time']);
                $dateStr = $punchTime->format('Y-m-d');
                $timeStr = $punchTime->format('H:i:s');
                
                if (!isset($grouped[$empCode])) {
                    $grouped[$empCode] = [];
                }
                
                if (!isset($grouped[$empCode][$dateStr])) {
                    $grouped[$empCode][$dateStr] = [
                        'all_punches' => [],
                        'devices_used' => [],
                        'first_punch' => null,
                        'last_punch' => null,
                        'total_punches' => 0
                    ];
                }
                
                $grouped[$empCode][$dateStr]['all_punches'][] = $timeStr;
                
                $deviceInfo = [
                    'device_sn' => $transaction['device_sn'] ?? null,
                    'device_name' => $transaction['device_name'] ?? null,
                    'time' => $timeStr
                ];
                
                $grouped[$empCode][$dateStr]['devices_used'][] = $deviceInfo;
                
                if (!$grouped[$empCode][$dateStr]['first_punch'] || 
                    $timeStr < $grouped[$empCode][$dateStr]['first_punch']) {
                    $grouped[$empCode][$dateStr]['first_punch'] = $timeStr;
                }
                
                if (!$grouped[$empCode][$dateStr]['last_punch'] || 
                    $timeStr > $grouped[$empCode][$dateStr]['last_punch']) {
                    $grouped[$empCode][$dateStr]['last_punch'] = $timeStr;
                }
                
                $grouped[$empCode][$dateStr]['total_punches']++;
                
                sort($grouped[$empCode][$dateStr]['all_punches']);
                
            } catch (\Exception $e) {
                Log::warning("Erreur parsing transaction: " . $e->getMessage());
                continue;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Formater les données pour DataTables
     */
    private function formatForDataTables($groupedData, $employees)
    {
        $formattedData = [];
        
        foreach ($groupedData as $empCode => $dates) {
            foreach ($dates as $dateStr => $attendanceData) {
                $employee = $employees[$empCode] ?? null;
                
                // Calculer le temps de travail
                $totalWorkHours = null;
                if ($attendanceData['first_punch'] && $attendanceData['last_punch']) {
                    try {
                        $firstPunch = Carbon::createFromFormat('H:i:s', $attendanceData['first_punch']);
                        $lastPunch = Carbon::createFromFormat('H:i:s', $attendanceData['last_punch']);
                        if ($lastPunch > $firstPunch) {
                            $totalWorkMinutes = $lastPunch->diffInMinutes($firstPunch);
                            $totalWorkHours = round($totalWorkMinutes / 60, 2);
                        }
                    } catch (\Exception $e) {
                        // Ignorer
                    }
                }
                
                // Récupérer les appareils utilisés
                $devicesUsed = collect($attendanceData['devices_used'])
                    ->groupBy('device_name')
                    ->map(function($deviceGroup) {
                        return $deviceGroup->first()['device_name'] ?? $deviceGroup->first()['device_sn'] ?? 'Inconnu';
                    })
                    ->unique()
                    ->implode(', ');
                
                $fullName = 'Non enregistré';
                if ($employee) {
                    $fullName = $employee->first_name ?? '';
                    if ($employee->last_name) {
                        $fullName .= ' ' . $employee->last_name;
                    }
                    $fullName = trim($fullName) ?: 'Employé ' . $empCode;
                }
                
                $punchesFormatted = implode(', ', $attendanceData['all_punches']);
                
                $formattedData[] = [
                    'date' => $dateStr,
                    'employee' => [
                        'first_name' => $employee->first_name ?? '',
                        'last_name' => $employee->last_name ?? '',
                        'emp_code' => $empCode,
                        'full_name' => $fullName
                    ],
                    'emp_code' => $empCode,
                    'all_punches' => $punchesFormatted,
                    'first_punch' => $attendanceData['first_punch'],
                    'last_punch' => $attendanceData['last_punch'],
                    'total_punches' => $attendanceData['total_punches'],
                    'total_work_hours' => $totalWorkHours,
                    'devices_used' => $devicesUsed,
                    'status' => $attendanceData['total_punches'] > 0 ? 'present' : 'absent',
                    'observation' => 'Pointages: ' . $attendanceData['total_punches'],
                    'employee_found' => $employee ? 'yes' : 'no'
                ];
            }
        }
        
        // Trier par date décroissante, puis par code employé
        usort($formattedData, function($a, $b) {
            $dateCompare = strcmp($b['date'], $a['date']);
            if ($dateCompare === 0) {
                return strcmp($a['emp_code'], $b['emp_code']);
            }
            return $dateCompare;
        });
        
        return $formattedData;
    }

    private function formatForDisplay($groupedData, $employees)
    {
        $formattedData = [];
        
        foreach ($groupedData as $empCode => $dates) {
            foreach ($dates as $dateStr => $attendanceData) {
                $employee = $employees[$empCode] ?? null;
                
                // Calculer le temps de travail en HEURES
                $totalWorkHours = null;
                if ($attendanceData['first_punch'] && $attendanceData['last_punch']) {
                    try {
                        $firstPunch = Carbon::createFromFormat('H:i:s', $attendanceData['first_punch']);
                        $lastPunch = Carbon::createFromFormat('H:i:s', $attendanceData['last_punch']);
                        if ($lastPunch > $firstPunch) {
                            $totalWorkMinutes = $lastPunch->diffInMinutes($firstPunch);
                            $totalWorkHours = round($totalWorkMinutes / 60, 2);
                        }
                    } catch (\Exception $e) {
                        // Ignorer
                    }
                }
                
                // Récupérer les appareils utilisés
                $devicesUsed = collect($attendanceData['devices_used'])
                    ->groupBy('device_name')
                    ->map(function($deviceGroup) {
                        return $deviceGroup->first()['device_name'] ?? $deviceGroup->first()['device_sn'] ?? 'Inconnu';
                    })
                    ->unique()
                    ->implode(', ');
                
                $fullName = 'Non enregistré';
                if ($employee) {
                    $fullName = $employee->first_name ?? '';
                    if ($employee->last_name) {
                        $fullName .= ' ' . $employee->last_name;
                    }
                    $fullName = trim($fullName) ?: 'Employé ' . $empCode;
                }
                
                // Formater les pointages
                $punchesFormatted = implode(', ', $attendanceData['all_punches']);
                
                $formattedData[] = [
                    'date' => $dateStr,
                    'emp_code' => $empCode,
                    'full_name' => $fullName,
                    'all_punches' => $punchesFormatted,
                    'first_punch' => $attendanceData['first_punch'],
                    'last_punch' => $attendanceData['last_punch'],
                    'total_punches' => $attendanceData['total_punches'],
                    'total_work_hours' => $totalWorkHours,
                    'devices_used' => $devicesUsed,
                    'employee_found' => $employee ? 'oui' : 'non'
                ];
            }
        }
        
        return $formattedData;
    }
    
    /**
     * Récupérer le statut (maintenant basé sur l'API)
     */
    public function syncStatus(Request $request)
    {
        try {
            // Récupérer les données d'aujourd'hui
            $todayData = $this->getTodayData();
            $appName = \App\Models\Setting::where('key', 'app_name')->first();
            
            return response()->json([
                'client_name' => $appName ? $appName->value : 'CheckTime',
                'last_sync' => 'Direct API',
                'current_date' => date('d/m/Y'),
                'today_count' => $todayData['success'] ? count($todayData['data']) : 0,
                'today_message' => $todayData['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'client_name' => 'Erreur',
                'last_sync' => 'N/A',
                'current_date' => date('d/m/Y')
            ]);
        }
    }
    
    /**
     * Récupérer un employé par son code
     */
    public function getEmployeeByCode(Request $request)
    {
        if (!$request->has('emp_code')) {
            return response()->json(null);
        }
        
        $employee = Employee::where('emp_code', $request->emp_code)->first();
            
        if ($employee) {
            // Construire le nom complet
            $fullName = $employee->first_name ?? '';
            if ($employee->last_name) {
                $fullName .= ' ' . $employee->last_name;
            }
            $fullName = trim($fullName) ?: 'Employé ' . $employee->emp_code;
            
            return response()->json([
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'emp_code' => $employee->emp_code,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $fullName
            ]);
        }
        
        return response()->json(null);
    }
    
    /**
     * Exporter les présences en PDF (version AJAX)
     */
    public function exportPDF(Request $request)
    {
        try {
            // Récupérer les paramètres de filtre
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $terminalSn = $request->input('terminal_sn');
            
            // Si aucune date n'est fournie, utiliser aujourd'hui
            if (!$startDate && !$endDate) {
                $startDate = Carbon::today()->format('Y-m-d');
                $endDate = Carbon::today()->format('Y-m-d');
            } elseif ($startDate && !$endDate) {
                $endDate = $startDate;
            } elseif (!$startDate && $endDate) {
                $startDate = $endDate;
            }
            
            // Convertir en format datetime pour l'API
            $startTime = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endTime = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');
            
            // Récupérer le token d'authentification
            $accessConfig = DB::table('access_configs')->first();
            
            if (!$accessConfig || empty($accessConfig->general_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'accès non configuré.'
                ], 400);
            }
            
            $token = $accessConfig->general_token;
            
            // Récupérer les devices selon le filtre
            $devicesQuery = Device::query();
            
            if ($terminalSn && $terminalSn !== 'all') {
                $devicesQuery->where('device_sn', $terminalSn);
            }
            
            $devices = $devicesQuery->get();
            
            if ($devices->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun device trouvé.'
                ], 400);
            }
            
            // Récupérer tous les employés pour la correspondance
            $employees = Employee::whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get()
                ->keyBy('emp_code');
            
            // Collecter toutes les transactions depuis l'API avec la méthode robuste
            $allTransactions = collect();
            
            foreach ($devices as $device) {
                $deviceTransactions = $this->getDeviceTransactionsFromApiWithRetry(
                    $device,
                    $startTime,
                    $endTime,
                    $token,
                    $empCode
                );
                
                $allTransactions = $allTransactions->merge($deviceTransactions);
            }
            
            Log::info("Export PDF - Total transactions récupérées: " . $allTransactions->count());
            
            // Si peu de données, essayer la méthode par segments
            if ($allTransactions->count() < 10 && Carbon::parse($startDate)->diffInDays($endDate) > 1) {
                Log::info("Peu de données - essayer méthode par segments");
                $allTransactions = collect();
                
                foreach ($devices as $device) {
                    $deviceTransactions = $this->getDeviceTransactionsByTimeSegments(
                        $device,
                        $startTime,
                        $endTime,
                        $token,
                        $empCode
                    );
                    
                    $allTransactions = $allTransactions->merge($deviceTransactions);
                }
                
                Log::info("Export PDF - Total après segments: " . $allTransactions->count());
            }
            
            // Grouper et transformer les données
            $groupedData = $this->groupAndTransformData($allTransactions);
            
            // Convertir en tableau pour l'export
            $data = $this->formatForExport($groupedData, $employees);
            
            // Statistiques
            $statistics = $this->calculateStatistics($data);
            
            // Préparer les filtres pour l'affichage
            $filters = $this->prepareFiltersForDisplay($request);
            
            $appName = \App\Models\Setting::where('key', 'app_name')->first();
            
            // Générer le PDF
            $pdf = PDF::loadView('daily-attendance.exports.pdf', [
                'attendances' => $data,
                'client' => ['name' => $appName ? $appName->value : 'CheckTime'],
                'filters' => $filters,
                'statistics' => $statistics,
                'export_date' => now()->format('d/m/Y H:i'),
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
            ]);
            
            // Nom du fichier
            $filename = 'presences_' . '_' . 
                       Carbon::parse($startDate)->format('Ymd') . '_' . 
                       Carbon::parse($endDate)->format('Ymd') . '.pdf';
            
            // Sauvegarder le PDF temporairement
            $pdfPath = storage_path('app/public/pdfs/' . $filename);
            
            // Assurer que le dossier existe
            if (!file_exists(dirname($pdfPath))) {
                mkdir(dirname($pdfPath), 0755, true);
            }
            
            // Sauvegarder le PDF
            $pdf->save($pdfPath);
            
            // URL pour télécharger le fichier
            $pdfUrl = asset('storage/pdfs/' . $filename);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'pdf_url' => $pdfUrl,
                'filename' => $filename,
                'statistics' => [
                    'total_attendances' => count($data),
                    'total_employees' => collect($data)->pluck('emp_code')->unique()->count(),
                    'period' => Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y')
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater les données pour l'export PDF
     */
    private function formatForExport($groupedData, $employees)
    {
        $formattedData = [];
        
        foreach ($groupedData as $empCode => $dates) {
            foreach ($dates as $dateStr => $attendanceData) {
                $employee = $employees[$empCode] ?? null;
                
                // Calculer le temps de travail en HEURES
                $totalWorkHours = null;
                if ($attendanceData['first_punch'] && $attendanceData['last_punch']) {
                    try {
                        $firstPunch = Carbon::createFromFormat('H:i:s', $attendanceData['first_punch']);
                        $lastPunch = Carbon::createFromFormat('H:i:s', $attendanceData['last_punch']);
                        if ($lastPunch > $firstPunch) {
                            $totalWorkMinutes = $lastPunch->diffInMinutes($firstPunch);
                            $totalWorkHours = round($totalWorkMinutes / 60, 2);
                        }
                    } catch (\Exception $e) {
                        // Ignorer
                    }
                }
                
                // Récupérer les appareils utilisés
                $devicesUsed = collect($attendanceData['devices_used'])
                    ->groupBy('device_name')
                    ->map(function($deviceGroup) {
                        return $deviceGroup->first()['device_name'] ?? $deviceGroup->first()['device_sn'] ?? 'Inconnu';
                    })
                    ->unique()
                    ->implode(', ');
                
                // Construire le nom complet
                $fullName = 'Non enregistré';
                if ($employee) {
                    $fullName = $employee->first_name ?? '';
                    if ($employee->last_name) {
                        $fullName .= ' ' . $employee->last_name;
                    }
                    $fullName = trim($fullName) ?: 'Employé ' . $empCode;
                }
                
                // Formater les pointages comme tableau
                $punchList = $attendanceData['all_punches'];
                
                // Déterminer le statut
                $status = $attendanceData['total_punches'] > 0 ? 'Présent' : 'Absent';
                $statusClass = $attendanceData['total_punches'] > 0 ? 'present' : 'absent';
                
                $formattedData[] = [
                    'date' => $dateStr,
                    'date_formatted' => Carbon::parse($dateStr)->format('d/m/Y'),
                    'emp_code' => $empCode,
                    'full_name' => $fullName,
                    'first_name' => $employee->first_name ?? '',
                    'last_name' => $employee->last_name ?? '',
                    'all_punches' => implode(', ', $punchList),
                    'punch_list' => $punchList,
                    'first_punch' => $attendanceData['first_punch'],
                    'last_punch' => $attendanceData['last_punch'],
                    'total_punches' => $attendanceData['total_punches'],
                    'total_work_hours' => $totalWorkHours,
                    'devices_used' => $devicesUsed,
                    'status' => $status,
                    'status_class' => $statusClass,
                    'employee_found' => $employee ? 'Oui' : 'Non'
                ];
            }
        }
        
        // Trier par date décroissante, puis par nom
        usort($formattedData, function($a, $b) {
            $dateCompare = strcmp($b['date'], $a['date']);
            if ($dateCompare === 0) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            return $dateCompare;
        });
        
        return $formattedData;
    }

    /**
     * Calculer les statistiques
     */
    private function calculateStatistics($data)
    {
        $total = count($data);
        $present = collect($data)->where('status', 'Présent')->count();
        $absent = collect($data)->where('status', 'Absent')->count();
        
        // Calculer le nombre moyen de pointages par jour
        $averagePunches = $total > 0 ? 
            round(collect($data)->avg('total_punches'), 1) : 0;
        
        // Nombre d'employés uniques
        $uniqueEmployees = collect($data)->pluck('emp_code')->unique()->count();
        
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'average_punches' => $averagePunches,
            'unique_employees' => $uniqueEmployees
        ];
    }

    /**
     * Préparer les filtres pour l'affichage
     */
    private function prepareFiltersForDisplay($request)
    {
        $filters = [];
        
        if ($request->has('start_date') && $request->start_date) {
            $filters['date_début'] = Carbon::parse($request->start_date)->format('d/m/Y');
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $filters['date_fin'] = Carbon::parse($request->end_date)->format('d/m/Y');
        }
        
        if ($request->has('emp_code') && $request->emp_code && $request->emp_code !== 'all') {
            $employee = Employee::where('emp_code', $request->emp_code)->first();
                
            if ($employee) {
                $fullName = $employee->first_name ?? '';
                if ($employee->last_name) {
                    $fullName .= ' ' . $employee->last_name;
                }
                $filters['employé'] = $fullName . ' (' . $request->emp_code . ')';
            } else {
                $filters['code_employé'] = $request->emp_code;
            }
        }
        
        if ($request->has('terminal_sn') && $request->terminal_sn && $request->terminal_sn !== 'all') {
            $device = Device::where('device_sn', $request->terminal_sn)->first();
                
            $filters['appareil'] = $device ? $device->terminal_name : $request->terminal_sn;
        }
        
        return $filters;
    }
    
}