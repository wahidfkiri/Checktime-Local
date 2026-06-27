<?php

use Illuminate\Support\Facades\Route;
use Vendor\Employee\Controllers\AreaController;
use Vendor\Employee\Controllers\DepartmentController;
use Vendor\Employee\Controllers\EmployeeController;



Auth::routes();
Route::middleware(['web','auth', 'role:client','client.active'])->group(function () {

      Route::prefix('areas')->name('areas.')->group(function () {
        
        Route::get('/', [AreaController::class, 'index'])->name('index');
        Route::post('/store', [AreaController::class, 'store'])->name('store');
        Route::put('/{id}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('/{id}', [AreaController::class, 'destroy'])->name('areas.destroy');
        Route::post('/sync', [AreaController::class, 'sync'])->name('areas.sync');
        
        // Route pour obtenir les zones locales
        Route::get('/local', [AreaController::class, 'getLocalZones'])->name('local');
        Route::get('/status', [AreaController::class, 'syncStatus'])->name('status');
        Route::post('/reset', [AreaController::class, 'resetAndSync'])->name('reset');
    });

    Route::prefix('departments')->name('departments.')->group(function () {
        
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::put('/{id}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{id}', [DepartmentController::class, 'destroy'])->name('destroy');
        // Route pour synchroniser les départements depuis l'API externe
        Route::post('/sync', [DepartmentController::class, 'sync'])->name('sync');
        
        // Route pour obtenir les départements locaux
        Route::get('/local', [DepartmentController::class, 'getLocalDepartments'])->name('local');
        Route::get('/status', [DepartmentController::class, 'syncStatus'])->name('status');
        Route::post('/reset', [DepartmentController::class, 'resetAndSync'])->name('reset');
    });


        
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    // routes/web.php
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');
    Route::get('/local', [EmployeeController::class, 'getLocalEmployees'])->name('local');
    Route::post('/sync', [EmployeeController::class, 'sync'])->name('sync');
    Route::post('/force-sync', [EmployeeController::class, 'forceSync'])->name('force-sync');
    Route::get('/status', [EmployeeController::class, 'syncStatus'])->name('status');
    Route::post('/reset', [EmployeeController::class, 'resetAndSync'])->name('reset');
});


});