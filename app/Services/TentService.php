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
        $legacyOnly = $this->shouldRestrictToLegacyTents();
        $sql = "SELECT t.*,
                    admins.admin_id,
                    admins.admin_name,
                    admins.admin_email,
                    admins.admin_count,
                    admins.admin_names,
                    (
                        SELECT COUNT(*)
                        FROM members m
                        WHERE m.tent_id = t.id
                          AND m.active_status = 'active'
                    ) AS member_count,
                    (
                        SELECT COUNT(*)
                        FROM attendance a
                        JOIN members m2 ON m2.id = a.member_id
                        WHERE m2.tent_id = t.id
                          AND m2.active_status = 'active'
                          AND a.attendance_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                          AND a.attendance_date <= CURDATE()
                    ) AS month_attendance_count
             FROM tents t
             LEFT JOIN (
                 SELECT tent_id,
                        MIN(id) AS admin_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(full_name ORDER BY full_name ASC SEPARATOR '||'), '||', 1) AS admin_name,
                        SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(email, '') ORDER BY full_name ASC SEPARATOR '||'), '||', 1) AS admin_email,
                        COUNT(*) AS admin_count,
                        GROUP_CONCAT(full_name ORDER BY full_name ASC SEPARATOR ', ') AS admin_names
                 FROM users
                 WHERE role = 'Tent Admin'
                   AND status = 'active'
                   AND tent_id IS NOT NULL
                 GROUP BY tent_id
             ) admins ON admins.tent_id = t.id";

        if ($legacyOnly) {
            $sql .= "
             WHERE t.id IN (
                 SELECT DISTINCT target_id
                 FROM migration_logs
                 WHERE source_table = 'legacy_tents'
                   AND target_table = 'tents'
                   AND status IN ('success', 'skipped')
                   AND target_id IS NOT NULL
             )";
        }

        $sql .= '
             ORDER BY t.name ASC';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * @param array<int, array<string, mixed>> $tents
     * @return array<string, mixed>
     */
    public function overview(array $tents): array
    {
        $activeTents = array_values(array_filter(
            $tents,
            static fn (array $tent): bool => ($tent['status'] ?? 'inactive') === 'active'
        ));

        $activeCount = count($activeTents);
        $createdThisYear = 0;
        $totalMembers = 0;
        $topPerforming = null;
        $supportCount = 0;

        foreach ($tents as $tent) {
            $memberCount = (int) ($tent['member_count'] ?? 0);
            $monthAttendance = (int) ($tent['month_attendance_count'] ?? 0);
            $attendanceRate = $memberCount > 0 ? min(100, (int) round(($monthAttendance / $memberCount) * 100)) : 0;

            if ((int) date('Y', strtotime((string) ($tent['created_at'] ?? 'now'))) === (int) date('Y')) {
                $createdThisYear++;
            }

            if (($tent['status'] ?? 'inactive') === 'active') {
                $totalMembers += $memberCount;

                if ($memberCount > 0 && ($topPerforming === null || $attendanceRate > $topPerforming['attendance_rate'])) {
                    $topPerforming = [
                        'name' => $tent['name'],
                        'attendance_rate' => $attendanceRate,
                    ];
                }

                if ($memberCount > 0 && $attendanceRate < 40) {
                    $supportCount++;
                }
            }
        }

        $averageTentSize = $activeCount > 0 ? (int) round($totalMembers / $activeCount) : 0;

        return [
            'active_tents' => $activeCount,
            'created_this_year' => $createdThisYear,
            'average_tent_size' => $averageTentSize,
            'top_performing_name' => $topPerforming['name'] ?? 'No data yet',
            'top_performing_rate' => $topPerforming['attendance_rate'] ?? 0,
            'needs_support_count' => $supportCount,
        ];
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
    public function create(array $data): int
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

        return (int) $this->pdo->lastInsertId();
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
        $assign = $this->pdo->prepare(
            "UPDATE users SET tent_id = ? WHERE id = ? AND role = 'Tent Admin' AND status = 'active'"
        );
        $assign->execute([$tentId, $userId]);
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

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function availableForUser(array $user): array
    {
        if (($user['role'] ?? null) === 'Tent Admin') {
            $tent = $this->find((int) ($user['tent_id'] ?? 0));

            return $tent === null ? [] : [$tent];
        }

        return $this->all();
    }

    private function shouldRestrictToLegacyTents(): bool
    {
        $statement = $this->pdo->query(
            "SELECT COUNT(DISTINCT target_id)
             FROM migration_logs
             WHERE source_table = 'legacy_tents'
               AND target_table = 'tents'
               AND status IN ('success', 'skipped')
               AND target_id IS NOT NULL"
        );

        return (int) $statement->fetchColumn() > 0;
    }
}
