<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CheckTimeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\ReportTemplateController;
use App\Http\Controllers\WorkHourController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\DelayController;
use App\Http\Controllers\DailyAttendanceController;
use App\Http\Controllers\DailyAttendanceTestController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleRotationController;
use App\Http\Controllers\ScheduleAssignmentController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\EmployeePermissionController;
use App\Http\Controllers\CustomReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SignataireController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\InstallerMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', function () {
    // If not installed, redirect to installer
    if (!\App\Services\InstallationLock::isInstalled()) {
        return redirect()->route('installer.index');
    }
    return view('auth.login');
})->middleware('guest');

/*
|--------------------------------------------------------------------------
| Installation Routes
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('installer.')->middleware('web', InstallerMiddleware::class)->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('index');
    Route::post('/app-info', [InstallerController::class, 'saveAppInfo'])->name('app-info');
    Route::post('/admin', [InstallerController::class, 'saveAdmin'])->name('admin');
    Route::post('/endpoint', [InstallerController::class, 'saveEndpoint'])->name('endpoint');
    Route::post('/smtp', [InstallerController::class, 'saveSmtp'])->name('smtp');
    Route::get('/summary', [InstallerController::class, 'getSummary'])->name('summary');
    Route::post('/test-smtp', [InstallerController::class, 'testSmtp'])->name('test-smtp');
    Route::post('/install', [InstallerController::class, 'install'])->name('install');
    Route::post('/sync-employees', [InstallerController::class, 'syncEmployees'])->name('sync-employees');
});

Route::get('/checktime/token', [CheckTimeController::class, 'testToken']);
Route::get('/checktime/devices', [CheckTimeController::class, 'getDevices']);
Route::get('/checktime/employees', [CheckTimeController::class, 'getEmployees']);
Route::post('/checktime/employees/create', [CheckTimeController::class, 'createEmployee']);
Route::get('/checktime/transactions', [CheckTimeController::class, 'getTransactions']);

Auth::routes();

Route::get('/home', function() { 
    return redirect()->route('dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'web', 'installed'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStatsJson'])->name('super-admin.stats');
    Route::get('/api/weekly-stats', [DashboardController::class, 'getWeeklyStats'])->name('api.weekly-stats');

    // Clients (single client management)
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');

    // Devices
    Route::middleware('role_or_permission:admin|menu.devices')->prefix('devices')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/local', [DeviceController::class, 'getLocalDevices'])->name('local');
        Route::post('/sync', [DeviceController::class, 'sync'])->name('sync');
        Route::post('/force-sync', [DeviceController::class, 'forceSync'])->name('force-sync');
        Route::get('/status', [DeviceController::class, 'syncStatus'])->name('status');
        Route::post('/reset', [DeviceController::class, 'resetAndSync'])->name('reset');
    });

    // Leaves
    Route::middleware('role_or_permission:admin|menu.leaves')->prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::put('/{id}', [LeaveController::class, 'update'])->name('update');
        Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');
        Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');
        Route::get('/datatable', [LeaveController::class, 'datatable'])->name('datatable');
        Route::put('/{id}/status', [LeaveController::class, 'updateStatus'])->name('status');
        Route::post('/types', [LeaveTypeController::class, 'store'])->name('types.store');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // Paramètres de notification (onglet du profil)
    Route::prefix('profile/notifications')->name('profile.notifications.')->group(function () {
        Route::post('/smtp', [NotificationSettingsController::class, 'smtp'])->name('smtp');
        Route::post('/smtp/test', [NotificationSettingsController::class, 'testSmtp'])->name('smtp.test');
        Route::post('/sms', [NotificationSettingsController::class, 'sms'])->name('sms');
        Route::post('/sms/test', [NotificationSettingsController::class, 'testSms'])->name('sms.test');
        Route::post('/jobs', [NotificationSettingsController::class, 'updateJobs'])->name('jobs');
        Route::post('/jobs/run', [NotificationSettingsController::class, 'runJob'])->name('jobs.run');
    });

    // Rotating schedules
    Route::prefix('rotations')->name('rotations.')->group(function () {
        Route::get('/', [ScheduleRotationController::class, 'index'])->name('index');
        Route::post('/', [ScheduleRotationController::class, 'store'])->name('store');
        Route::put('/{scheduleRotation}', [ScheduleRotationController::class, 'update'])->name('update');
        Route::delete('/{scheduleRotation}', [ScheduleRotationController::class, 'destroy'])->name('destroy');
        Route::post('/generate', [ScheduleRotationController::class, 'generateNextRotations'])->name('generate');
    });

    // Authorizations (Absences/Delays/Leaves)
    Route::prefix('authorizations')->name('authorizations.')->group(function () {
        // Absences
        Route::prefix('absences')->name('absences.')->group(function () {
            Route::get('/', [AbsenceController::class, 'index'])->name('index');
            Route::post('/', [AbsenceController::class, 'store'])->name('store');
            Route::put('/{absence}', [AbsenceController::class, 'update'])->name('update');
            Route::delete('/{absence}', [AbsenceController::class, 'destroy'])->name('destroy');
            Route::post('/{absence}/approve', [AbsenceController::class, 'approve'])->name('approve');
            Route::post('/{absence}/reject', [AbsenceController::class, 'reject'])->name('reject');
        });

        // Delays
        Route::prefix('delays')->name('delays.')->group(function () {
            Route::get('/', [DelayController::class, 'index'])->name('index');
            Route::post('/', [DelayController::class, 'store'])->name('store');
            Route::put('/{delay}', [DelayController::class, 'update'])->name('update');
            Route::delete('/{delay}', [DelayController::class, 'destroy'])->name('destroy');
        });

        // Employee permissions
        Route::middleware('role_or_permission:admin|menu.employee-permissions')->prefix('employee-permissions')->name('employee-permissions.')->group(function () {
            Route::get('/', [EmployeePermissionController::class, 'index'])->name('index');
            Route::post('/', [EmployeePermissionController::class, 'store'])->name('store');
            Route::get('/{employeePermission}', [EmployeePermissionController::class, 'show'])->name('show');
            Route::get('/{employeePermission}/edit', [EmployeePermissionController::class, 'edit'])->name('edit');
            Route::put('/{employeePermission}', [EmployeePermissionController::class, 'update'])->name('update');
            Route::delete('/{employeePermission}', [EmployeePermissionController::class, 'destroy'])->name('delete');
            Route::post('/{employeePermission}/approve', [EmployeePermissionController::class, 'approve'])->name('approve');
            Route::post('/{employeePermission}/reject', [EmployeePermissionController::class, 'reject'])->name('reject');
            Route::get('/employee/{employee}', [EmployeePermissionController::class, 'byEmployee'])->name('by-employee');
            Route::get('/export', [EmployeePermissionController::class, 'export'])->name('export');
        });

        // Leaves (existing)
        Route::middleware('role_or_permission:admin|menu.leaves')->group(function () {
            Route::resource('leaves', LeaveController::class);
        });
    });

    // Daily attendance
    Route::prefix('daily-attendance')->group(function () {
        Route::get('/', [DailyAttendanceController::class, 'index'])->name('daily-attendance.index');
        Route::get('/data', [DailyAttendanceController::class, 'getData'])->name('daily-attendance.data');
        Route::post('/sync', [DailyAttendanceController::class, 'sync'])->name('daily-attendance.sync');
        Route::get('/sync-status', [DailyAttendanceController::class, 'syncStatus'])->name('daily-attendance.sync-status');
        Route::get('/test-api', [DailyAttendanceController::class, 'testSync'])->name('daily-attendance.test-api');
        Route::get('/debug-codes', [DailyAttendanceController::class, 'debugEmpCodes'])->name('daily-attendance.debug-codes');
        Route::get('/get-employee-by-code', [DailyAttendanceController::class, 'getEmployeeByCode'])->name('daily-attendance.get-employee-by-code');
        Route::post('/export-pdf', [DailyAttendanceController::class, 'exportPDF'])->name('daily-attendance.export-pdf');
        Route::get('/api-diagnostic', [DailyAttendanceController::class, 'apiDiagnostic'])->name('api.diagnostic');
    });

    // Reports
    // Cette section n'était protégée par aucune permission jusqu'ici (accessible
    // à tout utilisateur connecté) — corrigé ici, avec le même découpage fin
    // que « Historique des pointages » (menu.reports reste un raccourci
    // « accès à tout », complété par les permissions par sous-page).
    Route::prefix('reports')->name('reports.')->group(function () {

        // État de pointage (arrivées / départs)
        Route::middleware('role_or_permission:admin|menu.reports|menu.reports-absences-delays')->group(function () {
            Route::get('/absences-delays', [ReportController::class, 'absencesDelays'])->name('absences-delays');
            Route::get('/attendances', [ReportController::class, 'absencesDelays'])->name('attendance');
        });

        // Fonctions partagées / rapport personnalisé — le découpage précédent
        // ne distinguait pas ces actions par sous-page, on les rend donc
        // accessibles dès qu'on a accès à l'un des deux rapports.
        Route::middleware('role_or_permission:admin|menu.reports|menu.reports-absences-delays|menu.reports-custom-presence')->group(function () {
            Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
            Route::get('/export', [ReportController::class, 'export'])->name('export');
            Route::get('/automated', [ReportController::class, 'automated'])->name('automated');
            Route::get('/data', [ReportController::class, 'getData'])->name('data');
            Route::get('/debug', [ReportController::class, 'debugGetData'])->name('reports.debug');
            Route::post('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
            Route::get('/preview/pdf', [ReportController::class, 'previewPdf'])->name('preview.pdf');

            Route::post('/custom/export-pdf', [CustomReportController::class, 'exportCustomPdf'])
                ->name('reports.custom.export.pdf');
            Route::get('/custom/check-pdf-status/{request_id}', [CustomReportController::class, 'checkPdfStatus'])
                ->name('reports.custom.check-pdf-status');
            Route::get('/custom/download-pdf/{request_id}', [CustomReportController::class, 'downloadPdf'])
                ->name('reports.custom.download-pdf');
            Route::post('/custom/export-pdf-sync', [CustomReportController::class, 'exportCustomPdfSync'])
                ->name('reports.custom.export.pdf.sync');
        });
    });

    // Settings
    Route::middleware('role_or_permission:admin|menu.settings')->prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/update', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/test-rh', [SettingsController::class, 'testRhEmail'])->name('settings.test.rh');
        Route::post('/test-employees', [SettingsController::class, 'testEmployeesEmail'])->name('settings.test.employees');
        Route::get('/status', [SettingsController::class, 'getStatus'])->name('settings.status');
        Route::post('/access-key', [SettingsController::class, 'updateAccessKey'])->name('settings.access-key.update');
        Route::post('/access-key/test', [SettingsController::class, 'testAccessKey'])->name('settings.access-key.test');

        // Signataires (cartouche de signatures des rapports)
        Route::get('/signataires', [SignataireController::class, 'index'])->name('settings.signataires.index');
        Route::post('/signataires/postes', [SignataireController::class, 'storePoste'])->name('settings.signataires.postes.store');
        Route::put('/signataires/postes/{id}', [SignataireController::class, 'updatePoste'])->name('settings.signataires.postes.update');
        Route::delete('/signataires/postes/{id}', [SignataireController::class, 'destroyPoste'])->name('settings.signataires.postes.destroy');
        Route::post('/signataires/responsables', [SignataireController::class, 'storeSignataire'])->name('settings.signataires.responsables.store');
        Route::delete('/signataires/responsables/{id}', [SignataireController::class, 'destroySignataire'])->name('settings.signataires.responsables.destroy');
    });

    // Modèles d'édition PDF (colonnes à cocher) du rapport présence-ponctualité
    // — groupe à part (pas nesté sous menu.settings) pour qu'un utilisateur
    // ayant uniquement la permission menu.report-templates y accède, sans
    // avoir besoin de l'accès complet à Paramètres.
    Route::middleware('role_or_permission:admin|menu.settings|menu.report-templates')->prefix('settings/modeles-rapport')->name('settings.report-templates.')->group(function () {
        Route::get('/', [ReportTemplateController::class, 'index'])->name('index');
        Route::get('/list', [ReportTemplateController::class, 'list'])->name('list');
        Route::post('/', [ReportTemplateController::class, 'store'])->name('store');
        Route::put('/{id}', [ReportTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReportTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/default', [ReportTemplateController::class, 'setDefault'])->name('default');
        Route::post('/{id}/duplicate', [ReportTemplateController::class, 'duplicate'])->name('duplicate');
    });

    // Custom reports — lien sidebar « Rapport d'assiduité et de ponctualité »
    Route::middleware('role_or_permission:admin|menu.reports|menu.reports-custom-presence')->group(function () {
        Route::get('/rapport/presence-ponctualite', [CustomReportController::class, 'presencePonctualite'])->name('reports.custom.presence');
        Route::post('/rapport/presence-ponctualite/generate', [CustomReportController::class, 'generateCustomReport'])->name('reports.custom.generate');
        Route::post('/rapport/presence-ponctualite/export-pdf', [CustomReportController::class, 'exportCustomPdf'])->name('reports.custom.export.pdf');
        Route::post('/rapport/presence-ponctualite/export-dept-pdf', [CustomReportController::class, 'exportCustomPdfByDept'])->name('reports.export-department-pdf');
        Route::post('/rapport/presence-ponctualite/export-excel', [CustomReportController::class, 'exportCustomExcel'])->name('reports.custom.export.excel');
    });

    // Tableau de Suivi de la Ponctualité — grille employés × jours ouvrés
    Route::middleware('role_or_permission:admin|menu.reports|menu.reports-suivi-ponctualite')
        ->prefix('rapport/suivi-ponctualite')
        ->name('reports.suivi-ponctualite.')
        ->group(function () {
            Route::get('/', [CustomReportController::class, 'suiviPonctualite'])->name('index');
            Route::get('/generate', [CustomReportController::class, 'generateSuiviPonctualite'])->name('generate');
            Route::get('/export-pdf', [CustomReportController::class, 'exportSuiviPonctualitePdf'])->name('export.pdf');
            Route::get('/export-excel', [CustomReportController::class, 'exportSuiviPonctualiteExcel'])->name('export.excel');
        });

    // Missions
    Route::middleware('role_or_permission:admin|menu.missions')->prefix('missions')->name('missions.')->group(function () {
        Route::get('/', [MissionController::class, 'index'])->name('index');
        Route::post('/', [MissionController::class, 'store'])->name('store');
        Route::get('/generate-reference', [MissionController::class, 'generateReference'])->name('generate-reference');
        Route::get('/{id}', [MissionController::class, 'show'])->name('show');
        Route::put('/{id}', [MissionController::class, 'update'])->name('update');
        Route::delete('/{id}', [MissionController::class, 'destroy'])->name('destroy');
    });

    // Utilisateurs & permissions — réservé au rôle admin (pas de délégation
    // possible via permission, pour éviter qu'un utilisateur ne s'octroie
    // lui-même des droits supplémentaires).
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'web', 'installed'])->group(function () {
    // Transactions
    Route::get('/api/transactions', [BiometricController::class, 'getTransactions']);
    // Biometric verification — identification par id unique (les emp_code peuvent être en doublon)
    Route::get('/api/biometric/{id}', [BiometricController::class, 'getBiometricVerification']);
});
