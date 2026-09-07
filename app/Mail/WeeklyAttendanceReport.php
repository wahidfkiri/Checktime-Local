<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\SimpleXlsxWriter;

class WeeklyAttendanceReport extends Mailable
{
    use Queueable, SerializesModels;

    public array $emailData;

    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    public function build(): static
    {
        $employee   = $this->emailData['employee'];
        $startDate  = $this->emailData['start_date'];
        $endDate    = $this->emailData['end_date'];
        $clientName = $this->emailData['client_name'];

        // ── Génération du PDF avec la même view que exportCustomPdfByDept ──
        // On passe exactement les mêmes variables que le controller utilise,
        // mais pour un seul employé au lieu d'un département complet.
        $pdf = Pdf::loadView('reports.exports.weekly-attendance-employee-pdf', [
            // Variables identiques au controller
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'export_date'      => $this->emailData['export_date'],
            'client'           => $this->emailData['client'],
            'days_list'        => $this->emailData['days_list'],
            'period_days'      => $this->emailData['working_days'],
            'week_range'       => $this->emailData['week_range'],

            // Données de l'employé — même structure que $department['employees'][n]
            'employee_data'    => $this->emailData['employee_data'],
            'client_name'      => $clientName,
        ])->setPaper('A4', 'landscape');

        $safeCode = strtolower(str_replace([' ', '/'], '_', $employee->emp_code));
        $pdfFileName  = "rapport_presence_{$safeCode}_{$this->emailData['export_date']->format('Y-m-d')}.pdf";
        $xlsxFileName = "rapport_presence_{$safeCode}_{$this->emailData['export_date']->format('Y-m-d')}.xlsx";

        $employeeName = trim($employee->first_name . ' ' . $employee->last_name);

        return $this
            ->subject("Votre rapport de présence — {$startDate} au {$endDate}")
            ->view('emails.weekly-attendance-employee-pdf')
            ->with([
                'employeeName' => $employeeName,
                'clientName'   => $clientName,
                'startDate'    => $startDate,
                'endDate'      => $endDate,
                'stats'        => $this->emailData['employee_data']['stats'],
                'observations' => $this->emailData['employee_data']['observations'],
            ])
            ->attachData($pdf->output(), $pdfFileName, [
                'mime' => 'application/pdf',
            ])
            ->attachData($this->buildExcel($employeeName), $xlsxFileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }

    /**
     * Construit le classeur Excel joint au mail (mêmes données que le PDF :
     * grille jour par jour de l'employé, plus le résumé de la semaine).
     */
    private function buildExcel(string $employeeName): string
    {
        $xlsx = new SimpleXlsxWriter('Mon rapport hebdo');
        $xlsx->setColumnWidths([12, 12, 14, 10, 10, 30]);

        $xlsx->addRow(['Rapport de présence hebdomadaire'], true);
        $xlsx->addRow([$employeeName]);
        $xlsx->addRow(['Période : ' . $this->emailData['week_range']]);
        $xlsx->addRow([]);

        $xlsx->addRow(['Date', 'Jour', 'Statut', 'Entrée', 'Sortie'], true);

        $dailyChecks = $this->emailData['employee_data']['daily_checks'] ?? [];
        foreach ($this->emailData['days_list'] ?? [] as $day) {
            $check = $dailyChecks[$day['date_str']] ?? null;

            if (!$check) {
                $statut = 'Absent';
                $checkIn = $checkOut = '';
            } elseif (!empty($check['is_mission'])) {
                $statut = 'Mission';
                $checkIn = $checkOut = '';
            } elseif (!empty($check['is_leave'])) {
                $statut = 'Congé';
                $checkIn = $checkOut = '';
            } else {
                $statut = $check['status'] ?? '';
                $checkIn = $check['check_in'] ?? '';
                $checkOut = $check['check_out'] ?? '';
            }

            $xlsx->addRow([$day['date_str'], $day['day_name'], $statut, $checkIn, $checkOut]);
        }

        $xlsx->addRow([]);
        $stats = $this->emailData['employee_data']['stats'] ?? [];
        $xlsx->addRow(['Résumé'], true);
        $xlsx->addRow(['Présents', $stats['present'] ?? 0]);
        $xlsx->addRow(['Absents', $stats['absent'] ?? 0]);
        $xlsx->addRow(['Retards', $stats['late'] ?? 0]);
        $xlsx->addRow(['Départs anticipés', $stats['early_leave'] ?? 0]);
        $xlsx->addRow(['Demi-journées', $stats['half_day'] ?? 0]);
        $xlsx->addRow(['Missions', $stats['mission'] ?? 0]);
        $xlsx->addRow(['Congés', $stats['leave'] ?? 0]);
        $xlsx->addRow(['Taux de présence %', $stats['presence_rate'] ?? 0]);
        $xlsx->addRow(['Taux de ponctualité %', $stats['ponctualite_rate'] ?? 0]);
        $xlsx->addRow(['Observations', $this->emailData['employee_data']['observations'] ?? '']);

        return $xlsx->build();
    }
}