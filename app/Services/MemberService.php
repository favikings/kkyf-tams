<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;

final class MemberService
{
    private PDO $pdo;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function search(array $user, string $query = '', ?int $tentId = null): array
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

        $sql = "SELECT m.*, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.full_name ASC LIMIT 200';

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
        $sql = "SELECT m.*, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                WHERE m.id = ?";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $member = $stmt->fetch();

        return $member ?: null;
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function create(array $data): void
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
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE members SET active_status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
