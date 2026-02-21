<?php
// api/public_register.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// 1. Input Parsing
$input = json_decode(file_get_contents('php://input'), true);

$fullName = trim($input['full_name'] ?? '');
$status = $input['status'] ?? 'Student';
$school = $input['school'] ?? null;
$phone = $input['phone'] ?? null;
$dob = $input['dob'] ?? null;
$tentId = $input['tent_id'] ?? null;

// Basic Validation
if (empty($fullName) || empty($tentId) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Full Name, Phone, and Tent are required.']);
    exit;
}

try {
    // 2. Duplicate Check
    // Check by Phone OR exactly matching Name within the same Tent
    $stmtCheck = $pdo->prepare("
        SELECT Member_UUID FROM Members 
        WHERE Phone = ? OR (Full_Name = ? AND Current_Tent_ID = ?)
    ");
    $stmtCheck->execute([$phone, $fullName, $tentId]);
    $existing = $stmtCheck->fetchColumn();

    if ($existing) {
        echo json_encode(['success' => false, 'error' => 'DUPLICATE', 'message' => 'A member with this name or phone already exists in this Tent.']);
        exit;
    }

    // 3. Insert
    $uuid = bin2hex(random_bytes(16));

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

    echo json_encode(['success' => true, 'message' => 'Welcome to the family!']);

} catch (PDOException $e) {
    error_log("Public Register API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}