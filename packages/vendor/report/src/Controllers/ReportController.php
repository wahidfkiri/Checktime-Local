<?php

namespace Vendor\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Device;
use App\Models\EmployeeSchedule;
use App\Models\Leave;
use App\Models\Mission;
use App\Models\EmployeePermission;
use App\Models\DailyAttendance;
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
        $client = \App\Models\Setting::company();
        
        // Récupérer les employés pour les filtres
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
        
        return view('report::abscences.index', compact('employees', 'client'));
    }
    
    /**
     * Récupérer les données depuis la table daily_attendances pour DataTables
     */
    public function getData(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
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
            
            // Récupérer TOUTES les données (sans pagination)
            $allData = $this->getFullReportData(1, $startDate, $endDate, $empCode);
            
            // Pagination manuelle pour DataTables
            $totalRecords = count($allData);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);

            // RÃ©sumÃ© global : calculÃ© sur tout le dataset filtrÃ©
            $summary = [
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'leave' => 0,
                'permission' => 0,
                'total_days' => 0,
            ];

            if ($totalRecords > 0) {
                $summary['total_days'] = count(array_unique(array_column($allData, 'date')));

                foreach ($allData as $row) {
                    $status = $row['status'] ?? null;

                    if ($status === 'present') {
                        if (($row['late_minutes'] ?? 0) > 0 || ($row['is_late'] ?? false)) {
                            $summary['late']++;
                        } else {
                            $summary['present']++;
                        }
                        continue;
                    }

                    if ($status === 'absent') {
                        $summary['absent']++;
                    } elseif ($status === 'leave') {
                        $summary['leave']++;
                    } elseif ($status === 'permission') {
                        $summary['permission']++;
                    }
                }
            }
            
            $pageData = array_slice($allData, $start, $length);
            
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $pageData,
                'summary' => $summary
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
     * Récupérer TOUTES les données du rapport (sans pagination)
     * Cette méthode est utilisée pour l'export PDF
     */
    private function getFullReportData($clientId, $startDate, $endDate, $empCode = 'all')
    {
        // Limiter la période
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        $daysDiff = $startDateObj->diffInDays($endDateObj);
        
        if ($daysDiff > 31) {
            throw new \Exception('La période ne doit pas dépasser 31 jours.');
        }
        
        // Récupérer les permissions approuvées pour la période
        $permissions = EmployeePermission::where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');
        
        // Récupérer les congés pour la période
        $leaves = Leave::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        // Récupérer les missions pour la période
        $missions = \App\Models\Mission::where('start_date', '<=', $endDate . ' 23:59:59')
              ->where('end_date', '>=', $startDate . ' 00:00:00')
             ->get();
        
        // Récupérer les employés concernés
        $employeesQuery = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '');
        
        if ($empCode && $empCode !== 'all') {
            $employeesQuery->where('emp_code', $empCode);
        }
        
        $employees = $employeesQuery->get()->keyBy('emp_code');
        
        if ($employees->isEmpty()) {
            return [];
        }
        
        // Récupérer TOUTES les données de daily_attendances (sans limit)
        $query = DailyAttendance::whereBetween('attendance_date', [$startDate, $endDate]);
        
        if ($empCode && $empCode !== 'all') {
            $query->where('emp_code', $empCode);
        }
        
        $attendances = $query->get()->groupBy(function($item) {
            return $item->emp_code . '_' . $item->attendance_date->format('Y-m-d');
        });
        
        Log::info("Nombre d'enregistrements trouvés pour export: " . $attendances->count());
        
        // Générer les données pour chaque jour et chaque employé
        $reportData = [];
        $currentDate = Carbon::parse($startDate);
        
        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeekIso;
            $isWeekend = in_array($dayOfWeek, [6, 7]);
            
            foreach ($employees as $empCode => $employee) {
                $key = $empCode . '_' . $dateStr;
                $attendance = $attendances->get($key);
                
                // Récupérer le planning pour cette date
                $schedule = $this->getEmployeeScheduleForDateNew($employee, $dateStr);
                
                // Vérifier les congés et permissions
                $isOnLeave = $this->isEmployeeOnLeave($employee->id, $dateStr, $leaves);
                $hasPermission = $this->hasPermission($employee->id, $dateStr, $permissions);
                $isOnMission = $this->isEmployeeOnMission($employee->id, $dateStr, $missions);
                
                // Calculer les données de présence
                $result = $this->calculateAttendanceData(
                    $attendance ? $attendance->first() : null,
                    $schedule,
                    $employee,
                    $dateStr,
                    $isWeekend,
                    $isOnLeave,
                    $hasPermission,
                    $isOnMission 
                );
                
                if ($result) {
                    $reportData[] = $result;
                }
            }
            
            $currentDate->addDay();
        }
        
        // Trier par date décroissante, puis par employé
        usort($reportData, function($a, $b) {
            if ($a['date'] == $b['date']) {
                return strcmp($a['employee_code'], $b['employee_code']);
            }
            return strcmp($b['date'], $a['date']);
        });
        
        return $reportData;
    }

    private function isEmployeeOnMission($employeeId, $date, $missions)
{
    foreach ($missions as $mission) {
        if ($mission->employee_id == $employeeId) {
            $missionStart = Carbon::parse($mission->start_date)->startOfDay();
            $missionEnd   = Carbon::parse($mission->end_date)->endOfDay();
            $checkDate    = Carbon::parse($date);

            if ($checkDate->between($missionStart, $missionEnd)) {
                return true;
            }
        }
    }
    return false;
}
    
    /**
     * Calculer les données de présence pour un employé un jour donné
     */
private function calculateAttendanceData($attendance, $schedule, $employee, $dateStr, $isWeekend, $isOnLeave, $hasPermission, $isOnMission = false)    {
        // Initialiser les valeurs
        $arrivalTime = null;
        $departureTime = null;
        $allPunches = [];
        $workMinutes = 0;
        $lateMinutes = null;
        $earlyLeaveMinutes = null;
        $isLate = false;
        $isEarlyLeave = false;
        
        // Si présence dans daily_attendances
        if ($attendance) {
            $arrivalTime = $attendance->check_in ? $attendance->check_in->format('H:i:s') : null;
            $departureTime = $attendance->check_out ? $attendance->check_out->format('H:i:s') : null;
            $allPunches = $attendance->raw_data ? ($attendance->raw_data['all_punches'] ?? []) : [];
            
            // Calculer les minutes travaillées
            if ($arrivalTime && $departureTime) {
                try {
                    $arrival = Carbon::createFromFormat('H:i:s', $arrivalTime);
                    $departure = Carbon::createFromFormat('H:i:s', $departureTime);
                    
                    if ($departure > $arrival) {
                        $workMinutes = $departure->diffInMinutes($arrival);
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur calcul minutes travaillées: " . $e->getMessage());
                }
            }
        }
        
        // Déterminer le statut
        $status = $this->determineStatus(
            $schedule,
            $isWeekend,
            $isOnLeave,
            $hasPermission,
            $arrivalTime,
            $departureTime
        );
        
        // Calculer le retard et le départ anticipé seulement si présent
        if ($status === 'present' && $schedule && $schedule['is_working_day']) {
            // Calcul du retard
            if ($schedule['start_time'] && $arrivalTime) {
                try {
                    $scheduleStart = Carbon::createFromFormat('H:i:s', $schedule['start_time']);
                    $actualArrival = Carbon::createFromFormat('H:i:s', $arrivalTime);
                    
                    if ($actualArrival > $scheduleStart) {
                        $lateMinutes = $actualArrival->diffInMinutes($scheduleStart);
                        $isLate = true;
                    } else {
                        $lateMinutes = 0;
                        $isLate = false;
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur calcul retard: " . $e->getMessage());
                }
            }
            
            // Calcul du départ anticipé
            if ($schedule['end_time'] && $departureTime) {
                try {
                    $scheduleEnd = Carbon::createFromFormat('H:i:s', $schedule['end_time']);
                    $actualDeparture = Carbon::createFromFormat('H:i:s', $departureTime);
                    
                    if ($actualDeparture < $scheduleEnd) {
                        $earlyLeaveMinutes = $scheduleEnd->diffInMinutes($actualDeparture);
                        $isEarlyLeave = true;
                    } else {
                        $earlyLeaveMinutes = 0;
                        $isEarlyLeave = false;
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur calcul départ anticipé: " . $e->getMessage());
                }
            }
        }

        // Calcul des heures supplémentaires
$overtimeMinutes = null;
if ($status === 'present' && $schedule && $schedule['is_working_day'] && $schedule['end_time'] && $departureTime) {
    try {
        $scheduleEnd      = Carbon::createFromFormat('H:i:s', $schedule['end_time']);
        $actualDeparture  = Carbon::createFromFormat('H:i:s', $departureTime);

        if ($actualDeparture > $scheduleEnd) {
            $overtimeMinutes = $scheduleEnd->diffInMinutes($actualDeparture);
        }
    } catch (\Exception $e) {
        Log::error("Erreur calcul heures supplémentaires: " . $e->getMessage());
    }
}
        
        // Formater les données pour le retour
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
            'work_hours' => $workMinutes > 0 ? round($workMinutes / 60, 2) : 0,
            'status' => $status,
            'is_weekend' => $isWeekend,
            'is_holiday' => false,
            'is_on_leave' => $isOnLeave,
            'has_permission' => $hasPermission,
            'all_punches' => $allPunches,
            'schedule_type' => $schedule ? $schedule['schedule_type'] : 'Non planifié',
            'is_late' => $isLate,
            'is_early_leave' => $isEarlyLeave,
            'schedule_data' => $schedule ? json_encode($schedule) : null,
            'overtime_minutes' => $overtimeMinutes,
            'is_on_mission'    => $isOnMission,
        ];
    }
    
    /**
     * Déterminer le statut de présence
     */
private function determineStatus($schedule, $isWeekend, $isOnLeave, $hasPermission, $arrivalTime, $departureTime, $isOnMission = false)
    {
        // Priorité 1: Congé
        if ($isOnLeave) {
            return 'leave';
        }
        
        // Priorité 2: Permission
        if ($hasPermission) {
            return 'permission';
        }
        
        // Priorité 3: Weekend
        if ($isWeekend) {
            return 'weekend';
        }
        // Priorité 3b : Mission
if ($isOnMission) {
    return 'mission';
}
        // RÈGLE PRINCIPALE: Présent si au moins un pointage
        if ($arrivalTime !== null || $departureTime !== null) {
            return 'present';
        }
        
        // Sinon absent
        return 'absent';
    }
    
    /**
     * Récupérer le planning d'un employé pour une date spécifique
     */
    private function getEmployeeScheduleForDateNew($employee, $dateStr)
    {
        if (!$employee) {
            return null;
        }
        
        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;
        
        // 1. Chercher d'abord un planning spécifique à la date exacte
        $specificSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $dateStr)
            ->first();
        
        if ($specificSchedule) {
            return $this->formatScheduleData($specificSchedule);
        }
        
        // 2. Chercher un planning dans la plage de dates
        $rangeSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where(function($query) use ($dateStr) {
                $query->where('start_date', '<=', $dateStr)
                      ->where('end_date', '>=', $dateStr);
            })
            ->first();
        
        if ($rangeSchedule) {
            return $this->formatScheduleData($rangeSchedule);
        }
        
        // 3. Pour les types FIXE: chercher par jour de semaine
        $fixedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'fixe')
            ->where('day_of_week', $dayOfWeek)
            ->first();
        
        if ($fixedSchedule) {
            return $this->formatScheduleData($fixedSchedule);
        }
        
        // 4. Pour les types ROTATION
        $rotationSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'rotation')
            ->first();
        
        if ($rotationSchedule && $rotationSchedule->start_date && $rotationSchedule->end_date) {
            $scheduleStart = Carbon::parse($rotationSchedule->start_date);
            $scheduleEnd = Carbon::parse($rotationSchedule->end_date);
            $currentDate = Carbon::parse($dateStr);
            
            if ($currentDate->between($scheduleStart, $scheduleEnd)) {
                $daysFromStart = $scheduleStart->diffInDays($currentDate);
                $workDaysCount = $rotationSchedule->work_days_count ?? 1;
                $restDaysCount = $rotationSchedule->rest_days_count ?? 0;
                $cycleLength = $workDaysCount + $restDaysCount;
                
                $positionInCycle = $daysFromStart % $cycleLength;
                
                if ($positionInCycle < $workDaysCount) {
                    return $this->formatScheduleData($rotationSchedule);
                } else {
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
        
        // 5. Pour les types PLANIFIE
        $plannedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'planifie')
            ->first();
        
        if ($plannedSchedule) {
            return $this->formatScheduleData($plannedSchedule);
        }
        
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
     * Exporter le rapport en PDF (TOUTES les données)
     */
    public function exportPdf(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
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
            
            // Récupérer TOUTES les données via la méthode dédiée
            $reportData = $this->getFullReportData(1, $start_date, $end_date, $emp_code);
            
            if (empty($reportData)) {
                return redirect()->back()->with('error', 'Aucune donnée trouvée pour la période sélectionnée.');
            }
            
            // Grouper les données par employé
            $groupedData = [];
            foreach ($reportData as $record) {
                $empCode = $record['employee_code'];
                
                if (!isset($groupedData[$empCode])) {
                    $employee = Employee::where('emp_code', $empCode)
                        ->first();
                    $groupedData[$empCode] = [
                        'employee' => $employee,
                        'records' => []
                    ];
                }
                
                $groupedData[$empCode]['records'][] = $record;
            }
            
            // Trier les employés par code
            ksort($groupedData);
            
            // Trier les enregistrements par date (décroissante)
            foreach ($groupedData as &$employeeData) {
                usort($employeeData['records'], function($a, $b) {
                    return strcmp($b['date'], $a['date']);
                });
            }
            
            // Calculer les statistiques globales
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLate = 0;
            $totalWorkHours = 0;
            $totalLeave = 0;
            $totalPermission = 0;
            $totalWeekend = 0;
            
            foreach ($reportData as $record) {
                switch ($record['status']) {
                    case 'present':
                        $totalPresent++;
                        if ($record['is_late']) {
                            $totalLate++;
                        }
                        $totalWorkHours += $record['work_hours'] ?? 0;
                        break;
                    case 'absent':
                        $totalAbsent++;
                        break;
                    case 'leave':
                        $totalLeave++;
                        break;
                    case 'permission':
                        $totalPermission++;
                        break;
                    case 'weekend':
                        $totalWeekend++;
                        break;
                }
            }
            
            // Données pour le PDF
            $data = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'export_date' => Carbon::now(),
                'client' => $client,
                'filters' => [
                    'emp_code' => $emp_code === 'all' ? 'Tous les employés' : $emp_code
                ],
                'total_employees' => count($groupedData),
                'total_records' => count($reportData),
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'total_late' => $totalLate,
                'total_leave' => $totalLeave,
                'total_permission' => $totalPermission,
                'total_weekend' => $totalWeekend,
                'total_work_hours' => round($totalWorkHours, 2),
                'grouped_data' => $groupedData,
            ];
            
            // Générer le PDF
            $pdf = Pdf::loadView('report::abscences.exports.pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_absences_retards_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher le PDF dans le navigateur (prévisualisation)
     */
    public function previewPdf(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
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
            
            // Récupérer TOUTES les données
            $reportData = $this->getFullReportData(1, $start_date, $end_date, $emp_code);
            
            if (empty($reportData)) {
                return response()->json(['error' => 'Aucune donnée trouvée'], 404);
            }
            
            // Grouper les données
            $groupedData = [];
            foreach ($reportData as $record) {
                $empCode = $record['employee_code'];
                if (!isset($groupedData[$empCode])) {
                    $employee = Employee::where('emp_code', $empCode)
                        ->first();
                    $groupedData[$empCode] = [
                        'employee' => $employee,
                        'records' => []
                    ];
                }
                $groupedData[$empCode]['records'][] = $record;
            }
            
            $data = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'export_date' => Carbon::now(),
                'client' => $client,
                'filters' => [
                    'emp_code' => $emp_code === 'all' ? 'Tous les employés' : $emp_code
                ],
                'total_employees' => count($groupedData),
                'total_records' => count($reportData),
                'grouped_data' => $groupedData,
            ];
            
            $pdf = Pdf::loadView('report::abscences.exports.pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            return $pdf->stream('apercu_rapport.pdf');
            
        } catch (\Exception $e) {
            Log::error('Erreur prévisualisation PDF: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Debug: Voir ce que retourne getData()
     */
    public function debugGetData(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
            // Simuler les paramètres
            $request->merge([
                'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d'),
                'emp_code' => 'all',
                'draw' => 1,
                'start' => 0,
                'length' => 25
            ]);
            
            // Appeler la méthode getData
            $response = $this->getData($request);
            $data = json_decode($response->getContent(), true);
            
            // Compter le nombre total de données réelles
            $totalData = count($this->getFullReportData(1, 
                Carbon::now()->subDays(7)->format('Y-m-d'), 
                Carbon::now()->format('Y-m-d'), 
                'all'));
            
            return response()->json([
                'success' => true,
                'controller_response' => $data,
                'total_records_available' => $totalData,
                'records_returned_in_pagination' => count($data['data'] ?? []),
                'message' => 'Test réussi',
                'daily_attendances_count' => DailyAttendance::count(),
                'employees_count' => Employee::count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
