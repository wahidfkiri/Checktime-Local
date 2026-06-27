<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GeneratePresenceReportPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    protected $userId;
    protected $startDate;
    protected $endDate;
    protected $empCode;
    protected $requestId;

    public function __construct($userId, $startDate, $endDate, $empCode, $requestId)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->empCode = $empCode;
        $this->requestId = $requestId;
    }

    public function handle()
    {
        try {
            Log::info("Début génération PDF Job - Request ID: {$this->requestId}");

            // Mettre à jour le statut IMMÉDIATEMENT
            DB::table('pdf_exports')
                ->where('request_id', $this->requestId)
                ->update([
                    'status' => 'processing',
                    'updated_at' => Carbon::now()
                ]);

            // Stocker les paramètres pour une éventuelle regénération
            DB::table('pdf_export_params')->insert([
                'request_id' => $this->requestId,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'emp_code' => $this->empCode,
                'params_json' => json_encode([
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'emp_code' => $this->empCode,
                    'user_id' => $this->userId,
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Utiliser la méthode du contrôleur pour générer le PDF
            $controller = new \App\Http\Controllers\CustomReportController();
            $pdf = $controller->generatePdfSync($this->startDate, $this->endDate, $this->empCode);

            // Sauvegarder le PDF
            $filename = "presence_reports/report_{$this->requestId}.pdf";
            $fullPath = Storage::disk('public')->path($filename);

            // S'assurer que le dossier existe
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Sauvegarder le PDF
            $pdf->save($fullPath);

            // Mettre à jour la base de données avec SUCCÈS
            DB::table('pdf_exports')
                ->where('request_id', $this->requestId)
                ->update([
                    'filename' => $filename,
                    'status' => 'completed',
                    'download_url' => Storage::disk('public')->url($filename),
                    'period' => $this->startDate . ' au ' . $this->endDate,
                    'filter' => $this->empCode === 'all' ? 'Tous' : $this->empCode,
                    'updated_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addHours(24),
                ]);

            Log::info("PDF généré avec succès - Request ID: {$this->requestId}, Fichier: {$filename}");

        } catch (\Exception $e) {
            Log::error("Erreur dans GeneratePresenceReportPdf Job: " . $e->getMessage());

            // Enregistrer l'erreur
            DB::table('pdf_exports')
                ->where('request_id', $this->requestId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => Carbon::now(),
                ]);

            throw $e;
        }
    }
}