<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\Device;
use App\Models\EmployeeSchedule;
use App\Models\Leave;
use App\Models\EmployeePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Afficher la page des rapports
     */
    public function absencesDelays(Request $request)
    {
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(function($employee) {
                return [
                    'emp_code' => $employee->emp_code,
                    'full_name' => $employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : '')
                ];
            });
        
        $client = collect(Setting::getGroup('company'));
        
        return view('reports.index', compact('employees', 'client'));
    }
    
    /**
     * Récupérer les données pour DataTables
     */
    public function getData(Request $request)
    {
        try {
            // Valider les paramètres
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }
            
            // Récupérer les paramètres
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code', 'all');
            
            Log::info("Rapport - Début: {$startDate}, Fin: {$endDate}, Employé: {$empCode}");
            
            // Récupérer le token d'authentification
            $accessConfig = DB::table('access_configs')->first();
            
            if (!$accessConfig || empty($accessConfig->general_token)) {
                return response()->json(['error' => 'Token d\'accès non configuré'], 400);
            }
            
            $token = $accessConfig->general_token;
            
            // Récupérer tous les devices
            $devices = Device::all();
            
            if ($devices->isEmpty()) {
                Log::warning("Aucun device trouvé");
                return response()->json([
                    'draw' => (int) $request->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }
            
            // Récupérer les employés selon le filtre
            $employeesQuery = Employee::whereNotNull('emp_code')
                ->where('emp_code', '!=', '');
            
            if ($empCode && $empCode !== 'all') {
                $employeesQuery->where('emp_code', $empCode);
            }
            
            $employees = $employeesQuery->get();
            
            if ($employees->isEmpty()) {
                Log::warning("Aucun employé trouvé");
                return response()->json([
                    'draw' => (int) $request->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }
            
            Log::info("Nombre d'employés à analyser: " . $employees->count());
            
            // Convertir en format datetime pour l'API
            $startTimeAPI = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endTimeAPI = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');
            
            // Récupérer les permissions approuvées
            $permissions = EmployeePermission::where('status', 'approved')
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy('employee_id');
            
            // Récupérer les congés
            $leaves = Leave::whereBetween('start_date', [$startDate, $endDate])
                ->get();
            
            // Analyser chaque jour de la période
            $reportData = [];
            $currentDate = Carbon::parse($startDate);
            $endDateObj = Carbon::parse($endDate);
            
            // Limiter la période pour éviter les timeouts
            $daysDiff = $currentDate->diffInDays($endDateObj);
            if ($daysDiff > 31) {
                return response()->json([
                    'error' => 'La période ne doit pas dépasser 31 jours. Période sélectionnée: ' . ($daysDiff + 1) . ' jours.'
                ], 400);
            }
            
            Log::info("Analyse de {$startDate} à {$endDate} (" . ($daysDiff + 1) . " jours)");
            
            while ($currentDate <= $endDateObj) {
                $dateStr = $currentDate->format('Y-m-d');
                
                // Collecter toutes les transactions pour cette date
                $allTransactions = collect();
                
                foreach ($devices as $device) {
                    $deviceTransactions = $this->getDeviceTransactionsForDate(
                        $device,
                        $currentDate,
                        $token,
                        $employees
                    );
                    $allTransactions = $allTransactions->merge($deviceTransactions);
                }
                
                // Grouper par employé
                $groupedTransactions = $this->groupTransactionsByEmployee($allTransactions, $employees);
                
                // Analyser chaque employé pour cette date
                foreach ($employees as $employee) {
                    $analysis = $this->analyzeEmployeeDay(
                        $employee,
                        $dateStr,
                        $groupedTransactions[$employee->emp_code] ?? null,
                        $permissions,
                        $leaves
                    );
                    
                    if ($analysis) {
                        $reportData[] = $analysis;
                    }
                }
                
                $currentDate->addDay();
            }
            
            Log::info("Rapport généré avec " . count($reportData) . " enregistrements");
            
            // Pagination manuelle pour DataTables
            $totalRecords = count($reportData);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            
            // Trier par date décroissante, puis par employé
            usort($reportData, function($a, $b) {
                if ($a['date'] == $b['date']) {
                    return strcmp($a['employee_code'], $b['employee_code']);
                }
                return strcmp($b['date'], $a['date']);
            });
            
            $pageData = array_slice($reportData, $start, $length);
            
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur génération rapport: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Erreur serveur: ' . $e->getMessage(),
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Récupérer les transactions d'un device pour une date spécifique
     */
    private function getDeviceTransactionsForDate($device, $date, $token, $employees)
    {
        $startTime = $date->startOfDay()->format('Y-m-d H:i:s');
        $endTime = $date->endOfDay()->format('Y-m-d H:i:s');
        
        $page = 1;
        $limit = 100;
        $allTransactions = collect();
        $maxPages = 3;
        
        do {
            try {
                // Filtrer par emp_code si peu d'employés
                $empCodes = $employees->pluck('emp_code')->toArray();
                $empCodeFilter = count($empCodes) <= 10 ? implode(',', $empCodes) : null;
                
                $apiParams = [
                    'page' => $page,
                    'limit' => $limit,
                    'terminal_sn' => $device->device_sn,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ];
                
                if ($empCodeFilter) {
                    $apiParams['emp_code'] = $empCodeFilter;
                }
                
                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $token,
                    'Accept' => 'application/json',
                ])->withOptions([
                    'verify' => false,
                    'timeout' => 30,
                ])->get('http://54.37.15.111/iclock/api/transactions/', $apiParams);
                
                if (!$response->successful()) {
                    Log::warning("Erreur API device {$device->device_sn}: " . $response->status());
                    break;
                }
                
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data'])) {
                    break;
                }
                
                if (empty($data['data'])) {
                    break;
                }
                
                $transactions = collect($data['data']);
                $allTransactions = $allTransactions->merge($transactions);
                
                $hasNextPage = !empty($data['next']);
                if (!$hasNextPage || $page >= $maxPages) {
                    break;
                }
                
                $page++;
                usleep(100000);
                
            } catch (\Exception $e) {
                Log::error("Erreur récupération device {$device->device_sn}: " . $e->getMessage());
                break;
            }
            
        } while (true);
        
        return $allTransactions;
    }
    
    /**
     * Grouper les transactions par employé
     */
    private function groupTransactionsByEmployee($transactions, $employees)
    {
        $grouped = [];
        
        foreach ($transactions as $transaction) {
            $empCode = $transaction['emp_code'] ?? null;
            if (!$empCode) continue;
            
            $punchTime = Carbon::parse($transaction['punch_time']);
            $timeStr = $punchTime->format('H:i:s');
            
            if (!isset($grouped[$empCode])) {
                $grouped[$empCode] = [
                    'arrival_time' => null,
                    'departure_time' => null,
                    'all_punches' => []
                ];
            }
            
            $grouped[$empCode]['all_punches'][] = $timeStr;
            
            // Déterminer si c'est une arrivée ou un départ
            $hour = $punchTime->hour;
            $isArrival = ($hour < 14);
            
            // Garder la première arrivée et le dernier départ
            if ($isArrival) {
                if (!$grouped[$empCode]['arrival_time'] || $timeStr < $grouped[$empCode]['arrival_time']) {
                    $grouped[$empCode]['arrival_time'] = $timeStr;
                }
            } else {
                if (!$grouped[$empCode]['departure_time'] || $timeStr > $grouped[$empCode]['departure_time']) {
                    $grouped[$empCode]['departure_time'] = $timeStr;
                }
            }
        }
        
        return $grouped;
    }
    
    /**
     * Analyser la journée d'un employé
     */
    private function analyzeEmployeeDay($employee, $dateStr, $transactions, $permissions, $leaves)
    {
        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;
        
        // 1. Récupérer l'horaire prévu avec le nouveau système (fixe, rotation, planifié)
        $schedule = $this->getEmployeeScheduleForDateNew($employee, $dateStr);
        
        // 2. Vérifier si jour férié ou weekend
        $isWeekend = in_array($dayOfWeek, [6, 7]);
        $isHoliday = $this->isHoliday($dateStr);
        
        // 3. Vérifier les congés
        $isOnLeave = $this->isEmployeeOnLeave($employee->id, $dateStr, $leaves);
        
        // 4. Vérifier les permissions
        $hasPermission = $this->hasPermission($employee->id, $dateStr, $permissions);
        
        // 5. Analyser la présence
        $arrivalTime = $transactions['arrival_time'] ?? null;
        $departureTime = $transactions['departure_time'] ?? null;
        
        // 6. Déterminer le statut
        $status = $this->determineStatus(
            $schedule,
            $isWeekend,
            $isHoliday,
            $isOnLeave,
            $hasPermission,
            $arrivalTime,
            $departureTime
        );
        
        // 7. Initialiser à null par défaut
        $lateMinutes = null;
        $earlyLeaveMinutes = null;
        $workMinutes = 0;
        $isLate = false;
        $isEarlyLeave = false;
        
        // 8. Calculer le retard si c'est un jour travaillé ET que l'employé est présent
        if ($status === 'present' && $schedule && $schedule['is_working_day'] && $schedule['start_time'] && $arrivalTime) {
            try {
                $scheduleStart = Carbon::createFromFormat('H:i:s', $schedule['start_time']);
                $actualArrival = Carbon::createFromFormat('H:i:s', $arrivalTime);
                
                if ($actualArrival > $scheduleStart) {
                    $lateMinutes = $actualArrival->diffInMinutes($scheduleStart);
                    $isLate = true;
                } else {
                    $lateMinutes = 0;
                }
            } catch (\Exception $e) {
                Log::error("Erreur calcul retard pour {$employee->emp_code}: " . $e->getMessage());
                $lateMinutes = null;
            }
        }
        
        // 9. Calculer le départ anticipé si présent
        if ($status === 'present' && $schedule && $schedule['is_working_day'] && $schedule['end_time'] && $departureTime) {
            try {
                $scheduleEnd = Carbon::createFromFormat('H:i:s', $schedule['end_time']);
                $actualDeparture = Carbon::createFromFormat('H:i:s', $departureTime);
                
                if ($actualDeparture < $scheduleEnd) {
                    $earlyLeaveMinutes = $scheduleEnd->diffInMinutes($actualDeparture);
                    $isEarlyLeave = true;
                } else {
                    $earlyLeaveMinutes = 0;
                }
            } catch (\Exception $e) {
                Log::error("Erreur calcul départ anticipé pour {$employee->emp_code}: " . $e->getMessage());
                $earlyLeaveMinutes = null;
            }
        }
        
        // 10. Calculer les heures travaillées
        if ($arrivalTime && $departureTime) {
            try {
                $arrival = Carbon::createFromFormat('H:i:s', $arrivalTime);
                $departure = Carbon::createFromFormat('H:i:s', $departureTime);
                
                if ($departure > $arrival) {
                    $workMinutes = $departure->diffInMinutes($arrival);
                }
            } catch (\Exception $e) {
                Log::error("Erreur calcul heures travaillées pour {$employee->emp_code}: " . $e->getMessage());
                $workMinutes = 0;
            }
        }
        
        // 11. Formater pour DataTables
        return [
            'date' => $dateStr,
            'employee_code' => $employee->emp_code,
            'employee_name' => $employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : ''),
            'schedule_start' => $schedule && $schedule['start_time'] ? $schedule['start_time'] : '-',
            'schedule_end' => $schedule && $schedule['end_time'] ? $schedule['end_time'] : '-',
            'actual_arrival' => $arrivalTime,
            'actual_departure' => $departureTime,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'work_minutes' => $workMinutes,
            'work_hours' => round($workMinutes / 60, 2),
            'status' => $status,
            'is_weekend' => $isWeekend,
            'is_holiday' => $isHoliday,
            'is_on_leave' => $isOnLeave,
            'has_permission' => $hasPermission,
            'all_punches' => $transactions['all_punches'] ?? [],
            'schedule_type' => $schedule ? $schedule['schedule_type'] : 'Non planifié',
            'is_late' => $isLate,
            'is_early_leave' => $isEarlyLeave,
            'schedule_data' => $schedule ? json_encode($schedule) : null,
        ];
    }
    
    /**
     * Récupérer le planning d'un employé pour une date spécifique (supporte 3 types)
     */
    private function getEmployeeScheduleForDateNew($employee, $dateStr)
    {
        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;
        
        // 1. Chercher d'abord un planning spécifique à la date exacte
        $specificSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $dateStr)
            ->first();
        
        if ($specificSchedule) {
            Log::info("Planning spécifique trouvé pour {$employee->emp_code} le {$dateStr} - Type: {$specificSchedule->schedule_type}");
            return $this->formatScheduleData($specificSchedule);
        }
        
        // 2. Chercher un planning dans la plage de dates (start_date - end_date)
        $rangeSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where(function($query) use ($dateStr) {
                $query->where('start_date', '<=', $dateStr)
                      ->where('end_date', '>=', $dateStr);
            })
            ->first();
        
        if ($rangeSchedule) {
            Log::info("Planning dans plage trouvé pour {$employee->emp_code} le {$dateStr} - Type: {$rangeSchedule->schedule_type}");
            return $this->formatScheduleData($rangeSchedule);
        }
        
        // 3. Pour les types FIXE: chercher par jour de semaine
        $fixedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'fixe')
            ->where('day_of_week', $dayOfWeek)
            ->first();
        
        if ($fixedSchedule) {
            Log::info("Planning fixe trouvé pour {$employee->emp_code} le {$dateStr} (jour {$dayOfWeek})");
            return $this->formatScheduleData($fixedSchedule);
        }
        
        // 4. Pour les types ROTATION: calculer selon le cycle
        $rotationSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'rotation')
            ->first();
        
        if ($rotationSchedule && $rotationSchedule->start_date && $rotationSchedule->end_date) {
            // Vérifier si la date est dans la période de rotation
            $scheduleStart = Carbon::parse($rotationSchedule->start_date);
            $scheduleEnd = Carbon::parse($rotationSchedule->end_date);
            $currentDate = Carbon::parse($dateStr);
            
            if ($currentDate->between($scheduleStart, $scheduleEnd)) {
                // Calculer si c'est un jour de travail dans le cycle
                $daysFromStart = $scheduleStart->diffInDays($currentDate);
                $workDaysCount = $rotationSchedule->work_days_count ?? 1;
                $restDaysCount = $rotationSchedule->rest_days_count ?? 0;
                $cycleLength = $workDaysCount + $restDaysCount;
                
                $positionInCycle = $daysFromStart % $cycleLength;
                
                if ($positionInCycle < $workDaysCount) {
                    Log::info("Planning rotation (jour travail) trouvé pour {$employee->emp_code} le {$dateStr}");
                    return $this->formatScheduleData($rotationSchedule);
                } else {
                    Log::info("Planning rotation (jour repos) trouvé pour {$employee->emp_code} le {$dateStr}");
                    return [
                        'schedule_type' => 'rotation',
                        'is_working_day' => false,
                        'start_time' => null,
                        'end_time' => null,
                        'work_days_count' => $workDaysCount,
                        'rest_days_count' => $restDaysCount
                    ];
                }
            }
        }
        
        // 5. Pour les types PLANIFIE: chercher un planning standard
        $plannedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'planifie')
            ->first();
        
        if ($plannedSchedule) {
            Log::info("Planning planifié trouvé pour {$employee->emp_code} le {$dateStr}");
            return $this->formatScheduleData($plannedSchedule);
        }
        
        // 6. Dernier recours: chercher n'importe quel planning
        $anySchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($anySchedule) {
            Log::info("Dernier planning trouvé pour {$employee->emp_code} le {$dateStr} - Type: {$anySchedule->schedule_type}");
            return $this->formatScheduleData($anySchedule);
        }
        
        Log::warning("Aucun planning trouvé pour {$employee->emp_code} le {$dateStr}");
        return null;
    }
    
    /**
     * Formater les données du planning
     */
    private function formatScheduleData($schedule)
    {
        return [
            'schedule_type' => $schedule->schedule_type,
            'is_working_day' => $schedule->is_working_day ?? true,
            'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i:s') : null,
            'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i:s') : null,
            'work_days_count' => $schedule->work_days_count ?? null,
            'rest_days_count' => $schedule->rest_days_count ?? null,
            'daily_hours' => $schedule->daily_hours ?? null,
            'break_minutes' => $schedule->break_minutes ?? 0,
            'start_date' => $schedule->start_date,
            'end_date' => $schedule->end_date,
        ];
    }
    
    /**
     * Vérifier si c'est un jour férié
     */
    private function isHoliday($date)
    {
        // À implémenter selon votre table des jours fériés
        return false;
    }
    
    /**
     * Vérifier si l'employé est en congé
     */
    private function isEmployeeOnLeave($employeeId, $date, $leaves)
    {
        foreach ($leaves as $leave) {
            if ($leave->employee_id == $employeeId) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $checkDate = Carbon::parse($date);
                
                if ($checkDate->between($leaveStart, $leaveEnd)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Vérifier si l'employé a une permission
     */
    private function hasPermission($employeeId, $date, $permissions)
    {
        if (isset($permissions[$employeeId])) {
            foreach ($permissions[$employeeId] as $permission) {
                if (Carbon::parse($permission->date)->format('Y-m-d') == $date) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Déterminer le statut de présence
     */
    private function determineStatus($schedule, $isWeekend, $isHoliday, $isOnLeave, $hasPermission, $arrivalTime, $departureTime)
    {
        // 1. Si jour férié
        if ($isHoliday) {
            return 'holiday';
        }
        
        // 2. Si en congé
        if ($isOnLeave) {
            return 'leave';
        }
        
        // 3. Si permission
        if ($hasPermission) {
            return 'permission';
        }
        
        // 4. Si pas d'horaire prévu
        if (!$schedule) {
            // Si weekend sans planning
            if ($isWeekend) {
                return 'weekend';
            }
            // Si jour de semaine sans planning
            return 'no_schedule';
        }
        
        // 5. Si jour non travaillé dans le planning
        if ($schedule['is_working_day'] === false) {
            return 'day_off';
        }
        
        // 6. Si weekend mais avec planning de travail
        if ($isWeekend && $schedule['is_working_day'] === true) {
            // Weekend mais prévu pour travailler
            if ($arrivalTime || $departureTime) {
                return 'present';
            } else {
                return 'absent';
            }
        }
        
        // 7. Si présent (au moins un pointage)
        if ($arrivalTime !== null || $departureTime !== null) {
            return 'present';
        }
        
        // 8. Si absence justifiée (à implémenter si besoin)
        
        // 9. Sinon absent
        return 'absent';
    }
    
    /**
     * Exporter le rapport en Excel
     */
    public function export(Request $request)
    {
        // À implémenter selon vos besoins
    }

    /**
     * Déterminer si un employé est en retard selon différentes règles
     */
    private function calculateLateStatus($employee, $dateStr, $schedule, $arrivalTime, $departureTime)
    {
        if (!$schedule || !$schedule->start_time) {
            return [
                'late_minutes' => 0,
                'is_late' => false,
                'late_reason' => 'Pas de planning'
            ];
        }
        
        $scheduleStart = Carbon::createFromFormat('H:i:s', $schedule->start_time->format('H:i:s'));
        $lateMinutes = 0;
        $isLate = false;
        $lateReason = '';
        
        // RÈGLE 1: Si arrivée enregistrée après start_time
        if ($arrivalTime) {
            $actualArrival = Carbon::createFromFormat('H:i:s', $arrivalTime);
            
            if ($actualArrival > $scheduleStart) {
                $lateMinutes = $actualArrival->diffInMinutes($scheduleStart);
                $isLate = true;
                $lateReason = 'Arrivée tardive';
                
                // Vérifier la tolérance (ex: 5 minutes de grâce)
                $toleranceMinutes = 5; // À configurer
                if ($lateMinutes <= $toleranceMinutes) {
                    $isLate = false;
                    $lateReason = 'Dans la tolérance';
                }
            }
        }
        // RÈGLE 2: Si pas d'arrivée mais départ enregistré (pointage manqué le matin)
        else if ($departureTime) {
            $actualDeparture = Carbon::createFromFormat('H:i:s', $departureTime);
            
            // Si le départ est après midi, considérer comme retard
            if ($actualDeparture->hour >= 12) {
                $lateMinutes = 240; // 4 heures par défaut (matinée manquée)
                $isLate = true;
                $lateReason = 'Pointage matin manqué';
            }
        }
        // RÈGLE 3: Si aucun pointage mais jour ouvré
        else {
            $now = Carbon::now();
            $currentTime = Carbon::createFromTime($now->hour, $now->minute, 0);
            
            // Si nous sommes après l'heure de début + marge
            $marginMinutes = 30; // Marge avant de considérer absent
            $cutoffTime = $scheduleStart->copy()->addMinutes($marginMinutes);
            
            if ($currentTime > $cutoffTime) {
                $lateMinutes = $currentTime->diffInMinutes($scheduleStart);
                $isLate = true;
                $lateReason = 'Absence avec retard';
            }
        }
        
        // RÈGLE 4: Vérifier les permissions pour la matinée
        if ($isLate) {
            $hasMorningPermission = $this->hasMorningPermission($employee->id, $dateStr);
            if ($hasMorningPermission) {
                $isLate = false;
                $lateReason = 'Permission matin';
                $lateMinutes = 0;
            }
        }
        
        return [
            'late_minutes' => $lateMinutes,
            'is_late' => $isLate,
            'late_reason' => $lateReason
        ];
    }

    /**
     * Vérifier si l'employé a une permission pour la matinée
     */
    private function hasMorningPermission($employeeId, $date)
    {
        // Implémentez selon votre logique de permissions
        return false;
    }

    /**
     * Debug: Voir ce que retourne getData()
     */
    public function debugGetData(Request $request)
    {
        try {
            // Simuler les paramètres
            $request->merge([
                'start_date' => '2025-12-01',
                'end_date' => '2025-12-02',
                'emp_code' => 'all',
                'draw' => 1,
                'start' => 0,
                'length' => 25
            ]);
            
            // Appeler la méthode getData
            $response = $this->getData($request);
            $data = json_decode($response->getContent(), true);
            
            return response()->json([
                'success' => true,
                'controller_response' => $data,
                'message' => 'Test réussi'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Exporter le rapport en PDF
     */
    public function exportPdf(Request $request)
    {
        // Valider les paramètres
        $validator = \Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'emp_code' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Récupérer les paramètres
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $emp_code = $request->input('emp_code', 'all');
        
        Log::info("Export PDF - Début: {$start_date}, Fin: {$end_date}, Employé: {$emp_code}");
        
        // Récupérer le token d'authentification
        $accessConfig = DB::table('access_configs')->first();
        
        if (!$accessConfig || empty($accessConfig->general_token)) {
            return redirect()->back()->with('error', 'Token d\'accès non configuré');
        }
        
        $token = $accessConfig->general_token;
        
        // Récupérer tous les devices
        $devices = Device::all();
        
        if ($devices->isEmpty()) {
            return redirect()->back()->with('error', 'Aucun device trouvé.');
        }
        
        // Récupérer les employés selon le filtre
        $employeesQuery = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '');
        
        if ($emp_code && $emp_code !== 'all') {
            $employeesQuery->where('emp_code', $emp_code);
        }
        
        $employees = $employeesQuery->get();
        
        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'Aucun employé trouvé pour les critères sélectionnés.');
        }
        
        // Récupérer les permissions approuvées
        $permissions = EmployeePermission::where('status', 'approved')
            ->whereBetween('date', [$start_date, $end_date])
            ->get()
            ->groupBy('employee_id');
        
        // Récupérer les congés
        $leaves = Leave::whereBetween('start_date', [$start_date, $end_date])
            ->get();
        
        // Analyser chaque jour de la période
        $reportData = [];
        $currentDate = Carbon::parse($start_date);
        $endDateObj = Carbon::parse($end_date);
        
        // Limiter la période pour éviter les timeouts
        $daysDiff = $currentDate->diffInDays($endDateObj);
        if ($daysDiff > 31) {
            return redirect()->back()->with('error', 'La période ne doit pas dépasser 31 jours pour l\'export PDF.');
        }
        
        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Collecter toutes les transactions pour cette date
            $allTransactions = collect();
            
            foreach ($devices as $device) {
                $deviceTransactions = $this->getDeviceTransactionsForDate(
                    $device,
                    $currentDate,
                    $token,
                    $employees
                );
                $allTransactions = $allTransactions->merge($deviceTransactions);
            }
            
            // Grouper par employé
            $groupedTransactions = $this->groupTransactionsByEmployee($allTransactions, $employees);
            
            // Analyser chaque employé pour cette date
            foreach ($employees as $employee) {
                $analysis = $this->analyzeEmployeeDay(
                    $employee,
                    $dateStr,
                    $groupedTransactions[$employee->emp_code] ?? null,
                    $permissions,
                    $leaves
                );
                
                if ($analysis) {
                    $reportData[] = $analysis;
                }
            }
            
            $currentDate->addDay();
        }
        
        // Grouper les données par employé pour l'affichage
        $groupedData = [];
        foreach ($reportData as $record) {
            $empCode = $record['employee_code'];
            
            if (!isset($groupedData[$empCode])) {
                $employee = Employee::where('emp_code', $empCode)->first();
                $groupedData[$empCode] = [
                    'employee' => $employee,
                    'records' => []
                ];
            }
            
            $groupedData[$empCode]['records'][] = $record;
        }
        
        // Trier les employés par code
        ksort($groupedData);
        
        // Trier les enregistrements de chaque employé par date
        foreach ($groupedData as &$employeeData) {
            usort($employeeData['records'], function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }
        
        $client = collect(Setting::getGroup('company'));
        
        // Données pour le PDF
        $data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'export_date' => Carbon::now(),
            'client' => $client,
            'filters' => [
                'emp_code' => $emp_code === 'all' ? 'Tous les employés' : $emp_code
            ],
            'total_employees' => $employees->count(),
            'total_records' => count($reportData),
            'grouped_data' => $groupedData,
        ];
        
        // Générer le PDF
        $pdf = Pdf::loadView('reports.exports.pdf', $data);
        
        // Nom du fichier
        $filename = 'rapport_absences_retards_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
        
        // Options du PDF
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download($filename);
    }
    
    /**
     * Afficher le PDF dans le navigateur (prévisualisation)
     */
    public function previewPdf(Request $request)
    {
        try {
            $client = collect(Setting::getGroup('company'));
            
            // Valider les paramètres
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }
            
            // Récupérer les paramètres
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $emp_code = $request->input('emp_code', 'all');
            
            // Générer des données de test pour la prévisualisation
            $data = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'export_date' => Carbon::now(),
                'client' => $client,
                'filters' => [
                    'emp_code' => $emp_code === 'all' ? 'Tous les employés' : $emp_code
                ],
                'total_employees' => 3,
                'total_records' => 15,
                'grouped_data' => $this->generateSampleData(),
            ];
            
            $pdf = Pdf::loadView('reports.pdf.export', $data);
            
            return $pdf->stream('preview_rapport.pdf');
            
        } catch (\Exception $e) {
            Log::error('Erreur prévisualisation PDF: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Générer des données d'exemple pour la prévisualisation
     */
    private function generateSampleData()
    {
        $sampleData = [];
        
        // Exemple d'employés
        $sampleEmployees = [
            (object)[
                'emp_code' => 'EMP001',
                'first_name' => 'Jean',
                'last_name' => 'Dupont'
            ],
            (object)[
                'emp_code' => 'EMP002',
                'first_name' => 'Marie',
                'last_name' => 'Martin'
            ],
            (object)[
                'emp_code' => 'EMP003',
                'first_name' => 'Pierre',
                'last_name' => 'Durand'
            ]
        ];
        
        // Dates d'exemple
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-05');
        
        foreach ($sampleEmployees as $employee) {
            $empCode = $employee->emp_code;
            $records = [];
            
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->dayOfWeekIso;
                
                // Générer des données aléatoires mais réalistes
                $isWeekend = in_array($dayOfWeek, [6, 7]);
                
                $record = [
                    'date' => $dateStr,
                    'employee_code' => $empCode,
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'schedule_start' => $isWeekend ? '-' : '08:00',
                    'schedule_end' => $isWeekend ? '-' : '17:00',
                    'actual_arrival' => $isWeekend ? null : ($dayOfWeek === 2 ? '08:45' : '08:15'),
                    'actual_departure' => $isWeekend ? null : ($dayOfWeek === 3 ? '16:30' : '17:10'),
                    'late_minutes' => $isWeekend ? null : ($dayOfWeek === 2 ? 45 : 0),
                    'early_leave_minutes' => $isWeekend ? null : ($dayOfWeek === 3 ? 30 : 0),
                    'work_minutes' => $isWeekend ? 0 : 540,
                    'status' => $isWeekend ? 'weekend' : ($dayOfWeek === 4 ? 'leave' : 'present'),
                    'is_weekend' => $isWeekend,
                    'is_holiday' => false,
                    'is_on_leave' => $dayOfWeek === 4,
                    'has_permission' => false,
                    'all_punches' => $isWeekend ? [] : ['08:15:00', '12:00:00', '13:00:00', '17:10:00'],
                    'schedule_type' => $isWeekend ? 'Non planifié' : 'Fixe',
                    'is_late' => $dayOfWeek === 2,
                    'has_start_time' => !$isWeekend,
                    'has_end_time' => !$isWeekend,
                    'has_any_attendance' => !$isWeekend,
                    'has_both_times' => !$isWeekend && $dayOfWeek !== 4
                ];
                
                $records[] = $record;
                $currentDate->addDay();
            }
            
            $sampleData[$empCode] = [
                'employee' => $employee,
                'records' => $records
            ];
        }
        
        return $sampleData;
    }
}