<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/notifications.php';

requireLogin();
header('Content-Type: application/json');

if (!isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Super Admin access required.']);
    exit;
}

$jsonInput = json_decode((string) file_get_contents('php://input'), true);
if (is_array($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

verifyCsrf();

$me = currentUser();
$action = trim((string) ($_POST['action'] ?? ''));
$endpoint = trim((string) ($_POST['endpoint'] ?? ''));

if ($endpoint === '' || strlen($endpoint) > 4096 || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid push subscription endpoint.']);
    exit;
}

$scheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));
if ($scheme !== 'https') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Push subscription endpoint must use HTTPS.']);
    exit;
}

$endpointHash = hash('sha256', $endpoint);

if ($action === 'unsubscribe') {
    $delete = db()->prepare(
        'DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint_hash = ?'
    );
    $delete->execute([(int) $me['id'], $endpointHash]);

    echo json_encode(['success' => true, 'data' => ['subscribed' => false]]);
    exit;
}

if ($action !== 'subscribe') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid subscription action.']);
    exit;
}

if (!pushIsConfigured()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Push notifications are not configured.']);
    exit;
}

$publicKey = trim((string) ($_POST['public_key'] ?? ''));
$authToken = trim((string) ($_POST['auth_token'] ?? ''));
$contentEncoding = trim((string) ($_POST['content_encoding'] ?? 'aes128gcm'));

if (
    $publicKey === ''
    || strlen($publicKey) > 255
    || !preg_match('/\A[A-Za-z0-9_-]+\z/', $publicKey)
    || $authToken === ''
    || strlen($authToken) > 255
    || !preg_match('/\A[A-Za-z0-9_-]+\z/', $authToken)
    || !in_array($contentEncoding, ['aes128gcm', 'aesgcm'], true)
) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid push subscription keys.']);
    exit;
}

$userAgent = mb_substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255);
$upsert = db()->prepare(
    'INSERT INTO push_subscriptions
        (user_id, endpoint_hash, endpoint, public_key, auth_token, content_encoding, user_agent)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        user_id = VALUES(user_id),
        endpoint = VALUES(endpoint),
        public_key = VALUES(public_key),
        auth_token = VALUES(auth_token),
        content_encoding = VALUES(content_encoding),
        user_agent = VALUES(user_agent),
        updated_at = CURRENT_TIMESTAMP'
);
$upsert->execute([
    (int) $me['id'],
    $endpointHash,
    $endpoint,
    $publicKey,
    $authToken,
    $contentEncoding,
    $userAgent !== '' ? $userAgent : null,
]);

echo json_encode(['success' => true, 'data' => ['subscribed' => true]]);
