<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Creates one persistent notification for every approved, active Super Admin.
 * The caller owns the surrounding transaction.
 *
 * @return list<int> recipient user IDs
 */
function createSuperAdminRegistrationNotifications(
    PDO $pdo,
    int $registeredUserId,
    string $registeredName,
    string $tentName
): array {
    $superAdmins = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE role = 'super_admin'
           AND status = 'approved'
           AND is_active = 1"
    );
    $superAdmins->execute();
    $recipientIds = array_map('intval', $superAdmins->fetchAll(PDO::FETCH_COLUMN));

    if ($recipientIds === []) {
        return [];
    }

    $title = 'New admin registration';
    $message = $registeredName . ' requested access for ' . $tentName . '.';
    $insert = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, message, action_url, related_user_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($recipientIds as $recipientId) {
        $insert->execute([
            $recipientId,
            'tent_admin_registration',
            $title,
            $message,
            'tent-admins.php',
            $registeredUserId,
        ]);
    }

    return $recipientIds;
}

function notificationUnreadCount(int $userId): int
{
    $statement = db()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL'
    );
    $statement->execute([$userId]);

    return (int) $statement->fetchColumn();
}

/** @return list<array<string,mixed>> */
function notificationsForUser(int $userId, int $limit = 50): array
{
    $limit = max(1, min($limit, 100));
    $statement = db()->prepare(
        'SELECT id, type, title, message, action_url, related_user_id, read_at, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT ?'
    );
    $statement->bindValue(1, $userId, PDO::PARAM_INT);
    $statement->bindValue(2, $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function markNotificationRead(int $userId, int $notificationId): void
{
    $statement = db()->prepare(
        'UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ? AND user_id = ?'
    );
    $statement->execute([$notificationId, $userId]);
}

function markAllNotificationsRead(int $userId): void
{
    $statement = db()->prepare(
        'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL'
    );
    $statement->execute([$userId]);
}

function pushIsConfigured(): bool
{
    $subject = trim((string) env('VAPID_SUBJECT', ''));
    $publicKey = trim((string) env('VAPID_PUBLIC_KEY', ''));
    $privateKey = trim((string) env('VAPID_PRIVATE_KEY', ''));

    return $subject !== '' && $publicKey !== '' && $privateKey !== '';
}

function pushPublicKey(): string
{
    return pushIsConfigured() ? trim((string) env('VAPID_PUBLIC_KEY', '')) : '';
}

/**
 * Sends a best-effort Web Push alert to every stored device belonging to the
 * supplied users. Expired browser subscriptions are removed automatically.
 * Registration must never roll back because a third-party push service fails.
 *
 * @param list<int> $userIds
 */
function sendPushToUsers(array $userIds, string $title, string $message, string $actionUrl): void
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if ($userIds === [] || !pushIsConfigured()) {
        return;
    }

    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoloadPath)) {
        throw new RuntimeException('Web Push dependencies are not installed.');
    }
    require_once $autoloadPath;

    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $statement = db()->prepare(
        "SELECT id, endpoint, public_key, auth_token, content_encoding
         FROM push_subscriptions
         WHERE user_id IN ($placeholders)"
    );
    $statement->execute($userIds);
    $subscriptions = $statement->fetchAll();

    if ($subscriptions === []) {
        return;
    }

    $webPush = new Minishlink\WebPush\WebPush(
        [
            'VAPID' => [
                'subject' => trim((string) env('VAPID_SUBJECT', '')),
                'publicKey' => trim((string) env('VAPID_PUBLIC_KEY', '')),
                'privateKey' => trim((string) env('VAPID_PRIVATE_KEY', '')),
            ],
        ],
        ['TTL' => 300, 'urgency' => 'high']
    );

    $payload = json_encode([
        'title' => $title,
        'body' => $message,
        'url' => $actionUrl,
        'icon' => 'assets/icons/icon-192.png',
        'badge' => 'assets/icons/icon-192.png',
        'tag' => 'tent-admin-registration',
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $subscriptionIdsByEndpoint = [];
    foreach ($subscriptions as $row) {
        $endpoint = (string) $row['endpoint'];
        $subscriptionIdsByEndpoint[$endpoint] = (int) $row['id'];
        $webPush->queueNotification(
            new Minishlink\WebPush\Subscription(
                $endpoint,
                (string) $row['public_key'],
                (string) $row['auth_token'],
                (string) $row['content_encoding']
            ),
            $payload
        );
    }

    $deleteExpired = db()->prepare('DELETE FROM push_subscriptions WHERE id = ?');
    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            continue;
        }

        $endpoint = $report->getEndpoint();
        if ($report->isSubscriptionExpired() && isset($subscriptionIdsByEndpoint[$endpoint])) {
            $deleteExpired->execute([$subscriptionIdsByEndpoint[$endpoint]]);
            continue;
        }

        error_log('Web Push delivery failed: ' . $report->getReason());
    }
}
