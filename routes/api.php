<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckTimeController;
use App\Http\Controllers\BiometricController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// =============================
// 🔥 TOKEN & HEALTH
// =============================
Route::get('/checktime/token', [CheckTimeController::class, 'testToken']);
Route::get('/checktime/health', [CheckTimeController::class, 'healthCheck']);

// =============================
// 🔥 DEVICES
// =============================
Route::get('/checktime/devices', [CheckTimeController::class, 'getDevices']);
Route::get('/checktime/devices/{id}', [CheckTimeController::class, 'getDevice']);

// =============================
// 🔥 EMPLOYEES
// =============================
Route::get('/checktime/employees', [CheckTimeController::class, 'getEmployees']);
Route::get('/checktime/employees/{id}', [CheckTimeController::class, 'getEmployee']);
Route::post('/checktime/employees', [CheckTimeController::class, 'createEmployee']);
Route::get('/checktime/areas', [CheckTimeController::class, 'getAreas']);
Route::get('/checktime/devices', [CheckTimeController::class, 'getDevices']);
Route::patch('/checktime/employees/{id}', [CheckTimeController::class, 'updateEmployee']);
Route::delete('/checktime/employees/{id}', [CheckTimeController::class, 'deleteEmployee']);
Route::post('/checktime/employees/batch', [CheckTimeController::class, 'batchCreateEmployees']);

// =============================
// 🔥 DEPARTMENTS
// =============================
Route::get('/checktime/departments', [CheckTimeController::class, 'getDepartments']);
Route::get('/checktime/departments/{id}', [CheckTimeController::class, 'getDepartment']);
Route::post('/checktime/departments', [CheckTimeController::class, 'createDepartment']);
Route::patch('/checktime/departments/{id}', [CheckTimeController::class, 'updateDepartment']);
Route::delete('/checktime/departments/{id}', [CheckTimeController::class, 'deleteDepartment']);

// =============================
// 🔥 AREAS
// =============================
Route::get('/checktime/areas', [CheckTimeController::class, 'getAreas']);
Route::get('/checktime/areas/{id}', [CheckTimeController::class, 'getArea']);
Route::post('/checktime/areas', [CheckTimeController::class, 'createArea']);
Route::patch('/checktime/areas/{id}', [CheckTimeController::class, 'updateArea']);
Route::delete('/checktime/areas/{id}', [CheckTimeController::class, 'deleteArea']);

// =============================
// 🔥 POSITIONS
// =============================
Route::get('/checktime/positions', [CheckTimeController::class, 'getPositions']);
Route::get('/checktime/positions/{id}', [CheckTimeController::class, 'getPosition']);
Route::post('/checktime/positions', [CheckTimeController::class, 'createPosition']);
Route::patch('/checktime/positions/{id}', [CheckTimeController::class, 'updatePosition']);
Route::delete('/checktime/positions/{id}', [CheckTimeController::class, 'deletePosition']);

// =============================
// 🔥 TRANSACTIONS
// =============================
Route::get('/checktime/transactions', [CheckTimeController::class, 'getTransactions']);



