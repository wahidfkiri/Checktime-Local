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
use App\Models\Holiday;
use App\Reports\AttendanceSupport;
use App\Reports\PresencePonctualiteColumns;
use App\Reports\SuiviPonctualiteReport;
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
        return AttendanceSupport::filterByDepartment($employees, $departmentIds);
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
        // calendaires si le modèle choisi inclut les week-ends — jours fériés
        // chômés toujours exclus dans les deux cas (voir countWorkingDays()).
        $workingDays  = $this->countWorkingDays($startDate, $endDate, $includeWeekends);
        $holidayDates = $this->getNonWorkingHolidayDates($startDate, $endDate);

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
                // Un jour férié chômé reste exclu dans tous les cas (il n'est
                // jamais compté dans $workingDays, voir countWorkingDays()).
                if ((!$includeWeekends && Carbon::parse($dateKey)->dayOfWeekIso > 5)
                    || isset($holidayDates[$dateKey])) {
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
                if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                    && !isset($holidayDates[$dateStr])) {
                    $totalPresent++;
                }
            }

            foreach ($leaveDates as $dateStr => $leave) {
                if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                    && !isset($holidayDates[$dateStr])
                    && !isset($missionDates[$dateStr])) {
                    $totalPresent++;
                }
            }

            // Une autorisation d'absence ne compte pas comme une absence
            // (même traitement que mission / congé), sans double comptage.
            foreach ($permissionDates as $dateStr => $permission) {
                if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                    && !isset($holidayDates[$dateStr])
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
                    && !isset($holidayDates[$dateStr])
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

            $workingDays  = $this->countWorkingDays($startDate, $endDate, $includeWeekends);
            $holidayDates = $this->getNonWorkingHolidayDates($startDate, $endDate);
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
                    // modèle choisi inclut explicitement les week-ends. Un jour
                    // férié chômé reste exclu dans tous les cas.
                    if ((!$includeWeekends && Carbon::parse($dateKey)->dayOfWeekIso > 5)
                        || isset($holidayDates[$dateKey])) {
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
                    if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                        && !isset($holidayDates[$dateStr])) {
                        $totalMission++;
                    }
                }
                foreach ($leaveDates as $dateStr => $leave) {
                    if (($includeWeekends || Carbon::parse($dateStr)->dayOfWeekIso <= 5)
                        && !isset($holidayDates[$dateStr])
                        && !isset($missionDates[$dateStr])) {
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
                // toute la période choisie et on saute le week-end, ainsi que
                // les jours fériés chômés (jamais affichés, comme dans
                // $workingDays — voir countWorkingDays()).
                if (($includeWeekends || $currentDate->dayOfWeekIso <= 5)
                    && !isset($holidayDates[$currentDate->format('Y-m-d')])) {
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

        $pdf = (new SuiviPonctualiteReport())->pdf($data);

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

        return (new SuiviPonctualiteReport())->excelDownload(
            $report,
            'suivi_ponctualite_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
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
        if ($jours > SuiviPonctualiteReport::MAX_JOURS) {
            return ['error' => 'La période ne doit pas dépasser un mois (' . SuiviPonctualiteReport::MAX_JOURS . ' jours).'];
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
     * Grille du Tableau de Suivi de la Ponctualité.
     * La construction vit dans SuiviPonctualiteReport : les rapports envoyés
     * par email joignent exactement la même grille.
     */
    private function buildSuiviPonctualiteData($startDate, $endDate, $empCode, $departmentIds): array
    {
        return (new SuiviPonctualiteReport())->build($startDate, $endDate, $empCode, $departmentIds);
    }

    /**
     * Calculer le retard en comparant check-in avec l'heure de début du planning.
     * Règle partagée avec les rapports envoyés par email (cf. AttendanceSupport).
     */
    private function calculateLateFromPlanning($employee, $attendance, string $dateKey): array
    {
        return AttendanceSupport::lateFromPlanning($employee, $attendance, $dateKey);
    }

    /**
     * Planning applicable à un employé pour une date donnée.
     */
    private function getEmployeeScheduleForDateNew($employee, $dateStr)
    {
        return AttendanceSupport::scheduleForDate($employee, $dateStr);
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
     * Jours fériés chômés compris dans une période, indexés par date
     * (Y-m-d) pour un test en O(1). Évite de ré-interroger la base à
     * chaque jour et pour chaque employé dans les boucles de calcul de
     * présence (une même date est testée une fois par employé sinon).
     */
    private function getNonWorkingHolidayDates($startDate, $endDate): array
    {
        $dates   = [];
        $current = Carbon::parse($startDate);
        $end     = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            if (Holiday::isNonWorkingHoliday($dateStr)) {
                $dates[$dateStr] = true;
            }
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Compter les jours d'une période, jours fériés chômés toujours exclus :
     * seulement les jours ouvrés (lundi-vendredi) par défaut, ou tous les
     * jours calendaires si $includeWeekends est vrai (un jour férié chômé
     * reste exclu même dans ce cas, contrairement à un simple week-end).
     */
    private function countWorkingDays($startDate, $endDate, $includeWeekends = false)
    {
        $start         = Carbon::parse($startDate);
        $end           = Carbon::parse($endDate);
        $holidayDates  = $this->getNonWorkingHolidayDates($startDate, $endDate);
        $workingDays   = 0;

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            if (isset($holidayDates[$date->format('Y-m-d')])) {
                continue;
            }
            if ($includeWeekends || ($date->dayOfWeekIso >= 1 && $date->dayOfWeekIso <= 5)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}