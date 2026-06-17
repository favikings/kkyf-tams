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

        return [
            'type' => $type,
            'title' => $this->titleFor($type),
            'date_from' => $resolvedFrom,
            'date_to' => $resolvedTo,
            'selected_tent_id' => $effectiveTentId,
            'summary' => $this->summary($resolvedFrom, $resolvedTo, $effectiveTentId),
            'rows' => $type === 'sunday'
                ? $this->sundayRows($resolvedFrom, $effectiveTentId)
                : $this->aggregateRows($resolvedFrom, $resolvedTo, $effectiveTentId),
            'columns' => $type === 'sunday'
                ? ['Member', 'Tent', 'Phone', 'Checked By', 'Source']
                : ['Date', 'Tent', 'Check-ins', 'Unique Members'],
        ];
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

        return [
            'total_checkins' => $totalCheckins,
            'unique_members' => (int) ($row['unique_members'] ?? 0),
            'tents_reached' => (int) ($row['tents_reached'] ?? 0),
            'service_days' => (int) ($row['service_days'] ?? 0),
            'average_daily_attendance' => round($totalCheckins / $serviceDays, 1),
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
            'monthly' => 'Monthly Report',
            'yearly' => 'Yearly Report',
            'sunday' => 'Sunday Summary',
            default => 'Weekly Report',
        };
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
