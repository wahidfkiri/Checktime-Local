<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=checktime_database', 'root', '');
    $stmt = $pdo->query('DESCRIBE employees');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
