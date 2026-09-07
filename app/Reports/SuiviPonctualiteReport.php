<?php

namespace App\Reports;

use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\EmployeePermission;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Mission;
use App\Models\SignatairePoste;
use App\Support\SimpleXlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;

/**
 * Tableau de Suivi de la Ponctualité (grille employés × jours ouvrés).
 *
 * Utilisé par l'écran /rapport/suivi-ponctualite et par les rapports envoyés
 * par email (hebdomadaire RH et mensuel RH), qui joignent la même grille en
 * PDF et en Excel. Un seul jeu de règles pour les trois usages.
 */
class SuiviPonctualiteReport
{
    /**
     * Abréviations des jours utilisées en tête de colonne (Lund, Mard, …).
     */
    public const JOURS_COURTS = [
        1 => 'Lund', 2 => 'Mard', 3 => 'Merc', 4 => 'Jeud', 5 => 'Vend', 6 => 'Sam', 7 => 'Dim',
    ];

    /** Période maximale couverte par le tableau. */
    public const MAX_JOURS = 31;

    /**
     * Construit la grille employés × jours ouvrés.
     *
     * Une cellule porte, par ordre de priorité : congé, mission, autorisation
     * d'absence, absence non justifiée, retard (en minutes), sortie anticipée.
     * Une journée normale reste vide, comme sur le formulaire papier.
     *
     * @return array{days: array, rows: array, totals: array, month_label: string, period_label: string}
     */
    public function build($startDate, $endDate, $empCode = 'all', $departmentIds = ['all']): array
    {
        $periodStart = Carbon::parse($startDate)->startOfDay();
        $periodEnd   = Carbon::parse($endDate)->startOfDay();

        // --- Colonnes : uniquement les jours ouvrés (lundi-vendredi hors jours fériés chômés) ---
        $days = [];
        for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
            if ($d->dayOfWeekIso <= 5 && !Holiday::isNonWorkingHoliday($d->format('Y-m-d'))) {
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
        $employees = AttendanceSupport::filterByDepartment(
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
                $lateData = AttendanceSupport::lateFromPlanning($employee, $attendance, $dateKey);
                $details  = [];

                if ($lateData['is_late']) {
                    $nbRetards++;
                    $minutesTotal += (int) $lateData['late_minutes'];
                    $details[] = (int) $lateData['late_minutes'] . ' mn';
                }

                if (strtoupper($attendance->status) === 'EARLY_LEAVE' || !empty($attendance->is_early_leave)) {
                    $details[] = 'Incomplet';
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
     * Rend la grille en PDF (A4 paysage), mise en page identique à l'export
     * proposé sur la page.
     */
    public function pdf(array $report)
    {
        $pdf = Pdf::loadView('reports.suivi-ponctualite.exports.pdf', [
            'report'           => $report,
            'export_date'      => Carbon::now(),
            'signatairePostes' => $this->signatairePostes(),
        ]);

        return $pdf->setPaper('A4', 'landscape');
    }

    /**
     * Contenu binaire du classeur Excel (mêmes colonnes que le PDF).
     */
    public function excel(array $report): string
    {
        return $this->xlsx($report)->build();
    }

    /**
     * Réponse de téléchargement du classeur Excel (usage écran).
     */
    public function excelDownload(array $report, string $filename)
    {
        return $this->xlsx($report)->download($filename);
    }

    /**
     * Joint la grille de la période à un email de rapport, en PDF et en Excel.
     *
     * Utilisé par les rapports RH hebdomadaire et mensuel. Les pièces jointes
     * sont ajoutées avant l'envoi : Mailable::build() conserve les pièces
     * déclarées en amont.
     *
     * @param  string $filenameSuffix  Suffixe des noms de fichiers, ex. « 2026_09 ».
     * @return bool  false si la période ne contient aucune donnée (rien n'est joint).
     */
    public function attachTo(Mailable $mail, $startDate, $endDate, string $filenameSuffix): bool
    {
        $report = $this->build($startDate, $endDate);

        if (self::isEmpty($report)) {
            return false;
        }

        $mail->attachData(
            $this->pdf($report)->output(),
            'Suivi_Ponctualite_' . $filenameSuffix . '.pdf',
            ['mime' => 'application/pdf']
        );

        $mail->attachData(
            $this->excel($report),
            'Suivi_Ponctualite_' . $filenameSuffix . '.xlsx',
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        return true;
    }

    /**
     * Postes de signataires du cartouche de signatures en fin de PDF.
     */
    public function signatairePostes()
    {
        return SignatairePoste::with('signataires')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Vrai si la grille ne contient aucune ligne exploitable : inutile de
     * joindre une pièce jointe vide à un email.
     */
    public static function isEmpty(array $report): bool
    {
        return empty($report['rows']) || empty($report['days']);
    }

    // ────────────────────────────────────────────────────────────────

    /**
     * Classeur Excel de la grille (feuille unique, impression en paysage).
     */
    private function xlsx(array $report): SimpleXlsxWriter
    {
        $nbJours = count($report['days']);

        $xlsx = new SimpleXlsxWriter('Suivi ponctualité');
        $xlsx->setLandscape();
        $xlsx->setColumnWidths(array_merge([32], array_fill(0, $nbJours, 6), [9, 9]));

        $xlsx->addRow(['Tableau de Suivi de la Ponctualité'], true);
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

        return $xlsx;
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
}
