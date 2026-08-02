<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class ReportService
{
    private PDO $pdo;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function build(array $user, string $type = 'weekly', ?int $tentId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $type = in_array($type, ['weekly', 'monthly', 'yearly', 'sunday'], true) ? $type : 'weekly';
        [$resolvedFrom, $resolvedTo] = $this->resolveDateWindow($type, $dateFrom, $dateTo);
        $effectiveTentId = (($user['role'] ?? null) === 'Tent Admin')
            ? (int) ($user['tent_id'] ?? 0)
            : (($tentId !== null && $tentId > 0) ? $tentId : null);
        $summary = $this->summary($resolvedFrom, $resolvedTo, $effectiveTentId);

        return [
            'type' => $type,
            'title' => $this->titleFor($type),
            'date_from' => $resolvedFrom,
            'date_to' => $resolvedTo,
            'selected_tent_id' => $effectiveTentId,
            'selected_tent_name' => $effectiveTentId !== null ? $this->tentName($effectiveTentId) : 'All Tents',
            'generated_on' => date('M j, Y'),
            'period_label' => $this->formatDate($resolvedFrom) . ' - ' . $this->formatDate($resolvedTo),
            'summary' => $summary,
            'rows' => $type === 'sunday'
                ? $this->sundayRows($resolvedFrom, $effectiveTentId)
                : $this->aggregateRows($resolvedFrom, $resolvedTo, $effectiveTentId),
            'trend_points' => $this->trendPoints($type, $resolvedFrom, $resolvedTo, $effectiveTentId),
            'tent_performance' => $this->tentPerformance($resolvedFrom, $resolvedTo, $effectiveTentId),
            'demographics' => $this->demographics($effectiveTentId),
            'columns' => $type === 'sunday'
                ? ['Member', 'Tent', 'Phone', 'Checked By', 'Source']
                : ['Date', 'Tent', 'Check-ins', 'Unique Members'],
        ];
    }

    /**
     * All attendance records for every tent, from the earliest recorded
     * attendance date to today. Scoped by role: Tent Admins see only their
     * assigned tent, Super Admins see every tent.
     *
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function allAttendanceRows(array $user): array
    {
        $params = [];
        $where = ['a.attendance_date <= CURRENT_DATE'];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $where[] = 'm.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql = "SELECT a.id,
                       a.attendance_date,
                       a.service_type,
                       a.created_at,
                       a.source,
                       m.full_name,
                       m.phone,
                       t.name AS tent_name,
                       u.full_name AS checked_by_name
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                JOIN tents t ON t.id = m.tent_id
                LEFT JOIN users u ON u.id = a.checked_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.attendance_date ASC, t.name ASC, m.full_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveDateWindow(string $type, ?string $dateFrom, ?string $dateTo): array
    {
        $today = new DateTimeImmutable('today');
        $defaultFrom = $today;
        $defaultTo = $today;

        switch ($type) {
            case 'monthly':
                $defaultFrom = $today->modify('first day of this month');
                $defaultTo = $today->modify('last day of this month');
                break;
            case 'yearly':
                $defaultFrom = $today->setDate((int) $today->format('Y'), 1, 1);
                $defaultTo = $today->setDate((int) $today->format('Y'), 12, 31);
                break;
            case 'sunday':
                $defaultFrom = $this->currentSunday();
                $defaultTo = $defaultFrom;
                break;
            case 'weekly':
            default:
                $defaultFrom = $today->modify('-6 days');
                $defaultTo = $today;
                break;
        }

        $from = $this->normalizedDate($dateFrom) ?? $defaultFrom->format('Y-m-d');
        $to = $this->normalizedDate($dateTo) ?? $defaultTo->format('Y-m-d');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($type === 'sunday') {
            $from = $to = $from;
        }

        return [$from, $to];
    }

    /**
     * @return array<string, int|float>
     */
    private function summary(string $dateFrom, string $dateTo, ?int $tentId = null): array
    {
        $params = [$dateFrom, $dateTo];
        $sql = "SELECT COUNT(*) AS total_checkins,
                       COUNT(DISTINCT a.member_id) AS unique_members,
                       COUNT(DISTINCT m.tent_id) AS tents_reached,
                       COUNT(DISTINCT a.attendance_date) AS service_days
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                WHERE a.attendance_date BETWEEN ? AND ?";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ?';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        $serviceDays = max(1, (int) ($row['service_days'] ?? 0));
        $totalCheckins = (int) ($row['total_checkins'] ?? 0);
        $activeMembers = $this->activeMembersCount($tentId);
        $newMembers = $this->newMembersCount($dateFrom, $dateTo, $tentId);
        $smsSent = $this->smsSentCount($dateFrom, $dateTo, $tentId);

        return [
            'total_checkins' => $totalCheckins,
            'unique_members' => (int) ($row['unique_members'] ?? 0),
            'tents_reached' => (int) ($row['tents_reached'] ?? 0),
            'service_days' => (int) ($row['service_days'] ?? 0),
            'average_daily_attendance' => round($totalCheckins / $serviceDays, 1),
            'active_members_total' => $activeMembers,
            'new_members' => $newMembers,
            'retention_rate' => $activeMembers > 0
                ? round((((int) ($row['unique_members'] ?? 0)) / $activeMembers) * 100)
                : 0,
            'sms_sent_total' => $smsSent,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateRows(string $dateFrom, string $dateTo, ?int $tentId = null): array
    {
        $params = [$dateFrom, $dateTo];
        $sql = "SELECT a.attendance_date,
                       t.name AS tent_name,
                       COUNT(*) AS total_checkins,
                       COUNT(DISTINCT a.member_id) AS unique_members
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                JOIN tents t ON t.id = m.tent_id
                WHERE a.attendance_date BETWEEN ? AND ?";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= " GROUP BY a.attendance_date, t.id
                  ORDER BY a.attendance_date DESC, t.name ASC
                  LIMIT 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sundayRows(string $date, ?int $tentId = null): array
    {
        $params = [$date];
        $sql = "SELECT m.full_name,
                       t.name AS tent_name,
                       m.phone,
                       u.full_name AS checked_by_name,
                       a.source
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                JOIN tents t ON t.id = m.tent_id
                JOIN users u ON u.id = a.checked_by
                WHERE a.attendance_date = ?";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= ' ORDER BY t.name ASC, m.full_name ASC LIMIT 500';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function titleFor(string $type): string
    {
        return match ($type) {
            'monthly' => 'Monthly Organization Performance Report',
            'yearly' => 'Yearly Organization Performance Report',
            'sunday' => 'Sunday Service Performance Report',
            default => 'Weekly Organization Performance Report',
        };
    }

    /**
     * @return array<int, array{label:string,value:int}>
     */
    private function trendPoints(string $type, string $dateFrom, string $dateTo, ?int $tentId = null): array
    {
        $params = [$dateFrom, $dateTo];
        $sql = "SELECT a.attendance_date, COUNT(*) AS total_checkins
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                WHERE a.attendance_date BETWEEN ? AND ?";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= ' GROUP BY a.attendance_date ORDER BY a.attendance_date ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $totalsByDate = [];

        foreach ($rows as $row) {
            $totalsByDate[(string) $row['attendance_date']] = (int) $row['total_checkins'];
        }

        if ($type === 'yearly') {
            $points = [];
            $year = (int) substr($dateFrom, 0, 4);

            for ($month = 1; $month <= 12; $month++) {
                $label = date('M', mktime(0, 0, 0, $month, 1, $year));
                $value = 0;

                foreach ($totalsByDate as $date => $total) {
                    if ((int) substr($date, 0, 4) === $year && (int) substr($date, 5, 2) === $month) {
                        $value += $total;
                    }
                }

                $points[] = ['label' => $label, 'value' => $value];
            }

            return $points;
        }

        if ($type === 'monthly') {
            $points = [
                'W1' => 0,
                'W2' => 0,
                'W3' => 0,
                'W4' => 0,
                'W5' => 0,
            ];

            foreach ($totalsByDate as $date => $total) {
                $day = (int) substr($date, 8, 2);
                $bucket = 'W' . (string) min(5, (int) floor(($day - 1) / 7) + 1);
                $points[$bucket] += $total;
            }

            return array_map(
                static fn (string $label, int $value): array => ['label' => $label, 'value' => $value],
                array_keys($points),
                array_values($points)
            );
        }

        if ($type === 'sunday') {
            return [[
                'label' => 'Sunday',
                'value' => array_sum($totalsByDate),
            ]];
        }

        $points = [];
        $cursor = new DateTimeImmutable($dateFrom);
        $end = new DateTimeImmutable($dateTo);

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $points[] = [
                'label' => $cursor->format('D'),
                'value' => (int) ($totalsByDate[$key] ?? 0),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $points;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tentPerformance(string $dateFrom, string $dateTo, ?int $tentId = null): array
    {
        $params = [$dateFrom, $dateTo];
        $sql = "SELECT t.id,
                       t.name AS tent_name,
                       COALESCE(NULLIF(t.leader_name, ''), 'Not assigned') AS leader_name,
                       COUNT(a.id) AS total_checkins,
                       COUNT(DISTINCT a.attendance_date) AS service_days,
                       (
                           SELECT COUNT(*)
                           FROM members m2
                           WHERE m2.tent_id = t.id
                             AND m2.active_status = 'active'
                       ) AS active_members
                FROM tents t
                LEFT JOIN members m ON m.tent_id = t.id
                LEFT JOIN attendance a
                    ON a.member_id = m.id
                   AND a.attendance_date BETWEEN ? AND ?";

        if ($tentId !== null) {
            $sql .= ' WHERE t.id = ?';
            $params[] = $tentId;
        }

        $sql .= ' GROUP BY t.id, t.name, t.leader_name
                  HAVING total_checkins > 0 OR active_members > 0
                  ORDER BY total_checkins DESC, t.name ASC
                  LIMIT 6';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $activeMembers = max(1, (int) ($row['active_members'] ?? 0));
            $serviceDays = max(1, (int) ($row['service_days'] ?? 0));
            $rate = (int) round((((int) $row['total_checkins']) / ($activeMembers * $serviceDays)) * 100);
            $row['average_attendance_rate'] = min(100, $rate);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, array{label:string,count:int,percentage:int}>
     */
    private function demographics(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT occupation, COUNT(*) AS total
                FROM members
                WHERE active_status = 'active'";

        if ($tentId !== null) {
            $sql .= ' AND tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= ' GROUP BY occupation';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $map = [
            'Student' => 0,
            'Worker' => 0,
            'Alumni' => 0,
        ];

        foreach ($rows as $row) {
            $occupation = (string) ($row['occupation'] ?? '');
            if (isset($map[$occupation])) {
                $map[$occupation] = (int) ($row['total'] ?? 0);
            }
        }

        $overall = max(1, array_sum($map));
        $result = [];

        foreach ($map as $label => $count) {
            $result[] = [
                'label' => $label,
                'count' => $count,
                'percentage' => (int) round(($count / $overall) * 100),
            ];
        }

        return $result;
    }

    private function activeMembersCount(?int $tentId = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM members WHERE active_status = 'active'";

        if ($tentId !== null) {
            $sql .= ' AND tent_id = ?';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function newMembersCount(string $dateFrom, string $dateTo, ?int $tentId = null): int
    {
        $params = [$dateFrom, $dateTo];
        $sql = "SELECT COUNT(*)
                FROM members
                WHERE DATE(COALESCE(join_date, created_at)) BETWEEN ? AND ?";

        if ($tentId !== null) {
            $sql .= ' AND tent_id = ?';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function smsSentCount(string $dateFrom, string $dateTo, ?int $tentId = null): int
    {
        if (!$this->tableExists('sms_logs')) {
            return 0;
        }

        $params = [$dateFrom, $dateTo];
        $sql = "SELECT COALESCE(SUM(recipient_count), 0)
                FROM sms_logs
                WHERE DATE(created_at) BETWEEN ? AND ?
                  AND status IN ('sent', 'simulated')";

        if ($tentId !== null) {
            $sql .= ' AND (tent_id = ? OR (tent_id IS NULL AND scope = \'bulk\'))';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?"
        );
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function tentName(int $tentId): string
    {
        $stmt = $this->pdo->prepare("SELECT name FROM tents WHERE id = ? LIMIT 1");
        $stmt->execute([$tentId]);
        $name = $stmt->fetchColumn();

        return is_string($name) && $name !== '' ? $name : 'Selected Tent';
    }

    private function formatDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date ? $date->format('M j, Y') : $value;
    }

    private function normalizedDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }

    private function currentSunday(): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today');

        return $today->modify($today->format('N') === '7' ? 'today' : 'last sunday');
    }
}
