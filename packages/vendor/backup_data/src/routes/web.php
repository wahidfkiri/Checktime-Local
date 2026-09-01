<?php

use Illuminate\Support\Facades\Route;
use Vendor\BackupData\Controllers\BackupDataController;

// Module de sauvegarde des données — réservé aux administrateurs.
Route::middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('/backup-data', [BackupDataController::class, 'index'])->name('backup-data.index');
    Route::post('/backup-data/export', [BackupDataController::class, 'export'])->name('backup-data.export');
    Route::get('/backup-data/{backup}/download', [BackupDataController::class, 'download'])->name('backup-data.download');
    Route::delete('/backup-data/{backup}', [BackupDataController::class, 'destroy'])->name('backup-data.destroy');
});
