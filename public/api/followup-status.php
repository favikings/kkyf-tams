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
    $scopeTentId = scopedTentId();

    $followupId = (int) ($_POST['followup_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    $assignedTo = $_POST['assigned_to'] ?? null;
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $notes = $notes === '' ? null : $notes;

    $allowedStatuses = ['pending', 'called', 'converted', 'not_returning'];
    if (!in_array($status, $allowedStatuses, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid follow-up status.']);
        exit;
    }

    $followupStmt = db()->prepare(
        'SELECT id, tent_id, member_id, status FROM first_timer_followups WHERE id = ?'
    );
    $followupStmt->execute([$followupId]);
    $followup = $followupStmt->fetch();

    if ($followup === false || ($scopeTentId !== null && (int) $followup['tent_id'] !== $scopeTentId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Follow-up not found in your tent.']);
        exit;
    }

    $cleanAssignedTo = null;
    if ($assignedTo !== null && $assignedTo !== '' && (int) $assignedTo !== 0) {
        $cleanAssignedTo = (int) $assignedTo;
        $adminStmt = db()->prepare(
            "SELECT id FROM users WHERE id = ? AND role = 'tent_admin' AND tent_id = ? AND status = 'approved' AND is_active = 1"
        );
        $adminStmt->execute([$cleanAssignedTo, (int) $followup['tent_id']]);
        if ($adminStmt->fetch() === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Assigned admin is not valid for this tent.']);
            exit;
        }
    }

    $updateStmt = db()->prepare(
        'UPDATE first_timer_followups SET status = ?, assigned_to = ?, notes = ?, updated_by = ? WHERE id = ?'
    );
    $updateStmt->execute([$status, $cleanAssignedTo, $notes, (int) $me['id'], $followupId]);

    $refreshStmt = db()->prepare(
        'SELECT member_id, status, assigned_to, updated_by, updated_at FROM first_timer_followups WHERE id = ?'
    );
    $refreshStmt->execute([$followupId]);
    $row = $refreshStmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'followup_id' => $followupId,
            'member_id' => (int) $row['member_id'],
            'status' => (string) $row['status'],
            'assigned_to' => $row['assigned_to'] !== null ? (int) $row['assigned_to'] : null,
            'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
            'updated_at' => $row['updated_at'] !== null
                ? (new DateTime((string) $row['updated_at']))->format(DATE_ATOM)
                : null,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
