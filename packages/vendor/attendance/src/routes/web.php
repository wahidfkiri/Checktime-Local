<?php
use Illuminate\Support\Facades\Route;
use Vendor\Attendance\Controllers\DailyAttendanceController;



Auth::routes();
Route::middleware(['web','auth', 'role:admin','client.active'])->group(function () {
// Gestion des présences
Route::prefix('admin/daily-attendance')->group(function () {
    Route::get('/', [DailyAttendanceController::class, 'index'])->name('admin.daily-attendance.index');
    Route::get('/data', [DailyAttendanceController::class, 'getData'])->name('admin.daily-attendance.data');
    Route::post('/sync', [DailyAttendanceController::class, 'sync'])->name('admin.daily-attendance.sync');
    Route::get('/sync-status', [DailyAttendanceController::class, 'syncStatus'])->name('admin.daily-attendance.sync-status');
    Route::get('/test-api', [DailyAttendanceController::class, 'testSync'])->name('admin.daily-attendance.test-api');
    Route::get('/debug-codes', [DailyAttendanceController::class, 'debugEmpCodes'])->name('admin.daily-attendance.debug-codes');
     Route::get('/get-employee-by-code', [DailyAttendanceController::class, 'getEmployeeByCode'])->name('admin.daily-attendance.get-employee-by-code');
     Route::post('/export-pdf', [DailyAttendanceController::class, 'exportPDF'])->name('admin.daily-attendance.export-pdf');
     Route::get('/api-diagnostic', [DailyAttendanceController::class, 'apiDiagnostic'])->name('admin.daily-attendance.api-diagnostic');


      // Nouvelles routes pour les présences/absences
    Route::get('presences', [DailyAttendanceController::class, 'presenceList'])->name('admin.daily-attendance.presence');
    Route::get('absences', [DailyAttendanceController::class, 'absenceList'])->name('admin.daily-attendance.absence');
    Route::get('presences/data', [DailyAttendanceController::class, 'getPresenceData'])->name('admin.daily-attendance.presence.data');
    Route::get('absences/data', [DailyAttendanceController::class, 'getAbsenceData'])->name('admin.daily-attendance.absence.data');
    Route::post('absence/export-pdf', [DailyAttendanceController::class, 'exportAbsencePdf'])->name('admin.daily-attendance.absence.export-pdf');
    Route::post('presences/export-pdf', [DailyAttendanceController::class, 'exportPresencePdf'])->name('admin.daily-attendance.presences.export-pdf');

    Route::get('retards', [DailyAttendanceController::class, 'retardList'])->name('admin.daily-attendance.retards');

    
    Route::post('/sync/data', [DailyAttendanceController::class, 'syncAttendance'])
        ->name('admin.daily-attendance.sync.data');
        Route::get('/retards/data', [DailyAttendanceController::class, 'getRetardData'])->name('admin.daily-attendance.retard.data');
Route::post('/retards/export-pdf', [DailyAttendanceController::class, 'exportRetardPdf'])->name('admin.daily-attendance.retard.export-pdf');
Route::post('/retards/justify', [DailyAttendanceController::class, 'justifyRetard'])->name('admin.daily-attendance.justify-retard');
Route::get('/attendance/details', [DailyAttendanceController::class, 'showDetails'])->name('admin.daily-attendance.details');
Route::get('/employee/by-code', [DailyAttendanceController::class, 'getEmployeeByCode'])->name('admin.daily-attendance.get-employee-by-code');
});
});

