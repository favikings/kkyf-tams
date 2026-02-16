<?php
// api/test_api.php
// Script to simulate API call and verify Audit Log

require_once '../includes/db_connect.php';

session_start();
// Mock Super Admin Session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Super Admin';
$_SESSION['username'] = 'TestAdmin';

// 1. Seed a Test Member
$uuid = 'test-uuid-' . time();
$stmt = $pdo->prepare("INSERT INTO Members (Member_UUID, Full_Name, Current_Tent_ID, Status) VALUES (?, 'Test User', 1, 'Student')");
$stmt->execute([$uuid]);

echo "Seeded Test Member: $uuid\n";

// 2. Simulate POST Request
// Because we are in the same dir as member_ops.php now, the require should work if we adjust the path
// actually member_ops.php has require_once '../includes/db_connect.php';
// so if we run this from api/ dir, it should work.

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_member';
$_POST['member_uuid'] = $uuid;
$_POST['full_name'] = 'Test User Updated';
$_POST['status'] = 'Worker';
$_POST['tent_id'] = 2; // Moving to Tent 2

// Capture Output
ob_start();
require 'member_ops.php';
$output = ob_get_clean();

echo "API Output: $output\n";

// 3. Verify Audit Log
$stmt = $pdo->query("SELECT * FROM Audit_Log ORDER BY Log_ID DESC LIMIT 1");
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if ($log && strpos($log['Details'], $uuid) !== false) {
    echo "Audit Log Verification: PASS\n";
    echo "Log Details: " . $log['Details'] . "\n";
} else {
    echo "Audit Log Verification: FAIL\n";
    print_r($log);
}

// Cleanup
$pdo->prepare("DELETE FROM Members WHERE Member_UUID = ?")->execute([$uuid]);
if ($log) {
    $pdo->prepare("DELETE FROM Audit_Log WHERE Log_ID = ?")->execute([$log['Log_ID']]);
}
?>