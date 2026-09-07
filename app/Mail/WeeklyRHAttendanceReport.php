<?php
// app/Mail/WeeklyRHAttendanceReport.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\SimpleXlsxWriter;

class WeeklyRHAttendanceReport extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;
    public $client;
    public $startDate;
    public $endDate;
    public $exportDate;

    public function __construct(array $reportData, $client, string $startDate, string $endDate)
    {
        $this->reportData = $reportData;
        $this->client = $client;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->exportDate = now();
    }

    public function build(): static
    {
        // Génération du PDF avec les deux tableaux
        $pdf = Pdf::loadView('reports.exports.weekly-rh-attendance-pdf', [
            'report_data'    => $this->reportData['departments'] ?? [],
            'totals'         => $this->reportData['totals'] ?? [],
            'days_list'      => $this->reportData['days_list'] ?? [],
            'start_date'     => $this->startDate,
            'end_date'       => $this->endDate,
            'period_days'    => $this->reportData['period_days'] ?? 0,
            'total_departments' => $this->reportData['total_departments'] ?? 0,
            'client'         => $this->client,
            'export_date'    => $this->exportDate,
        ])->setPaper('A4', 'landscape');

        $pdfFileName  = "rapport_presence_rh_{$this->exportDate->format('Y-m-d')}.pdf";
        $xlsxFileName = "rapport_presence_rh_{$this->exportDate->format('Y-m-d')}.xlsx";

        $clientName = $this->client->raison_sociale ?? config('app.name', 'CheckTime');

        return $this
            ->subject("Rapport de présence hebdomadaire — {$this->startDate} au {$this->endDate}")
            ->view('emails.weekly-rh-attendance-report')
            ->with([
                'clientName'    => $clientName,
                'startDate'     => $this->startDate,
                'endDate'       => $this->endDate,
                'totalEmployees'=> $this->reportData['totals']['total_employees'] ?? 0,
                'avgPresenceRate'=> $this->reportData['totals']['avg_presence_rate'] ?? 0,
                'totalDepartments'=> $this->reportData['total_departments'] ?? 0,
            ])
            ->attachData($pdf->output(), $pdfFileName, [
                'mime' => 'application/pdf',
            ])
            ->attachData($this->buildExcel(), $xlsxFileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }

    /**
     * Construit le classeur Excel joint au mail (mêmes données que le PDF :
     * détail par employé, groupé par département).
     */
    private function buildExcel(): string
    {
        $xlsx = new SimpleXlsxWriter('Présence RH hebdo');
        $xlsx->setLandscape();
        $xlsx->setColumnWidths([22, 10, 8, 8, 8, 12, 8, 9, 9, 11, 12, 30]);

        $xlsx->addRow(['Rapport de présence hebdomadaire RH'], true);
        $xlsx->addRow(['Période : ' . $this->startDate . ' au ' . $this->endDate
            . ' (' . ($this->reportData['period_days'] ?? 0) . ' jours ouvrés)']);
        $xlsx->addRow([]);

        $xlsx->addRow([
            'Employé', 'Code', 'Présents', 'Absents', 'Retards', 'Départs anticipés',
            'Demi-j.', 'Missions', 'Congés', 'Taux présence %', 'Taux ponctualité %', 'Observations',
        ], true);

        foreach ($this->reportData['departments'] ?? [] as $dept) {
            $xlsx->addRow([$dept['department_name'] ?? ''], true);

            foreach ($dept['employees'] ?? [] as $emp) {
                $s = $emp['stats'] ?? [];
                $xlsx->addRow([
                    $emp['employee_name'] ?? '',
                    $emp['employee_code'] ?? '',
                    $s['present'] ?? 0,
                    $s['absent'] ?? 0,
                    $s['late'] ?? 0,
                    $s['early_leave'] ?? 0,
                    $s['half_day'] ?? 0,
                    $s['mission'] ?? 0,
                    $s['leave'] ?? 0,
                    $s['presence_rate'] ?? 0,
                    $s['ponctualite_rate'] ?? 0,
                    $emp['observations'] ?? '',
                ]);
            }
        }

        $xlsx->addRow([]);
        $totals = $this->reportData['totals'] ?? [];
        $xlsx->addRow([
            'TOTAL GÉNÉRAL', '',
            $totals['total_present'] ?? 0,
            $totals['total_absent'] ?? 0,
            $totals['total_late'] ?? 0,
            $totals['total_early_leave'] ?? 0,
            '',
            $totals['total_mission'] ?? 0,
            $totals['total_leave'] ?? 0,
            $totals['avg_presence_rate'] ?? 0,
            $totals['avg_ponctualite_rate'] ?? 0,
            '',
        ], true);

        return $xlsx->build();
    }
}