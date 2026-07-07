<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=checktime_database', 'root', '');
    $stmt = $pdo->query('DESCRIBE employee_schedules');
    foreach ($stmt as $row) {
        echo $row['Field'] . "\n";
    }
    echo "\n---\n";
    // Also check if employee_schedules has data
    $count = $pdo->query('SELECT COUNT(*) FROM employee_schedules')->fetchColumn();
    echo "Rows: " . $count . "\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
