<?php
// api/session_ops.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Auth: Super Admin ONLY for session management
checkAuth('Super Admin');

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'reset_session') {
    $sessionName = $_POST['session_name'] ?? date('Y');

    try {
        $pdo->beginTransaction();

        // 1. Deactivate current active session(s)
        $stmtDeactivate = $pdo->prepare("UPDATE Sessions SET Is_Active = 0 WHERE Is_Active = 1");
        $stmtDeactivate->execute();

        // 2. Create New Session
        $stmtNew = $pdo->prepare("INSERT INTO Sessions (Session_Name, Start_Date, Is_Active) VALUES (?, CURDATE(), 1)");
        $stmtNew->execute([$sessionName]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "New session '$sessionName' started successfully."]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>