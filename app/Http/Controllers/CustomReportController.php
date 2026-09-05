<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SignatairePoste;
use App\Models\DailyAttendance;
use App\Models\EmployeeSchedule;
use App\Models\Mission;
use App\Models\Leave;
use App\Models\EmployeePermission;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Reports\PresencePonctualiteColumns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomReportController extends Controller
{
    /** Nombre d'observations affichées dans la colonne avant troncature. */
    private const MAX_OBSERVATIONS = 5;

    /** Priorités d'affichage dans la colonne Observation (le plus petit d'abord). */
    private const OBS_JUSTIFICATION = 0;
    private const OBS_COURANTE      = 1;

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

        // Récupérer les départements pour le filtre à partir du champ dept_name des employés.
        // dept_name est chiffré (cast "encrypted"), on doit donc déchiffrer côté PHP
        // pour obtenir la liste distincte des départements réellement présents.
        $departments = Employee::get()
            ->pluck('dept_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values();

        ReportTemplate::ensureDefaultFor();
        $templates = ReportTemplate::forReport()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('reports.custom-report', compact('employees', 'departments', 'templates'));
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
                'emp_code'   => 'nullable|string',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $empCode   = $request->input('emp_code', 'all');
            $departmentIds = $request->input('department_ids', ['all']);

            // Normaliser : si "all" est présent, on ignore les autres valeurs.
            if (in_array('all', (array) $departmentIds)) {
                $departmentIds = ['all'];
            }

            $reportData = $this->getPresencePonctualiteData($startDate, $endDate, $empCode, $departmentIds);

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
    /**
     * Met en forme la colonne Observation.
     *
     * Les entrées arrivent sous la forme [date Y-m-d, texte, priorité] et sont
     * classées par priorité puis par date.
     *
     * Les justifications (mission, congé, autorisation) passent devant : elles
     * sont l'information exceptionnelle du rapport, alors que les absences et
     * retards sont nombreux et faisaient disparaître les justifications, la
     * colonne n'affichant que les cinq premières entrées.
     *
     * @param  array<int, array{0: string, 1: string, 2?: int}> $observations
     */
    private function formatObservations(array $observations): string
    {
        if (empty($observations)) {
            return 'Aucune observation';
        }

        usort($observations, function ($a, $b) {
            $priorite = ($a[2] ?? self::OBS_COURANTE) <=> ($b[2] ?? self::OBS_COURANTE);

            return $priorite !== 0 ? $priorite : strcmp($a[0], $b[0]);
        });

        $textes   = array_column($observations, 1);
        $affiches = array_slice($textes, 0, self::MAX_OBSERVATIONS);
        $reste    = count($textes) - count($affiches);

        return implode(', ', $affiches)
            . ($reste > 0 ? ' … (+' . $reste . ' autre' . ($reste > 1 ? 's' : '') . ')' : '');
    }

    /**
     * Filtrer une collection d'employés par nom de département.
     *
     * dept_name étant chiffré en base (cast "encrypted"), un LIKE SQL ne peut pas
     * fonctionner. On filtre donc côté PHP par recherche « contient », insensible
     * à la casse, sur la valeur déchiffrée par Eloquent.
     */
    private function filterEmployeesByDepartment($employees, $departmentIds)
    {
        if (!$departmentIds || !is_array($departmentIds) || in_array('all', $departmentIds)) {
            return $employees->values();
        }

        $selectedDepts = array_values(array_filter(array_map(
            fn ($d) => mb_strtolower(trim((string) $d)),
            $departmentIds
        ), fn ($d) => $d !== ''));

        if (empty($selectedDepts)) {
            return $employees->values();
        }

        return $employees->filter(function ($employee) use ($selectedDepts) {
            $deptName = mb_strtolower(trim((string) ($employee->dept_name ?? '')));
            if ($deptName === '') {
                return false;
            }
            foreach ($selectedDepts as $selected) {
                if (str_contains($deptName, $selected)) {
                    return true;
                }
            }
            return false;
        })->values();
    }

    /**
     * Récupérer les postes de signataires (avec leurs responsables),
     * pour le cartouche de signatures affiché en fin de PDF.
     */
    private function getSignatairePostes()
    {
        return SignatairePoste::with('signataires')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function getPresencePonctualiteData($startDate, $endDate, $empCode, $departmentIds = ['all'], $includeWeekends = false)
    {
        $employeesQuery = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '');

        if ($empCode && $empCode !== 'all') {
            $employeesQuery->where('emp_code', $empCode);
        }

        $employees = $employeesQuery->orderBy('emp_code')->get();

        // Filtrer par département(s) sélectionné(s) sur le champ dept_name (chiffré → filtre PHP).
        $employees = $this->filterEmployeesByDepartment($employees, $departmentIds);

        if ($employees->isEmpty()) {
            return [];
        }

        // Calculer les jours ouvrés (lundi à vendredi), ou tous les jours
        // calendaires si le modèle choisi inclut les week-ends.
        $workingDays = $includeWeekends
            ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
            : $this->countWorkingDays($startDate, $endDate);

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

        // Récupérer les autorisations d'absence approuvées pour la période.
        $allPermissions = EmployeePermission::where('status', 'approved')
            ->overlappingPeriod($startDate, $endDate)
            ->get()
            ->groupBy('employee_id');

        $attendances = $allAttendances->groupBy('employee_id');

        $reportData  = [];
        $orderNumber = 1;

        foreach ($employees as $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect());
            $employeeMissions    = $allMissions->get($employee->id, collect());
            $employeeLeaves      = $leaves->get($employee->id, collect());
            $employeePermissions = $allPermissions->get($employee->id, collect());

            // La requête de missions/congés remonte tout enregistrement qui
            // chevauche la période (y compris ceux qui commencent avant ou
            // finissent après) — il faut donc borner l'itération jour par jour
            // à [$startDate, $endDate], sinon des jours hors période sont
            // comptés dans la présence (et l'absence peut devenir négative).
            $periodStart = Carbon::parse($startDate)->startOfDay();
            $periodEnd   = Carbon::parse($endDate)->startOfDay();

            // --- Dates de mission ---
            $missionDates = [];
            foreach ($employeeMissions as $mission) {
                $missionStart = Carbon::parse($mission->start_date)->max($periodStart);
                $missionEnd   = Carbon::parse($mission->end_date)->min($periodEnd);
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
                $leaveStart = Carbon::parse($leave->start_date)->max($periodStart);
                $leaveEnd   = Carbon::parse($leave->end_date)->min($periodEnd);
                $current    = $leaveStart->copy();
                $typeName   = $leave->type ? $leave->type->name : 'Congé';
                while ($current <= $leaveEnd) {
                    $leaveDates[$current->format('Y-m-d')] = [
                        'type_name' => $typeName,
                    ];
                    $current->addDay();
                }
            }

            // --- Dates d'autorisation d'absence ---
            $permissionDates = [];
            foreach ($employeePermissions as $permission) {
                $permStart = Carbon::parse($permission->getEffectiveStartDate())->max($periodStart);
                $permEnd   = Carbon::parse($permission->getEffectiveEndDate())->min($periodEnd);
                $current   = $permStart->copy();
                while ($current <= $permEnd) {
                    $permissionDates[$current->format('Y-m-d')] = [
                        'raison' => $permission->raison,
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

                // Le rapport ne porte que sur les jours ouvrés (lundi-vendredi,
                // voir légende du PDF) : un pointage un week-end ne doit pas
                // gonfler le total de présence au-delà des jours ouvrés,
                // sinon l'absence (jours ouvrés - présence) devient négative.
                // Sauf si le modèle choisi inclut explicitement les week-ends.
                if (!$includeWeekends && Carbon::parse($dateKey)->dayOfWeekIso > 5) {
                    continue;
                }

                if (isset($missionDates[$dateKey]) || isset($leaveDates[$dateKey])
                    || isset($permissionDates[$dateKey])) {
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
                if ($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5) {
                    $totalPresent++;
                }
            }

            foreach ($leaveDates as $dateStr => $leave) {
                if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5) && !isset($missionDates[$dateStr])) {
                    $totalPresent++;
                }
            }

            // Une autorisation d'absence ne compte pas comme une absence
            // (même traitement que mission / congé), sans double comptage.
            foreach ($permissionDates as $dateStr => $permission) {
                if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                    && !isset($missionDates[$dateStr])
                    && !isset($leaveDates[$dateStr])) {
                    $totalPresent++;
                }
            }

            $totalAbsent = $workingDays - $totalPresent;
            $totalOnTime = $totalPresent - $totalLate - $totalEarlyLeave;

            $presenceRate    = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;
            $ponctualiteRate = $totalPresent > 0 ? round(($totalOnTime / $totalPresent) * 100, 1) : 0;

            // --- Observations ---
            // Chaque entrée porte sa date : les observations sont ensuite triées
            // chronologiquement. Sans ce tri, toutes les absences étaient listées
            // avant les missions et congés (et pouvaient les évincer, la colonne
            // n'affichant que les 5 premières entrées).
            $observations = [];

            foreach ($employeeAttendances as $attendance) {
                $status = strtoupper($attendance->status);
                $date   = Carbon::parse($attendance->attendance_date)->format('d/m');
                $dateKey = Carbon::parse($attendance->attendance_date)->format('Y-m-d');

                // Journée déjà justifiée (mission / congé / autorisation) : la
                // justification est ajoutée plus bas, ne pas la contredire avec
                // un « Absent le … ».
                if (isset($missionDates[$dateKey]) || isset($leaveDates[$dateKey])
                    || isset($permissionDates[$dateKey])) {
                    continue;
                }

                if ($status === 'ABSENT') {
                    $observations[] = [$dateKey, 'Absent le ' . $date];
                } else {
                    $lateData = $this->calculateLateFromPlanning($employee, $attendance, $dateKey);
                    if ($lateData['is_late']) {
                        $observations[] = [$dateKey, 'Retard ' . $lateData['late_minutes'] . ' min le ' . $date];
                    }
                    if ($status === 'HALF_DAY') {
                        $observations[] = [$dateKey, 'Pointage incomplet le ' . $date];
                    }
                    if ($status === 'EARLY_LEAVE') {
                        $observations[] = [$dateKey, 'Départ anticipé le ' . $date];
                    }
                    if ($status === 'PRESENT' && $attendance->notes) {
                        $observations[] = [$dateKey, $attendance->notes . ' le ' . $date];
                    }
                }
            }

            foreach ($missionDates as $dateStr => $mission) {
                $observations[] = [$dateStr, 'En mission le ' . Carbon::parse($dateStr)->format('d/m'), self::OBS_JUSTIFICATION];
            }

            foreach ($leaveDates as $dateStr => $leave) {
                if (!isset($missionDates[$dateStr])) {
                    $observations[] = [$dateStr, $leave['type_name'] . ' le ' . Carbon::parse($dateStr)->format('d/m'), self::OBS_JUSTIFICATION];
                }
            }

            foreach ($permissionDates as $dateStr => $permission) {
                if (isset($missionDates[$dateStr]) || isset($leaveDates[$dateStr])) {
                    continue;
                }

                $raison = trim((string) ($permission['raison'] ?? ''));
                $observations[] = [$dateStr, "Autorisation d'absence"
                    . ($raison !== '' ? ' (' . $raison . ')' : '')
                    . ' le ' . Carbon::parse($dateStr)->format('d/m'), self::OBS_JUSTIFICATION];
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
                if (($includeWeekends || ($dayOfWeek >= 1 && $dayOfWeek <= 5))
                    && !in_array($dateStr, $recordedDates)
                    && !isset($missionDates[$dateStr])
                    && !isset($leaveDates[$dateStr])
                    && !isset($permissionDates[$dateStr])
                ) {
                    $observations[] = [$dateStr, 'Absent le ' . $currentDate->format('d/m')];
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
                'observation'   => $this->formatObservations($observations)
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
            $empCode   = $request->input('emp_code', 'all');
            $departmentIds = $request->input('department_ids', ['all']);

            if (in_array('all', (array) $departmentIds)) {
                $departmentIds = ['all'];
            }

            $template = ReportTemplate::resolveFor($request->input('template_id'));
            $options  = $template->resolvedOptions();
            $includeWeekends = $options['show_weekends'];

            $workingDays = $includeWeekends
                ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
                : $this->countWorkingDays($startDate, $endDate);
            $periodStart = Carbon::parse($startDate)->startOfDay();
            $periodEnd   = Carbon::parse($endDate)->startOfDay();

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

            $employeesQuery = Employee::query();

            if ($empCode && $empCode !== 'all') {
                $employeesQuery->where('emp_code', $empCode);
            }

            $employees = $employeesQuery
                ->orderBy('dept_name')
                ->orderBy('first_name')
                ->get();

            // Filtrer par département(s) sélectionné(s) sur dept_name (chiffré → filtre PHP).
            $employees = $this->filterEmployeesByDepartment($employees, $departmentIds);

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

                // Bornée à [$periodStart, $periodEnd] : la requête remonte aussi
                // les missions/congés qui débordent de la période (voir plus
                // haut), il ne faut pas compter leurs jours hors période.
                $missionDates = [];
                foreach ($employeeMissions as $mission) {
                    $missionStart = Carbon::parse($mission->start_date)->max($periodStart);
                    $missionEnd   = Carbon::parse($mission->end_date)->min($periodEnd);
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
                    $leaveStart = Carbon::parse($leave->start_date)->max($periodStart);
                    $leaveEnd   = Carbon::parse($leave->end_date)->min($periodEnd);
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
                    // Idem que dans getPresencePonctualiteData() : ne pas compter les
                    // pointages du week-end dans la présence, sinon l'absence
                    // (jours ouvrés - présence) peut devenir négative. Sauf si le
                    // modèle choisi inclut explicitement les week-ends.
                    if (!$includeWeekends && Carbon::parse($dateKey)->dayOfWeekIso > 5) {
                        continue;
                    }
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
                    if ($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5) {
                        $totalMission++;
                    }
                }
                foreach ($leaveDates as $dateStr => $leave) {
                    if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5) && !isset($missionDates[$dateStr])) {
                        $totalLeave++;
                    }
                }

                $totalPresent += $totalMission + $totalLeave;
                $totalAbsent   = $workingDays - $totalPresent;
                $presenceRate  = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;
                $ponctualiteRate = $totalPresent > 0 ? round((($totalPresent - $totalLate - $totalEarlyLeave) / $totalPresent) * 100, 1) : 0;

                // Entrées datées puis triées chronologiquement (cf. formatObservations).
                $observations = [];
                foreach ($employeeAttendances as $att) {
                    $status = strtoupper($att->status);
                    $date   = Carbon::parse($att->attendance_date)->format('d/m');
                    $dateKey = Carbon::parse($att->attendance_date)->format('Y-m-d');

                    // Journée justifiée : la mission ou le congé est ajouté plus bas,
                    // ne pas le contredire avec un « Absent le … ».
                    if (isset($missionDates[$dateKey]) || isset($leaveDates[$dateKey])) {
                        continue;
                    }

                    if ($status === 'ABSENT') {
                        $observations[] = [$dateKey, 'Absent le ' . $date];
                    } else {
                        $lateData = $this->calculateLateFromPlanning($employee, $att, $dateKey);
                        if ($lateData['is_late']) {
                            $observations[] = [$dateKey, 'Retard ' . $lateData['late_minutes'] . ' min le ' . $date];
                        }
                        if ($status === 'HALF_DAY')    $observations[] = [$dateKey, 'Pointage incomplet le ' . $date];
                        if ($status === 'EARLY_LEAVE') $observations[] = [$dateKey, 'Départ anticipé le ' . $date];
                    }
                }
                foreach ($missionDates as $dateStr => $mission) {
                    $observations[] = [$dateStr, 'En mission le ' . Carbon::parse($dateStr)->format('d/m'), self::OBS_JUSTIFICATION];
                }
                foreach ($leaveDates as $dateStr => $leave) {
                    if (!isset($missionDates[$dateStr])) {
                        $observations[] = [$dateStr, $leave['type_name'] . ' le ' . Carbon::parse($dateStr)->format('d/m'), self::OBS_JUSTIFICATION];
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
                    'observations' => $this->formatObservations($observations),
                ];
            }

            $daysList    = [];
            $currentDate = Carbon::parse($startDate);
            $endDateObj  = Carbon::parse($endDate);
            while ($currentDate <= $endDateObj) {
                // N'afficher les colonnes samedi/dimanche que si l'option
                // "Inclure les week-ends" du modèle est activée. On parcourt
                // toute la période choisie et on saute uniquement le week-end.
                if ($includeWeekends || $currentDate->dayOfWeekIso <= 5) {
                    $daysList[] = [
                        'date'     => $currentDate->copy(),
                        'date_str' => $currentDate->format('Y-m-d'),
                        'day_name' => $this->getDayNameFrench($currentDate->dayOfWeekIso),
                    ];
                }
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
                'signatairePostes'  => $this->getSignatairePostes(),
                'options'           => $options,
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
                'emp_code'   => 'nullable|string',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $empCode   = $request->input('emp_code', 'all');
            $departmentIds = $request->input('department_ids', ['all']);

            if (in_array('all', (array) $departmentIds)) {
                $departmentIds = ['all'];
            }

            $template = ReportTemplate::resolveFor($request->input('template_id'));
            $options  = $template->resolvedOptions();

            $reportData = $this->getPresencePonctualiteData($startDate, $endDate, $empCode, $departmentIds, $options['show_weekends']);

            $data = [
                'start_date'       => $startDate,
                'end_date'         => $endDate,
                'export_date'      => Carbon::now(),
                'report_data'      => $reportData,
                'total_employees'  => count($reportData),
                'period_days'      => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                'signatairePostes' => $this->getSignatairePostes(),
                'columns'          => $template->resolvedColumns(),
                'catalogue'        => PresencePonctualiteColumns::all(),
                'options'          => $options,
                'template_name'    => $template->name,
            ];

            $pdf = Pdf::loadView('reports.exports.custom-report-pdf-template', $data);
            $pdf->setPaper('A4', $options['orientation']);

            $filename = 'rapport_presence_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Exporter le rapport présence & ponctualité en Excel (.xlsx).
     *
     * Reprend les mêmes filtres que la génération / l'export PDF et écrit
     * les données résumées (une ligne par employé) dans un vrai fichier xlsx.
     */
    public function exportCustomExcel(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'emp_code'   => 'nullable|string',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');
            $empCode   = $request->input('emp_code', 'all');
            $departmentIds = $request->input('department_ids', ['all']);

            if (in_array('all', (array) $departmentIds)) {
                $departmentIds = ['all'];
            }

            // Même option week-ends que le modèle sélectionné (cohérence avec le PDF).
            $template = ReportTemplate::resolveFor($request->input('template_id'));
            $options  = $template->resolvedOptions();

            $reportData = $this->getPresencePonctualiteData(
                $startDate,
                $endDate,
                $empCode,
                $departmentIds,
                $options['show_weekends'] ?? false
            );

            $xlsx = new \App\Support\SimpleXlsxWriter('Présence & Ponctualité');
            $xlsx->setColumnWidths([6, 14, 28, 22, 10, 10, 16, 12, 10, 18, 45]);

            // Ligne de titre (période)
            $xlsx->addRow([
                'Rapport Présence & Ponctualité — du ' . Carbon::parse($startDate)->format('d/m/Y')
                    . ' au ' . Carbon::parse($endDate)->format('d/m/Y'),
            ], true);
            $xlsx->addRow([]); // ligne vide

            // En-têtes
            $xlsx->addRow([
                'N°', 'Code', 'Nom & Prénom', 'Département',
                'Présent', 'Absent', 'Taux présence (%)',
                'À l\'heure', 'Retard', 'Taux ponctualité (%)',
                'Observations',
            ], true);

            $totalPresent = 0;
            $totalAbsent  = 0;
            $totalOnTime  = 0;
            $totalLate    = 0;

            foreach ($reportData as $row) {
                $totalPresent += (int) ($row['presence_data']['present'] ?? 0);
                $totalAbsent  += (int) ($row['presence_data']['absent'] ?? 0);
                $totalOnTime  += (int) ($row['ponctualite_data']['on_time'] ?? 0);
                $totalLate    += (int) ($row['ponctualite_data']['late'] ?? 0);

                $xlsx->addRow([
                    (int) ($row['order_number'] ?? 0),
                    (string) ($row['employee_code'] ?? ''),
                    (string) ($row['employee_name'] ?? ''),
                    (string) ($row['department_name'] ?? ''),
                    (int) ($row['presence_data']['present'] ?? 0),
                    (int) ($row['presence_data']['absent'] ?? 0),
                    (float) ($row['presence_data']['rate'] ?? 0),
                    (int) ($row['ponctualite_data']['on_time'] ?? 0),
                    (int) ($row['ponctualite_data']['late'] ?? 0),
                    (float) ($row['ponctualite_data']['rate'] ?? 0),
                    (string) ($row['observation'] ?? ''),
                ]);
            }

            // Ligne des totaux
            $xlsx->addRow([
                '', '', 'TOTAUX', '',
                $totalPresent, $totalAbsent, '',
                $totalOnTime, $totalLate, '', '',
            ], true);

            $filename = 'rapport_presence_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            return $xlsx->download($filename);

        } catch (\Exception $e) {
            Log::error('Erreur export Excel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Abréviations des jours utilisées en tête de colonne (Lund, Mard, …).
     */
    private const JOURS_COURTS = [
        1 => 'Lund', 2 => 'Mard', 3 => 'Merc', 4 => 'Jeud', 5 => 'Vend', 6 => 'Sam', 7 => 'Dim',
    ];

    /** Période maximale couverte par le Tableau de Suivi de la Ponctualité. */
    private const SUIVI_MAX_JOURS = 31;

    /**
     * Page « Tableau de Suivi de la Ponctualité ».
     */
    public function suiviPonctualite(Request $request)
    {
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(fn ($employee) => [
                'emp_code'  => $employee->emp_code,
                'full_name' => trim($employee->first_name . ' ' . ($employee->last_name ?? '')),
            ]);

        // dept_name est chiffré en base : liste distincte construite côté PHP.
        $departments = Employee::get()
            ->pluck('dept_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values();

        return view('reports.suivi-ponctualite.index', compact('employees', 'departments'));
    }

    /**
     * Données du tableau (AJAX).
     */
    public function generateSuiviPonctualite(Request $request)
    {
        try {
            $validated = $this->validateSuiviPeriode($request);

            if (isset($validated['error'])) {
                return response()->json(['error' => $validated['error']], 400);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->buildSuiviPonctualiteData(
                    $validated['start_date'],
                    $validated['end_date'],
                    $validated['emp_code'],
                    $validated['department_ids']
                ),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suivi ponctualité: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export PDF (A4 paysage), même mise en forme que le tableau à l'écran.
     */
    public function exportSuiviPonctualitePdf(Request $request)
    {
        $validated = $this->validateSuiviPeriode($request);

        if (isset($validated['error'])) {
            return redirect()->back()->with('error', $validated['error']);
        }

        $data = $this->buildSuiviPonctualiteData(
            $validated['start_date'],
            $validated['end_date'],
            $validated['emp_code'],
            $validated['department_ids']
        );

        $pdf = Pdf::loadView('reports.suivi-ponctualite.exports.pdf', [
            'report'           => $data,
            'export_date'      => Carbon::now(),
            'signatairePostes' => $this->getSignatairePostes(),
        ]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('suivi_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Export Excel (.xlsx), mêmes colonnes que le PDF, impression en paysage.
     */
    public function exportSuiviPonctualiteExcel(Request $request)
    {
        $validated = $this->validateSuiviPeriode($request);

        if (isset($validated['error'])) {
            return redirect()->back()->with('error', $validated['error']);
        }

        $report = $this->buildSuiviPonctualiteData(
            $validated['start_date'],
            $validated['end_date'],
            $validated['emp_code'],
            $validated['department_ids']
        );

        $nbJours = count($report['days']);

        $xlsx = new \App\Support\SimpleXlsxWriter('Suivi ponctualité');
        $xlsx->setLandscape();
        $xlsx->setColumnWidths(array_merge([32], array_fill(0, $nbJours, 6), [9, 9]));

        $xlsx->addRow(['STATISTIQUES DES RETARDS, SORTIES ET ABSENCES NON JUSTIFIÉES PAR JOUR'], true);
        $xlsx->addRow([$report['month_label']], true);
        $xlsx->addRow([$report['period_label']]);
        $xlsx->addRow([]);

        // Deux lignes d'en-tête : abréviation du jour, puis numéro du jour.
        $ligneJours   = array_merge(['Nom et Prénoms'], array_column($report['days'], 'day_short'), ['TOTAL', '']);
        $ligneNumeros = array_merge([''], array_column($report['days'], 'day_number'), ['Retard', 'en mn']);
        $xlsx->addRow($ligneJours, true);
        $xlsx->addRow($ligneNumeros, true);

        foreach ($report['rows'] as $row) {
            $cellules = [$row['employee_name']];
            foreach ($report['days'] as $day) {
                $cell = $row['cells'][$day['date']] ?? null;
                $cellules[] = $cell
                    ? $cell['text'] . (($cell['detail'] ?? '') !== '' ? ' (' . $cell['detail'] . ')' : '')
                    : '';
            }
            $cellules[] = $row['total_retards'];
            $cellules[] = $row['total_minutes'];

            $xlsx->addRow($cellules);
        }

        // Ligne des totaux généraux.
        $xlsx->addRow(array_merge(
            ['TOTAL GÉNÉRAL'],
            array_fill(0, $nbJours, ''),
            [$report['totals']['retards'], $report['totals']['minutes']]
        ), true);

        return $xlsx->download('suivi_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    /**
     * Valide et normalise les filtres du suivi (période plafonnée à un mois).
     *
     * @return array{start_date?: string, end_date?: string, emp_code?: string, department_ids?: array, error?: string}
     */
    private function validateSuiviPeriode(Request $request): array
    {
        $validator = \Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'emp_code'   => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors()->first()];
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $jours = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        if ($jours > self::SUIVI_MAX_JOURS) {
            return ['error' => 'La période ne doit pas dépasser un mois (' . self::SUIVI_MAX_JOURS . ' jours).'];
        }

        $departmentIds = $request->input('department_ids', ['all']);
        if (in_array('all', (array) $departmentIds)) {
            $departmentIds = ['all'];
        }

        return [
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'emp_code'       => $request->input('emp_code', 'all'),
            'department_ids' => $departmentIds,
        ];
    }

    /**
     * Construit la grille employés × jours ouvrés du Tableau de Suivi de la Ponctualité.
     *
     * Une cellule porte, par ordre de priorité : congé, mission, autorisation
     * d'absence, absence non justifiée, retard (en minutes), sortie anticipée.
     * Une journée normale reste vide, comme sur le formulaire papier.
     *
     * @return array{days: array, rows: array, totals: array, month_label: string, period_label: string}
     */
    private function buildSuiviPonctualiteData($startDate, $endDate, $empCode, $departmentIds): array
    {
        $periodStart = Carbon::parse($startDate)->startOfDay();
        $periodEnd   = Carbon::parse($endDate)->startOfDay();

        // --- Colonnes : uniquement les jours ouvrés (lundi-vendredi) ---
        $days = [];
        for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
            if ($d->dayOfWeekIso <= 5) {
                $days[] = [
                    'date'       => $d->format('Y-m-d'),
                    'day_short'  => self::JOURS_COURTS[$d->dayOfWeekIso],
                    'day_number' => $d->format('d'),
                ];
            }
        }

        // --- Employés ---
        $employeesQuery = Employee::whereNotNull('emp_code')->where('emp_code', '!=', '');
        if ($empCode && $empCode !== 'all') {
            $employeesQuery->where('emp_code', $empCode);
        }
        $employees = $this->filterEmployeesByDepartment(
            $employeesQuery->orderBy('emp_code')->get(),
            $departmentIds
        );

        // --- Sources annexes, chargées en une fois pour toute la période ---
        $attendances = DailyAttendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $leaves = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->get()
            ->groupBy('employee_id');

        $missions = Mission::whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->get()
            ->groupBy('employee_id');

        $permissions = EmployeePermission::where('status', 'approved')
            ->overlappingPeriod($startDate, $endDate)
            ->get()
            ->groupBy('employee_id');

        $rows = [];
        $totalRetards = 0;
        $totalMinutes = 0;

        foreach ($employees as $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect())
                ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->format('Y-m-d'));

            $missionDates    = $this->joursCouverts($missions->get($employee->id, collect()), $periodStart, $periodEnd,
                fn ($m) => [$m->start_date, $m->end_date]);
            $leaveDates      = $this->joursCouverts($leaves->get($employee->id, collect()), $periodStart, $periodEnd,
                fn ($l) => [$l->start_date, $l->end_date]);
            $permissionDates = $this->joursCouverts($permissions->get($employee->id, collect()), $periodStart, $periodEnd,
                fn ($p) => [$p->getEffectiveStartDate(), $p->getEffectiveEndDate()]);

            $cells        = [];
            $nbRetards    = 0;
            $minutesTotal = 0;

            foreach ($days as $day) {
                $dateKey    = $day['date'];
                $attendance = $employeeAttendances->get($dateKey);

                // Justifications d'abord : congé > mission > autorisation.
                if (isset($leaveDates[$dateKey])) {
                    $cells[$dateKey] = ['text' => 'en congé', 'detail' => '', 'type' => 'leave'];
                    continue;
                }
                if (isset($missionDates[$dateKey])) {
                    $cells[$dateKey] = ['text' => 'en mission', 'detail' => '', 'type' => 'mission'];
                    continue;
                }
                if (isset($permissionDates[$dateKey])) {
                    $cells[$dateKey] = ['text' => 'autorisation', 'detail' => '', 'type' => 'permission'];
                    continue;
                }

                if (!$attendance || strtoupper($attendance->status) === 'ABSENT') {
                    $cells[$dateKey] = ['text' => 'absent', 'detail' => '', 'type' => 'absent'];
                    continue;
                }

                // Employé présent : la cellule porte les horaires d'arrivée et de
                // départ, l'anomalie éventuelle (retard, sortie) passe en second.
                $lateData = $this->calculateLateFromPlanning($employee, $attendance, $dateKey);
                $details  = [];

                if ($lateData['is_late']) {
                    $nbRetards++;
                    $minutesTotal += (int) $lateData['late_minutes'];
                    $details[] = (int) $lateData['late_minutes'] . ' mn';
                }

                if (strtoupper($attendance->status) === 'EARLY_LEAVE' || !empty($attendance->is_early_leave)) {
                    $details[] = 'sortie';
                }

                $cells[$dateKey] = [
                    'text'   => $this->plageHoraire($attendance),
                    'detail' => implode(' / ', $details),
                    'type'   => $lateData['is_late'] ? 'late' : (empty($details) ? 'ok' : 'early'),
                ];
            }

            $totalRetards += $nbRetards;
            $totalMinutes += $minutesTotal;

            $rows[] = [
                'employee_code'   => $employee->emp_code,
                'employee_name'   => trim($employee->first_name . ' ' . ($employee->last_name ?? '')),
                'department_name' => $employee->dept_name ?? 'Non défini',
                'cells'           => $cells,
                'total_retards'   => $nbRetards,
                'total_minutes'   => $minutesTotal,
            ];
        }

        $moisDebut = $periodStart->locale('fr')->monthName;
        $moisFin   = $periodEnd->locale('fr')->monthName;

        return [
            'days'   => $days,
            'rows'   => $rows,
            'totals' => ['retards' => $totalRetards, 'minutes' => $totalMinutes],
            'month_label' => $moisDebut === $moisFin
                ? 'Mois de ' . $moisDebut . ' ' . $periodStart->format('Y')
                : 'Du ' . $moisDebut . ' ' . $periodStart->format('Y') . ' à ' . $moisFin . ' ' . $periodEnd->format('Y'),
            'period_label' => 'Période du ' . $periodStart->locale('fr')->dayName . ' ' . $periodStart->format('d')
                . ' ' . $moisDebut . ' au ' . $periodEnd->locale('fr')->dayName . ' ' . $periodEnd->format('d')
                . ' ' . $moisFin . ' ' . $periodEnd->format('Y'),
        ];
    }

    /**
     * Horaires d'arrivée et de départ d'un pointage, au format « 08:30 , 17:00 ».
     *
     * Un horaire manquant est rendu par « --:-- » pour que la cellule reste lisible.
     */
    private function plageHoraire($attendance): string
    {
        $format = function ($valeur) {
            if (!$valeur) {
                return '--:--';
            }

            return $valeur instanceof Carbon
                ? $valeur->format('H:i')
                : Carbon::parse($valeur)->format('H:i');
        };

        return $format($attendance->check_in) . ' , ' . $format($attendance->check_out);
    }

    /**
     * Jours (Y-m-d) couverts par une collection d'enregistrements, bornés à la période.
     *
     * Les instances Carbon sont recopiées : Carbon::max()/min() renvoient l'un
     * des deux objets reçus, et la boucle muterait alors les bornes partagées.
     *
     * @param  callable $bornes  Renvoie [début, fin] pour un enregistrement.
     * @return array<string, true>
     */
    private function joursCouverts($records, Carbon $periodStart, Carbon $periodEnd, callable $bornes): array
    {
        $jours = [];

        foreach ($records as $record) {
            [$debut, $fin] = $bornes($record);

            if (!$debut) {
                continue;
            }

            $jour = Carbon::parse($debut)->startOfDay();
            $last = Carbon::parse($fin ?: $debut)->startOfDay();

            if ($jour->lt($periodStart)) {
                $jour = $periodStart->copy();
            }
            if ($last->gt($periodEnd)) {
                $last = $periodEnd->copy();
            }

            while ($jour->lte($last)) {
                $jours[$jour->format('Y-m-d')] = true;
                $jour->addDay();
            }
        }

        return $jours;
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

            // Marge de tolérance configurable : en deçà, pas de retard.
            $toleranceMinutes = Setting::lateToleranceMinutes();

            if ($checkIn->gt($plannedStart)) {
                $diffMinutes = $checkIn->diffInMinutes($plannedStart);
                if ($diffMinutes > $toleranceMinutes) {
                    $result['is_late'] = true;
                    $result['late_minutes'] = $diffMinutes;
                }
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