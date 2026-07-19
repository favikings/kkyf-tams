<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateTimeImmutable;
use PDO;
use Throwable;

final class NotificationService
{
    private PDO $pdo;
    private ?bool $tableExists = null;
    private BirthdayService $birthdays;
    private AnniversaryService $anniversaries;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
        $this->birthdays = new BirthdayService();
        $this->anniversaries = new AnniversaryService();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function feedForUser(array $user, int $limit = 12): array
    {
        if (!$this->tableExists()) {
            return [
                'items' => [],
                'unread_count' => 0,
                'notifications_enabled' => false,
            ];
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return [
                'items' => [],
                'unread_count' => 0,
                'notifications_enabled' => true,
            ];
        }

        $this->ensureSystemRemindersForUser($user);

        $stmt = $this->pdo->prepare(
            "SELECT n.*
             FROM notifications n
             WHERE n.user_id = ?
               AND n.read_at IS NULL
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT " . max(1, min($limit, 50))
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $unreadStmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM notifications
             WHERE user_id = ?
               AND read_at IS NULL"
        );
        $unreadStmt->execute([$userId]);
        $unreadCount = (int) $unreadStmt->fetchColumn();

        foreach ($rows as &$row) {
            $row['is_unread'] = empty($row['read_at']);
            $row['created_at_human'] = $this->relativeTime((string) ($row['created_at'] ?? ''));
        }
        unset($row);

        return [
            'items' => $rows,
            'unread_count' => $unreadCount,
            'notifications_enabled' => true,
        ];
    }

    /**
     * @param array<string, mixed> $user
     */
    public function markRead(array $user, int $notificationId): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0 || $notificationId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE id = ?
               AND user_id = ?"
        );
        $stmt->execute([$notificationId, $userId]);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function markAllRead(array $user): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE user_id = ?
               AND read_at IS NULL"
        );
        $stmt->execute([$userId]);
    }

    /**
     * @param array<int, int> $userIds
     * @param array<string, mixed> $metadata
     */
    public function notifyUsers(
        array $userIds,
        string $type,
        string $category,
        string $title,
        string $message,
        ?string $linkUrl = null,
        ?string $entityType = null,
        string|int|null $entityId = null,
        array $metadata = [],
        ?int $actorUserId = null,
        ?string $dedupeSeed = null
    ): void {
        if (!$this->tableExists()) {
            return;
        }

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if ($userIds === []) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO notifications (
                    user_id, actor_user_id, type, category, title, message, link_url,
                    entity_type, entity_id, dedupe_key, metadata
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                 )"
            );

            foreach ($userIds as $userId) {
                $dedupeKey = $dedupeSeed !== null ? $dedupeSeed . ':user:' . $userId : null;
                try {
                    $stmt->execute([
                        $userId,
                        $actorUserId,
                        $type,
                        $category,
                        $title,
                        $message,
                        $linkUrl,
                        $entityType,
                        $entityId !== null ? (string) $entityId : null,
                        $dedupeKey,
                        $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                } catch (Throwable) {
                    // Ignore duplicate dedupe keys or insert failures so primary actions continue.
                }
            }
        } catch (Throwable) {
            // Notification writes must never break a primary workflow.
        }
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $metadata
     */
    public function notifyAllUsers(
        array $user,
        string $type,
        string $category,
        string $title,
        string $message,
        ?string $linkUrl = null,
        ?string $entityType = null,
        string|int|null $entityId = null,
        array $metadata = [],
        ?string $dedupeSeed = null
    ): void {
        $this->notifyUsers(
            $this->activeUserIds(),
            $type,
            $category,
            $title,
            $message,
            $linkUrl,
            $entityType,
            $entityId,
            $metadata,
            (int) ($user['id'] ?? 0) ?: null,
            $dedupeSeed
        );
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $metadata
     */
    public function notifyTentUsers(
        array $user,
        int $tentId,
        string $type,
        string $category,
        string $title,
        string $message,
        ?string $linkUrl = null,
        ?string $entityType = null,
        string|int|null $entityId = null,
        array $metadata = [],
        ?string $dedupeSeed = null
    ): void {
        $this->notifyUsers(
            $this->activeUserIds($tentId),
            $type,
            $category,
            $title,
            $message,
            $linkUrl,
            $entityType,
            $entityId,
            $metadata,
            (int) ($user['id'] ?? 0) ?: null,
            $dedupeSeed
        );
    }

    private function tableExists(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = 'notifications'"
        );
        $stmt->execute();
        $this->tableExists = (int) $stmt->fetchColumn() > 0;

        return $this->tableExists;
    }

    /**
     * @return array<int, int>
     */
    private function activeUserIds(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT id
                FROM users
                WHERE status = 'active'";

        if ($tentId !== null && $tentId > 0) {
            $sql .= ' AND (role = \'Super Admin\' OR tent_id = ?)';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    /**
     * @param array<string, mixed> $user
     */
    private function ensureSystemRemindersForUser(array $user): void
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        foreach ($this->birthdays->upcomingBirthdaysForUser($user, 1) as $birthday) {
            if (empty($birthday['is_today_birthday'])) {
                continue;
            }

            $fullName = trim((string) ($birthday['full_name'] ?? 'A member'));
            $tentName = trim((string) ($birthday['tent_name'] ?? 'their tent'));

            $this->notifyUsers(
                [$userId],
                'birthday.today',
                'birthday',
                'Birthday today',
                $fullName . ' is celebrating a birthday today in ' . $tentName . '.',
                '/birthdays',
                'member',
                $birthday['id'] ?? null,
                [
                    'member_id' => (int) ($birthday['id'] ?? 0),
                    'tent_name' => $tentName,
                    'date' => $today,
                ],
                null,
                'birthday:' . $today . ':member:' . (int) ($birthday['id'] ?? 0)
            );
        }

        foreach ($this->anniversaries->upcomingAnniversariesForUser($user, 1) as $anniversary) {
            if (empty($anniversary['is_today_anniversary'])) {
                continue;
            }

            $fullName = trim((string) ($anniversary['full_name'] ?? 'A member'));
            $years = (int) ($anniversary['celebrating_years'] ?? 0);

            $this->notifyUsers(
                [$userId],
                'anniversary.today',
                'anniversary',
                'Anniversary today',
                $fullName . ' is marking ' . $years . ' year' . ($years === 1 ? '' : 's') . ' in KKYF today.',
                '/anniversaries',
                'member',
                $anniversary['id'] ?? null,
                [
                    'member_id' => (int) ($anniversary['id'] ?? 0),
                    'celebrating_years' => $years,
                    'date' => $today,
                ],
                null,
                'anniversary:' . $today . ':member:' . (int) ($anniversary['id'] ?? 0)
            );
        }
    }

    private function relativeTime(string $timestamp): string
    {
        try {
            $date = new DateTimeImmutable($timestamp);
        } catch (Throwable) {
            return $timestamp;
        }

        $now = new DateTimeImmutable();
        $seconds = max(0, $now->getTimestamp() - $date->getTimestamp());

        if ($seconds < 60) {
            return 'Just now';
        }

        $minutes = (int) floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' min' . ($minutes === 1 ? '' : 's') . ' ago';
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        $days = (int) floor($hours / 24);
        if ($days < 7) {
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }

        return $date->format('M j, Y g:i A');
    }
}
