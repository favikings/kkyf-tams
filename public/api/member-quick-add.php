<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/auth.php';

requireLogin();
header('Content-Type: application/json');

$jsonInput = json_decode((string) file_get_contents('php://input'), true);
if (is_array($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

verifyCsrf();

try {
    $me = currentUser();
    $isSuper = isSuperAdmin();
    $scopeTentId = scopedTentId();

    $input = $_POST;
    if (!$isSuper) {
        $input['tent_id'] = $scopeTentId;
    }

    $tentId = (int) ($input['tent_id'] ?? 0);

    if ($isSuper) {
        $tentCheckStmt = db()->prepare('SELECT id FROM tents WHERE id = ? AND is_active = 1');
        $tentCheckStmt->execute([$tentId]);
        if ($tentCheckStmt->fetch() === false) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tent not found.']);
            exit;
        }
    }

    $result = validateMember($input);
    $errors = $result['errors'];
    $clean = $result['clean'];

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $errors[0], 'errors' => $errors]);
        exit;
    }

    $today = currentSunday();

    db()->beginTransaction();
    try {
        $insertMemberStmt = db()->prepare(
            'INSERT INTO members (tent_id, full_name, phone, birth_month, birth_day, occupation,
                                  school_name, is_first_timer, first_seen_sunday, join_date, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, \'active\', ?)'
        );
        $insertMemberStmt->execute([
            $clean['tent_id'],
            $clean['full_name'],
            $clean['phone'],
            $clean['birth_month'],
            $clean['birth_day'],
            $clean['occupation'],
            $clean['school_name'],
            $today,
            $today,
            $me['id'],
        ]);
        $memberId = (int) db()->lastInsertId();

        $insertAttendanceStmt = db()->prepare(
            'INSERT INTO attendance (member_id, tent_id, sunday_date, marked_by, is_retroactive)
             VALUES (?, ?, ?, ?, 0)'
        );
        $insertAttendanceStmt->execute([$memberId, $clean['tent_id'], $today, $me['id']]);

        $insertFollowupStmt = db()->prepare(
            'INSERT INTO first_timer_followups (member_id, tent_id, first_visit, status)
             VALUES (?, ?, ?, \'pending\')'
        );
        $insertFollowupStmt->execute([$memberId, $clean['tent_id'], $today]);
        $followupId = (int) db()->lastInsertId();

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'member_id' => $memberId,
            'full_name' => $clean['full_name'],
            'phone' => $clean['phone'],
            'occupation' => $clean['occupation'],
            'is_first_timer' => true,
            'first_seen_sunday' => $today,
            'join_date' => $today,
            'sunday_date' => $today,
            'checked_in' => true,
            'followup_id' => $followupId,
            'followup_status' => 'pending',
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
