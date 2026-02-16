<?php
// api/test_db_structure.php
require_once '../includes/db_connect.php';

header('Content-Type: text/plain');

try {
    echo "--- Database Connection ---\n";
    echo "Status: Connected\n";
    echo "Database: " . $dbname . "\n\n";

    $tables = ['Members', 'Attendance_Log', 'Sessions'];

    foreach ($tables as $table) {
        echo "--- Table: $table ---\n";
        $stmt = $pdo->prepare("DESCRIBE $table");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            echo str_pad($col['Field'], 20) . " | " . $col['Type'] . "\n";
        }
        echo "\n";
    }

} catch (PDOException $e) {
    echo "Connection Failed: " . $e->getMessage();
}
?>