<?php

namespace Vendor\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Department;
use App\Models\EmployeeSchedule;
use App\Models\Leave;
use App\Models\EmployeePermission;
use App\Models\DailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomReportController extends Controller
{
    /**
     * Afficher la page du rapport personnalisé
     */
    public function presencePonctualite(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return redirect()->route('home')->with('error', 'Client non trouvé.');
        }
        
        // Récupérer les employés pour les filtres
        $employees = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(function($employee) {
                return [
                    'emp_code' => $employee->emp_code,
                    'full_name' => $employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : '')
                ];
            });
        
        // Récupérer les départements
        $departments = Department::where('client_id', $client->id)
            ->orderBy('name')
            ->get();
        
        return view('report::ponctualites.index', compact('employees', 'departments', 'client'));
    }
    
    /**
     * Générer les données pour le rapport personnalisé
     */
    public function generateCustomReport(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json(['error' => 'Client non trouvé'], 404);
            }
            
            // Valider les paramètres
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'department_id' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }
            
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code', 'all');
            $departmentId = $request->input('department_id', 'all');
            
            // Récupérer les données du rapport
            $reportData = $this->getPresencePonctualiteData($client, $startDate, $endDate, $empCode, $departmentId);
            
            return response()->json([
                'success' => true,
                'data' => $reportData,
                'total_employees' => count($reportData),
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur génération rapport personnalisé: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Récupérer les données de présence et ponctualité
     */
    private function getPresencePonctualiteData($client, $startDate, $endDate, $empCode = 'all', $departmentId = 'all')
    {
        // Récupérer les employés selon les filtres
        $employeesQuery = Employee::where('client_id', $client->id)
            ->whereNotNull('emp_code')
            ->where('emp_code', '!=', '');
        
        if ($empCode && $empCode !== 'all') {
            $employeesQuery->where('emp_code', $empCode);
        }
        
        if ($departmentId && $departmentId !== 'all') {
            $employeesQuery->where('department_id', $departmentId);
        }
        
        $employees = $employeesQuery->orderBy('emp_code')->get();
        
        if ($employees->isEmpty()) {
            return [];
        }
        
        // Récupérer les permissions approuvées pour la période
        $permissions = EmployeePermission::where('client_id', $client->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');
        
        // Récupérer les congés pour la période
        $leaves = Leave::where('client_id', $client->id)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();
        
        $reportData = [];
        $orderNumber = 1;
        
        // Calculer le nombre total de jours ouvrés dans la période
        $totalWorkingDays = $this->countWorkingDays($startDate, $endDate);
        
        foreach ($employees as $employee) {
            // Analyser la période pour l'employé
            $analysis = $this->analyzeEmployeePeriod(
                $employee,
                $startDate,
                $endDate,
                $totalWorkingDays,
                $permissions,
                $leaves
            );
            
            if ($analysis) {
                // Récupérer le département de l'employé
                $department = Department::find($employee->department_id);
                
                $reportData[] = [
                    'order_number' => $orderNumber++,
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->emp_code,
                    'employee_name' => $employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : ''),
                    'department_id' => $employee->department_id,
                    'department_name' => $department ? $department->name : 'Non défini',
                    'presence_data' => $analysis['presence'],
                    'ponctualite_data' => $analysis['ponctualite'],
                    'total_days' => $analysis['total_days'],
                    'observation' => $analysis['observation']
                ];
            }
        }
        
        return $reportData;
    }
    
    /**
     * Compter le nombre de jours ouvrés (lundi-vendredi) dans une période
     */
    private function countWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $count = 0;
        
        while ($start <= $end) {
            $dayOfWeek = $start->dayOfWeekIso;
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $count++;
            }
            $start->addDay();
        }
        
        return $count;
    }
    
    /**
     * Analyser la période pour un employé
     */
    private function analyzeEmployeePeriod($employee, $startDate, $endDate, $totalWorkingDays, $permissions, $leaves)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $presenceData = [
            'present' => 0,
            'absent' => 0,
            'present_days' => [], // Pour stocker les jours de présence
            'absent_days' => []   // Pour stocker les jours d'absence
        ];
        
        $ponctualiteData = [
            'on_time' => 0,
            'late' => 0,
            'late_minutes_total' => 0,
            'on_time_days' => [], // Pour stocker les jours à l'heure
            'late_days' => []     // Pour stocker les jours en retard
        ];
        
        $observations = [];
        
        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeekIso;
            
            // Vérifier si c'est un jour de travail (lundi-vendredi)
            $isWorkingDay = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
            
            if ($isWorkingDay) {
                // Récupérer les données d'attendance pour ce jour
                $attendance = DailyAttendance::where('client_id', $employee->client_id)
                    ->where('employee_id', $employee->id)
                    ->where('attendance_date', $dateStr)
                    ->first();
                
                // Vérifier les permissions
                $hasPermission = $this->hasPermission($employee->id, $dateStr, $permissions);
                
                // Vérifier les congés
                $isOnLeave = $this->isEmployeeOnLeave($employee->id, $dateStr, $leaves);
                
                // Récupérer le planning pour calculer les retards
                $schedule = $this->getEmployeeScheduleForDate($employee, $dateStr);
                
                if ($isOnLeave) {
                    // Congé : ne compte ni présent ni absent
                    $observations[] = 'Congé le ' . $currentDate->format('d/m');
                } else if ($hasPermission) {
                    // Permission : ne compte ni présent ni absent
                    $observations[] = 'Permission le ' . $currentDate->format('d/m');
                } else if ($attendance) {
                    // Présent
                    $presenceData['present']++;
                    $presenceData['present_days'][] = $currentDate->format('d/m');
                    
                    // Analyser la ponctualité
                    if ($attendance->is_late && $attendance->late_minutes > 0) {
                        $ponctualiteData['late']++;
                        $ponctualiteData['late_minutes_total'] += $attendance->late_minutes;
                        $ponctualiteData['late_days'][] = $currentDate->format('d/m') . ' (' . $attendance->late_minutes . ' min)';
                        $observations[] = 'Retard de ' . $attendance->late_minutes . ' min le ' . $currentDate->format('d/m');
                    } else {
                        $ponctualiteData['on_time']++;
                        $ponctualiteData['on_time_days'][] = $currentDate->format('d/m');
                    }
                } else {
                    // Absent sans justification
                    $presenceData['absent']++;
                    $presenceData['absent_days'][] = $currentDate->format('d/m');
                    $observations[] = 'Absent le ' . $currentDate->format('d/m');
                }
            }
            
            $currentDate->addDay();
        }
        
        // Calculer les taux
        $presenceRate = $totalWorkingDays > 0 ? round(($presenceData['present'] / $totalWorkingDays) * 100, 1) : 0;
        $ponctualiteRate = $presenceData['present'] > 0 ? round(($ponctualiteData['on_time'] / $presenceData['present']) * 100, 1) : 0;
        
        return [
            'presence' => [
                'present' => $presenceData['present'],
                'absent' => $presenceData['absent'],
                'rate' => $presenceRate,
                'present_days_display' => $presenceData['present'] . '/' . $totalWorkingDays,
                'absent_days_display' => $presenceData['absent'] . '/' . $totalWorkingDays,
                'present_days_list' => implode(', ', array_slice($presenceData['present_days'], 0, 5)) . 
                    (count($presenceData['present_days']) > 5 ? '...' : ''),
                'absent_days_list' => implode(', ', array_slice($presenceData['absent_days'], 0, 5)) . 
                    (count($presenceData['absent_days']) > 5 ? '...' : '')
            ],
            'ponctualite' => [
                'on_time' => $ponctualiteData['on_time'],
                'late' => $ponctualiteData['late'],
                'rate' => $ponctualiteRate,
                'avg_late_minutes' => $ponctualiteData['late'] > 0 ? 
                    round($ponctualiteData['late_minutes_total'] / $ponctualiteData['late'], 1) : 0,
                'late_days_list' => implode(', ', array_slice($ponctualiteData['late_days'], 0, 3)) . 
                    (count($ponctualiteData['late_days']) > 3 ? '...' : '')
            ],
            'total_days' => $totalWorkingDays,
            'observation' => implode(', ', array_slice($observations, 0, 3)) . 
                (count($observations) > 3 ? '... (+' . (count($observations) - 3) . ' autres)' : '')
        ];
    }
    
    /**
     * Récupérer le planning d'un employé pour une date
     */
    private function getEmployeeScheduleForDate($employee, $dateStr)
    {
        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;
        
        // 1. Planning spécifique à la date
        $specificSchedule = EmployeeSchedule::where('client_id', $employee->client_id)
            ->where('employee_id', $employee->id)
            ->where('schedule_date', $dateStr)
            ->first();
        
        if ($specificSchedule) {
            return $specificSchedule;
        }
        
        // 2. Planning dans la plage de dates
        $rangeSchedule = EmployeeSchedule::where('client_id', $employee->client_id)
            ->where('employee_id', $employee->id)
            ->where(function($query) use ($dateStr) {
                $query->where('start_date', '<=', $dateStr)
                      ->where('end_date', '>=', $dateStr);
            })
            ->first();
        
        if ($rangeSchedule) {
            return $rangeSchedule;
        }
        
        // 3. Planning fixe par jour de semaine
        $fixedSchedule = EmployeeSchedule::where('client_id', $employee->client_id)
            ->where('employee_id', $employee->id)
            ->where('schedule_type', 'fixe')
            ->where('day_of_week', $dayOfWeek)
            ->first();
        
        if ($fixedSchedule) {
            return $fixedSchedule;
        }
        
        // 4. Planning planifié par défaut
        $plannedSchedule = EmployeeSchedule::where('client_id', $employee->client_id)
            ->where('employee_id', $employee->id)
            ->where('schedule_type', 'planifie')
            ->first();
        
        if ($plannedSchedule) {
            return $plannedSchedule;
        }
        
        return null;
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
     * Exporter le rapport personnalisé en PDF
     */
    public function exportCustomPdf(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return redirect()->back()->with('error', 'Client non trouvé.');
            }
            
            // Valider les paramètres
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'emp_code' => 'nullable|string',
                'department_id' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $empCode = $request->input('emp_code', 'all');
            $departmentId = $request->input('department_id', 'all');
            
            // Récupérer les données du rapport
            $reportData = $this->getPresencePonctualiteData($client, $startDate, $endDate, $empCode, $departmentId);
            
            // Calculer les totaux
            $totals = $this->calculateTotals($reportData, $startDate, $endDate);
            
            // Préparer les données pour la vue PDF
            $data = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'export_date' => Carbon::now(),
                'client' => $client,
                'filters' => [
                    'emp_code' => $empCode === 'all' ? 'Tous les employés' : $empCode,
                    'department' => $departmentId === 'all' ? 'Tous les départements' : 'Département spécifique'
                ],
                'report_data' => $reportData,
                'totals' => $totals,
                'total_employees' => count($reportData),
                'period_days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                'working_days' => $this->countWorkingDays($startDate, $endDate)
            ];
            
            // Générer le PDF
            $pdf = Pdf::loadView('report::pontualites.exports.pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_presence_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF personnalisé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Exporter le rapport par département en PDF
     */
    public function exportDepartmentPdf(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return redirect()->back()->with('error', 'Client non trouvé.');
            }
            
            // Valider les paramètres
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department_id' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $departmentId = $request->input('department_id', 'all');
            
            // Récupérer les données par département
            $departmentData = $this->getDepartmentData($client, $startDate, $endDate, $departmentId);
            
            // Calculer les totaux
            $totals = $this->calculateDepartmentTotals($departmentData);
            
            // Calculer le nombre de jours ouvrés
            $workingDays = $this->countWorkingDays($startDate, $endDate);
            
            // Préparer les données pour la vue PDF
            $data = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'export_date' => Carbon::now(),
                'client' => $client,
                'filters' => [
                    'department_id' => $departmentId === 'all' ? 'Tous les départements' : $departmentId
                ],
                'department_data' => $departmentData,
                'totals' => $totals,
                'total_departments' => count($departmentData),
                'period_days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                'working_days' => $workingDays
            ];
            
            // Générer le PDF
            $pdf = Pdf::loadView('report::pontualites.departements.exports.pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_departements_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF département: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Récupérer les données par département
     */
    private function getDepartmentData($client, $startDate, $endDate, $departmentId)
    {
        // Récupérer les départements
        $departmentsQuery = Department::where('client_id', $client->id);
        
        if ($departmentId !== 'all') {
            $departmentsQuery->where('id', $departmentId);
        }
        
        $departments = $departmentsQuery->get();
        
        $departmentData = [];
        $orderNumber = 1;
        
        // Calculer le nombre de jours ouvrés
        $workingDays = $this->countWorkingDays($startDate, $endDate);
        
        foreach ($departments as $department) {
            // Récupérer les données pour ce département
            $data = $this->getPresencePonctualiteData($client, $startDate, $endDate, 'all', $department->id);
            
            $totalEmployees = count($data);
            
            if ($totalEmployees > 0) {
                $totalPresencePresent = array_sum(array_column(array_column($data, 'presence_data'), 'present'));
                $totalPresenceAbsent = array_sum(array_column(array_column($data, 'presence_data'), 'absent'));
                $totalPonctualiteOnTime = array_sum(array_column(array_column($data, 'ponctualite_data'), 'on_time'));
                $totalPonctualiteLate = array_sum(array_column(array_column($data, 'ponctualite_data'), 'late'));
                
                // Calculer les taux
                $totalDays = $totalEmployees * $workingDays;
                $presenceRate = $totalDays > 0 ? round(($totalPresencePresent / $totalDays) * 100, 2) : 0;
                
                $totalChecks = $totalPresencePresent;
                $ponctualiteRate = $totalChecks > 0 ? round(($totalPonctualiteOnTime / $totalChecks) * 100, 2) : 0;
                
                $departmentData[] = [
                    'order_number' => $orderNumber++,
                    'department_id' => $department->id,
                    'department_name' => $department->name,
                    'employee_count' => $totalEmployees,
                    'presence_data' => [
                        'present' => $totalPresencePresent,
                        'absent' => $totalPresenceAbsent,
                        'rate' => $presenceRate,
                        'present_avg' => $totalEmployees > 0 ? round($totalPresencePresent / $totalEmployees, 1) : 0,
                        'absent_avg' => $totalEmployees > 0 ? round($totalPresenceAbsent / $totalEmployees, 1) : 0
                    ],
                    'ponctualite_data' => [
                        'on_time' => $totalPonctualiteOnTime,
                        'late' => $totalPonctualiteLate,
                        'rate' => $ponctualiteRate,
                        'avg_late' => $totalPonctualiteLate > 0 ? 
                            round(array_sum(array_column(array_column($data, 'ponctualite_data'), 'avg_late_minutes')) / $totalPonctualiteLate, 1) : 0
                    ],
                    'total_rate' => round(($presenceRate + $ponctualiteRate) / 2, 2)
                ];
            }
        }
        
        // Trier par taux total décroissant
        usort($departmentData, function($a, $b) {
            return $b['total_rate'] <=> $a['total_rate'];
        });
        
        return $departmentData;
    }
    
    /**
     * Calculer les totaux pour le rapport
     */
    private function calculateTotals($reportData, $startDate, $endDate)
    {
        $totals = [
            'total_employees' => count($reportData),
            'total_presence_present' => 0,
            'total_presence_absent' => 0,
            'total_ponctualite_on_time' => 0,
            'total_ponctualite_late' => 0,
            'total_late_minutes' => 0,
            'avg_presence_rate' => 0,
            'avg_ponctualite_rate' => 0
        ];
        
        if (count($reportData) > 0) {
            foreach ($reportData as $data) {
                $totals['total_presence_present'] += $data['presence_data']['present'];
                $totals['total_presence_absent'] += $data['presence_data']['absent'];
                $totals['total_ponctualite_on_time'] += $data['ponctualite_data']['on_time'];
                $totals['total_ponctualite_late'] += $data['ponctualite_data']['late'];
                $totals['total_late_minutes'] += $data['ponctualite_data']['avg_late_minutes'] * $data['ponctualite_data']['late'];
            }
            
            $totals['avg_presence_rate'] = round(array_sum(array_column(array_column($reportData, 'presence_data'), 'rate')) / count($reportData), 1);
            $totals['avg_ponctualite_rate'] = round(array_sum(array_column(array_column($reportData, 'ponctualite_data'), 'rate')) / count($reportData), 1);
        }
        
        return $totals;
    }
    
    /**
     * Calculer les totaux par département
     */
    private function calculateDepartmentTotals($departmentData)
    {
        $totals = [
            'total_departments' => count($departmentData),
            'total_employees' => 0,
            'total_presence_present' => 0,
            'total_presence_absent' => 0,
            'total_ponctualite_on_time' => 0,
            'total_ponctualite_late' => 0,
            'avg_presence_rate' => 0,
            'avg_ponctualite_rate' => 0
        ];
        
        $totalPresenceRate = 0;
        $totalPonctualiteRate = 0;
        
        foreach ($departmentData as $department) {
            $totals['total_employees'] += $department['employee_count'];
            $totals['total_presence_present'] += $department['presence_data']['present'];
            $totals['total_presence_absent'] += $department['presence_data']['absent'];
            $totals['total_ponctualite_on_time'] += $department['ponctualite_data']['on_time'];
            $totals['total_ponctualite_late'] += $department['ponctualite_data']['late'];
            
            $totalPresenceRate += $department['presence_data']['rate'];
            $totalPonctualiteRate += $department['ponctualite_data']['rate'];
        }
        
        if (count($departmentData) > 0) {
            $totals['avg_presence_rate'] = round($totalPresenceRate / count($departmentData), 2);
            $totals['avg_ponctualite_rate'] = round($totalPonctualiteRate / count($departmentData), 2);
        }
        
        return $totals;
    }
    
    /**
     * Debug : Vérifier les données
     */
    public function debugData(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            if (!$client) {
                return response()->json(['error' => 'Client non trouvé'], 404);
            }
            
            $startDate = $request->input('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            
            $workingDays = $this->countWorkingDays($startDate, $endDate);
            
            $dailyAttendances = DailyAttendance::where('client_id', $client->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->count();
            
            $employees = Employee::where('client_id', $client->id)->count();
            
            return response()->json([
                'success' => true,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'working_days' => $workingDays,
                    'total_days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
                ],
                'stats' => [
                    'total_employees' => $employees,
                    'daily_attendances_count' => $dailyAttendances,
                    'avg_per_day' => $workingDays > 0 ? round($dailyAttendances / $workingDays, 1) : 0
                ],
                'message' => 'Données chargées depuis daily_attendances'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}