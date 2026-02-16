<?php
// api/register_admin.php
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

// SECURITY: Define the shared secret code here (or pull from config)
// For simplicity, we hardcode it now. In production, this should be in an env file.
define('REGISTRATION_CODE', 'KKYF2026');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // 1. Inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $tentId = $_POST['tent_id'] ?? null;
    $regCode = $_POST['reg_code'] ?? '';

    // 2. Validation
    if (empty($username) || empty($email) || empty($password) || empty($tentId) || empty($regCode)) {
        throw new Exception("All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address.");
    }

    if ($regCode !== REGISTRATION_CODE) {
        throw new Exception("Invalid Registration Code.");
    }

    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters.");
    }

    // 3. Duplicate Check (Username OR Email)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Admin_User WHERE Username = ? OR Email = ?");
    $stmtCheck->execute([$username, $email]);
    if ($stmtCheck->fetchColumn() > 0) {
        throw new Exception("Username or Email already exists.");
    }

    // 4. Create User
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Tent Admin'; // Only Tent Admins can self-register

    $stmtInsert = $pdo->prepare("
        INSERT INTO Admin_User (Username, Email, Password_Hash, Role, Assigned_Tent_ID)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$username, $email, $passwordHash, $role, $tentId]);
    $newId = $pdo->lastInsertId();

    // 5. Audit Log (Simulated since we don't have a session user yet, or log as System)
    // We can log "New Admin Registered"
    $stmtAudit = $pdo->prepare("
        INSERT INTO Audit_Log (Action_Type, Details) 
        VALUES ('REGISTER_ADMIN', ?)
    ");
    $stmtAudit->execute(["New Tent Admin ($username) registered for Tent ID $tentId"]);

    echo json_encode(['success' => true, 'message' => 'Account created successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>