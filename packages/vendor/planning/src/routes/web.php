<?php

use Illuminate\Support\Facades\Route;
use Vendor\Planning\Controllers\WorkHourController;
use Vendor\Planning\Controllers\EmployeeScheduleController;
use Vendor\Planning\Controllers\ScheduleAssignmentController;



Auth::routes();
Route::middleware(['web','auth', 'role:admin','client.active'])->group(function () {

     // Gestion des Plannings
    Route::prefix('work-hours')->name('work-hours.')->group(function () {
        Route::get('/', [WorkHourController::class, 'index'])->name('index');
        Route::post('/', [WorkHourController::class, 'store'])->name('store');
        Route::get('/create', [WorkHourController::class, 'create'])->name('create');
        Route::get('/export', [WorkHourController::class, 'export'])->name('export');
        Route::get('/{workHourType}', [WorkHourController::class, 'show'])->name('show');
        Route::put('/{workHourType}', [WorkHourController::class, 'update'])->name('update');
        Route::delete('/{workHourType}', [WorkHourController::class, 'destroy'])->name('destroy');
        Route::post('/{workHourType}/duplicate', [WorkHourController::class, 'duplicate'])->name('duplicate');
        Route::post('/{workHourType}/toggle-status', [WorkHourController::class, 'toggleStatus'])->name('toggle-status');
    
    });


    // Plannings des employés
Route::prefix('employee-schedules')->name('employee-schedules.')->group(function () {
    Route::get('/', [EmployeeScheduleController::class, 'index'])->name('index');
    Route::post('/', [EmployeeScheduleController::class, 'store'])->name('store');
    Route::get('/{employeeSchedule}/edit', [EmployeeScheduleController::class, 'edit'])->name('edit');
    Route::put('/{employeeSchedule}', [EmployeeScheduleController::class, 'update'])->name('update');
    Route::delete('/{employeeSchedule}', [EmployeeScheduleController::class, 'destroy'])->name('destroy');
    
    // Actions supplémentaires
    Route::post('/{employeeSchedule}/duplicate', [EmployeeScheduleController::class, 'duplicate'])->name('duplicate');
    Route::post('/{employeeSchedule}/toggle-status', [EmployeeScheduleController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/import', [EmployeeScheduleController::class, 'import'])->name('import');
    Route::post('/export', [EmployeeScheduleController::class, 'export'])->name('export');
     // Actions en masse
    Route::post('/bulk-delete', [EmployeeScheduleController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/bulk-update', [EmployeeScheduleController::class, 'bulkUpdate'])->name('bulk-update');
    // Ajoutez dans web.php
    Route::post('/bulk-create', [EmployeeScheduleController::class, 'bulkCreate'])->name('bulk-create');
});

// Programme des horaires
Route::prefix('schedules')->name('schedules.')->group(function () {
    // Calendrier
    Route::get('/calendar', [ScheduleAssignmentController::class, 'calendar'])->name('calendar');
    Route::get('/get-schedules', [ScheduleAssignmentController::class, 'getSchedules'])->name('get-schedules');
    
    // Assignation
    Route::post('/assign', [ScheduleAssignmentController::class, 'assignSchedule'])->name('assign');
    Route::post('/mass-assign', [ScheduleAssignmentController::class, 'massAssign'])->name('mass-assign');
    Route::delete('/remove', [ScheduleAssignmentController::class, 'removeSchedule'])->name('remove');
    
    // Route pour récupérer les données d'une cellule
    Route::get('/get-cell-data', [ScheduleAssignmentController::class, 'getCellData'])->name('get-cell-data');
    // Export
    Route::get('/export', [ScheduleAssignmentController::class, 'export'])->name('export');
    Route::get('/export-pdf', [ScheduleAssignmentController::class, 'exportPdf'])
    ->name('export.pdf');
});

});