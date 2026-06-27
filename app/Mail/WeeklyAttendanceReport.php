<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $pdfFileName = "rapport_presence_{$safeCode}_{$this->emailData['export_date']->format('Y-m-d')}.pdf";

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
            ]);
    }
}