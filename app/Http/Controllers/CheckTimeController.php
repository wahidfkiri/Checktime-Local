<?php

namespace App\Http\Controllers;

use App\Services\CheckTimeService;
use App\Services\CheckTimeServiceTest;
use Illuminate\Http\Request;

class CheckTimeController extends Controller
{
    private CheckTimeService $api;
    private CheckTimeServiceTest $apiTest;

    public function __construct(CheckTimeService $api, CheckTimeServiceTest $apiTest)
    {
        $this->api = $api;
        $this->apiTest = $apiTest;
    }

    // =============================
    // 🔥 TOKEN MANAGEMENT
    // =============================
    public function testToken()
    {
        return response()->json([
            "jwt_token" => $this->apiTest->getJwtToken(),
            "general_token" => $this->apiTest->getGeneralToken(),
            "staff_jwt_token" => $this->apiTest->getStaffJwtToken(),
            "staff_general_token" => $this->apiTest->getStaffGeneralToken()
        ]);
    }

    // =============================
    // 🔥 DEVICES API
    // =============================
    public function getDevices(Request $request)
    {
        $params = $request->only(['sn', 'page', 'limit', 'alias', 'area']);
        return $this->api->get('/iclock/api/terminals/', $params, $request->token);
    }

    public function getDevice($id)
    {
        return $this->api->get("/iclock/api/terminals/{$id}/");
    }

    // =============================
    // 🔥 EMPLOYEES API
    // =============================
    public function getEmployees(Request $request)
    {
        $params = $request->only([
            'emp_code', 'page', 'limit', 'first_name', 
            'last_name', 'department', 'app_status'
        ]);
        return $this->api->get('/personnel/api/employees/', $params, $request->token);
    }

    public function getEmployee($id)
    {
        return $this->api->get("/personnel/api/employees/{$id}/");
    }

    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'emp_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'department' => 'required|integer',
            'area' => 'required|array',
            'area.*' => 'integer',
            'hire_date' => 'nullable|date',
            'birthday' => 'nullable|date',
            'nickname' => 'nullable|string|max:100',
            'card_no' => 'nullable|string|max:50',
            'gender' => 'nullable|string|in:M,F',
            'contact_tel' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'enable_att' => 'nullable|boolean',
            'enable_overtime' => 'nullable|boolean',
            'enable_holiday' => 'nullable|boolean',
        ]);

        return $this->api->post('/personnel/api/employees/', $validated);
    }

    public function updateEmployee(Request $request, $id)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'department' => 'nullable|integer',
            'area' => 'nullable|array',
            'area.*' => 'integer',
            'hire_date' => 'nullable|date',
            'birthday' => 'nullable|date',
            'nickname' => 'nullable|string|max:100',
            'card_no' => 'nullable|string|max:50',
            'gender' => 'nullable|string|in:M,F',
            'contact_tel' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'enable_att' => 'nullable|boolean',
            'enable_overtime' => 'nullable|boolean',
            'enable_holiday' => 'nullable|boolean',
        ]);

        return $this->api->patch("/personnel/api/employees/{$id}/", $validated);
    }

    public function deleteEmployee($id)
    {
        return $this->api->delete("/personnel/api/employees/{$id}/");
    }

    // =============================
    // 🔥 DEPARTMENT API
    // =============================
    public function getDepartments(Request $request)
    {
        $params = $request->only([
            'dept_code', 'page', 'limit', 'dept_name', 'parent_dept'
        ]);
        return $this->api->get('/personnel/api/departments/', $params);
    }

    public function getDepartment($id)
    {
        return $this->api->get("/personnel/api/departments/{$id}/");
    }

    public function createDepartment(Request $request)
    {
        $validated = $request->validate([
            'dept_code' => 'required|string|max:50',
            'dept_name' => 'required|string|max:100',
            'parent_dept' => 'nullable|integer'
        ]);

        return $this->api->post('/personnel/api/departments/', $validated);
    }

    public function updateDepartment(Request $request, $id)
    {
        $validated = $request->validate([
            'dept_code' => 'nullable|string|max:50',
            'dept_name' => 'nullable|string|max:100',
            'parent_dept' => 'nullable|integer'
        ]);

        return $this->api->patch("/personnel/api/departments/{$id}/", $validated);
    }

    public function deleteDepartment($id)
    {
        return $this->api->delete("/personnel/api/departments/{$id}/");
    }

    // =============================
    // 🔥 AREA API
    // =============================
    public function getAreas(Request $request)
    {
        $params = $request->only([
            'area_code', 'page', 'limit', 'area_name', 'parent_area'
        ]);
        return $this->api->get('/personnel/api/areas/', $params, $request->token);
    }

    public function getArea($id)
    {
        return $this->api->get("/personnel/api/areas/{$id}/");
    }

    public function createArea(Request $request)
    {
        $validated = $request->validate([
            'area_code' => 'required|string|max:50',
            'area_name' => 'required|string|max:100',
            'parent_area' => 'nullable|integer'
        ]);

        return $this->api->post('/personnel/api/areas/', $validated);
    }

    public function updateArea(Request $request, $id)
    {
        $validated = $request->validate([
            'area_code' => 'nullable|string|max:50',
            'area_name' => 'nullable|string|max:100',
            'parent_area' => 'nullable|integer'
        ]);

        return $this->api->patch("/personnel/api/areas/{$id}/", $validated);
    }

    public function deleteArea($id)
    {
        return $this->api->delete("/personnel/api/areas/{$id}/");
    }

    // =============================
    // 🔥 POSITION API
    // =============================
    public function getPositions(Request $request)
    {
        $params = $request->only([
            'position_code', 'page', 'limit', 'position_name', 'parent_position'
        ]);
        return $this->api->get('/personnel/api/positions/', $params);
    }

    public function getPosition($id)
    {
        return $this->api->get("/personnel/api/positions/{$id}/");
    }

    public function createPosition(Request $request)
    {
        $validated = $request->validate([
            'position_code' => 'required|string|max:50',
            'position_name' => 'required|string|max:100',
            'parent_position' => 'nullable|integer'
        ]);

        return $this->api->post('/personnel/api/positions/', $validated);
    }

    public function updatePosition(Request $request, $id)
    {
        $validated = $request->validate([
            'position_code' => 'nullable|string|max:50',
            'position_name' => 'nullable|string|max:100',
            'parent_position' => 'nullable|integer'
        ]);

        return $this->api->patch("/personnel/api/positions/{$id}/", $validated);
    }

    public function deletePosition($id)
    {
        return $this->api->delete("/personnel/api/positions/{$id}/");
    }

    // =============================
    // 🔥 TRANSACTIONS API
    // =============================
    public function getTransactions(Request $request)
    {
        $params = $request->only([
            'emp_code', 'page' => $request->page ?? 1, 'limit' => $request->limit ?? 100, 'terminal_sn', 
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);
        return $this->api->get('/iclock/api/transactions/', $params, $request->token);
    }

    // =============================
    // 🔥 BATCH OPERATIONS
    // =============================
    public function batchCreateEmployees(Request $request)
    {
        $validated = $request->validate([
            'employees' => 'required|array',
            'employees.*.emp_code' => 'required|string|max:50',
            'employees.*.first_name' => 'required|string|max:100',
            'employees.*.last_name' => 'required|string|max:100',
            'employees.*.department' => 'required|integer',
            'employees.*.area' => 'required|array',
        ]);

        $results = [];
        foreach ($validated['employees'] as $employee) {
            $results[] = $this->api->post('/personnel/api/employees/', $employee);
        }

        return response()->json([
            'success' => true,
            'message' => 'Batch operation completed',
            'results' => $results
        ]);
    }

    // =============================
    // 🔥 HEALTH CHECK
    // =============================
    public function healthCheck()
    {
        try {
            $devices = $this->api->get('/iclock/api/terminals/', ['limit' => 1]);
            $employees = $this->api->get('/personnel/api/employees/', ['limit' => 1]);
            
            return response()->json([
                'status' => 'healthy',
                'devices_api' => isset($devices['code']) ? $devices['code'] : 'error',
                'employees_api' => isset($employees['code']) ? $employees['code'] : 'error',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }
}