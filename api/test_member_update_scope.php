<?php
// api/test_member_update_scope.php
require_once '../includes/db_connect.php';

// Mock Session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Tent Admin';
$_SESSION['assigned_tent_id'] = 1;

header('Content-Type: text/plain');

function test($name, $condition)
{
    echo $name . ": " . ($condition ? "PASS" : "FAIL") . "\n";
}

try {
    echo "--- STARTING SCOPE VERIFICATION ---\n";

    // Setup: Ensure active session for create logic (if we use create)
    // But let's just insert directly to be faster/cleaner for setup
    $uuid1 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    $pdo->prepare("INSERT INTO Members (Member_UUID, Full_Name, Status, Current_Tent_ID, Join_Date) VALUES (?, 'Member One', 'Student', 1, NOW())")->execute([$uuid1]);

    $uuid2 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    $pdo->prepare("INSERT INTO Members (Member_UUID, Full_Name, Status, Current_Tent_ID, Join_Date) VALUES (?, 'Member Two', 'Student', 2, NOW())")->execute([$uuid2]);

    // Test 1: Update Own Member
    echo "\n[Test 1] Update Own Member ($uuid1)\n";
    $_POST = [
        'action' => 'update_member',
        'member_uuid' => $uuid1,
        'full_name' => 'Member One Updated',
        'status' => 'Worker',
        'tent_id' => 1 // Correct Tent ID
    ];
    ob_start();
    include 'member_ops.php';
    $out1 = ob_get_clean();
    $res1 = json_decode($out1, true);

    test("Update Own Member Success", $res1['success'] ?? false);

    // Verify DB
    $stmt = $pdo->prepare("SELECT Full_Name FROM Members WHERE Member_UUID = ?");
    $stmt->execute([$uuid1]);
    $name = $stmt->fetchColumn();
    test("Name Changed to '$name'", $name === 'Member One Updated');


    // Test 2: Update Other Member
    echo "\n[Test 2] Update Other Tent Member ($uuid2)\n";
    $_POST = [
        'action' => 'update_member',
        'member_uuid' => $uuid2,
        'full_name' => 'Member Two Hacked',
        'status' => 'Worker',
        'tent_id' => 1 // Trying to pull them into my tent? Or just update them?
    ];
    ob_start();
    include 'member_ops.php';
    $out2 = ob_get_clean();
    $res2 = json_decode($out2, true);

    test("Update Other Member Blocked", ($res2['success'] === false && strpos($res2['error'], 'Unauthorized') !== false));


    // Test 3: Try to Move Own Member to Another Tent
    echo "\n[Test 3] Try to Move Own Member to Tent 2\n";
    $_POST = [
        'action' => 'update_member',
        'member_uuid' => $uuid1,
        'full_name' => 'Member One Moved?',
        'status' => 'Student',
        'tent_id' => 2 // Intruder attempt
    ];
    ob_start();
    include 'member_ops.php';
    $out3 = ob_get_clean();

    // API logic forces tent_id = assigned_tent_id for Tent Admins, ignoring POST.
    // So update should succeed, but Tent ID should remain 1.
    $res3 = json_decode($out3, true);
    test("Update Executed (ignoring tent change)", $res3['success'] ?? false);

    $stmt = $pdo->prepare("SELECT Current_Tent_ID FROM Members WHERE Member_UUID = ?");
    $stmt->execute([$uuid1]);
    $tent = $stmt->fetchColumn();
    test("Tent ID remained 1 (Actual: $tent)", $tent == 1);

    echo "\n--- VERIFICATION COMPLETE ---\n";

    // Cleanup
    $pdo->prepare("DELETE FROM Members WHERE Member_UUID IN (?, ?) AND Full_Name LIKE 'Member%'")->execute([$uuid1, $uuid2]);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
