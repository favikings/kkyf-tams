<?php
// api/get_tents.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// No auth check needed for public join form 
// (or could optionally rate limit, but simple for now)

try {
    $stmt = $pdo->query("SELECT Tent_ID, Tent_Name FROM Tents ORDER BY Tent_Name ASC");
    $tents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $tents]);

} catch (PDOException $e) {
    error_log("Get Tents Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
