<?php
// api/attendance_sync.php
// Handles bulk attendance syncing with deduplication

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// 1. Auth Check (API safe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow Tent Admin or Super Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Tent Admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 2. Parse Input
$input = json_decode(file_get_contents('php://input'), true);
$logs = $input['logs'] ?? [];

if (empty($logs)) {
    echo json_encode(['success' => false, 'error' => 'No logs provided']);
    exit;
}

// 3. Process Logs
$stats = [
    'received' => count($logs),
    'inserted' => 0,
    'skipped' => 0,
    'errors' => 0
];

try {
    // Get Active Session
    $stmtSession = $pdo->query("SELECT Session_ID FROM Sessions WHERE Is_Active = 1 LIMIT 1");
    $sessionId = $stmtSession->fetchColumn();

    if (!$sessionId) {
        // Fallback or Error? For now, if no session active, we can't record properly.
        // Let's assume a default or fail.
        echo json_encode(['success' => false, 'error' => 'No active session found']);
        exit;
    }

    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Attendance_Log WHERE Member_UUID = ? AND Attendance_Date = ?");
    $stmtInsert = $pdo->prepare("
        INSERT INTO Attendance_Log (Tent_ID, Member_UUID, Session_ID, Attendance_Date, Check_In_Time)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($logs as $log) {
        // Basic Validation
        if (empty($log['member_uuid']) || empty($log['date']) || empty($log['tent_id'])) {
            $stats['errors']++;
            continue; // Skip invalid
        }

        // Deduplication: Check if log exists for this member on this date
        $stmtCheck->execute([$log['member_uuid'], $log['date']]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists > 0) {
            $stats['skipped']++;
        } else {
            $stmtInsert->execute([
                $log['tent_id'],
                $log['member_uuid'],
                $sessionId,
                $log['date'],
                $log['timestamp'] ?? date('Y-m-d H:i:s')
            ]);
            $stats['inserted']++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'stats' => $stats]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Sync Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()]);
}
?>