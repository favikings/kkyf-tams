<?php
// api/register_member.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// 1. Auth Check (Tent Admin Role)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Tent Admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 2. Input Parsing
$input = json_decode(file_get_contents('php://input'), true);

$fullName = trim($input['full_name'] ?? '');
$status = $input['status'] ?? 'Student';
$school = $input['school'] ?? null;
$phone = $input['phone'] ?? null;
$dob = $input['dob'] ?? null;
if ($dob) {
    // Force year 2000 for privacy
    $dob = '2000-' . date('m-d', strtotime($dob));
}
$tentId = $input['tent_id'] ?? $_SESSION['assigned_tent_id'] ?? null;

// Basic Validation
if (empty($fullName) || empty($tentId)) {
    echo json_encode(['success' => false, 'error' => 'Full Name and Tent ID are required.']);
    exit;
}

try {
    // 3. Insert Logic
    // Create new Member UUID
    $uuid = bin2hex(random_bytes(16)); // Simple generation or UUID v4

    $sql = "
        INSERT INTO Members (Member_UUID, Full_Name, Status, Current_Tent_ID, School, Phone, DOB, Join_Date)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $uuid,
        $fullName,
        $status,
        $tentId,
        $school,
        $phone,
        $dob
    ]);

    // Return the new member details including UUID so frontend can immediately mark attendance
    echo json_encode([
        'success' => true,
        'message' => 'Member registered successfully',
        'member' => [
            'Member_UUID' => $uuid,
            'Full_Name' => $fullName,
            'Status' => $status
        ]
    ]);

} catch (PDOException $e) {
    error_log("Registration API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
