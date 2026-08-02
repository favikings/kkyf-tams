<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/import.php';

requireSuperAdmin();
header('Content-Type: application/json');

$jsonInput = json_decode((string) file_get_contents('php://input'), true);
if (is_array($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

verifyCsrf();

try {
    $pending = $_SESSION['import_pending'] ?? null;
    if (!is_array($pending) || !isset($pending['columns'], $pending['rows'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'No pending import. Upload a file first.']);
        exit;
    }

    $columns = $pending['columns'];
    $rows = $pending['rows'];
    $tentId = (int) ($_POST['tent_id'] ?? 0);
    $mapping = $_POST['mapping'] ?? null;

    if (!is_array($mapping)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Column mapping is required.']);
        exit;
    }

    $mappingErrors = importValidateMapping($mapping, $columns);
    if ($mappingErrors !== []) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid column mapping: ' . implode('; ', $mappingErrors)]);
        exit;
    }

    $me = currentUser();
    $today = date('Y-m-d');
    $imported = 0;
    $skipped = 0;
    $errors = [];

    db()->beginTransaction();
    try {
        foreach ($rows as $index => $row) {
            $input = importBuildRowInput($row, $mapping, $tentId);
            $result = validateMember($input);
            if ($result['errors'] !== []) {
                $skipped++;
                $errors[] = [
                    'row' => $index + 1,
                    'name' => (string) $input['full_name'],
                    'errors' => $result['errors'],
                ];
                continue;
            }

            try {
                $insertStmt = db()->prepare(
                    'INSERT INTO members
                        (tent_id, full_name, phone, birth_month, birth_day, occupation, school_name,
                         is_first_timer, join_date, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)'
                );
                $insertStmt->execute([
                    $result['clean']['tent_id'],
                    $result['clean']['full_name'],
                    $result['clean']['phone'],
                    $result['clean']['birth_month'],
                    $result['clean']['birth_day'],
                    $result['clean']['occupation'],
                    $result['clean']['school_name'],
                    $today,
                    (int) $me['id'],
                ]);
                $imported++;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $skipped++;
                    $errors[] = [
                        'row' => $index + 1,
                        'name' => (string) $input['full_name'],
                        'errors' => ['This record already exists or violates a constraint.'],
                    ];
                    continue;
                }
                throw $e;
            }
        }

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    unset($_SESSION['import_pending']);

    echo json_encode([
        'success' => true,
        'data' => [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
