<?php
// api/search_members.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// 1. Auth Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Tent Admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 2. Input Validation
$query = trim($_GET['query'] ?? '');
$tentId = $_GET['tent_id'] ?? $_SESSION['assigned_tent_id'] ?? null;

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]); // Return empty if query too short
    exit;
}

if (!$tentId) {
    echo json_encode(['success' => false, 'error' => 'No Tent Context']);
    exit;
}

try {
    // 3. Search Logic
    // Search by Name or Phone
    $sql = "
        SELECT Member_UUID, Full_Name, Status, Phone, School
        FROM Members
        WHERE Current_Tent_ID = ?
        AND (Full_Name LIKE ? OR Phone LIKE ?)
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([$tentId, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add 'is_present' flag check? Use subquery or separate check if needed.
    // For now, simple search. Frontend can check local state or we can enhance later.

    echo json_encode(['success' => true, 'data' => $results]);

} catch (PDOException $e) {
    error_log("Search API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
