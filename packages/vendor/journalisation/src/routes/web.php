<?php

use Illuminate\Support\Facades\Route;
use Vendor\Journalisation\Controllers\JournalisationController;

// Journal des activités — super-admin / admin, ou utilisateur disposant
// de la permission dédiée « menu.journalisation ».
Route::middleware(['web', 'auth', 'role_or_permission:admin|super-admin|menu.journalisation'])->group(function () {
    Route::get('/journalisation', [JournalisationController::class, 'index'])->name('journalisation.index');
    Route::get('/journalisation/export-excel', [JournalisationController::class, 'exportExcel'])->name('journalisation.export.excel');
    Route::get('/journalisation/export-pdf', [JournalisationController::class, 'exportPdf'])->name('journalisation.export.pdf');
});
