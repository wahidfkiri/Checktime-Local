<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\DailyAttendance;
use App\Models\Mission;
use App\Models\Leave;
use App\Models\Setting;
use App\Mail\WeeklyRHAttendanceReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWeeklyRHReports extends Command
{
    protected $signature = 'attendance:send-weekly-rh-reports 
                            {--date= : Date de référence pour le rapport (format Y-m-d)}';
    
    protected $description = 'Envoyer les rapports de présence hebdomadaires aux RH (email settings)';

    /**
     * Check if SMTP is properly configured.
     */
    private function isSmtpConfigured(): bool
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $mailer = config('mail.default');

        return $mailer === 'smtp' && !empty($host) && !empty($port);
    }

    public function handle()
    {
        // Vérifier la configuration SMTP
        if (!$this->isSmtpConfigured()) {
            $this->warn('⚠️  SMTP non configuré. Les rapports par email ne peuvent pas être envoyés.');
            $this->info('💡  Configurez SMTP dans l\'installateur (/install) ou dans le fichier .env');
            Log::warning('Rapports RH hebdomadaires annulés: SMTP non configuré');
            return Command::SUCCESS;
        }

        $this->info('🚀 Début de l\'envoi des rapports RH hebdomadaires...');

        $referenceDate = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::now();

        $this->info("📅 Date de référence: " . $referenceDate->format('d/m/Y'));

        $currentDayOfWeek = $referenceDate->dayOfWeekIso;

        if ($currentDayOfWeek == 7 || $currentDayOfWeek == 6) {
            $startOfWeek = $referenceDate->copy()->previous(Carbon::MONDAY);
            $endOfWeek   = $startOfWeek->copy()->addDays(4);
        } else {
            $startOfWeek = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = $startOfWeek->copy()->addDays(4);
        }

        $startDate = $startOfWeek->toDateString();
        $endDate   = $endOfWeek->toDateString();

        $this->info("📊 Période du rapport: {$startOfWeek->format('d/m/Y')} au {$endOfWeek->format('d/m/Y')}");

        $workingDays = $this->countWorkingDays($startDate, $endDate);
        $this->info("📆 Jours ouvrés (Lun-Ven): {$workingDays}");
        
        if ($workingDays == 0) {
            $this->warn("⚠️  Aucun jour ouvré dans la période sélectionnée");
            return 0;
        }

        $daysList = $this->buildDaysList($startDate, $endDate);

        // Récupérer les paramètres globaux
        $settings = Setting::first();
        
        if (!$settings || !$settings->email_is_active) {
            $this->warn("⚠️  Email RH non activé dans les paramètres");
            return 0;
        }

        $rhEmail = $settings->email ?? null;
        if (!$rhEmail) {
            $this->warn("⚠️  Pas d'email RH configuré dans les paramètres");
            return 0;
        }

        $this->info("\n--- Rapport RH pour: {$rhEmail} ---");

        // Récupération des données pour la période
        $allAttendances = DailyAttendance::whereBetween('attendance_date', [$startDate, $endDate])->get();
        $this->info("📊 Pointages trouvés: " . $allAttendances->count());

        $allMissions = Mission::where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $startDate)
                                        ->where('end_date', '>=', $endDate));
        })->get();

        $allLeaves = Leave::with('type')
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $startDate)
                                            ->where('end_date', '>=', $endDate));
            })->get();

        // Indexation par employee_id
        $attendanceByEmployee = [];
        foreach ($allAttendances as $att) {
            $attendanceByEmployee[$att->employee_id][] = $att;
        }

        $missionsByEmployee = [];
        foreach ($allMissions as $mission) {
            $missionsByEmployee[$mission->employee_id][] = $mission;
        }

        $leavesByEmployee = [];
        foreach ($allLeaves as $leave) {
            $leavesByEmployee[$leave->employee_id][] = $leave;
        }

        // Employés
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->orderBy('dept_name')
            ->orderBy('first_name')
            ->get();

        $this->info("👥 Employés trouvés: " . $employees->count());

        if ($employees->isEmpty()) {
            $this->warn("⚠️  Aucun employé trouvé");
            return 0;
        }

        // Construction des données par département
        $departmentsData = [];
        $globalTotals = [
            'total_employees'    => 0,
            'total_present'      => 0,
            'total_absent'       => 0,
            'total_on_time'      => 0,
            'total_late'         => 0,
            'total_early_leave'  => 0,
            'total_mission'      => 0,
            'total_leave'        => 0,
            'total_presence_sum' => 0,
            'total_ponctualite_sum' => 0,
        ];

        $employeesByDept = [];
        foreach ($employees as $employee) {
            $deptName = $employee->dept_name ?: 'Sans département';
            if (!isset($employeesByDept[$deptName])) {
                $employeesByDept[$deptName] = [];
            }
            $employeesByDept[$deptName][] = $employee;
        }

        foreach ($employeesByDept as $deptName => $deptEmployees) {
            $deptData = [
                'department_name'       => $deptName,
                'total_employees'       => count($deptEmployees),
                'employees'             => [],
                'total_present'         => 0,
                'total_absent'          => 0,
                'total_on_time'         => 0,
                'total_late'            => 0,
                'total_early_leave'     => 0,
                'total_mission'         => 0,
                'total_leave'           => 0,
                'avg_presence_rate'     => 0,
                'avg_ponctualite_rate'  => 0,
            ];

            foreach ($deptEmployees as $employee) {
                $employeeAttendances = $attendanceByEmployee[$employee->id] ?? [];
                $employeeMissions    = $missionsByEmployee[$employee->id] ?? [];
                $employeeLeaves      = $leavesByEmployee[$employee->id] ?? [];

                $missionDates = [];
                foreach ($employeeMissions as $mission) {
                    $cur = Carbon::parse($mission->start_date);
                    $end = Carbon::parse($mission->end_date);
                    while ($cur->lte($end)) {
                        if ($cur->dayOfWeekIso <= 5) {
                            $missionDates[$cur->format('Y-m-d')] = [
                                'title'       => $mission->title,
                                'destination' => $mission->destination,
                            ];
                        }
                        $cur->addDay();
                    }
                }

                $leaveDates = [];
                foreach ($employeeLeaves as $leave) {
                    $cur      = Carbon::parse($leave->start_date);
                    $end      = Carbon::parse($leave->end_date);
                    $typeName = $leave->type ? $leave->type->name : 'Congé';
                    while ($cur->lte($end)) {
                        if ($cur->dayOfWeekIso <= 5 && !isset($missionDates[$cur->format('Y-m-d')])) {
                            $leaveDates[$cur->format('Y-m-d')] = ['type_name' => $typeName];
                        }
                        $cur->addDay();
                    }
                }

                $dailyChecks = $this->buildDailyChecks(
                    $startDate, $endDate, $employeeAttendances, $missionDates, $leaveDates
                );

                $stats = $this->calculateEmployeeStats(
                    $workingDays, $employeeAttendances, $missionDates, $leaveDates
                );

                $deptData['total_present']     += $stats['present'];
                $deptData['total_absent']      += $stats['absent'];
                $deptData['total_on_time']     += $stats['on_time'];
                $deptData['total_late']        += $stats['late'];
                $deptData['total_early_leave'] += $stats['early_leave'];
                $deptData['total_mission']     += $stats['mission'];
                $deptData['total_leave']       += $stats['leave'];

                $observations = $this->buildObservations(
                    $employeeAttendances, $missionDates, $leaveDates
                );

                $deptData['employees'][] = [
                    'employee_code' => $employee->emp_code,
                    'employee_name' => trim($employee->first_name . ' ' . $employee->last_name),
                    'daily_checks'  => $dailyChecks,
                    'stats'         => $stats,
                    'observations'  => !empty($observations)
                        ? implode(', ', array_slice($observations, 0, 3))
                        : 'Aucune observation',
                ];
            }

            $totalPresentAbsent = $deptData['total_present'] + $deptData['total_absent'];
            $deptData['avg_presence_rate'] = $totalPresentAbsent > 0
                ? round(($deptData['total_present'] / $totalPresentAbsent) * 100, 1) : 0;

            $totalPonctualite = $deptData['total_on_time'] + $deptData['total_late'] + $deptData['total_early_leave'];
            $deptData['avg_ponctualite_rate'] = $totalPonctualite > 0
                ? round(($deptData['total_on_time'] / $totalPonctualite) * 100, 1) : 0;

            $departmentsData[] = $deptData;

            $globalTotals['total_employees']       += $deptData['total_employees'];
            $globalTotals['total_present']         += $deptData['total_present'];
            $globalTotals['total_absent']          += $deptData['total_absent'];
            $globalTotals['total_on_time']         += $deptData['total_on_time'];
            $globalTotals['total_late']            += $deptData['total_late'];
            $globalTotals['total_early_leave']     += $deptData['total_early_leave'];
            $globalTotals['total_mission']         += $deptData['total_mission'];
            $globalTotals['total_leave']           += $deptData['total_leave'];
            $globalTotals['total_presence_sum']    += $deptData['avg_presence_rate']    * $deptData['total_employees'];
            $globalTotals['total_ponctualite_sum'] += $deptData['avg_ponctualite_rate'] * $deptData['total_employees'];
        }

        $globalTotals['avg_presence_rate'] = $globalTotals['total_employees'] > 0
            ? round($globalTotals['total_presence_sum'] / $globalTotals['total_employees'], 1) : 0;
        $globalTotals['avg_ponctualite_rate'] = $globalTotals['total_employees'] > 0
            ? round($globalTotals['total_ponctualite_sum'] / $globalTotals['total_employees'], 1) : 0;

        if ($globalTotals['total_employees'] == 0) {
            $this->warn("⚠️  Aucune donnée à envoyer");
            return 0;
        }

        $reportData = [
            'departments'       => $departmentsData,
            'totals'            => $globalTotals,
            'days_list'         => $daysList,
            'period_days'       => $workingDays,
            'total_departments' => count($departmentsData),
        ];

        try {
            Mail::to($rhEmail)->send(new WeeklyRHAttendanceReport(
                $reportData,
                null,
                $startOfWeek->format('d/m/Y'),
                $endOfWeek->format('d/m/Y')
            ));

            $this->info("✅  Rapport envoyé à {$rhEmail}");
            Log::info("Rapport RH envoyé à {$rhEmail}");

        } catch (\Exception $e) {
            $this->error("❌  Erreur envoi à {$rhEmail}: " . $e->getMessage());
            Log::error("Erreur envoi rapport RH à {$rhEmail}: " . $e->getMessage());
        }

        $this->line('');
        $this->info("═══════════════════════════════════════════");
        $this->info("📋  RÉSUMÉ DE L'EXÉCUTION HEBDOMADAIRE RH");
        $this->info("═══════════════════════════════════════════");
        $this->info("Date d'exécution : " . $referenceDate->format('d/m/Y H:i'));
        $this->info("Période analysée : {$startOfWeek->format('d/m/Y')} au {$endOfWeek->format('d/m/Y')}");
        $this->info("Jours ouvrés     : {$workingDays}");
        $this->info("Employés traités : {$globalTotals['total_employees']}");
        
        Log::info("Rapports RH hebdomadaires terminés");
        
        return 0;
    }

    private function getDayNameFrench(int $dayOfWeekIso): string
    {
        return [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'][$dayOfWeekIso] ?? '';
    }

    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $days = 0;
        $cur = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($cur->lte($end)) {
            if ($cur->dayOfWeekIso >= 1 && $cur->dayOfWeekIso <= 5) $days++;
            $cur->addDay();
        }
        return $days;
    }

    private function buildDaysList(string $startDate, string $endDate): array
    {
        $daysList = [];
        $cur = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($cur->lte($end)) {
            if ($cur->dayOfWeekIso >= 1 && $cur->dayOfWeekIso <= 5) {
                $daysList[] = [
                    'date'     => $cur->copy(),
                    'date_str' => $cur->format('Y-m-d'),
                    'day_name' => $this->getDayNameFrench($cur->dayOfWeekIso),
                ];
            }
            $cur->addDay();
        }
        return $daysList;
    }

    private function buildDailyChecks($startDate, $endDate, $employeeAttendances, $missionDates, $leaveDates): array
    {
        $dailyChecks = [];
        $cur = Carbon::parse($startDate);
        $endObj = Carbon::parse($endDate);

        while ($cur->lte($endObj)) {
            $dateStr = $cur->format('Y-m-d');
            if ($cur->dayOfWeekIso > 5) { $cur->addDay(); continue; }

            $attendance = null;
            foreach ($employeeAttendances as $att) {
                $attDate = $att->attendance_date instanceof Carbon
                    ? $att->attendance_date->format('Y-m-d')
                    : date('Y-m-d', strtotime($att->attendance_date));
                if ($attDate === $dateStr) { $attendance = $att; break; }
            }

            $isMission = isset($missionDates[$dateStr]);
            $isLeave   = isset($leaveDates[$dateStr]);

            if ($isMission) {
                $dailyChecks[$dateStr] = [
                    'check_in' => null, 'check_out' => null, 'status' => 'MISSION',
                    'is_late' => false, 'is_early_leave' => false,
                    'mission_info' => $missionDates[$dateStr], 'leave_info' => null,
                ];
            } elseif ($isLeave) {
                $dailyChecks[$dateStr] = [
                    'check_in' => null, 'check_out' => null, 'status' => 'CONGE',
                    'is_late' => false, 'is_early_leave' => false,
                    'mission_info' => null, 'leave_info' => $leaveDates[$dateStr],
                ];
            } elseif ($attendance && strtoupper($attendance->status) !== 'ABSENT') {
                $checkIn = $attendance->check_in
                    ? ($attendance->check_in instanceof Carbon ? $attendance->check_in->format('H:i') : substr($attendance->check_in, 11, 5))
                    : null;
                $checkOut = $attendance->check_out
                    ? ($attendance->check_out instanceof Carbon ? $attendance->check_out->format('H:i') : substr($attendance->check_out, 11, 5))
                    : null;
                $dailyChecks[$dateStr] = [
                    'check_in' => $checkIn, 'check_out' => $checkOut, 'status' => $attendance->status,
                    'is_late' => (bool) $attendance->is_late, 'is_early_leave' => (bool) $attendance->is_early_leave,
                    'mission_info' => null, 'leave_info' => null,
                ];
            } else {
                $dailyChecks[$dateStr] = null;
            }

            $cur->addDay();
        }

        return $dailyChecks;
    }

    private function calculateEmployeeStats($workingDays, $employeeAttendances, $missionDates, $leaveDates): array
    {
        $totalPresent = $totalLate = $totalEarlyLeave = $totalHalfDay = $totalMission = $totalLeave = $totalOnTime = 0;

        foreach ($employeeAttendances as $att) {
            $status = strtoupper($att->status);
            if (Carbon::parse($att->attendance_date)->dayOfWeekIso > 5) continue;
            if ($status !== 'ABSENT') {
                $totalPresent++;
                if ($status === 'LATE') $totalLate++;
                if ($status === 'EARLY_LEAVE') $totalEarlyLeave++;
                if ($status === 'HALF_DAY') $totalHalfDay++;
                if ($status !== 'LATE' && $status !== 'EARLY_LEAVE' && $status !== 'HALF_DAY') $totalOnTime++;
            }
        }

        foreach ($missionDates as $dateStr => $m) {
            if (Carbon::parse($dateStr)->dayOfWeekIso <= 5) $totalMission++;
        }
        foreach ($leaveDates as $dateStr => $l) {
            if (Carbon::parse($dateStr)->dayOfWeekIso <= 5) $totalLeave++;
        }

        $totalPresentWithMissionLeave = $totalPresent + $totalMission + $totalLeave;
        $totalAbsent = max(0, $workingDays - $totalPresentWithMissionLeave);
        $presenceRate = $workingDays > 0 ? round(($totalPresentWithMissionLeave / $workingDays) * 100, 1) : 0;
        $ponctualiteRate = $totalPresent > 0 ? round((($totalPresent - $totalLate - $totalEarlyLeave) / $totalPresent) * 100, 1) : 0;

        return [
            'present' => $totalPresentWithMissionLeave, 'absent' => $totalAbsent,
            'late' => $totalLate, 'early_leave' => $totalEarlyLeave, 'half_day' => $totalHalfDay,
            'mission' => $totalMission, 'leave' => $totalLeave, 'on_time' => $totalOnTime,
            'presence_rate' => $presenceRate, 'ponctualite_rate' => $ponctualiteRate,
        ];
    }

    private function buildObservations($employeeAttendances, $missionDates, $leaveDates): array
    {
        $observations = [];
        foreach ($employeeAttendances as $att) {
            if (Carbon::parse($att->attendance_date)->dayOfWeekIso > 5) continue;
            $status = strtoupper($att->status);
            $date = Carbon::parse($att->attendance_date)->format('d/m');
            if ($status === 'HALF_DAY') $observations[] = 'Demi-journée le ' . $date;
            elseif ($status === 'LATE') $observations[] = 'Retard ' . ($att->late_minutes ?? 0) . ' min le ' . $date;
            elseif ($status === 'EARLY_LEAVE') $observations[] = 'Départ anticipé le ' . $date;
            elseif ($status === 'ABSENT') $observations[] = 'Absent le ' . $date;
        }
        foreach ($missionDates as $dateStr => $m) {
            $observations[] = 'Mission: ' . $m['title'] . ' (' . $m['destination'] . ') le ' . Carbon::parse($dateStr)->format('d/m');
        }
        foreach ($leaveDates as $dateStr => $l) {
            $observations[] = $l['type_name'] . ' le ' . Carbon::parse($dateStr)->format('d/m');
        }
        return $observations;
    }
}