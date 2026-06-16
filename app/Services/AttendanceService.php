<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;
use RuntimeException;

final class AttendanceService
{
    private PDO $pdo;
    private StreakBadgeService $streaks;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
        $this->streaks = new StreakBadgeService();
    }

    public function currentSunday(): string
    {
        $today = new \DateTimeImmutable('today');

        return $today->modify($today->format('N') === '7' ? 'today' : 'last sunday')->format('Y-m-d');
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function searchableMembers(array $user, string $query = '', ?int $tentId = null): array
    {
        $params = [];
        $where = ["m.active_status = 'active'"];

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

        $sql = "SELECT m.id, m.full_name, m.phone, m.tent_id, t.name AS tent_name,
                       a.id AS attendance_id
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                LEFT JOIN attendance a ON a.member_id = m.id AND a.attendance_date = ?
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.full_name ASC
                LIMIT 100";

        array_unshift($params, $this->currentSunday());
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     */
    public function checkIn(array $user, int $memberId): void
    {
        $member = $this->scopedMember($user, $memberId);
        if ($member === null) {
            throw new RuntimeException('Member not found or outside your tent.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO attendance (member_id, attendance_date, service_type, checked_by, source)
             VALUES (?, ?, 'Sunday Service', ?, 'web')"
        );

        try {
            $stmt->execute([$memberId, $this->currentSunday(), (int) $user['id']]);
            $this->streaks->refreshMember($memberId);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('This member has already been checked in for this Sunday.');
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function history(array $user, ?int $tentId = null, ?string $date = null): array
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

        if ($date !== null && $date !== '') {
            $where[] = 'a.attendance_date = ?';
            $params[] = $date;
        }

        $sql = "SELECT a.*, m.full_name, m.phone, t.name AS tent_name, u.full_name AS checked_by_name
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                JOIN tents t ON t.id = m.tent_id
                JOIN users u ON u.id = a.checked_by";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.attendance_date DESC, a.created_at DESC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, int|string>
     */
    public function sundaySummary(array $user, ?int $tentId = null): array
    {
        $date = $this->currentSunday();
        $params = [$date];
        $where = ['a.attendance_date = ?'];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $where[] = 'm.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        } elseif ($tentId !== null && $tentId > 0) {
            $where[] = 'm.tent_id = ?';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM attendance a
             JOIN members m ON m.id = a.member_id
             WHERE " . implode(' AND ', $where)
        );
        $stmt->execute($params);

        return [
            'attendance_date' => $date,
            'total' => (int) $stmt->fetchColumn(),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>|null
     */
    private function scopedMember(array $user, int $memberId): ?array
    {
        $params = [$memberId];
        $sql = "SELECT * FROM members WHERE id = ? AND active_status = 'active'";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $member = $stmt->fetch();

        return $member ?: null;
    }
}
