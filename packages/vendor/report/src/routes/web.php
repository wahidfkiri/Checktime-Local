<?php 

use Illuminate\Support\Facades\Route;
use Vendor\Report\Controllers\ReportController;
use Vendor\Report\Controllers\CustomReportController;


Auth::routes();
Route::middleware(['web','auth', 'role:client','client.active'])->group(function () {
    // Your routes here
// Rapports
Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('/absences-delays', [ReportController::class, 'absencesDelays'])->name('absences-delays');
    Route::get('/attendances', [ReportController::class, 'absencesDelays'])->name('attendance');
    Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
    Route::get('/automated', [ReportController::class, 'automated'])->name('automated');
    Route::get('/data', [ReportController::class, 'getData'])->name('data');
    Route::get('/debug', [ReportController::class, 'debugGetData'])->name('reports.debug');
    Route::post('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/preview/pdf', [ReportController::class, 'previewPdf'])->name('preview.pdf');

    Route::post('/custom/export-pdf', [CustomReportController::class, 'exportCustomPdf'])
        ->name('reports.custom.export.pdf');
    
    // Route pour vérifier le statut
    Route::get('/custom/check-pdf-status/{request_id}', [CustomReportController::class, 'checkPdfStatus'])
        ->name('reports.custom.check-pdf-status');
    
    // Route pour télécharger
    Route::get('/custom/download-pdf/{request_id}', [CustomReportController::class, 'downloadPdf'])
        ->name('reports.custom.download-pdf');
    
    // Route synchrone (pour backup)
    Route::post('/custom/export-pdf-sync', [CustomReportController::class, 'exportCustomPdfSync'])
        ->name('reports.custom.export.pdf.sync');
});

// Routes pour le rapport personnalisé
Route::get('/admin/rapport/presence-ponctualite', [CustomReportController::class, 'presencePonctualite'])->name('admin.reports.custom.presence');
Route::post('/admin/rapport/presence-ponctualite/generate', [CustomReportController::class, 'generateCustomReport'])->name('admin.reports.custom.generate');
Route::post('/admin/rapport/presence-ponctualite/export-pdf', [CustomReportController::class, 'exportCustomPdf'])->name('admin.reports.custom.export.pdf');


});