<?php
// api/member_ops.php
// API Endpoint for Member Operations (Update, etc.)

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// 1. Auth Check (API version)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$role = $_SESSION['role'];


// 2. Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_member') {
        // Allow Tent Admin to create members
        if ($role !== 'Super Admin' && $role !== 'Tent Admin') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $status = $_POST['status'] ?? 'Student';
        $phone = $_POST['phone'] ?? null;
        $dob = $_POST['dob'] ?? null;
        if ($dob) {
            // Force year 2000 for privacy
            $dob = '2000-' . date('m-d', strtotime($dob));
        }

        // Scope to assigned tent if Tent Admin
        $tentId = ($role === 'Tent Admin') ? $_SESSION['assigned_tent_id'] : ($_POST['tent_id'] ?? null);

        if (empty($fullName) || empty($tentId)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        // Generate UUID in PHP to ensure we have it for both tables
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        try {
            $pdo->beginTransaction();

            // Insert Member
            $stmtInsert = $pdo->prepare("
                INSERT INTO Members (Member_UUID, Full_Name, Status, Current_Tent_ID, Phone, Birthdate, Join_Date)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmtInsert->execute([$uuid, $fullName, $status, $tentId, $phone, $dob]);
            $memberId = $pdo->lastInsertId();

            // Fetch Active Session
            $stmtSession = $pdo->query("SELECT Session_ID FROM Sessions WHERE Is_Active = 1 LIMIT 1");
            $sessionId = $stmtSession->fetchColumn();

            if (!$sessionId) {
                // Fallback: If no session active, maybe we shouldn't fail the member creation?
                // But Attendance Log requires it. Let's log a warning and skip attendance or fail.
                // Decided: Fail to ensure data integrity.
                throw new Exception("No active session found.");
            }

            // Auto-Mark as Present (First Timer) - USING UUID
            $stmtLog = $pdo->prepare("
                INSERT INTO Attendance_Log (Member_UUID, Tent_ID, Attendance_Date, Session_ID, Is_First_Timer, Check_In_Time)
                VALUES (?, ?, CURDATE(), ?, 1, NOW())
            ");
            $stmtLog->execute([$uuid, $tentId, $sessionId]);

            // Audit
            $stmtAudit = $pdo->prepare("INSERT INTO Audit_Log (Admin_ID, Action_Type, Details, IP_Address) VALUES (?, 'CREATE_MEMBER', ?, ?)");
            $stmtAudit->execute([$_SESSION['user_id'], "Created Member $fullName ($status) in Tent $tentId", $_SERVER['REMOTE_ADDR']]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Member added and marked present']);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Create Member Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database Error']);
        }

    } elseif ($action === 'update_member') {
        // Allow Super Admin and Tent Admin
        if ($role !== 'Super Admin' && $role !== 'Tent Admin') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $uuid = $_POST['member_uuid'] ?? '';
        $fullName = trim($_POST['full_name'] ?? ''); // Restore Full Name
        $status = $_POST['status'] ?? 'Student';
        $phone = $_POST['phone'] ?? null;
        $dob = $_POST['dob'] ?? null;
        if ($dob) {
            // Force year 2000 for privacy
            $dob = '2000-' . date('m-d', strtotime($dob));
        }

        // Tent Logic: Tent Admin cannot change Tent ID, Super Admin can.
        if ($role === 'Tent Admin') {
            $tentId = $_SESSION['assigned_tent_id'];
        } else {
            $tentId = $_POST['tent_id'] ?? null;
        }

        if (empty($uuid) || empty($fullName) || empty($tentId)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        try {
            // Begin Transaction
            $pdo->beginTransaction();

            // Get Old Data for Audit
            $stmtOld = $pdo->prepare("SELECT * FROM Members WHERE Member_UUID = ?");
            $stmtOld->execute([$uuid]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception("Member not found");
            }

            // Scope Check for Tent Admin
            if ($role === 'Tent Admin' && $oldData['Current_Tent_ID'] != $_SESSION['assigned_tent_id']) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized: Member belongs to another tent']);
                $pdo->rollBack();
                exit;
            }

            // Business Logic: If status is 'Worker', School must be NULL
            $school = $_POST['school'] ?? null;
            if ($status === 'Worker') {
                $school = null;
            }

            // Update Member WITH PHONE AND DOB
            $stmtUpdate = $pdo->prepare("
                UPDATE Members 
                SET Full_Name = ?, Status = ?, Current_Tent_ID = ?, School = ?, Phone = ?, Birthdate = ?
                WHERE Member_UUID = ?
            ");
            $stmtUpdate->execute([$fullName, $status, $tentId, $school, $phone, $dob, $uuid]);

            // Create Audit Log Details
            $details = "Updated Member ($uuid): ";
            if ($oldData['Full_Name'] !== $fullName)
                $details .= "Name changed from '{$oldData['Full_Name']}' to '$fullName'. ";
            if ($oldData['Current_Tent_ID'] != $tentId)
                $details .= "Moved Tent from {$oldData['Current_Tent_ID']} to $tentId. ";
            if ($oldData['Status'] !== $status)
                $details .= "Status changed from {$oldData['Status']} to $status. ";
            // Log School change
            $oldSchool = $oldData['School'] ?? 'NULL';
            $newSchool = $school ?? 'NULL';
            if ($oldSchool !== $newSchool)
                $details .= "School changed from '$oldSchool' to '$newSchool'. ";
            // Log Phone change
            $oldPhone = $oldData['Phone'] ?? 'NULL';
            $newPhone = $phone ?? 'NULL';
            if ($oldPhone !== $newPhone)
                $details .= "Phone changed from '$oldPhone' to '$newPhone'. ";
            // Log DOB change
            $oldDob = $oldData['Birthdate'] ?? 'NULL';
            $newDob = $dob ?? 'NULL';
            if ($oldDob !== $newDob)
                $details .= "DOB changed from '$oldDob' to '$newDob'. ";

            // Insert Audit Log
            $stmtAudit = $pdo->prepare("
                INSERT INTO Audit_Log (Admin_ID, Action_Type, Details, IP_Address)
                VALUES (?, 'UPDATE_MEMBER', ?, ?)
            ");
            $stmtAudit->execute([
                $_SESSION['user_id'],
                $details,
                $_SERVER['REMOTE_ADDR']
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Member updated successfully']);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("API Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } elseif ($action === 'delete_member') {
        // Enforce SUPER ADMIN ONLY or ADMIN (as implied by request to add delete to both)
        // Brief check: "Add a delete action to all necessary place where i can delete members from either super admin or tent admin"
        // So Tent Admin can delete too.
        if ($role !== 'Super Admin' && $role !== 'Tent Admin') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $uuid = $_POST['member_uuid'] ?? '';
        if (empty($uuid)) {
            echo json_encode(['success' => false, 'error' => 'Missing UUID']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Fetch Member for scope check
            $stmtM = $pdo->prepare("SELECT Full_Name, Current_Tent_ID FROM Members WHERE Member_UUID = ?");
            $stmtM->execute([$uuid]);
            $member = $stmtM->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                throw new Exception("Member not found");
            }

            // Scope Check
            if ($role === 'Tent Admin' && $member['Current_Tent_ID'] != $_SESSION['assigned_tent_id']) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized: Member belongs to another tent']);
                $pdo->rollBack();
                exit;
            }

            // Delete Related Attendance Logs (Cascade usually handles this, but let's be explicit if FK missing)
            $pdo->prepare("DELETE FROM Attendance_Log WHERE Member_UUID = ?")->execute([$uuid]);

            // Delete Member
            $pdo->prepare("DELETE FROM Members WHERE Member_UUID = ?")->execute([$uuid]);

            // Audit
            $stmtAudit = $pdo->prepare("INSERT INTO Audit_Log (Admin_ID, Action_Type, Details, IP_Address) VALUES (?, 'DELETE_MEMBER', ?, ?)");
            $stmtAudit->execute([$_SESSION['user_id'], "Deleted Member {$member['Full_Name']} from Tent {$member['Current_Tent_ID']}", $_SERVER['REMOTE_ADDR']]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Member deleted']);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Delete Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database Error']);
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>