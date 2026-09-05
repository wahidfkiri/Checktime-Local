<?php
use Illuminate\Support\Facades\Route;
use Vendor\Attendance\Controllers\DailyAttendanceController;



Auth::routes();
// menu.daily-attendance reste un raccourci « accès à toutes les sous-pages » ;
// chaque sous-page a en plus sa propre permission (menu.attendance-*) pour un
// contrôle fin depuis la page Utilisateurs.
Route::middleware(['web', 'auth', 'client.active'])->group(function () {
Route::prefix('admin/daily-attendance')->group(function () {

    // Endpoints partagés entre les 4 sous-pages (sync, diagnostic, lookup...) :
    // accessibles dès qu'on a accès à au moins une des 4.
    Route::middleware('role_or_permission:admin|menu.daily-attendance|menu.attendance-history|menu.attendance-presence|menu.attendance-absence|menu.attendance-retards')->group(function () {
        Route::get('/data', [DailyAttendanceController::class, 'getData'])->name('admin.daily-attendance.data');
        Route::post('/sync', [DailyAttendanceController::class, 'sync'])->name('admin.daily-attendance.sync');
        Route::get('/sync-status', [DailyAttendanceController::class, 'syncStatus'])->name('admin.daily-attendance.sync-status');
        Route::get('/test-api', [DailyAttendanceController::class, 'testSync'])->name('admin.daily-attendance.test-api');
        Route::get('/debug-codes', [DailyAttendanceController::class, 'debugEmpCodes'])->name('admin.daily-attendance.debug-codes');
        Route::get('/get-employee-by-code', [DailyAttendanceController::class, 'getEmployeeByCode'])->name('admin.daily-attendance.get-employee-by-code');
        Route::post('/export-pdf', [DailyAttendanceController::class, 'exportPDF'])->name('admin.daily-attendance.export-pdf');
        Route::get('/export-excel', [DailyAttendanceController::class, 'exportExcel'])->name('admin.daily-attendance.export-excel');
        Route::get('/api-diagnostic', [DailyAttendanceController::class, 'apiDiagnostic'])->name('admin.daily-attendance.api-diagnostic');
        Route::post('/sync/data', [DailyAttendanceController::class, 'syncAttendance'])->name('admin.daily-attendance.sync.data');
        Route::get('/attendance/details', [DailyAttendanceController::class, 'showDetails'])->name('admin.daily-attendance.details');
    });

    // Historique complet
    Route::middleware('role_or_permission:admin|menu.daily-attendance|menu.attendance-history')->group(function () {
        Route::get('/', [DailyAttendanceController::class, 'index'])->name('admin.daily-attendance.index');
    });

    // Liste des présences
    Route::middleware('role_or_permission:admin|menu.daily-attendance|menu.attendance-presence')->group(function () {
        Route::get('presences', [DailyAttendanceController::class, 'presenceList'])->name('admin.daily-attendance.presence');
        Route::get('presences/data', [DailyAttendanceController::class, 'getPresenceData'])->name('admin.daily-attendance.presence.data');
        Route::post('presences/export-pdf', [DailyAttendanceController::class, 'exportPresencePdf'])->name('admin.daily-attendance.presences.export-pdf');
        Route::get('presences/export-excel', [DailyAttendanceController::class, 'exportPresenceExcel'])->name('admin.daily-attendance.presences.export-excel');
    });

    // Liste des absences
    Route::middleware('role_or_permission:admin|menu.daily-attendance|menu.attendance-absence')->group(function () {
        Route::get('absences', [DailyAttendanceController::class, 'absenceList'])->name('admin.daily-attendance.absence');
        Route::get('absences/data', [DailyAttendanceController::class, 'getAbsenceData'])->name('admin.daily-attendance.absence.data');
        Route::post('absence/export-pdf', [DailyAttendanceController::class, 'exportAbsencePdf'])->name('admin.daily-attendance.absence.export-pdf');
        Route::get('absences/export-excel', [DailyAttendanceController::class, 'exportAbsenceExcel'])->name('admin.daily-attendance.absence.export-excel');
    });

    // Liste des retards
    Route::middleware('role_or_permission:admin|menu.daily-attendance|menu.attendance-retards')->group(function () {
        Route::get('retards', [DailyAttendanceController::class, 'retardList'])->name('admin.daily-attendance.retards');
        Route::get('/retards/data', [DailyAttendanceController::class, 'getRetardData'])->name('admin.daily-attendance.retard.data');
        Route::post('/retards/export-pdf', [DailyAttendanceController::class, 'exportRetardPdf'])->name('admin.daily-attendance.retard.export-pdf');
        Route::get('/retards/export-excel', [DailyAttendanceController::class, 'exportRetardExcel'])->name('admin.daily-attendance.retard.export-excel');
        Route::post('/retards/justify', [DailyAttendanceController::class, 'justifyRetard'])->name('admin.daily-attendance.justify-retard');
    });
});
});

