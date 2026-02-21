<?php
// api/public_mark_attendance.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$memberUuid = $input['member_uuid'] ?? '';
$tentId = $input['tent_id'] ?? '';
$date = date('Y-m-d');

if (empty($memberUuid) || empty($tentId)) {
    echo json_encode(['success' => false, 'error' => 'Missing member or tent information.']);
    exit;
}

try {
    // 1. Get Active Session
    $stmtSession = $pdo->query("SELECT Session_ID FROM Sessions WHERE Is_Active = 1 LIMIT 1");
    $sessionId = $stmtSession->fetchColumn();

    if (!$sessionId) {
        echo json_encode(['success' => false, 'error' => 'NO_ACTIVE_SESSION', 'message' => 'Attendance cannot be marked right now. No active session found.']);
        exit;
    }

    // 2. Duplicate Check
    $stmtCheck = $pdo->prepare("SELECT Log_ID FROM Attendance_Log WHERE Member_UUID = ? AND Attendance_Date = ?");
    $stmtCheck->execute([$memberUuid, $date]);
    if ($stmtCheck->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'ALREADY_CHECKED_IN', 'message' => 'You are already checked in today!']);
        exit;
    }

    // 3. Mark Attendance
    $stmtInsert = $pdo->prepare("
        INSERT INTO Attendance_Log (Tent_ID, Member_UUID, Session_ID, Attendance_Date, Check_In_Time)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$tentId, $memberUuid, $sessionId, $date, date('Y-m-d H:i:s')]);

    echo json_encode(['success' => true, 'message' => 'Have a great service!']);

} catch (PDOException $e) {
    error_log("Mark Attendance API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
}