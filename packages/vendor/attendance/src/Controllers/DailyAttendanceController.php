<?php

namespace Vendor\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Device; 
use App\Models\Department;
use App\Models\DailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\AttendanceSyncService;

class DailyAttendanceController extends Controller
{
    private AttendanceSyncService $attendanceService;

    public function __construct(AttendanceSyncService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }
    
    /**
     * Afficher la page des pointages
     */
    public function index(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return redirect()->route('home')->with('error', 'Client non trouvé.');
        }
        
        // Synchroniser les données d'aujourd'hui avant d'afficher
        try {
            $this->attendanceService->updateDailySummariesForPeriod($client);
            Log::info("Données synchronisées pour aujourd'hui - Client: {$client->id}");
        } catch (\Exception $e) {
            Log::error("Erreur synchronisation: " . $e->getMessage());
        }
        
        // Récupérer les devices pour les filtres
        $devices = Device::where('client_id', $client->id)
            ->orderBy('terminal_name')
            ->get();
        
        // Récupérer les employés avec leurs codes pour le filtre
        $employees = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
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

        // Récupérer les départements UNIQUES depuis la table employees
        $departments = Department::where('client_id', $client->id)
            ->orderBy('name')
            ->get();
        
        // Données d'aujourd'hui depuis la base
        $todayData = $this->getTodayDataFromDatabase($client);
        
        return view('attendance::index', compact('devices', 'employees', 'client', 'todayData', 'departments'));
    }

    /**
     * Afficher la page des retards
     */
    public function retardList(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return redirect()->route('home')->with('error', 'Client non trouvé.');
        }
        
        // Synchroniser les données si nécessaire
        try {
            $this->attendanceService->updateDailySummariesForPeriod($client);
            Log::info("Données synchronisées pour les retards - Client: {$client->id}");
        } catch (\Exception $e) {
            Log::error("Erreur synchronisation: " . $e->getMessage());
        }
        
        // Récupérer les devices pour les filtres
        $devices = Device::where('client_id', $client->id)
            ->orderBy('terminal_name')
            ->get();
        
        // Récupérer les employés pour les filtres
        $employees = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
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
        
        // Récupérer les départements uniques
        $allEmployees = Employee::where('client_id', $client->id)
            ->whereNotNull('dept_name')
            ->get(['dept_name']);
        
        $departments = $allEmployees->pluck('dept_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        
        // Récupérer les statistiques des retards
        $today = Carbon::today()->format('Y-m-d');
        $retardStats = [
            'today' => DailyAttendance::where('client_id', $client->id)
                ->where('attendance_date', $today)
                ->where('is_late', true)
                ->count(),
            'week' => DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->where('is_late', true)
                ->count(),
            'month' => DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->where('is_late', true)
                ->count()
        ];
        
        return view('attendance::retard', compact('devices', 'employees', 'client', 'departments', 'retardStats'));
    }

    /**
     * Afficher la liste des présences
     */
    public function presenceList(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return redirect()->route('home')->with('error', 'Client non trouvé.');
        }
        
        // Synchroniser les données si nécessaire
        try {
            $this->attendanceService->updateDailySummariesForPeriod($client);
        } catch (\Exception $e) {
            Log::error("Erreur synchronisation: " . $e->getMessage());
        }
        
        // Récupérer les devices pour les filtres
        $devices = Device::where('client_id', $client->id)
            ->orderBy('terminal_name')
            ->get();
        
        // Récupérer les employés pour les filtres
        $employees = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
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
        
        // Récupérer les départements uniques
        $allEmployees = Employee::where('client_id', $client->id)
            ->whereNotNull('dept_name')
            ->get(['dept_name']);
        
        $departments = $allEmployees->pluck('dept_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        
        // Statistiques pour aujourd'hui
        $todayStats = $this->getTodayStats($client);
        
        return view('attendance::presence', compact('devices', 'employees', 'client', 'departments', 'todayStats'));
    }

    /**
     * Afficher la liste des absences
     */
    public function absenceList(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return redirect()->route('home')->with('error', 'Client non trouvé.');
        }
        
        // Synchroniser les données si nécessaire
        try {
            $this->attendanceService->updateDailySummariesForPeriod($client);
        } catch (\Exception $e) {
            Log::error("Erreur synchronisation: " . $e->getMessage());
        }
        
        // Récupérer les devices pour les filtres
        $devices = Device::where('client_id', $client->id)
            ->orderBy('terminal_name')
            ->get();
        
        // Récupérer les employés pour les filtres
        $employees = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
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
        
        // Récupérer les départements uniques
        $allEmployees = Employee::where('client_id', $client->id)
            ->whereNotNull('dept_name')
            ->get(['dept_name']);
        
        $departments = $allEmployees->pluck('dept_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        
        // Statistiques pour aujourd'hui
        $todayStats = $this->getTodayStats($client);
        
        return view('attendance::absence', compact('devices', 'employees', 'client', 'departments', 'todayStats'));
    }

    /**
     * Récupérer les données des retards pour DataTables
     */
    public function getRetardData(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json(['error' => 'Client non trouvé'], 404);
            }
            
            // Valider les paramètres
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'terminal_sn' => 'nullable|string',
                'department' => 'nullable|string',
            ]);
            
            // Récupérer les paramètres
            $startDate = $request->input('start_date', Carbon::today()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            $terminalSn = $request->input('terminal_sn');
            
            Log::info("Récupération données retards pour: " . $startDate . " à " . $endDate);
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('is_late', true)
                ->with('employee');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all' && $empCode !== '') {
                $query->where('emp_code', $empCode);
            }
            
            // Filtrer par département
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            // Filtrer par minutes de retard
            // if ($minLateMinutes) {
            //     $query->where('late_minutes', '>=', $minLateMinutes);
            // }
            // if ($maxLateMinutes) {
            //     $query->where('late_minutes', '<=', $maxLateMinutes);
            // }
            
            // Filtrer par appareil
            if ($terminalSn && $terminalSn !== 'all' && $terminalSn !== '') {
                $query->where('raw_data', 'LIKE', '%"' . $terminalSn . '"%');
            }
            
            // Exécuter la requête
            $attendances = $query->orderBy('attendance_date', 'desc')
                // ->orderBy('late_minutes', 'desc')
                ->orderBy('emp_code')
                ->get();
            
            // Formater pour DataTables
            $data = $this->formatRetardsForDataTables($attendances);
            
            // Calculer le résumé des retards
            $summary = $this->calculateRetardSummary($attendances, $client, $startDate, $endDate);
            
            // Pagination
            $totalRecords = count($data);
            $start = $request->input('start', 0);
            $length = $request->input('length', 50);
            $pageData = array_slice($data, $start, $length);
            
            $response = [
                'draw' => $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData,
                'summary' => $summary
            ];
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données retards: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Formater les retards pour DataTables
     */
    private function formatRetardsForDataTables($attendances)
    {
        return $attendances->map(function ($attendance) {
            // Gérer raw_data de manière sécurisée
            $rawData = $this->ensureArray($attendance->raw_data);
            
            // Extraire les horaires de pointage
            $punchTimes = collect($rawData)
                ->pluck('punch_time')
                ->map(function ($time) {
                    try {
                        return Carbon::parse($time)->format('H:i:s');
                    } catch (\Exception $e) {
                        return $time;
                    }
                })
                ->filter()
                ->sort()
                ->values()
                ->toArray();
            
            // Extraire les appareils utilisés
            $devicesUsed = collect($rawData)
                ->pluck('terminal_alias')
                ->unique()
                ->filter()
                ->implode(', ');
            
            // Nom complet de l'employé
            $fullName = 'Non enregistré';
            if ($attendance->employee) {
                $fullName = $attendance->employee->first_name ?? '';
                if ($attendance->employee->last_name) {
                    $fullName .= ' ' . $attendance->employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Employé ' . $attendance->emp_code;
            }
            
            // Heure théorique d'arrivée (par défaut 09:00, à adapter selon votre logique)
            $theoreticalStartTime = '09:00:00';
            $actualCheckIn = $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null;
            
            // Calcul du retard en minutes (si disponible)
            $lateMinutes = $attendance->late_minutes ?? 0;
            
            // Catégoriser le retard
            $retardCategory = $this->categorizeRetard($lateMinutes);
            
            return [
                'DT_RowId' => 'row_' . $attendance->id,
                'date' => $attendance->attendance_date,
                'date_formatted' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                'day_name' => $this->getFrenchDayName($attendance->attendance_date),
                'employee' => [
                    'first_name' => $attendance->employee->first_name ?? '',
                    'last_name' => $attendance->employee->last_name ?? '',
                    'emp_code' => $attendance->emp_code,
                    'full_name' => $fullName,
                    'dept_name' => $attendance->employee->dept_name ?? 'Non défini'
                ],
                'emp_code' => $attendance->emp_code,
                'full_name' => $fullName,
                'dept_name' => $attendance->employee->dept_name ?? 'Non défini',
                'check_in' => $actualCheckIn,
                'theoretical_start' => $theoreticalStartTime,
                // 'late_minutes' => $lateMinutes,
                'late_hours_formatted' => $this->formatMinutesToHours($lateMinutes),
                'retard_category' => $retardCategory,
                'retard_category_label' => $this->getRetardCategoryLabel($retardCategory),
                'retard_category_class' => $this->getRetardCategoryClass($retardCategory),
                'punch_times' => implode(', ', $punchTimes),
                'devices_used' => $devicesUsed ?: 'Non disponible',
                'status' => $attendance->status,
                'status_label' => $this->getStatusLabel($attendance->status),
                'work_hours' => $attendance->work_hours,
                'notes' => $attendance->notes,
                'observation' => $this->generateRetardObservation($attendance),
                'actions' => $this->getRetardActionButtons($attendance)
            ];
        })->toArray();
    }

    /**
     * Calculer le résumé des retards
     */
    private function calculateRetardSummary($attendances, $client, $startDate, $endDate)
    {
        $totalRetards = $attendances->count();
        $totalLateMinutes = $attendances->sum('late_minutes');
        $averageLateMinutes = $totalRetards > 0 ? round($totalLateMinutes / $totalRetards, 1) : 0;
        
        // Nombre d'employés concernés
        $uniqueEmployees = $attendances->pluck('employee_id')->filter()->unique()->count();
        
        // Répartition par catégorie
        $categories = [
            'minor' => 0,
            'moderate' => 0,
            'significant' => 0,
            'severe' => 0
        ];
        
        foreach ($attendances as $attendance) {
            $minutes = $attendance->late_minutes ?? 0;
            $category = $this->categorizeRetard($minutes);
            if (isset($categories[$category])) {
                $categories[$category]++;
            }
        }
        
        // Top retardataires
        $topOffenders = $attendances->groupBy('emp_code')
            ->map(function ($items, $empCode) {
                $totalMinutes = $items->sum('late_minutes');
                $employee = $items->first()->employee;
                $fullName = $employee ? 
                    trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) : 
                    'Employé ' . $empCode;
                
                return [
                    'emp_code' => $empCode,
                    'full_name' => $fullName ?: 'Employé ' . $empCode,
                    'dept_name' => $employee->dept_name ?? 'Non défini',
                    'total_retards' => $items->count(),
                    'total_late_minutes' => $totalMinutes,
                    'average_late_minutes' => round($totalMinutes / $items->count(), 1)
                ];
            })
            ->sortByDesc('total_late_minutes')
            ->take(5)
            ->values()
            ->toArray();
        
        // Évolution par jour
        $dailyEvolution = $attendances->groupBy(function($item) {
            return Carbon::parse($item->attendance_date)->format('Y-m-d');
        })->map(function ($items, $date) {
            return [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('d/m/Y'),
                'count' => $items->count(),
                'total_minutes' => $items->sum('late_minutes'),
                'average_minutes' => round($items->avg('late_minutes'), 1)
            ];
        })->sortBy('date')->values()->toArray();
        
        return [
            'total_retards' => $totalRetards,
            'total_late_minutes' => $totalLateMinutes,
            'total_late_hours' => round($totalLateMinutes / 60, 1),
            'average_late_minutes' => $averageLateMinutes,
            'unique_employees' => $uniqueEmployees,
            'categories' => $categories,
            'top_offenders' => $topOffenders,
            'daily_evolution' => $dailyEvolution,
            'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
            'end_date' => Carbon::parse($endDate)->format('d/m/Y')
        ];
    }

    /**
     * Catégoriser le retard selon le nombre de minutes
     */
    private function categorizeRetard($minutes)
    {
        if ($minutes <= 5) return 'minor';
        if ($minutes <= 15) return 'moderate';
        if ($minutes <= 30) return 'significant';
        return 'severe';
    }

    /**
     * Obtenir le label de la catégorie de retard
     */
    private function getRetardCategoryLabel($category)
    {
        $labels = [
            'minor' => 'Retard léger',
            'moderate' => 'Retard modéré',
            'significant' => 'Retard significatif',
            'severe' => 'Retard sévère'
        ];
        
        return $labels[$category] ?? $category;
    }

    /**
     * Obtenir la classe CSS de la catégorie de retard
     */
    private function getRetardCategoryClass($category)
    {
        $classes = [
            'minor' => 'success',
            'moderate' => 'info',
            'significant' => 'warning',
            'severe' => 'danger'
        ];
        
        return $classes[$category] ?? 'secondary';
    }

    /**
     * Formater les minutes en heures
     */
    private function formatMinutesToHours($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? str_pad($mins, 2, '0', STR_PAD_LEFT) : '00');
        }
        
        return $mins . ' min';
    }

    /**
     * Générer une observation pour le retard
     */
    private function generateRetardObservation($attendance)
    {
        $observations = [];
        
        if ($attendance->late_minutes > 0) {
            $observations[] = "Retard de " . $this->formatMinutesToHours($attendance->late_minutes);
        }
        
        // Vérifier s'il y a eu plusieurs pointages
        $rawData = $this->ensureArray($attendance->raw_data);
        $punchCount = count($rawData);
        
        if ($punchCount > 2) {
            $observations[] = "{$punchCount} pointages";
        }
        
        // Vérifier si c'est aussi un départ anticipé
        if ($attendance->is_early_leave) {
            $observations[] = "Départ anticipé";
        }
        
        return !empty($observations) ? implode(' | ', $observations) : '-';
    }

    /**
     * Obtenir les boutons d'action pour les retards
     */
    private function getRetardActionButtons($attendance)
    {
        $buttons = [];
        
        // Bouton pour voir les détails
        $buttons[] = '<button class="btn btn-sm btn-info view-details" 
                      data-id="' . $attendance->id . '" 
                      data-emp-code="' . $attendance->emp_code . '"
                      data-date="' . $attendance->attendance_date . '"
                      title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>';
        
        // Bouton pour justifier/commenter
        $buttons[] = '<button class="btn btn-sm btn-warning justify-retard" 
                      data-id="' . $attendance->id . '"
                      data-emp-code="' . $attendance->emp_code . '"
                      data-date="' . $attendance->attendance_date . '"
                      data-late-minutes="' . ($attendance->late_minutes ?? 0) . '"
                      title="Justifier le retard">
                        <i class="fas fa-pen"></i>
                    </button>';
        
        return implode(' ', $buttons);
    }

    /**
     * Récupérer les données des présences pour DataTables
     */
    public function getPresenceData(Request $request)
    {
        return $this->getFilteredAttendanceData($request, ['PRESENT', 'LATE', 'HALF_DAY', 'OVERTIME', 'SHORT_WORK']);
    }

    /**
     * Récupérer les données des absences pour DataTables
     */
    public function getAbsenceData(Request $request)
    {
        return $this->getFilteredAttendanceData($request, ['ABSENT']);
    }

    /**
     * Méthode générique pour récupérer les données filtrées
     */
    private function getFilteredAttendanceData(Request $request, array $statuses)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json(['error' => 'Client non trouvé'], 404);
            }
            
            // Valider les paramètres
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'terminal_sn' => 'nullable|string',
                'department' => 'nullable|string',
                'status' => 'nullable|string'
            ]);
            
            // Récupérer les paramètres
            $startDate = $request->input('start_date', Carbon::today()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            $terminalSn = $request->input('terminal_sn');
            $status = $request->input('status');
            
            Log::info("Récupération données " . implode(',', $statuses) . " pour: " . $startDate . " à " . $endDate);
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->whereIn('status', $statuses)
                ->with('employee');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all' && $empCode !== '') {
                $query->where('emp_code', $empCode);
            }
            
            // Filtrer par département
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            // Filtrer par statut supplémentaire (pour les présences)
            if ($status && $status !== '' && $status !== 'all') {
                if ($status === 'late') {
                    $query->where('is_late', true);
                } elseif ($status === 'early_leave') {
                    $query->where('is_early_leave', true);
                } elseif ($status === 'overtime') {
                    $query->where('is_overtime', true);
                } elseif ($status === 'half_day') {
                    $query->where('status', 'HALF_DAY');
                }
            }
            
            // Filtrer par appareil
            if ($terminalSn && $terminalSn !== 'all' && $terminalSn !== '') {
                $query->where('raw_data', 'LIKE', '%"' . $terminalSn . '"%');
            }
            
            // Exécuter la requête
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code')
                ->get();
            
            // Formater pour DataTables
            $data = $this->formatAttendancesForDataTables($attendances);
            
            // Pour les absences, ajouter le résumé
            $summary = null;
            if (in_array('ABSENT', $statuses)) {
                $summary = $this->calculateAbsenceSummary($attendances, $client, $startDate, $endDate);
            }
            
            // Pagination
            $totalRecords = count($data);
            $start = $request->input('start', 0);
            $length = $request->input('length', 50);
            $pageData = array_slice($data, $start, $length);
            
            $response = [
                'draw' => $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData
            ];
            
            // Ajouter le résumé pour les absences
            if ($summary) {
                $response['summary'] = $summary;
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculer le résumé des absences
     */
    private function calculateAbsenceSummary($attendances, $client, $startDate, $endDate)
    {
        // Nombre total d'absences
        $totalAbsences = $attendances->count();
        
        // Nombre d'employés concernés
        $uniqueEmployees = $attendances->pluck('employee_id')->filter()->unique()->count();
        
        // Compter les jours ouvrés dans la période
        $workingDays = $this->countWorkingDays($startDate, $endDate);
        
        // Nombre total d'employés
        $totalEmployees = Employee::where('client_id', $client->id)->count();
        
        // Calculer le taux d'absence
        $totalPossiblePresences = $totalEmployees * $workingDays;
        $absenceRate = $totalPossiblePresences > 0 
            ? round(($totalAbsences / $totalPossiblePresences) * 100, 1) 
            : 0;
        
        return [
            'total_absences' => $totalAbsences,
            'total_employees' => $uniqueEmployees,
            'total_working_days' => $workingDays,
            'absence_rate' => $absenceRate
        ];
    }

    /**
     * Compter les jours ouvrés entre deux dates
     */
    private function countWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workingDays = 0;
        
        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            if ($date->dayOfWeekIso >= 1 && $date->dayOfWeekIso <= 5) {
                $workingDays++;
            }
        }
        
        return $workingDays;
    }

    /**
     * Obtenir les statistiques du jour
     */
    private function getTodayStats($client)
    {
        $today = Carbon::today()->format('Y-m-d');
        
        $total = DailyAttendance::where('client_id', $client->id)
            ->where('attendance_date', $today)
            ->count();
        
        $present = DailyAttendance::where('client_id', $client->id)
            ->where('attendance_date', $today)
            ->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY', 'OVERTIME', 'SHORT_WORK'])
            ->count();
        
        $absent = DailyAttendance::where('client_id', $client->id)
            ->where('attendance_date', $today)
            ->where('status', 'ABSENT')
            ->count();
        
        $totalEmployees = Employee::where('client_id', $client->id)->count();
        
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'total_employees' => $totalEmployees,
            'date' => Carbon::parse($today)->format('d/m/Y'),
            'presence_rate' => $totalEmployees > 0 ? round(($present / $totalEmployees) * 100, 2) : 0,
            'absence_rate' => $totalEmployees > 0 ? round(($absent / $totalEmployees) * 100, 2) : 0
        ];
    }
    
    /**
     * Récupérer les données d'aujourd'hui depuis la base de données
     */
    private function getTodayDataFromDatabase($client)
    {
        try {
            $today = Carbon::today()->format('Y-m-d');
            
            Log::info("Récupération données pour aujourd'hui depuis DB: " . $today);
            
            // Récupérer toutes les présences d'aujourd'hui
            $attendances = DailyAttendance::where('client_id', $client->id)
                ->where('attendance_date', $today)
                ->with('employee')
                ->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code')
                ->get();
            
            Log::info("Nombre de présences trouvées: " . $attendances->count());
            
            if ($attendances->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Aucune présence enregistrée pour ' . Carbon::parse($today)->format('d/m/Y'),
                    'data' => []
                ];
            }
            
            // Formater les données pour l'affichage
            $formattedData = $this->formatAttendancesForDisplay($attendances);
            
            // Calculer les statistiques
            $stats = $this->calculateAttendanceStats($attendances);
            
            return [
                'success' => true,
                'message' => 'Données récupérées avec succès',
                'date' => Carbon::parse($today)->format('d/m/Y'),
                'total_attendances' => $attendances->count(),
                'matched_employees' => $stats['matched_employees'],
                'unmatched_employees' => $stats['unmatched_employees'],
                'unmatched_codes' => $stats['unmatched_codes'],
                'stats' => $stats,
                'data' => $formattedData
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données DB: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Récupérer les données pour DataTables depuis la base de données
     */
    public function getData(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json(['error' => 'Client non trouvé'], 404);
            }
            
            // Valider les paramètres
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'terminal_sn' => 'nullable|string',
                'department' => 'nullable|string',
                'terminal_alias' => 'nullable|string',
            ]);
            
            // Récupérer les paramètres
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            $terminalSn = $request->input('terminal_sn');
            $terminalAlias  = $request->input('terminal_alias');
            
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
            
            Log::info("Récupération données depuis DB pour: " . $startDate . " à " . $endDate . 
                     ", emp_code: " . ($empCode ?: 'all') . 
                     ", department: " . ($department ?: 'all'));
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->with('employee');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all') {
                $query->where('emp_code', $empCode);
            }
            
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            // Filtrer par statut
            if ($request->has('status') && $request->status && $request->status !== 'all') {
                if ($request->status === 'present') {
                    $query->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY', 'OVERTIME', 'SHORT_WORK']);
                } elseif ($request->status === 'absent') {
                    $query->where('status', 'ABSENT');
                } elseif ($request->status === 'late') {
                    $query->where('is_late', true);
                } elseif ($request->status === 'early_leave') {
                    $query->where('is_early_leave', true);
                }
            }
            
            // Filtrer par terminal SN (ancien filtre caché)
            if ($terminalSn && $terminalSn !== 'all') {
              $query->where('raw_data', 'LIKE', '%"' . $terminalSn . '"%');
            }

            // Filtrer par terminal alias (depuis raw_data JSON longtext, avec/sans caractères d'échappement)
            if ($terminalAlias && $terminalAlias !== 'all' && trim($terminalAlias) !== '') {
                $terminalAlias = trim($terminalAlias);
                $escapedAlias = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($terminalAlias));
                $jsonAliasNeedle = '%"terminal_alias":"' . $escapedAlias . '"%';

                // Certains enregistrements sont stockés comme JSON échappé (avec \"...\"), d'autres non.
                // On normalise en retirant les backslashes puis on applique un LIKE insensible à la casse.
                $query->whereRaw(
                    "LOWER(REPLACE(raw_data, '\\\\', '')) LIKE ? ESCAPE '\\\\'",
                    [$jsonAliasNeedle]
                );
            }
            
            // Exécuter la requête
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code')
                ->get();
            
            Log::info("Total présences récupérées depuis DB: " . $attendances->count());
            
            if ($attendances->isEmpty()) {
                return response()->json([
                    'draw' => $request->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }
            
            // Convertir en tableau pour DataTables
            $data = $this->formatAttendancesForDataTables($attendances);
            
            // Pagination manuelle pour DataTables
            $totalRecords = count($data);
            $start = $request->input('start', 0);
            $length = $request->input('length', 50);
            $pageData = array_slice($data, $start, $length);
            
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données pointages depuis DB: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Formater les présences pour l'affichage
     */
    private function formatAttendancesForDisplay($attendances)
    {
        return $attendances->map(function ($attendance) {
            // Décoder les données brutes
            $rawData = $this->ensureArray($attendance->raw_data);
            
            // Extraire les horaires de pointage depuis raw_data
            $punchTimes = collect($rawData)
                ->pluck('punch_time')
                ->map(function ($time) {
                    try {
                        return Carbon::parse($time)->format('H:i:s');
                    } catch (\Exception $e) {
                        return $time;
                    }
                })
                ->filter()
                ->sort()
                ->values()
                ->toArray();
            
            // Extraire les appareils utilisés
            $devicesUsed = collect($rawData)
                ->pluck('terminal_alias')
                ->unique()
                ->filter()
                ->implode(', ');
            
            // Nom complet de l'employé
            $fullName = 'Non enregistré';
            if ($attendance->employee) {
                $fullName = $attendance->employee->first_name ?? '';
                if ($attendance->employee->last_name) {
                    $fullName .= ' ' . $attendance->employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Employé ' . $attendance->emp_code;
            }
            
            // Formater le check-in et check-out
            $checkIn = $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null;
            $checkOut = $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null;
            
            return [
                'date' => $attendance->attendance_date,
                'date_formatted' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                'emp_code' => $attendance->emp_code,
                'full_name' => $fullName,
                'dept_name' => $attendance->employee->dept_name ?? 'Non défini',
                'all_punches' => implode(', ', $punchTimes),
                'punch_times' => $punchTimes,
                'first_punch' => !empty($punchTimes) ? $punchTimes[0] : null,
                'last_punch' => !empty($punchTimes) ? end($punchTimes) : null,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_punches' => count($punchTimes),
                'total_work_hours' => $attendance->work_hours,
                'effective_hours' => $attendance->effective_hours ?? $attendance->work_hours,
                'break_hours' => $attendance->break_hours ?? 1,
                'overtime_hours' => $attendance->overtime_hours ?? 0,
                'devices_used' => $devicesUsed ?: 'Non disponible',
                'status' => $attendance->status,
                'status_label' => $this->getStatusLabel($attendance->status),
                'status_class' => $this->getStatusClass($attendance->status),
                'is_late' => (bool) $attendance->is_late,
                // 'late_minutes' => $attendance->late_minutes ?? 0,
                'is_early_leave' => (bool) $attendance->is_early_leave,
                'early_minutes' => $attendance->early_minutes ?? 0,
                'is_overtime' => (bool) $attendance->is_overtime,
                'is_short_work' => (bool) $attendance->is_short_work,
                'short_hours' => $attendance->short_hours ?? 0,
                'notes' => $attendance->notes,
                'employee_found' => $attendance->employee ? 'oui' : 'non',
                'has_multiple_punches' => count($punchTimes) > 2,
                'raw_data_count' => count($rawData),
                'last_sync_at' => $attendance->last_sync_at ? Carbon::parse($attendance->last_sync_at)->format('H:i:s') : null,
                'updated_at' => $attendance->updated_at ? Carbon::parse($attendance->updated_at)->format('d/m/Y H:i:s') : null
            ];
        })->toArray();
    }
    
    /**
     * Formater les présences pour DataTables
     */
    private function formatAttendancesForDataTables($attendances)
    {
        return $attendances->map(function ($attendance) {
            // Gérer raw_data de manière sécurisée (peut être tableau, string ou null)
            $rawData = $this->ensureArray($attendance->raw_data);
            
            // Extraire les horaires de pointage
            $punchTimes = collect($rawData)
                ->pluck('punch_time')
                ->map(function ($time) {
                    try {
                        return Carbon::parse($time)->format('H:i:s');
                    } catch (\Exception $e) {
                        return $time;
                    }
                })
                ->filter()
                ->sort()
                ->values()
                ->toArray();
            
            // Extraire les appareils utilisés
            $devicesUsed = collect($rawData)
                ->pluck('terminal_alias')
                ->unique()
                ->filter()
                ->implode(', ');
            
            // Nom complet de l'employé
            $fullName = 'Non enregistré';
            if ($attendance->employee) {
                $fullName = $attendance->employee->first_name ?? '';
                if ($attendance->employee->last_name) {
                    $fullName .= ' ' . $attendance->employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Employé ' . $attendance->emp_code;
            }
            
            // Formater pour DataTables
            return [
                'DT_RowId' => 'row_' . $attendance->id,
                'date' => $attendance->attendance_date,
                'date_formatted' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                'day_name' => $this->getFrenchDayName($attendance->attendance_date),
                'employee' => [
                    'first_name' => $attendance->employee->first_name ?? '',
                    'last_name' => $attendance->employee->last_name ?? '',
                    'emp_code' => $attendance->emp_code,
                    'full_name' => $fullName,
                    'dept_name' => $attendance->employee->dept_name ?? 'Non défini'
                ],
                'emp_code' => $attendance->emp_code,
                'full_name' => $fullName,
                'dept_name' => $attendance->employee->dept_name ?? 'Non défini',
                'all_punches' => implode(', ', $punchTimes),
                'punch_list' => $punchTimes,
                'first_punch' => !empty($punchTimes) ? $punchTimes[0] : null,
                'last_punch' => !empty($punchTimes) ? end($punchTimes) : null,
                'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null,
                'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null,
                'total_punches' => count($punchTimes),
                'total_work_hours' => $attendance->work_hours,
                'effective_hours' => $attendance->effective_hours ?? $attendance->work_hours,
                'overtime_hours' => $attendance->overtime_hours ?? 0,
                'devices_used' => $devicesUsed ?: 'Non disponible',
                'status' => $attendance->status,
                'status_label' => $this->getStatusLabel($attendance->status),
                'is_late' => (bool) $attendance->is_late,
                // 'late_minutes' => $attendance->late_minutes ?? 0,
                'is_early_leave' => (bool) $attendance->is_early_leave,
                'early_minutes' => $attendance->early_minutes ?? 0,
                'is_overtime' => (bool) $attendance->is_overtime,
                'is_short_work' => (bool) $attendance->is_short_work,
                'notes' => $attendance->notes,
                'employee_found' => $attendance->employee ? 'yes' : 'no',
                'has_multiple_punches' => count($punchTimes) > 2,
                'raw_data_count' => count($rawData),
                'observation' => $this->generateObservation($attendance),
                'terminal_alias' => $devicesUsed ?: 'Non disponible',
                'actions' => $this->getActionButtons($attendance)
            ];
        })->toArray();
    }

    /**
     * S'assurer que la valeur est un tableau
     * 
     * @param mixed $data
     * @return array
     */
    private function ensureArray($data): array
    {
        if (is_array($data)) {
            return $data;
        }
        
        if (is_string($data) && !empty($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        if (is_object($data)) {
            return (array) $data;
        }
        
        return [];
    }

    /**
     * Obtenir le nom du jour en français
     */
    private function getFrenchDayName($date)
    {
        $days = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];
        
        $dayName = Carbon::parse($date)->format('l');
        return $days[$dayName] ?? $dayName;
    }
    
    /**
     * Calculer les statistiques des présences
     */
    private function calculateAttendanceStats($attendances)
    {
        $total = $attendances->count();
        $present = $attendances->whereIn('status', ['PRESENT', 'LATE', 'OVERTIME', 'SHORT_WORK'])->count();
        $absent = $attendances->where('status', 'ABSENT')->count();
        $late = $attendances->where('status', 'LATE')->count();
        $halfDay = $attendances->where('status', 'HALF_DAY')->count();
        $leave = $attendances->where('status', 'LEAVE')->count();
        $overtime = $attendances->where('is_overtime', true)->count();
        
        // Employés avec correspondance
        $matchedEmployees = $attendances->whereNotNull('employee_id')->count();
        $unmatchedEmployees = $attendances->whereNull('employee_id')->count();
        
        // Codes non trouvés
        $unmatchedCodes = $attendances->whereNull('employee_id')
            ->pluck('emp_code')
            ->unique()
            ->values()
            ->toArray();
        
        // Moyennes
        $avgWorkHours = round($attendances->whereNotNull('work_hours')->avg('work_hours') ?? 0, 2);
        $avgOvertime = round($attendances->whereNotNull('overtime_hours')->avg('overtime_hours') ?? 0, 2);
        
        return [
            'total_days' => $total,
            'present_days' => $present,
            'absent_days' => $absent,
            'late_days' => $late,
            'half_days' => $halfDay,
            'leave_days' => $leave,
            'overtime_days' => $overtime,
            'matched_employees' => $matchedEmployees,
            'unmatched_employees' => $unmatchedEmployees,
            'unmatched_codes' => $unmatchedCodes,
            'avg_work_hours' => $avgWorkHours,
            'avg_overtime_hours' => $avgOvertime,
            'total_overtime_hours' => round($attendances->sum('overtime_hours') ?? 0, 2)
        ];
    }
    
    /**
     * Obtenir le label du statut
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'PRESENT' => 'Présent',
            'ABSENT' => 'Absent',
            'LATE' => 'Retard',
            'EARLY_LEAVE' => 'Départ anticipé',
            'HALF_DAY' => 'Demi-journée',
            'OVERTIME' => 'Heures supplémentaires',
            'SHORT_WORK' => 'Présent',
            'LEAVE' => 'Congé',
            'IRREGULAR' => 'Irregular',
            'MULTIPLE_PUNCHES' => 'Pointages multiples'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Obtenir la classe CSS du statut
     */
    private function getStatusClass($status)
    {
        $classes = [
            'PRESENT' => 'success',
            'ABSENT' => 'danger',
            'LATE' => 'warning',
            'EARLY_LEAVE' => 'warning',
            'HALF_DAY' => 'info',
            'OVERTIME' => 'primary',
            'SHORT_WORK' => 'warning',
            'LEAVE' => 'secondary',
            'IRREGULAR' => 'warning',
            'MULTIPLE_PUNCHES' => 'info'
        ];
        
        return $classes[$status] ?? 'secondary';
    }
    
    /**
     * Générer une observation basée sur les données
     */
    private function generateObservation($attendance)
    {
        $observations = [];
        
        // if ($attendance->is_late && $attendance->late_minutes > 0) {
        //     $observations[] = "Retard de {$attendance->late_minutes} min";
        // }
        
        if ($attendance->is_early_leave && $attendance->early_minutes > 0) {
            $observations[] = "Départ anticipé de {$attendance->early_minutes} min";
        }
        
        if ($attendance->is_overtime && $attendance->overtime_hours > 0) {
            $observations[] = "Heures supp: {$attendance->overtime_hours}h";
        }
        
        if ($attendance->is_short_work && $attendance->short_hours > 0) {
            $observations[] = "Manque: {$attendance->short_hours}h";
        }
        
        if (!empty($observations)) {
            return implode(' | ', $observations);
        }
        
        // Compter les pointages depuis raw_data
        $rawData = $this->ensureArray($attendance->raw_data);
        $punchCount = count($rawData);
        
        if ($punchCount > 0) {
            return "{$punchCount} pointage" . ($punchCount > 1 ? 's' : '');
        }
        
        return $attendance->notes ?: '';
    }
    
    /**
     * Obtenir les boutons d'action
     */
    private function getActionButtons($attendance)
    {
        $buttons = [];
        
        // Bouton pour voir les détails
        $buttons[] = '<button class="btn btn-sm btn-info view-details" 
                      data-id="' . $attendance->id . '" 
                      data-emp-code="' . $attendance->emp_code . '"
                      data-date="' . $attendance->attendance_date . '"
                      title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>';
        
        // Bouton pour resynchroniser
        $buttons[] = '<button class="btn btn-sm btn-warning resync-attendance" 
                      data-id="' . $attendance->id . '"
                      data-emp-code="' . $attendance->emp_code . '"
                      data-date="' . $attendance->attendance_date . '"
                      title="Resynchroniser">
                        <i class="fas fa-sync-alt"></i>
                    </button>';
        
        return implode(' ', $buttons);
    }
    
    /**
     * Récupérer le statut de synchronisation
     */
    public function syncStatus(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'client_name' => 'Non associé',
                    'last_sync' => 'N/A',
                    'current_date' => date('d/m/Y')
                ]);
            }
            
            // Dernière synchronisation depuis les données
            $lastSync = DailyAttendance::where('client_id', $client->id)
                ->whereNotNull('last_sync_at')
                ->orderBy('last_sync_at', 'desc')
                ->first();
            
            // Données d'aujourd'hui
            $todayData = $this->getTodayDataFromDatabase($client);
            
            return response()->json([
                'client_name' => $client->nraison_sociale,
                'last_sync' => $lastSync ? Carbon::parse($lastSync->last_sync_at)->format('d/m/Y H:i:s') : 'Jamais',
                'current_date' => date('d/m/Y'),
                'today_count' => $todayData['success'] ? $todayData['total_attendances'] : 0,
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
     * Resynchroniser une présence spécifique
     */
    public function resyncAttendance(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.'
                ], 404);
            }
            
            $attendanceId = $request->input('attendance_id');
            $empCode = $request->input('emp_code');
            $date = $request->input('date');
            
            // Forcer la mise à jour pour cette date
            if ($date) {
                $carbonDate = Carbon::parse($date);
                
                // Synchroniser les transactions depuis l'API
                $accessConfig = DB::table('access_configs')->where('client_id', $client->id)->first();
                
                if (!$accessConfig || !$accessConfig->general_token) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token d\'accès non configuré.'
                    ], 400);
                }
                
                // Synchroniser pour la date spécifique
                $this->attendanceService->syncForDate($carbonDate);
                
                Log::info("Resynchronisation manuelle pour {$empCode} le {$date}");
                
                // Récupérer la présence mise à jour
                $attendance = DailyAttendance::where('client_id', $client->id)
                    ->where('emp_code', $empCode)
                    ->where('attendance_date', $date)
                    ->first();
                
                if ($attendance) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Présence resynchronisée avec succès.',
                        'attendance' => [
                            'id' => $attendance->id,
                            'date' => $attendance->attendance_date,
                            'status' => $attendance->status,
                            'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null,
                            'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null,
                            'work_hours' => $attendance->work_hours,
                            'last_sync_at' => $attendance->last_sync_at ? Carbon::parse($attendance->last_sync_at)->format('H:i:s') : null
                        ]
                    ]);
                }
            }
            
            return response->json([
                'success' => false,
                'message' => 'Impossible de resynchroniser.'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Erreur resynchronisation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Afficher les détails d'une présence
     */
    public function showDetails(Request $request)
    {
        try {
            $attendanceId = $request->input('attendance_id');
            
            $attendance = DailyAttendance::with(['client', 'employee'])
                ->find($attendanceId);
            
            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Présence non trouvée.'
                ], 404);
            }
            
            // Décoder les données brutes
            $rawData = $this->ensureArray($attendance->raw_data);
            
            // Formater les données brutes
            $formattedRawData = collect($rawData)->map(function ($transaction) {
                return [
                    'transaction_id' => $transaction['id'] ?? $transaction['transaction_id'] ?? null,
                    'punch_time' => isset($transaction['punch_time']) ? 
                        Carbon::parse($transaction['punch_time'])->format('d/m/Y H:i:s') : null,
                    'terminal_alias' => $transaction['terminal_alias'] ?? null,
                    'area_alias' => $transaction['area_alias'] ?? null,
                    'verify_type' => $transaction['verify_type'] ?? null,
                    'punch_state' => $transaction['punch_state'] ?? null
                ];
            });
            
            // Calculer les statistiques détaillées
            $punchTimes = collect($rawData)
                ->pluck('punch_time')
                ->map(function ($time) {
                    try {
                        return Carbon::parse($time);
                    } catch (\Exception $e) {
                        return null;
                    }
                })
                ->filter()
                ->sort();
            
            $timeSegments = [];
            if ($punchTimes->count() >= 2) {
                for ($i = 0; $i < $punchTimes->count() - 1; $i++) {
                    $segmentStart = $punchTimes[$i];
                    $segmentEnd = $punchTimes[$i + 1];
                    
                    if ($segmentStart && $segmentEnd) {
                        $durationMinutes = $segmentStart->diffInMinutes($segmentEnd);
                        $timeSegments[] = [
                            'start' => $segmentStart->format('H:i:s'),
                            'end' => $segmentEnd->format('H:i:s'),
                            'duration_minutes' => $durationMinutes,
                            'duration_hours' => round($durationMinutes / 60, 2)
                        ];
                    }
                }
            }
            
            // Informations employé
            $employeeInfo = null;
            if ($attendance->employee) {
                $employeeInfo = [
                    'full_name' => ($attendance->employee->first_name ?? '') . ' ' . ($attendance->employee->last_name ?? ''),
                    'employee_id' => $attendance->employee->employee_id,
                    'email' => $attendance->employee->email,
                    'phone' => $attendance->employee->phone,
                    'dept_name' => $attendance->employee->dept_name ?? 'Non défini'
                ];
            }
            
            return response()->json([
                'success' => true,
                'attendance' => [
                    'id' => $attendance->id,
                    'date' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                    'emp_code' => $attendance->emp_code,
                    'status' => $attendance->status,
                    'status_label' => $this->getStatusLabel($attendance->status),
                    'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null,
                    'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null,
                    'work_hours' => $attendance->work_hours,
                    'effective_hours' => $attendance->effective_hours,
                    'break_hours' => $attendance->break_hours,
                    'overtime_hours' => $attendance->overtime_hours,
                    'is_late' => (bool) $attendance->is_late,
                    // 'late_minutes' => $attendance->late_minutes,
                    'is_early_leave' => (bool) $attendance->is_early_leave,
                    'early_minutes' => $attendance->early_minutes,
                    'is_overtime' => (bool) $attendance->is_overtime,
                    'is_short_work' => (bool) $attendance->is_short_work,
                    'short_hours' => $attendance->short_hours,
                    'notes' => $attendance->notes,
                    'raw_data_count' => count($rawData),
                    'last_sync_at' => $attendance->last_sync_at ? Carbon::parse($attendance->last_sync_at)->format('d/m/Y H:i:s') : null,
                    'updated_at' => $attendance->updated_at ? Carbon::parse($attendance->updated_at)->format('d/m/Y H:i:s') : null
                ],
                'employee' => $employeeInfo,
                'raw_data' => $formattedRawData,
                'time_segments' => $timeSegments,
                'statistics' => [
                    'total_punches' => $punchTimes->count(),
                    'time_segments_count' => count($timeSegments),
                    'total_segment_hours' => round(collect($timeSegments)->sum('duration_minutes') / 60, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur affichage détails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exporter les présences en PDF (téléchargement direct)
     */
    public function exportPDF(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return redirect()->back()->with('error', 'Client non trouvé.');
            }
            
            // Récupérer les paramètres de filtre
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            
            // Si aucune date n'est fournie, utiliser aujourd'hui
            if (!$startDate && !$endDate) {
                $startDate = Carbon::today()->format('Y-m-d');
                $endDate = Carbon::today()->format('Y-m-d');
            } elseif ($startDate && !$endDate) {
                $endDate = $startDate;
            } elseif (!$startDate && $endDate) {
                $startDate = $endDate;
            }
            
            Log::info("Export PDF depuis DB pour: " . $startDate . " à " . $endDate . 
                     ", department: " . ($department ?: 'all'));
            
            // Récupérer tous les employés pour la correspondance
            $employees = Employee::where('client_id', $client->id)
                ->whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get()
                ->keyBy('emp_code');
            
            // Construire la requête pour les présences journalières
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->with('employee')
                ->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code');
            
            if ($empCode && $empCode !== 'all') {
                $query->where('emp_code', $empCode);
            }
            
            // Filtrer par département
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            $attendances = $query->get();
            
            Log::info("Export PDF - Total présences: " . $attendances->count());
            
            if ($attendances->isEmpty()) {
                return redirect()->back()->with('error', 'Aucune donnée à exporter pour cette période.');
            }
            
            // Transformer les données pour l'export
            $data = $this->formatAttendancesForExport($attendances, $employees);
            
            // Statistiques
            $statistics = $this->calculateAttendanceStats($attendances);
            
            // Préparer les filtres pour l'affichage
            $filters = $this->prepareFiltersForDisplay($request, $client);
            
            // Préparer les données pour la vue PDF
            $pdfData = [
                'attendances' => $data,
                'client' => $client,
                'filters' => $filters,
                'statistics' => $statistics,
                'export_date' => now()->format('d/m/Y H:i'),
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
                'title' => 'Rapport des Présences'
            ];
            
            // Générer le PDF
            $pdf = PDF::loadView('attendance::exports.pdf', $pdfData);
            
            // Configurer les options du PDF
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
            
            // Nom du fichier avec timestamp
            $filename = 'rapport_presences_' . $client->nraison_sociale . '_' . 
                       Carbon::parse($startDate)->format('Ymd') . '_' . 
                       Carbon::parse($endDate)->format('Ymd') . '_' . 
                       Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            // Téléchargement direct du PDF
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les présences en PDF (pour les présences)
     */
    public function exportPresencePdf(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.'
                ], 404);
            }
            
            // Récupérer les paramètres de filtre
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            $status = $request->input('status');
            
            // Si aucune date n'est fournie, utiliser les 30 derniers jours
            if (!$startDate) {
                $startDate = Carbon::today()->subDays(30)->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = Carbon::today()->format('Y-m-d');
            }
            
            Log::info("Export PDF Présences pour: " . $startDate . " à " . $endDate);
            
            // Récupérer tous les employés pour la correspondance
            $employees = Employee::where('client_id', $client->id)
                ->whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get()
                ->keyBy('emp_code');
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY', 'OVERTIME', 'SHORT_WORK'])
                ->with('employee')
                ->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all' && $empCode !== '') {
                $query->where('emp_code', $empCode);
            }
            
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            if ($status && $status !== '' && $status !== 'all') {
                if ($status === 'late') {
                    $query->where('is_late', true);
                } elseif ($status === 'early_leave') {
                    $query->where('is_early_leave', true);
                } elseif ($status === 'overtime') {
                    $query->where('is_overtime', true);
                } elseif ($status === 'half_day') {
                    $query->where('status', 'HALF_DAY');
                }
            }
            
            $attendances = $query->get();
            
            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée à exporter pour cette période.'
                ], 404);
            }
            
            // Transformer les données pour l'export
            $data = $this->formatAttendancesForExport($attendances, $employees);
            
            // Statistiques
            $statistics = $this->calculateAttendanceStats($attendances);
            
            // Préparer les filtres pour l'affichage
            $filters = $this->prepareFiltersForDisplay($request, $client);
            
            // Générer un nom de fichier unique
            $filename = 'rapport_presences_' . $client->nraison_sociale . '_' . 
                       Carbon::parse($startDate)->format('Ymd') . '_' . 
                       Carbon::parse($endDate)->format('Ymd') . '_' . 
                       Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            // Chemin temporaire pour stocker le PDF
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Créer le dossier temp s'il n'existe pas
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Préparer les données pour la vue PDF
            $pdfData = [
                'attendances' => $data,
                'client' => $client,
                'filters' => $filters,
                'statistics' => $statistics,
                'export_date' => now()->format('d/m/Y H:i'),
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
                'title' => 'Rapport des Présences'
            ];
            
            // Générer et sauvegarder le PDF
            $pdf = PDF::loadView('attendance::exports.pdf', $pdfData);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
            $pdf->save($tempPath);
            
            // Générer une URL temporaire pour télécharger le fichier
            $pdfUrl = route('admin.daily-attendance.download-temp', ['filename' => basename($tempPath)]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'pdf_url' => $pdfUrl,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF Présences: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les retards en PDF
     */
    public function exportRetardPdf(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.'
                ], 404);
            }
            
            // Récupérer les paramètres de filtre
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            // $minLateMinutes = $request->input('min_late_minutes');
            
            // Si aucune date n'est fournie, utiliser les 30 derniers jours
            if (!$startDate) {
                $startDate = Carbon::today()->subDays(30)->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = Carbon::today()->format('Y-m-d');
            }
            
            Log::info("Export PDF Retards pour: " . $startDate . " à " . $endDate);
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('is_late', true)
                ->with('employee')
                ->orderBy('attendance_date', 'desc')
                // ->orderBy('late_minutes', 'desc')
                ->orderBy('emp_code');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all' && $empCode !== '') {
                $query->where('emp_code', $empCode);
            }
            
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            // if ($minLateMinutes) {
            //     $query->where('late_minutes', '>=', $minLateMinutes);
            // }
            
            $attendances = $query->get();
            
            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée à exporter pour cette période.'
                ], 404);
            }
            
            // Transformer les données pour l'export
            $data = $this->formatRetardsForExport($attendances);
            
            // Calculer les statistiques
            $summary = $this->calculateRetardSummary($attendances, $client, $startDate, $endDate);
            
            // Préparer les filtres pour l'affichage
            $filters = $this->prepareFiltersForDisplay($request, $client);
            
            // Générer un nom de fichier unique
            $filename = 'rapport_retards_' . $client->nraison_sociale . '_' . 
                       Carbon::parse($startDate)->format('Ymd') . '_' . 
                       Carbon::parse($endDate)->format('Ymd') . '_' . 
                       Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            // Chemin temporaire pour stocker le PDF
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Créer le dossier temp s'il n'existe pas
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Préparer les données pour la vue PDF
            $pdfData = [
                'attendances' => $data,
                'client' => $client,
                'filters' => $filters,
                'summary' => $summary,
                'export_date' => now()->format('d/m/Y H:i'),
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
                'title' => 'Rapport des Retards'
            ];
            
            // Générer et sauvegarder le PDF
            $pdf = PDF::loadView('attendance::exports.retarddf', $pdfData);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
            $pdf->save($tempPath);
            
            // Générer une URL temporaire pour télécharger le fichier
            $pdfUrl = route('admin.daily-attendance.download-temp', ['filename' => basename($tempPath)]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'pdf_url' => $pdfUrl,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF Retards: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les absences en PDF
     */
    public function exportAbsencePdf(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.'
                ], 404);
            }
            
            // Récupérer les paramètres de filtre
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code');
            $department = $request->input('department');
            
            // Si aucune date n'est fournie, utiliser les 30 derniers jours
            if (!$startDate) {
                $startDate = Carbon::today()->subDays(30)->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = Carbon::today()->format('Y-m-d');
            }
            
            Log::info("Export PDF Absences pour: " . $startDate . " à " . $endDate);
            
            // Récupérer tous les employés pour la correspondance
            $employees = Employee::where('client_id', $client->id)
                ->whereNotNull('emp_code')
                ->where('emp_code', '!=', '')
                ->get()
                ->keyBy('emp_code');
            
            // Construire la requête
            $query = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('status', 'ABSENT')
                ->with('employee')
                ->orderBy('attendance_date', 'desc')
                ->orderBy('emp_code');
            
            // Appliquer les filtres
            if ($empCode && $empCode !== 'all' && $empCode !== '') {
                $query->where('emp_code', $empCode);
            }
            
            if ($department && $department !== '' && $department !== 'all') {
                $allEmployees = Employee::where('client_id', $client->id)->get();
                $filteredEmployees = $allEmployees->filter(function($emp) use ($department) {
                    return $emp->dept_name === $department;
                })->pluck('id')->toArray();
                
                if (!empty($filteredEmployees)) {
                    $query->whereIn('employee_id', $filteredEmployees);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
            
            $attendances = $query->get();
            
            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée à exporter pour cette période.'
                ], 404);
            }
            
            // Transformer les données pour l'export
            $data = $this->formatAttendancesForExport($attendances, $employees);
            
            // Statistiques spécifiques aux absences
            $totalAbsences = $attendances->count();
            $uniqueEmployees = $attendances->pluck('employee_id')->filter()->unique()->count();
            $workingDays = $this->countWorkingDays($startDate, $endDate);
            $totalEmployees = Employee::where('client_id', $client->id)->count();
            $absenceRate = $totalEmployees > 0 ? round(($totalAbsences / ($totalEmployees * $workingDays)) * 100, 1) : 0;
            
            $statistics = [
                'total_absences' => $totalAbsences,
                'unique_employees' => $uniqueEmployees,
                'working_days' => $workingDays,
                'total_employees' => $totalEmployees,
                'absence_rate' => $absenceRate
            ];
            
            // Préparer les filtres pour l'affichage
            $filters = $this->prepareFiltersForDisplay($request, $client);
            
            // Générer un nom de fichier unique
            $filename = 'rapport_absences_' . $client->nraison_sociale . '_' . 
                       Carbon::parse($startDate)->format('Ymd') . '_' . 
                       Carbon::parse($endDate)->format('Ymd') . '_' . 
                       Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            // Chemin temporaire pour stocker le PDF
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Créer le dossier temp s'il n'existe pas
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Préparer les données pour la vue PDF
            $pdfData = [
                'attendances' => $data,
                'client' => $client,
                'filters' => $filters,
                'statistics' => $statistics,
                'export_date' => now()->format('d/m/Y H:i'),
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
                'title' => 'Rapport des Absences'
            ];
            
            // Générer et sauvegarder le PDF
            $pdf = PDF::loadView('attendance::exports.absence-pdf', $pdfData);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
            $pdf->save($tempPath);
            
            // Générer une URL temporaire pour télécharger le fichier
            $pdfUrl = route('admin.daily-attendance.download-temp', ['filename' => basename($tempPath)]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF généré avec succès',
                'pdf_url' => $pdfUrl,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF Absences: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un fichier temporaire
     */
    public function downloadTempFile($filename)
    {
        $path = storage_path('app/temp/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, 'Fichier non trouvé.');
        }
        
        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Formater les retards pour l'export PDF
     */
    private function formatRetardsForExport($attendances)
    {
        return $attendances->map(function ($attendance) {
            // Nom complet de l'employé
            $fullName = 'Non enregistré';
            if ($attendance->employee) {
                $fullName = $attendance->employee->first_name ?? '';
                if ($attendance->employee->last_name) {
                    $fullName .= ' ' . $attendance->employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Employé ' . $attendance->emp_code;
            }
            
            // Heure théorique d'arrivée (par défaut 09:00)
            $theoreticalStartTime = '09:00';
            $actualCheckIn = $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '--:--';
            
            // Calcul du retard en minutes
            // $lateMinutes = $attendance->late_minutes ?? 0;
            
            return [
                'date' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                'day_name' => $this->getFrenchDayName($attendance->attendance_date),
                'emp_code' => $attendance->emp_code,
                'employee_name' => $fullName,
                'dept_name' => $attendance->employee->dept_name ?? 'Non défini',
                'check_in' => $actualCheckIn,
                'theoretical_start' => $theoreticalStartTime,
                // 'late_minutes' => $lateMinutes,
                'late_hours' => $this->formatMinutesToHours($lateMinutes),
                'status' => $attendance->status,
                'notes' => $attendance->notes ?: '-'
            ];
        })->toArray();
    }

    /**
     * Formater les présences pour l'export
     */
    private function formatAttendancesForExport($attendances, $employees)
    {
        return $attendances->map(function ($attendance) use ($employees) {
            $employee = $attendance->employee ?? $employees->get($attendance->emp_code);
            
            $fullName = 'Non enregistré';
            if ($employee) {
                $fullName = $employee->first_name ?? '';
                if ($employee->last_name) {
                    $fullName .= ' ' . $employee->last_name;
                }
                $fullName = trim($fullName) ?: 'Employé ' . $attendance->emp_code;
            }
            
            return [
                'date' => Carbon::parse($attendance->attendance_date)->format('d/m/Y'),
                'emp_code' => $attendance->emp_code,
                'employee_name' => $fullName,
                'dept_name' => $employee->dept_name ?? 'Non défini',
                'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '--:--',
                'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '--:--',
                'work_hours' => $attendance->work_hours,
                'status' => $this->getStatusLabel($attendance->status),
                'is_late' => $attendance->is_late ? 'Oui' : 'Non',
                // 'late_minutes' => $attendance->late_minutes ?? 0,
                'notes' => $attendance->notes ?: '-'
            ];
        })->toArray();
    }

    /**
     * Préparer les filtres pour l'affichage
     */
    private function prepareFiltersForDisplay($request, $client)
    {
        $filters = [];
        
        if ($request->has('start_date') && $request->start_date) {
            $filters['date_début'] = Carbon::parse($request->start_date)->format('d/m/Y');
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $filters['date_fin'] = Carbon::parse($request->end_date)->format('d/m/Y');
        }
        
        if ($request->has('emp_code') && $request->emp_code && $request->emp_code !== 'all') {
            $employee = Employee::where('client_id', $client->id)
                ->where('emp_code', $request->emp_code)
                ->first();
                
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

        // Ajouter le département aux filtres pour l'affichage
        if ($request->has('department') && $request->department && $request->department !== 'all') {
            $filters['département'] = $request->department;
        }
        
        // Ajouter le statut pour l'affichage
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $statusLabels = [
                'present' => 'Présent',
                'absent' => 'Absent',
                'late' => 'Retard',
                'early_leave' => 'Départ anticipé',
                'overtime' => 'Heures supplémentaires',
                'half_day' => 'Demi-journée'
            ];
            $filters['statut'] = $statusLabels[$request->status] ?? $request->status;
        }
        
        // Ajouter le filtre minutes de retard minimum
        // if ($request->has('min_late_minutes') && $request->min_late_minutes) {
        //     $filters['retard_minimum'] = $request->min_late_minutes . ' minutes';
        // }
        
        return $filters;
    }
    
    /**
     * Récupérer un employé par son code
     */
    public function getEmployeeByCode(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client || !$request->has('emp_code')) {
            return response()->json(null);
        }
        
        $employee = Employee::where('client_id', $client->id)
            ->where('emp_code', $request->emp_code)
            ->first();
            
        if ($employee) {
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
                'full_name' => $fullName,
                'dept_name' => $employee->dept_name ?? 'Non défini'
            ]);
        }
        
        return response()->json(null);
    }

    /**
     * Synchroniser manuellement les pointages pour le client connecté
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAttendance(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.'
                ], 404);
            }
            
            // Validation des paramètres
            $request->validate([
                'date' => 'nullable|date_format:Y-m-d',
                'days_back' => 'nullable|integer|min:1|max:30',
                'force' => 'nullable|boolean'
            ]);
            
            $specificDate = $request->input('date');
            $daysBack = $request->input('days_back', 7);
            $force = $request->input('force', false);
            
            Log::info("Synchronisation manuelle demandée pour le client: {$client->id}", [
                'date' => $specificDate,
                'days_back' => $daysBack,
                'force' => $force
            ]);
            
            // Vérifier la configuration d'accès
            $accessConfig = DB::table('access_configs')->where('client_id', $client->id)->first();
            
            if (!$accessConfig || !$accessConfig->general_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'accès non configuré. Veuillez contacter l\'administrateur.'
                ], 400);
            }
            
            // Exécuter la synchronisation selon les paramètres
            if ($specificDate) {
                // Synchronisation pour une date spécifique
                $date = Carbon::parse($specificDate);
                
                // Utiliser la méthode syncClientForDate si elle existe, sinon syncForDate
                if (method_exists($this->attendanceService, 'syncClientForDate')) {
                    $result = $this->attendanceService->syncClientForDate($client, $date);
                } else {
                    // Alternative: synchroniser pour cette date (tous clients)
                    $this->attendanceService->syncForDate($date);
                    $result = true;
                }
                
                $message = "Synchronisation effectuée pour la date {$date->format('d/m/Y')}";
                
            } else {
                // Synchronisation pour les derniers jours
                if (method_exists($this->attendanceService, 'syncClientAttendances')) {
                    $result = $this->attendanceService->syncClientAttendances($client, $daysBack);
                } else {
                    // Alternative: utiliser syncForPeriod ou syncAllClients
                    $endDate = Carbon::today();
                    $startDate = Carbon::today()->subDays($daysBack);
                    $this->attendanceService->syncForPeriod($startDate, $endDate);
                    $result = true;
                }
                
                $message = "Synchronisation effectuée pour les {$daysBack} derniers jours";
            }
            
            // Récupérer les statistiques après synchronisation
            $todayStats = $this->getTodayStats($client);
            
            // Récupérer les données d'aujourd'hui
            $todayData = $this->getTodayDataFromDatabase($client);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => $todayStats,
                'today_data' => $todayData,
                'sync_time' => now()->format('H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur synchronisation manuelle: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }
}
