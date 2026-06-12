<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;

final class TentService
{
    private PDO $pdo;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query(
            "SELECT t.*,
                    u.id AS admin_id,
                    u.full_name AS admin_name,
                    u.email AS admin_email
             FROM tents t
             LEFT JOIN users u ON u.tent_id = t.id AND u.role = 'Tent Admin'
             ORDER BY t.name ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tents WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $tent = $stmt->fetch();

        return $tent ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAssignedToUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*
             FROM users u
             JOIN tents t ON t.id = u.tent_id
             WHERE u.id = ? AND u.role = 'Tent Admin'
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $tent = $stmt->fetch();

        return $tent ?: null;
    }

    /**
     * @param array<string, string> $data
     */
    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tents (
                uuid, name, banner, color, leader_name, leader_phone, whatsapp_link, status
             ) VALUES (
                UUID(), ?, ?, ?, ?, ?, ?, 'active'
             )"
        );

        $stmt->execute([
            $data['name'],
            $data['banner'] ?: null,
            $data['color'] ?: null,
            $data['leader_name'] ?: null,
            $data['leader_phone'] ?: null,
            $data['whatsapp_link'] ?: null,
        ]);
    }

    /**
     * @param array<string, string> $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tents
             SET name = ?,
                 banner = ?,
                 color = ?,
                 leader_name = ?,
                 leader_phone = ?,
                 whatsapp_link = ?,
                 status = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );

        $stmt->execute([
            $data['name'],
            $data['banner'] ?: null,
            $data['color'] ?: null,
            $data['leader_name'] ?: null,
            $data['leader_phone'] ?: null,
            $data['whatsapp_link'] ?: null,
            $data['status'],
            $id,
        ]);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tents SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function assignAdmin(int $tentId, int $userId): void
    {
        $this->pdo->beginTransaction();

        $clear = $this->pdo->prepare("UPDATE users SET tent_id = NULL WHERE role = 'Tent Admin' AND tent_id = ?");
        $clear->execute([$tentId]);

        $assign = $this->pdo->prepare(
            "UPDATE users SET tent_id = ? WHERE id = ? AND role = 'Tent Admin' AND status = 'active'"
        );
        $assign->execute([$tentId, $userId]);

        $this->pdo->commit();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tentAdmins(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, full_name, email, tent_id
             FROM users
             WHERE role = 'Tent Admin' AND status = 'active'
             ORDER BY full_name ASC"
        );

        return $stmt->fetchAll();
    }
}
