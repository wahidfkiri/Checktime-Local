<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

try {
    // Simulate the POST request
    $req = Illuminate\Http\Request::create(
        '/admin/employee-schedules/bulk-create',
        'POST',
        [
            '_token' => csrf_token(),
            'employee_ids' => ['all'],
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'schedule_type' => 'fixe',
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '09:00',
            'end_time' => '18:00',
            'override_existing' => true,
        ]
    );
    
    $controller = app()->make(\Vendor\Planning\Controllers\EmployeeScheduleController::class);
    $resp = $controller->bulkCreate($req);
    
    echo "Status: " . $resp->getStatusCode() . "\n";
    echo "Content: " . $resp->getContent() . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
