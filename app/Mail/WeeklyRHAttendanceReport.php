<?php
// app/Mail/WeeklyRHAttendanceReport.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $pdfFileName = "rapport_presence_rh_{$this->exportDate->format('Y-m-d')}.pdf";

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
            ]);
    }
}