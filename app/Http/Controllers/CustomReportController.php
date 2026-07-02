<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\DailyAttendance;
use App\Models\EmployeeSchedule;
use App\Models\Mission;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomReportController extends Controller
{
    /**
     * Afficher la page du rapport personnalisé
     */
    public function presencePonctualite(Request $request)
    {
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(function ($employee) {
                return [
                    'emp_code'  => $employee->emp_code,
                    'full_name' => $employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : '')
                ];
            });

        return view('reports.custom-report', compact('employees'));
    }

    /**
     * Générer les données pour le rapport personnalisé (AJAX)
     */
    public function generateCustomReport(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'emp_code'   => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $empCode   = $request->input('emp_code', 'all');

            $reportData = $this->getPresencePonctualiteData($startDate, $endDate, $empCode);

            return response()->json([
                'success'         => true,
                'data'            => $reportData,
                'total_employees' => count($reportData),
                'period'          => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération rapport: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les données de présence et ponctualité depuis la base de données
     * en utilisant EmployeeSchedule pour le calcul des retards
     */
    private function getPresencePonctualiteData($startDate, $endDate, $empCode)
    {
        $employeesQuery = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '');

        if ($empCode && $empCode !== 'all') {
            $employeesQuery->where('emp_code', $empCode);
        }

        $employees = $employeesQuery->orderBy('emp_code')->get();

        if ($employees->isEmpty()) {
            return [];
        }

        // Calculer les jours ouvrés (lundi à vendredi)
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        // Récupérer toutes les présences pour la période
        $allAttendances = DailyAttendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        // Récupérer les congés approuvés pour la période
        $leaves = Leave::where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->get()
            ->groupBy('employee_id');

        // Récupérer toutes les missions pour la période
        $allMissions = Mission::where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->get()
            ->groupBy('employee_id');

        $attendances = $allAttendances->groupBy('employee_id');

        $reportData  = [];
        $orderNumber = 1;

        foreach ($employees as $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect());
            $employeeMissions    = $allMissions->get($employee->id, collect());
            $employeeLeaves      = $leaves->get($employee->id, collect());

            // --- Dates de mission ---
            $missionDates = [];
            foreach ($employeeMissions as $mission) {
                $missionStart = Carbon::parse($mission->start_date);
                $missionEnd   = Carbon::parse($mission->end_date);
                $current      = $missionStart->copy();
                while ($current <= $missionEnd) {
                    $missionDates[$current->format('Y-m-d')] = [
                        'title'       => $mission->title,
                        'destination' => $mission->destination
                    ];
                    $current->addDay();
                }
            }

            // --- Dates de congé ---
            $leaveDates = [];
            foreach ($employeeLeaves as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd   = Carbon::parse($leave->end_date);
                $current    = $leaveStart->copy();
                $typeName   = $leave->type ? $leave->type->name : 'Congé';
                while ($current <= $leaveEnd) {
                    $leaveDates[$current->format('Y-m-d')] = [
                        'type_name' => $typeName,
                    ];
                    $current->addDay();
                }
            }

            // --- Compteurs ---
            $totalPresent    = 0;
            $totalAbsent     = 0;
            $totalLate       = 0;
            $totalEarlyLeave = 0;
            $totalHalfDay    = 0;

            foreach ($employeeAttendances as $attendance) {
                $status = strtoupper($attendance->status);
                $dateKey = Carbon::parse($attendance->attendance_date)->format('Y-m-d');

                if (isset($missionDates[$dateKey]) || isset($leaveDates[$dateKey])) {
                    continue;
                }

                if ($status !== 'ABSENT') {
                    $totalPresent++;

                    $lateData = $this->calculateLateFromPlanning($employee, $attendance, $dateKey);
                    if ($lateData['is_late']) {
                        $totalLate++;
                    }

                    if ($status === 'EARLY_LEAVE') {
                        $totalEarlyLeave++;
                    }
                    if ($status === 'HALF_DAY') {
                        $totalHalfDay++;
                    }
                }
            }

            foreach ($missionDates as $dateStr => $mission) {
                $totalPresent++;
            }

            foreach ($leaveDates as $dateStr => $leave) {
                if (!isset($missionDates[$dateStr])) {
                    $totalPresent++;
                }
            }

            $totalAbsent = $workingDays - $totalPresent;
            $totalOnTime = $totalPresent - $totalLate - $totalEarlyLeave;

            $presenceRate    = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;
            $ponctualiteRate = $totalPresent > 0 ? round(($totalOnTime / $totalPresent) * 100, 1) : 0;

            // --- Observations ---
            $observations = [];

            foreach ($employeeAttendances as $attendance) {
                $status = strtoupper($attendance->status);
                $date   = Carbon::parse($attendance->attendance_date)->format('d/m');
                $dateKey = Carbon::parse($attendance->attendance_date)->format('Y-m-d');

                if ($status === 'ABSENT') {
                    $observations[] = 'Absent le ' . $date;
                } else {
                    $lateData = $this->calculateLateFromPlanning($employee, $attendance, $dateKey);
                    if ($lateData['is_late']) {
                        $observations[] = 'Retard ' . $lateData['late_minutes'] . ' min le ' . $date;
                    }
                    if ($status === 'HALF_DAY') {
                        $observations[] = 'Demi-journée le ' . $date;
                    }
                    if ($status === 'EARLY_LEAVE') {
                        $observations[] = 'Départ anticipé le ' . $date;
                    }
                    if ($status === 'PRESENT' && $attendance->notes) {
                        $observations[] = $attendance->notes . ' le ' . $date;
                    }
                }
            }

            foreach ($missionDates as $dateStr => $mission) {
                $date = Carbon::parse($dateStr)->format('d/m');
                $observations[] = 'Mission: ' . $mission['title'] . ' (' . $mission['destination'] . ') le ' . $date;
            }

            foreach ($leaveDates as $dateStr => $leave) {
                if (!isset($missionDates[$dateStr])) {
                    $date = Carbon::parse($dateStr)->format('d/m');
                    $observations[] = $leave['type_name'] . ' le ' . $date;
                }
            }

            $recordedDates = $employeeAttendances
                ->pluck('attendance_date')
                ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
                ->toArray();
            $currentDate = Carbon::parse($startDate);
            $endDateObj  = Carbon::parse($endDate);
            while ($currentDate <= $endDateObj) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->dayOfWeekIso;
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5
                    && !in_array($dateStr, $recordedDates)
                    && !isset($missionDates[$dateStr])
                    && !isset($leaveDates[$dateStr])
                ) {
                    $observations[] = 'Absent le ' . $currentDate->format('d/m');
                }
                $currentDate->addDay();
            }

            $reportData[] = [
                'order_number'    => $orderNumber++,
                'employee_id'     => $employee->id,
                'employee_code'   => $employee->emp_code,
                'employee_name'   => trim($employee->first_name . ($employee->last_name ? ' ' . $employee->last_name : '')),
                'department_name' => $employee->dept_name ?? 'Non défini',
                'presence_data'   => [
                    'present'              => $totalPresent,
                    'absent'               => $totalAbsent,
                    'rate'                 => $presenceRate,
                    'present_days_display' => $totalPresent . '/' . $workingDays
                ],
                'ponctualite_data' => [
                    'on_time'     => $totalOnTime,
                    'late'        => $totalLate,
                    'early_leave' => $totalEarlyLeave,
                    'half_day'    => $totalHalfDay,
                    'rate'        => $ponctualiteRate
                ],
                'mission_dates' => $missionDates,
                'observation'   => !empty($observations)
                    ? implode(', ', array_slice($observations, 0, 5))
                    : 'Aucune observation'
            ];
        }

        return $reportData;
    }

    /**
     * Exporter le rapport détaillé par département avec heures de pointage
     */
    public function exportCustomPdfByDept(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');

            $workingDays = $this->countWorkingDays($startDate, $endDate);

            $attendances = DailyAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $missions = Mission::where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->get();

            $leaves = Leave::with('type')
                ->where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->get();

            $employees = Employee::orderBy('dept_name')
                ->orderBy('first_name')
                ->get();

            $attendanceByEmployee = [];
            foreach ($attendances as $att) {
                $attendanceByEmployee[$att->employee_id][] = $att;
            }

            $missionsByEmployee = [];
            foreach ($missions as $mission) {
                $missionsByEmployee[$mission->employee_id][] = $mission;
            }

            $leavesByEmployee = [];
            foreach ($leaves as $leave) {
                $leavesByEmployee[$leave->employee_id][] = $leave;
            }

            $departmentData = [];

            foreach ($employees as $employee) {
                $deptName = $employee->dept_name ?: 'Sans département';

                if (!isset($departmentData[$deptName])) {
                    $departmentData[$deptName] = [
                        'department_name' => $deptName,
                        'employees'       => [],
                    ];
                }

                $employeeAttendances = $attendanceByEmployee[$employee->id] ?? [];
                $employeeMissions    = $missionsByEmployee[$employee->id]   ?? [];
                $employeeLeaves      = $leavesByEmployee[$employee->id]     ?? [];

                $missionDates = [];
                foreach ($employeeMissions as $mission) {
                    $missionStart = Carbon::parse($mission->start_date);
                    $missionEnd   = Carbon::parse($mission->end_date);
                    $current      = $missionStart->copy();
                    while ($current <= $missionEnd) {
                        $missionDates[$current->format('Y-m-d')] = [
                            'title'       => $mission->title,
                            'destination' => $mission->destination,
                        ];
                        $current->addDay();
                    }
                }

                $leaveDates = [];
                foreach ($employeeLeaves as $leave) {
                    $leaveStart = Carbon::parse($leave->start_date);
                    $leaveEnd   = Carbon::parse($leave->end_date);
                    $current    = $leaveStart->copy();
                    $typeName   = $leave->type ? $leave->type->name : 'Congé';
                    while ($current <= $leaveEnd) {
                        $leaveDates[$current->format('Y-m-d')] = [
                            'type_name' => $typeName,
                        ];
                        $current->addDay();
                    }
                }

                $dailyChecks = [];
                $currentDate = Carbon::parse($startDate);
                $endDateObj  = Carbon::parse($endDate);

                while ($currentDate <= $endDateObj) {
                    $dateStr = $currentDate->format('Y-m-d');

                    $attendance = null;
                    foreach ($employeeAttendances as $att) {
                        $attDate = $att->attendance_date instanceof Carbon
                            ? $att->attendance_date->format('Y-m-d')
                            : date('Y-m-d', strtotime($att->attendance_date));
                        if ($attDate === $dateStr) {
                            $attendance = $att;
                            break;
                        }
                    }

                    $isMission = isset($missionDates[$dateStr]);
                    $isLeave   = isset($leaveDates[$dateStr]);

                    $lateData = $this->calculateLateFromPlanning($employee, $attendance, $dateStr);
                    $isLateByPlanning = $lateData['is_late'];
                    $lateMinutesCalc  = $lateData['late_minutes'];

                    if ($isMission) {
                        $dailyChecks[$dateStr] = [
                            'check_in'       => null,
                            'check_out'      => null,
                            'status'         => 'MISSION',
                            'is_late'        => false,
                            'late_minutes'   => 0,
                            'is_early_leave' => false,
                            'is_mission'     => true,
                            'mission_info'   => $missionDates[$dateStr],
                            'is_leave'       => false,
                            'leave_info'     => null,
                        ];
                    } elseif ($isLeave) {
                        $dailyChecks[$dateStr] = [
                            'check_in'       => null,
                            'check_out'      => null,
                            'status'         => 'CONGE',
                            'is_late'        => false,
                            'late_minutes'   => 0,
                            'is_early_leave' => false,
                            'is_mission'     => false,
                            'mission_info'   => null,
                            'is_leave'       => true,
                            'leave_info'     => $leaveDates[$dateStr],
                        ];
                    } elseif ($attendance && strtoupper($attendance->status) !== 'ABSENT') {
                        $checkIn = null;
                        if ($attendance->check_in) {
                            $checkIn = $attendance->check_in instanceof Carbon
                                ? $attendance->check_in->format('H:i')
                                : substr($attendance->check_in, 11, 5);
                        }
                        $checkOut = null;
                        if ($attendance->check_out) {
                            $checkOut = $attendance->check_out instanceof Carbon
                                ? $attendance->check_out->format('H:i')
                                : substr($attendance->check_out, 11, 5);
                        }
                        $dailyChecks[$dateStr] = [
                            'check_in'       => $checkIn,
                            'check_out'      => $checkOut,
                            'status'         => $attendance->status,
                            'is_late'        => $isLateByPlanning,
                            'late_minutes'   => $lateMinutesCalc,
                            'is_early_leave' => (bool) $attendance->is_early_leave,
                            'is_mission'     => false,
                            'mission_info'   => null,
                            'is_leave'       => false,
                            'leave_info'     => null,
                        ];
                    } else {
                        $dailyChecks[$dateStr] = null;
                    }

                    $currentDate->addDay();
                }

                $totalPresent    = 0;
                $totalLate       = 0;
                $totalEarlyLeave = 0;
                $totalHalfDay    = 0;
                $totalMission    = 0;
                $totalLeave      = 0;

                foreach ($employeeAttendances as $att) {
                    $status = strtoupper($att->status);
                    $dateKey = Carbon::parse($att->attendance_date)->format('Y-m-d');
                    if ($status !== 'ABSENT' && !isset($missionDates[$dateKey]) && !isset($leaveDates[$dateKey])) {
                        $totalPresent++;
                        $lateData = $this->calculateLateFromPlanning($employee, $att, $dateKey);
                        if ($lateData['is_late']) {
                            $totalLate++;
                        }
                        if ($status === 'EARLY_LEAVE') $totalEarlyLeave++;
                        if ($status === 'HALF_DAY')    $totalHalfDay++;
                    }
                }

                foreach ($missionDates as $dateStr => $mission) {
                    if (Carbon::parse($dateStr)->dayOfWeekIso <= 5) {
                        $totalMission++;
                    }
                }
                foreach ($leaveDates as $dateStr => $leave) {
                    if (Carbon::parse($dateStr)->dayOfWeekIso <= 5 && !isset($missionDates[$dateStr])) {
                        $totalLeave++;
                    }
                }

                $totalPresent += $totalMission + $totalLeave;
                $totalAbsent   = $workingDays - $totalPresent;
                $presenceRate  = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;
                $ponctualiteRate = $totalPresent > 0 ? round((($totalPresent - $totalLate - $totalEarlyLeave) / $totalPresent) * 100, 1) : 0;

                $observations = [];
                foreach ($employeeAttendances as $att) {
                    $status = strtoupper($att->status);
                    $date   = Carbon::parse($att->attendance_date)->format('d/m');
                    $dateKey = Carbon::parse($att->attendance_date)->format('Y-m-d');
                    if ($status === 'ABSENT') {
                        $observations[] = 'Absent le ' . $date;
                    } else {
                        $lateData = $this->calculateLateFromPlanning($employee, $att, $dateKey);
                        if ($lateData['is_late']) {
                            $observations[] = 'Retard ' . $lateData['late_minutes'] . ' min le ' . $date;
                        }
                        if ($status === 'HALF_DAY')    $observations[] = 'Demi-journée le ' . $date;
                        if ($status === 'EARLY_LEAVE') $observations[] = 'Départ anticipé le ' . $date;
                    }
                }
                foreach ($missionDates as $dateStr => $mission) {
                    $observations[] = 'Mission: ' . $mission['title'] . ' (' . $mission['destination'] . ') le ' . Carbon::parse($dateStr)->format('d/m');
                }
                foreach ($leaveDates as $dateStr => $leave) {
                    if (!isset($missionDates[$dateStr])) {
                        $observations[] = $leave['type_name'] . ' le ' . Carbon::parse($dateStr)->format('d/m');
                    }
                }

                $departmentData[$deptName]['employees'][] = [
                    'employee_code' => $employee->emp_code,
                    'employee_name' => trim($employee->first_name . ' ' . $employee->last_name),
                    'daily_checks'  => $dailyChecks,
                    'stats'         => [
                        'present'          => $totalPresent,
                        'absent'           => $totalAbsent,
                        'late'             => $totalLate,
                        'early_leave'      => $totalEarlyLeave,
                        'half_day'         => $totalHalfDay,
                        'mission'          => $totalMission,
                        'leave'            => $totalLeave,
                        'presence_rate'    => $presenceRate,
                        'ponctualite_rate' => $ponctualiteRate,
                    ],
                    'observations' => !empty($observations)
                        ? implode(', ', array_slice($observations, 0, 5))
                        : 'Aucune observation',
                ];
            }

            $daysList    = [];
            $currentDate = Carbon::parse($startDate);
            $endDateObj  = Carbon::parse($endDate);
            while ($currentDate <= $endDateObj) {
                $daysList[] = [
                    'date'     => $currentDate->copy(),
                    'date_str' => $currentDate->format('Y-m-d'),
                    'day_name' => $this->getDayNameFrench($currentDate->dayOfWeekIso),
                ];
                $currentDate->addDay();
            }

            $reportData = [];
            foreach ($departmentData as $deptName => $dept) {
                if (empty($dept['employees'])) continue;

                $totalEmployees       = count($dept['employees']);
                $totalPresent         = 0;
                $totalAbsent          = 0;
                $totalLate            = 0;
                $totalEarlyLeave      = 0;
                $totalHalfDay         = 0;
                $totalMission         = 0;
                $totalLeave           = 0;
                $totalOnTime          = 0;
                $totalPresenceRate    = 0;
                $totalPonctualiteRate = 0;

                foreach ($dept['employees'] as $emp) {
                    $totalPresent        += $emp['stats']['present'];
                    $totalAbsent         += $emp['stats']['absent'];
                    $totalLate           += $emp['stats']['late'];
                    $totalEarlyLeave     += $emp['stats']['early_leave'];
                    $totalHalfDay        += $emp['stats']['half_day'];
                    $totalMission        += $emp['stats']['mission'];
                    $totalLeave          += $emp['stats']['leave'];
                    $totalOnTime         += ($emp['stats']['present'] - $emp['stats']['late'] - $emp['stats']['early_leave']);
                    $totalPresenceRate   += $emp['stats']['presence_rate'];
                    $totalPonctualiteRate += $emp['stats']['ponctualite_rate'];
                }

                $reportData[] = [
                    'department_name'      => $deptName,
                    'total_employees'      => $totalEmployees,
                    'employees'            => $dept['employees'],
                    'total_present'        => $totalPresent,
                    'total_absent'         => $totalAbsent,
                    'total_late'           => $totalLate,
                    'total_early_leave'    => $totalEarlyLeave,
                    'total_half_day'       => $totalHalfDay,
                    'total_mission'        => $totalMission,
                    'total_leave'          => $totalLeave,
                    'total_on_time'        => $totalOnTime,
                    'avg_presence_rate'    => $totalEmployees > 0 ? round($totalPresenceRate / $totalEmployees, 1) : 0,
                    'avg_ponctualite_rate' => $totalEmployees > 0 ? round($totalPonctualiteRate / $totalEmployees, 1) : 0,
                ];
            }

            $totals = [
                'total_employees'      => 0,
                'total_present'        => 0,
                'total_absent'         => 0,
                'total_late'           => 0,
                'total_early_leave'    => 0,
                'total_half_day'       => 0,
                'total_mission'        => 0,
                'total_leave'          => 0,
                'total_on_time'        => 0,
                'avg_presence_rate'    => 0,
                'avg_ponctualite_rate' => 0,
            ];
            foreach ($reportData as $data) {
                $totals['total_employees']   += $data['total_employees'];
                $totals['total_present']     += $data['total_present'];
                $totals['total_absent']      += $data['total_absent'];
                $totals['total_late']        += $data['total_late'];
                $totals['total_early_leave'] += $data['total_early_leave'];
                $totals['total_half_day']    += $data['total_half_day'];
                $totals['total_mission']     += $data['total_mission'];
                $totals['total_leave']       += $data['total_leave'];
                $totals['total_on_time']     += $data['total_on_time'];
            }
            $totals['avg_presence_rate'] = !empty($reportData) ? round(array_sum(array_column($reportData, 'avg_presence_rate')) / count($reportData), 1) : 0;
            $totals['avg_ponctualite_rate'] = !empty($reportData) ? round(array_sum(array_column($reportData, 'avg_ponctualite_rate')) / count($reportData), 1) : 0;

            $data = [
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'export_date'       => Carbon::now(),
                'report_data'       => $reportData,
                'totals'            => $totals,
                'total_departments' => count($reportData),
                'period_days'       => $workingDays,
                'days_list'         => $daysList,
            ];

            $pdf = Pdf::loadView('reports.exports.custom-report-pdf-by-dept', $data);
            $pdf->setPaper('A4', 'landscape');

            $filename = 'rapport_presence_departements_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Exporter le rapport personnalisé (standard) en PDF
     */
    public function exportCustomPdf(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'emp_code'   => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $empCode   = $request->input('emp_code', 'all');

            $reportData = $this->getPresencePonctualiteData($startDate, $endDate, $empCode);
            $totals = $this->calculateTotals($reportData);

            $data = [
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'export_date'     => Carbon::now(),
                'report_data'     => $reportData,
                'totals'          => $totals,
                'total_employees' => count($reportData),
                'period_days'     => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
            ];

            $pdf = Pdf::loadView('reports.exports.custom-report-pdf', $data);
            $pdf->setPaper('A4', 'landscape');

            $filename = 'rapport_presence_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Calculer les totaux pour le rapport standard
     */
    private function calculateTotals($reportData)
    {
        $totals = [
            'total_employees'          => count($reportData),
            'total_presence_present'   => 0,
            'total_presence_absent'    => 0,
            'total_ponctualite_on_time' => 0,
            'total_ponctualite_late'   => 0,
            'avg_presence_rate'        => 0,
            'avg_ponctualite_rate'     => 0
        ];

        if (count($reportData) > 0) {
            foreach ($reportData as $data) {
                $totals['total_presence_present']    += $data['presence_data']['present'];
                $totals['total_presence_absent']     += $data['presence_data']['absent'];
                $totals['total_ponctualite_on_time'] += $data['ponctualite_data']['on_time'];
                $totals['total_ponctualite_late']    += $data['ponctualite_data']['late'];
            }
            $totals['avg_presence_rate']    = round(array_sum(array_column(array_column($reportData, 'presence_data'), 'rate')) / count($reportData), 1);
            $totals['avg_ponctualite_rate'] = round(array_sum(array_column(array_column($reportData, 'ponctualite_data'), 'rate')) / count($reportData), 1);
        }

        return $totals;
    }

    /**
     * Calculer le retard en comparant check-in avec l'heure de début du planning
     */
    private function calculateLateFromPlanning($employee, $attendance, string $dateKey): array
    {
        $result = [
            'is_late' => false,
            'late_minutes' => 0,
        ];

        if (!$attendance || !$attendance->check_in || strtoupper($attendance->status) === 'ABSENT') {
            return $result;
        }

        $schedule = $this->getEmployeeScheduleForDateNew($employee, $dateKey);
        if (!$schedule || !$schedule['is_working_day'] || !$schedule['start_time']) {
            return $result;
        }

        try {
            $plannedStartTime = Carbon::parse($schedule['start_time'])->format('H:i:s');
            $checkInTime = $attendance->check_in instanceof Carbon
                ? $attendance->check_in->format('H:i:s')
                : Carbon::parse($attendance->check_in)->format('H:i:s');

            $plannedStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $plannedStartTime);
            $checkIn = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $checkInTime);

            if ($checkIn->gt($plannedStart)) {
                $result['is_late'] = true;
                $result['late_minutes'] = $checkIn->diffInMinutes($plannedStart);
            }
        } catch (\Exception $e) {
            Log::warning('Erreur calcul retard custom report', [
                'employee_id' => $employee->id ?? null,
                'date' => $dateKey,
                'message' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    private function getEmployeeScheduleForDateNew($employee, $dateStr)
    {
        if (!$employee) {
            return null;
        }

        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;

        // 1. Planning spécifique à la date exacte
        $specificSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $dateStr)
            ->first();

        if ($specificSchedule) {
            return $this->formatScheduleData($specificSchedule);
        }

        // 2. Planning dans une plage de dates
        $rangeSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($rangeSchedule) {
            return $this->formatScheduleData($rangeSchedule);
        }

        // 3. Planning fixe par jour de semaine
        $fixedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'fixe')
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($fixedSchedule) {
            return $this->formatScheduleData($fixedSchedule);
        }

        // 4. Planning rotation
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
                    ];
                }
            }
        }

        // 5. Planning planifié (générique)
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
            'schedule_type'   => $schedule->schedule_type,
            'is_working_day'  => $schedule->is_working_day ?? true,
            'start_time'      => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i:s') : null,
            'end_time'        => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i:s') : null,
            'work_days_count' => $schedule->work_days_count ?? null,
            'rest_days_count' => $schedule->rest_days_count ?? null,
            'daily_hours'     => $schedule->daily_hours ?? null,
            'break_minutes'   => $schedule->break_minutes ?? 0,
            'start_date'      => $schedule->start_date,
            'end_date'        => $schedule->end_date,
        ];
    }

    /**
     * Obtenir le nom du jour en français
     */
    private function getDayNameFrench($dayOfWeekIso)
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        return $days[$dayOfWeekIso] ?? '';
    }

    /**
     * Compter les jours ouvrés entre deux dates (lundi à vendredi)
     */
    private function countWorkingDays($startDate, $endDate)
    {
        $start       = Carbon::parse($startDate);
        $end         = Carbon::parse($endDate);
        $workingDays = 0;

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            if ($date->dayOfWeekIso >= 1 && $date->dayOfWeekIso <= 5) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}