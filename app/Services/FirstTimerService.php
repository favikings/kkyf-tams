<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class FirstTimerService
{
    private PDO $pdo;
    private StreakBadgeService $streaks;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
        $this->streaks = new StreakBadgeService();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function search(array $user, string $query = '', ?int $tentId = null, string $status = ''): array
    {
        $params = [];
        $where = [];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $where[] = 'ft.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        } elseif ($tentId !== null && $tentId > 0) {
            $where[] = 'ft.tent_id = ?';
            $params[] = $tentId;
        }

        if ($query !== '') {
            $where[] = '(ft.full_name LIKE ? OR ft.phone LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($status !== '' && in_array($status, ['Pending', 'Called', 'Converted', 'Not Returning'], true)) {
            $where[] = 'ft.status = ?';
            $params[] = $status;
        }

        $sql = "SELECT ft.*, t.name AS tent_name, m.id AS member_id_link, m.full_name AS converted_member_name
                FROM first_timers ft
                JOIN tents t ON t.id = ft.tent_id
                LEFT JOIN members m ON m.id = ft.converted_member_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY ft.first_visit_date DESC, ft.full_name ASC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>|null
     */
    public function findScoped(array $user, int $id): ?array
    {
        $params = [$id];
        $sql = "SELECT ft.*, t.name AS tent_name, m.full_name AS converted_member_name
                FROM first_timers ft
                JOIN tents t ON t.id = ft.tent_id
                LEFT JOIN members m ON m.id = ft.converted_member_id
                WHERE ft.id = ?";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND ft.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $record = $stmt->fetch();

        return $record ?: null;
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function create(array $data, int $createdBy): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO first_timers (
                uuid, full_name, phone, tent_id, first_visit_date, status, followup_notes, converted_member_id, created_by
             ) VALUES (
                UUID(), ?, ?, ?, ?, ?, ?, NULL, ?
             )"
        );

        $stmt->execute([
            $data['full_name'],
            $data['phone'] ?: null,
            $data['tent_id'],
            $data['first_visit_date'],
            $data['status'],
            $data['followup_notes'] ?: null,
            $createdBy,
        ]);
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE first_timers
             SET full_name = ?,
                 phone = ?,
                 tent_id = ?,
                 first_visit_date = ?,
                 status = ?,
                 followup_notes = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );

        $stmt->execute([
            $data['full_name'],
            $data['phone'] ?: null,
            $data['tent_id'],
            $data['first_visit_date'],
            $data['status'],
            $data['followup_notes'] ?: null,
            $id,
        ]);
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function convertToMember(int $id, array $data): int
    {
        $record = $this->findById($id);
        if ($record === null) {
            throw new RuntimeException('First-timer record not found.');
        }

        if (($record['converted_member_id'] ?? null) !== null || ($record['status'] ?? '') === 'Converted') {
            throw new RuntimeException('First-timer has already been converted.');
        }

        $this->pdo->beginTransaction();

        try {
            $notes = trim((string) ($data['notes'] ?? ''));
            $carryNotes = trim((string) ($record['followup_notes'] ?? ''));

            if ($carryNotes !== '') {
                $notes = $notes === ''
                    ? $carryNotes
                    : $notes . "\n\nFollow-up Notes:\n" . $carryNotes;
            }

            $memberStmt = $this->pdo->prepare(
                "INSERT INTO members (
                    uuid, full_name, phone, date_of_birth, occupation, school_name,
                    tent_id, join_date, profile_photo, notes, active_status
                 ) VALUES (
                    UUID(), ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'active'
                 )"
            );

            $memberStmt->execute([
                $record['full_name'],
                $record['phone'] ?: null,
                $data['date_of_birth'] ?: null,
                $data['occupation'],
                $data['school_name'] ?: null,
                $record['tent_id'],
                $data['join_date'] ?: $record['first_visit_date'],
                $notes !== '' ? $notes : null,
            ]);

            $memberId = (int) $this->pdo->lastInsertId();

            $updateStmt = $this->pdo->prepare(
                "UPDATE first_timers
                 SET status = 'Converted',
                     converted_member_id = ?,
                     followup_notes = ?,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            );

            $updateStmt->execute([
                $memberId,
                $carryNotes !== '' ? $carryNotes : null,
                $id,
            ]);

            $this->pdo->commit();

            $this->streaks->refreshMember($memberId);

            return $memberId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM first_timers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        return $record ?: null;
    }
}
