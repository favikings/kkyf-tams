<?php
// api/public_mark_attendance.php
require_once '../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Rate Limiting: Max 10 requests per minute per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = 'rate_limit_' . md5($ip);
$now = time();

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'reset' => $now + 60];
}

if ($now > $_SESSION[$rateKey]['reset']) {
    $_SESSION[$rateKey] = ['count' => 0, 'reset' => $now + 60];
}

if ($_SESSION[$rateKey]['count'] > 10) {
    echo json_encode(['success' => false, 'error' => 'RATE_LIMITED', 'message' => 'Too many requests. Please try again later.']);
    exit;
}
$_SESSION[$rateKey]['count']++;

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

    // 3. Check if First Timer (no prior attendance records)
    $stmtHistory = $pdo->prepare("SELECT COUNT(*) FROM Attendance_Log WHERE Member_UUID = ?");
    $stmtHistory->execute([$memberUuid]);
    $isFirstTimer = ($stmtHistory->fetchColumn() == 0) ? 1 : 0;

    // 4. Mark Attendance
    $stmtInsert = $pdo->prepare("
        INSERT INTO Attendance_Log (Tent_ID, Member_UUID, Session_ID, Attendance_Date, Check_In_Time, Is_First_Timer)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$tentId, $memberUuid, $sessionId, $date, date('Y-m-d H:i:s'), $isFirstTimer]);

    $message = $isFirstTimer ? 'Welcome to KKYF! You have been marked as a first timer.' : 'Have a great service!';
    echo json_encode(['success' => true, 'message' => $message, 'is_first_timer' => $isFirstTimer]);

} catch (PDOException $e) {
    error_log("Mark Attendance API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
}