<?php

namespace App\Http\Controllers;

use App\Services\CheckTimeService;
use Illuminate\Http\Request;

class CheckTimeController extends Controller
{
    private CheckTimeService $api;

    public function __construct(CheckTimeService $api)
    {
        $this->api = $api;
    }

    // =============================
    // 🔥 TOKEN TEST
    // =============================
    public function testToken()
    {
        return response()->json([
            "token" => $this->api->getGeneralToken()
        ]);
    }

    // =============================
    // 🔥 DEVICES
    // =============================
    public function getDevices()
    {
        return $this->api->get('/iclock/api/terminals/');
    }

    // =============================
    // 🔥 EMPLOYEES
    // =============================
    public function getEmployees()
    {
        return $this->api->get('/personnel/api/employees/');
    }
    

    public function getEmployee($id)
    {
        return $this->api->get("/personnel/api/employees/{$id}/");
    }

    public function createEmployee(Request $request)
    {
        // Valider les données reçues
        $validated = $request->validate([
            'emp_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'department' => 'required|integer',
            'area' => 'required|array',
            'area.*' => 'integer',
            'hire_date' => 'nullable|date',
            'birthday' => 'nullable|date',
        ]);

        // Appeler le service externe
        $response = $this->api->post('/personnel/api/employees/', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $response
        ], 201);
    }

    public function updateEmployee($id)
    {
        $data = [
            "first_name" => "Updated",
            "last_name" => "Employee"
        ];

        return $this->api->patch("/personnel/api/employees/{$id}/", $data);
    }

    public function deleteEmployee($id)
    {
        return $this->api->delete("/personnel/api/employees/{$id}/");
    }

    // =============================
    // 🔥 TRANSACTIONS
    // =============================
    public function getTransactions()
    {
        return $this->api->get('/iclock/api/transactions/', [
            // "emp_code" => "12",
            "start_time" => "2026-02-05 00:00:00",
            "end_time" => "2026-02-05 23:59:59"
        ]);
    }
}
