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

    $rowErrors = [];
    foreach ($rows as $index => $row) {
        $input = importBuildRowInput($row, $mapping, $tentId);
        $result = validateMember($input);
        if ($result['errors'] === []) {
            continue;
        }
        $rowErrors[] = [
            'row' => $index + 1,
            'name' => (string) $input['full_name'],
            'errors' => $result['errors'],
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'valid' => count($rows) - count($rowErrors),
            'invalid' => count($rowErrors),
            'total' => count($rows),
            'row_errors' => $rowErrors,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
