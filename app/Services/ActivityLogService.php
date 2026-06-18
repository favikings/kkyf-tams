<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;
use Throwable;

final class ActivityLogService
{
    private PDO $pdo;
    private ?bool $activityTableExists = null;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(?int $userId, string $action, ?string $entityType = null, string|int|null $entityId = null, array $metadata = []): void
    {
        if (!$this->tableExists()) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO activity_logs (
                    user_id, action, entity_type, entity_id, metadata, ip_address, user_agent
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                 )"
            );

            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId !== null ? (string) $entityId : null,
                $metadata === [] ? null : json_encode($this->sanitizeMetadata($metadata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $this->ipAddress(),
                $this->userAgent(),
            ]);
        } catch (Throwable) {
            // Logging must never break a primary workflow.
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function recent(array $filters = []): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $params = [];
        $where = [];
        $query = trim((string) ($filters['query'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));
        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        $userId = (int) ($filters['user_id'] ?? 0);

        if ($action !== '') {
            $where[] = 'al.action = ?';
            $params[] = $action;
        }

        if ($entityType !== '') {
            $where[] = 'al.entity_type = ?';
            $params[] = $entityType;
        }

        if ($userId > 0) {
            $where[] = 'al.user_id = ?';
            $params[] = $userId;
        }

        if ($query !== '') {
            $where[] = '(al.action LIKE ? OR al.entity_type LIKE ? OR al.entity_id LIKE ? OR u.full_name LIKE ?)';
            $wildcard = '%' . $query . '%';
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        $sql = "SELECT al.*, u.full_name AS actor_name, u.role AS actor_role
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY al.created_at DESC, al.id DESC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['metadata'] ?? ''), true);
            $row['metadata_array'] = is_array($decoded) ? $decoded : [];
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function actors(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, full_name, role
             FROM users
             WHERE status = 'active'
             ORDER BY full_name ASC"
        );

        return $stmt->fetchAll();
    }

    private function tableExists(): bool
    {
        if ($this->activityTableExists !== null) {
            return $this->activityTableExists;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = 'activity_logs'"
        );
        $stmt->execute();

        $this->activityTableExists = (int) $stmt->fetchColumn() > 0;

        return $this->activityTableExists;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, scalar|null|array<int|string, mixed>>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function ipAddress(): ?string
    {
        $value = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        return $value !== '' ? substr($value, 0, 45) : null;
    }

    private function userAgent(): ?string
    {
        $value = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return $value !== '' ? substr($value, 0, 255) : null;
    }
}
