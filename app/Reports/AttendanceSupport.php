<?php

namespace App\Reports;

use App\Models\EmployeeSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Règles de calcul partagées par les rapports de présence.
 *
 * Ces méthodes étaient privées à CustomReportController ; elles sont ici pour
 * que les rapports envoyés par email (commandes artisan) appliquent exactement
 * la même définition du « retard » et du filtrage par département que les
 * écrans, sans dupliquer la logique.
 */
class AttendanceSupport
{
    /**
     * Restreint une collection d'employés aux départements sélectionnés.
     *
     * Le nom de département est chiffré en base : la comparaison se fait donc
     * côté PHP, en sous-chaîne insensible à la casse.
     *
     * @param  \Illuminate\Support\Collection $employees
     * @param  array|null                     $departmentIds  ['all'] ou noms de départements
     * @return \Illuminate\Support\Collection
     */
    public static function filterByDepartment($employees, $departmentIds)
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
     * Retard d'un pointage par rapport à l'heure de début planifiée.
     *
     * @return array{is_late: bool, late_minutes: int}
     */
    public static function lateFromPlanning($employee, $attendance, string $dateKey): array
    {
        $result = [
            'is_late'      => false,
            'late_minutes' => 0,
        ];

        if (!$attendance || !$attendance->check_in || strtoupper($attendance->status) === 'ABSENT') {
            return $result;
        }

        $schedule = self::scheduleForDate($employee, $dateKey);

        if (!$schedule || !$schedule['is_working_day'] || !$schedule['start_time']) {
            return $result;
        }

        try {
            $plannedStartTime = Carbon::parse($schedule['start_time'])->format('H:i:s');
            $checkInTime = $attendance->check_in instanceof Carbon
                ? $attendance->check_in->format('H:i:s')
                : Carbon::parse($attendance->check_in)->format('H:i:s');

            $plannedStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $plannedStartTime);
            $checkIn      = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $checkInTime);

            // Marge de tolérance configurable : en deçà, pas de retard.
            $toleranceMinutes = Setting::lateToleranceMinutes();

            if ($checkIn->gt($plannedStart)) {
                $diffMinutes = $checkIn->diffInMinutes($plannedStart);

                if ($diffMinutes > $toleranceMinutes) {
                    $result['is_late']      = true;
                    $result['late_minutes'] = $diffMinutes;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erreur calcul retard custom report', [
                'employee_id' => $employee->id ?? null,
                'date'        => $dateKey,
                'message'     => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Planning applicable à un employé pour une date donnée.
     *
     * Ordre de résolution : date exacte, plage de dates, planning fixe du jour
     * de semaine, rotation, puis planning générique.
     */
    public static function scheduleForDate($employee, $dateStr)
    {
        if (!$employee) {
            return null;
        }

        $date      = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeekIso;

        // 1. Planning spécifique à la date exacte
        $specificSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $dateStr)
            ->first();

        if ($specificSchedule) {
            return self::formatSchedule($specificSchedule);
        }

        // 2. Planning dans une plage de dates
        $rangeSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($rangeSchedule) {
            return self::formatSchedule($rangeSchedule);
        }

        // 3. Planning fixe par jour de semaine
        $fixedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'fixe')
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($fixedSchedule) {
            return self::formatSchedule($fixedSchedule);
        }

        // 4. Planning rotation
        $rotationSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'rotation')
            ->first();

        if ($rotationSchedule && $rotationSchedule->start_date && $rotationSchedule->end_date) {
            $scheduleStart = Carbon::parse($rotationSchedule->start_date);
            $scheduleEnd   = Carbon::parse($rotationSchedule->end_date);
            $currentDate   = Carbon::parse($dateStr);

            if ($currentDate->between($scheduleStart, $scheduleEnd)) {
                $daysFromStart   = $scheduleStart->diffInDays($currentDate);
                $workDaysCount   = $rotationSchedule->work_days_count ?? 1;
                $restDaysCount   = $rotationSchedule->rest_days_count ?? 0;
                $cycleLength     = $workDaysCount + $restDaysCount;
                $positionInCycle = $daysFromStart % $cycleLength;

                if ($positionInCycle < $workDaysCount) {
                    return self::formatSchedule($rotationSchedule);
                }

                return [
                    'schedule_type'  => 'rotation',
                    'is_working_day' => false,
                    'start_time'     => null,
                    'end_time'       => null,
                ];
            }
        }

        // 5. Planning planifié (générique)
        $plannedSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'planifie')
            ->first();

        if ($plannedSchedule) {
            return self::formatSchedule($plannedSchedule);
        }

        return null;
    }

    /**
     * Normalise un enregistrement de planning.
     */
    public static function formatSchedule($schedule): array
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
}
