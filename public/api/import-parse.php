<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/import.php';
require_once __DIR__ . '/../../app/includes/xlsx.php';

requireSuperAdmin();
header('Content-Type: application/json');

verifyCsrf();

try {
    $file = $_FILES['file'] ?? null;
    if (!is_array($file) || !is_string($file['tmp_name'] ?? null)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
        exit;
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'File upload failed. The server may reject files over its upload limit.']);
        exit;
    }

    if ((int) $file['size'] > 10 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'File must be 10MB or smaller.']);
        exit;
    }

    $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['csv', 'xlsx'], true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Only .csv or .xlsx files are supported.']);
        exit;
    }

    $rows = $extension === 'csv'
        ? importReadCsvRows((string) $file['tmp_name'])
        : importReadXlsxRows((string) $file['tmp_name']);

    if ($rows === []) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'The file has no rows to import.']);
        exit;
    }

    $columns = array_map('trim', array_map('strval', $rows[0]));
    $dataRows = array_slice($rows, 1);
    $dataRows = array_values(array_filter(
        $dataRows,
        static fn (array $r): bool => trim(implode('', $r)) !== ''
    ));

    if ($columns === [] || $dataRows === []) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'The file needs a header row and at least one data row.']);
        exit;
    }

    $_SESSION['import_pending'] = [
        'columns' => $columns,
        'rows' => $dataRows,
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'columns' => $columns,
            'first_five' => array_slice($dataRows, 0, 5),
            'total' => count($dataRows),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
