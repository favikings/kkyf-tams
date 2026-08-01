<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;
use RuntimeException;

final class MemberService
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
    public function search(array $user, string $query = '', ?int $tentId = null, ?string $status = null): array
    {
        $params = [];
        $where = [];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $where[] = 'm.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        } elseif ($tentId !== null && $tentId > 0) {
            $where[] = 'm.tent_id = ?';
            $params[] = $tentId;
        }

        if ($query !== '') {
            $where[] = '(m.full_name LIKE ? OR m.phone LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($status !== null && in_array($status, ['active', 'inactive'], true)) {
            $where[] = 'm.active_status = ?';
            $params[] = $status;
        }

        $sql = "SELECT m.*, t.name AS tent_name,
                       COALESCE(s.current_streak, 0) AS current_streak,
                       COALESCE(s.longest_streak, 0) AS longest_streak,
                       COALESCE(s.total_attendance, 0) AS total_attendance,
                       GROUP_CONCAT(DISTINCT b.name ORDER BY
                           FIELD(b.name, 'Unstoppable', 'Faithful', 'On Fire', 'First Step', '1-Year Member', '6-Month Member', '3-Month Member')
                           SEPARATOR '||') AS badge_names
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                LEFT JOIN streaks s ON s.member_id = m.id
                LEFT JOIN member_badges mb ON mb.member_id = m.id
                LEFT JOIN badges b ON b.id = mb.badge_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY m.id ORDER BY m.full_name ASC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'hydrateBadges'], $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function all(array $user): array
    {
        $params = [];
        $where = [];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $where[] = 'm.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql = "SELECT m.id, m.full_name, m.phone, t.name AS tent_name,
                       m.occupation, m.active_status, m.date_of_birth, m.join_date
                FROM members m
                JOIN tents t ON t.id = m.tent_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.full_name ASC';

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
        $sql = "SELECT m.*, t.name AS tent_name,
                       COALESCE(s.current_streak, 0) AS current_streak,
                       COALESCE(s.longest_streak, 0) AS longest_streak,
                       COALESCE(s.total_attendance, 0) AS total_attendance,
                       GROUP_CONCAT(DISTINCT b.name ORDER BY
                           FIELD(b.name, 'Unstoppable', 'Faithful', 'On Fire', 'First Step', '1-Year Member', '6-Month Member', '3-Month Member')
                           SEPARATOR '||') AS badge_names
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                LEFT JOIN streaks s ON s.member_id = m.id
                LEFT JOIN member_badges mb ON mb.member_id = m.id
                LEFT JOIN badges b ON b.id = mb.badge_id
                WHERE m.id = ?";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' GROUP BY m.id LIMIT 1');
        $stmt->execute($params);
        $member = $stmt->fetch();

        return $member ? $this->hydrateBadges($member) : null;
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO members (
                uuid, full_name, phone, date_of_birth, occupation, school_name,
                tent_id, join_date, profile_photo, notes, active_status
             ) VALUES (
                UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active'
             )"
        );

        $stmt->execute([
            $data['full_name'],
            $data['phone'] ?: null,
            $data['date_of_birth'] ?: null,
            $data['occupation'],
            $data['school_name'] ?: null,
            $data['tent_id'],
            $data['join_date'] ?: null,
            $data['profile_photo'] ?: null,
            $data['notes'] ?: null,
        ]);

        $memberId = (int) $this->pdo->lastInsertId();
        $this->streaks->refreshMember($memberId);

        return $memberId;
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE members
             SET full_name = ?,
                 phone = ?,
                 date_of_birth = ?,
                 occupation = ?,
                 school_name = ?,
                 tent_id = ?,
                 join_date = ?,
                 profile_photo = ?,
                 notes = ?,
                 active_status = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );

        $stmt->execute([
            $data['full_name'],
            $data['phone'] ?: null,
            $data['date_of_birth'] ?: null,
            $data['occupation'],
            $data['school_name'] ?: null,
            $data['tent_id'],
            $data['join_date'] ?: null,
            $data['profile_photo'] ?: null,
            $data['notes'] ?: null,
            $data['active_status'],
            $id,
        ]);

        $this->streaks->refreshMember($id);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE members SET active_status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateBadges(array $row): array
    {
        $row['badges'] = [];

        if (!empty($row['badge_names'])) {
            $row['badges'] = array_values(array_filter(explode('||', (string) $row['badge_names'])));
        }

        return $row;
    }
}
