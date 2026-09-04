<?php

use Illuminate\Support\Facades\Route;
use Vendor\Journalisation\Controllers\JournalisationController;

// Journal des activités — réservé aux super-admin / admin.
Route::middleware(['web', 'auth', 'role:admin|super-admin'])->group(function () {
    Route::get('/journalisation', [JournalisationController::class, 'index'])->name('journalisation.index');
    Route::get('/journalisation/export-excel', [JournalisationController::class, 'exportExcel'])->name('journalisation.export.excel');
    Route::get('/journalisation/export-pdf', [JournalisationController::class, 'exportPdf'])->name('journalisation.export.pdf');
});
