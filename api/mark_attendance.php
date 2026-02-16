<?php
// api/mark_attendance.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Tent Admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$memberUuid = $input['member_uuid'] ?? null;
$tentId = $_SESSION['assigned_tent_id'];

if (!$memberUuid) {
    echo json_encode(['success' => false, 'error' => 'Missing Member UUID']);
    exit;
}

try {
    // 0. Get Session
    $stmtSession = $pdo->query("SELECT Session_ID FROM Sessions WHERE Is_Active = 1 LIMIT 1");
    $sessionId = $stmtSession->fetchColumn();
    if (!$sessionId)
        throw new Exception("No active session");

    // 1. Check Previous Attendance Today
    $stmtCheck = $pdo->prepare("SELECT Log_ID FROM Attendance_Log WHERE Member_UUID = ? AND Tent_ID = ? AND Attendance_Date = CURDATE()");
    $stmtCheck->execute([$memberUuid, $tentId]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already marked present']);
        exit;
    }

    // 2. Check if First Timer (Global History)
    // If they have NO attendance logs (in any tent), they are a first timer.
    $stmtHistory = $pdo->prepare("SELECT COUNT(*) FROM Attendance_Log WHERE Member_UUID = ?");
    $stmtHistory->execute([$memberUuid]);
    $historyCount = $stmtHistory->fetchColumn();
    $isFirstTimer = ($historyCount == 0) ? 1 : 0;

    // 3. Insert Log
    $stmtInsert = $pdo->prepare("
        INSERT INTO Attendance_Log (Member_UUID, Tent_ID, Attendance_Date, Session_ID, Check_In_Time, Is_First_Timer)
        VALUES (?, ?, CURDATE(), ?, NOW(), ?)
    ");
    $stmtInsert->execute([$memberUuid, $tentId, $sessionId, $isFirstTimer]);

    echo json_encode(['success' => true, 'message' => 'Marked Present']);

} catch (PDOException $e) {
    error_log("Mark Attendance Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database Error']);
}
?>