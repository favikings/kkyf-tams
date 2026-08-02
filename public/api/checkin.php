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
    $tentId = scopedTentId();
    $memberId = (int) ($_POST['member_id'] ?? 0);
    $sundayDate = trim((string) ($_POST['sunday_date'] ?? ''));
    $sundayDate = $sundayDate === '' ? currentSunday() : $sundayDate;

    $sundayDt = DateTimeImmutable::createFromFormat('!Y-m-d', $sundayDate);
    if ($sundayDt === false || $sundayDt->format('Y-m-d') !== $sundayDate || (int) $sundayDt->format('N') !== 7) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'sunday_date must be a Sunday.']);
        exit;
    }

    $memberStmt = db()->prepare('SELECT id, tent_id, full_name FROM members WHERE id = ?');
    $memberStmt->execute([$memberId]);
    $member = $memberStmt->fetch();

    if ($member === false || ($tentId !== null && (int) $member['tent_id'] !== $tentId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Member not found in your tent.']);
        exit;
    }

    $effectiveTentId = (int) $member['tent_id'];
    $isRetroactive = $sundayDate !== currentSunday() ? 1 : 0;

    $insertStmt = db()->prepare(
        'INSERT INTO attendance (member_id, tent_id, sunday_date, marked_by, is_retroactive)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insertStmt->execute([$memberId, $effectiveTentId, $sundayDate, $me['id'], $isRetroactive]);

    $checkedInStmt = db()->prepare('SELECT checked_in_at FROM attendance WHERE member_id = ? AND sunday_date = ?');
    $checkedInStmt->execute([$memberId, $sundayDate]);
    $checkedInAt = $checkedInStmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'member_id' => $memberId,
            'member_full_name' => (string) $member['full_name'],
            'sunday_date' => $sundayDate,
            'checked_in_at' => $checkedInAt !== false ? (string) $checkedInAt['checked_in_at'] : null,
            'is_retroactive' => $isRetroactive,
            'already_checked_in' => false,
        ],
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Already checked in for this Sunday.']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
