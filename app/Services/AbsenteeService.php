<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class AbsenteeService
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
    public function alerts(array $user, ?int $tentId = null, string $level = '', string $resolved = 'open'): array
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

        if ($level !== '' && in_array($level, ['Early Warning', 'Follow-Up Required', 'Critical'], true)) {
            $where[] = 'aa.alert_level = ?';
            $params[] = $level;
        }

        if ($resolved === 'resolved') {
            $where[] = 'aa.resolved = 1';
        } else {
            $where[] = 'aa.resolved = 0';
        }

        $sql = "SELECT aa.*, m.full_name, m.phone, m.active_status, t.name AS tent_name
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id
                JOIN tents t ON t.id = m.tent_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY
                    FIELD(aa.alert_level, 'Critical', 'Follow-Up Required', 'Early Warning'),
                    aa.missed_count DESC,
                    m.full_name ASC
                  LIMIT 250";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function recentAlerts(array $user, int $limit = 3): array
    {
        $params = [];
        $sql = "SELECT aa.*, m.full_name, t.name AS tent_name
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id
                JOIN tents t ON t.id = m.tent_id
                WHERE aa.resolved = 0";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql .= " ORDER BY
                    FIELD(aa.alert_level, 'Critical', 'Follow-Up Required', 'Early Warning'),
                    aa.missed_count DESC,
                    aa.updated_at DESC
                  LIMIT " . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, int>
     */
    public function summary(array $user): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN aa.resolved = 0 THEN 1 ELSE 0 END) AS open_total,
                    SUM(CASE WHEN aa.resolved = 0 AND aa.alert_level = 'Critical' THEN 1 ELSE 0 END) AS critical_total,
                    SUM(CASE WHEN aa.resolved = 0 AND aa.alert_level = 'Follow-Up Required' THEN 1 ELSE 0 END) AS follow_up_total,
                    SUM(CASE WHEN aa.resolved = 0 AND aa.alert_level = 'Early Warning' THEN 1 ELSE 0 END) AS early_warning_total
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id";

        $params = [];

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' WHERE m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'open_total' => (int) ($row['open_total'] ?? 0),
            'critical_total' => (int) ($row['critical_total'] ?? 0),
            'follow_up_total' => (int) ($row['follow_up_total'] ?? 0),
            'early_warning_total' => (int) ($row['early_warning_total'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>|null
     */
    public function findScoped(array $user, int $id): ?array
    {
        $params = [$id];
        $sql = "SELECT aa.*, m.tent_id
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id
                WHERE aa.id = ?";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $record = $stmt->fetch();

        return $record ?: null;
    }

    public function resolve(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE absentee_alerts
             SET resolved = 1,
                 resolved_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateForSunday(?string $date = null, bool $dryRun = false): array
    {
        $targetSunday = $this->normalizeSunday($date);
        $members = $this->activeMembers();
        $attendanceMap = $this->attendanceMap($targetSunday);
        $results = [];

        foreach ($members as $member) {
            $missedCount = $this->missedConsecutiveSundays($member, $attendanceMap, $targetSunday);
            $level = $this->alertLevelForCount($missedCount);

            $results[] = [
                'member_id' => (int) $member['id'],
                'full_name' => $member['full_name'],
                'missed_count' => $missedCount,
                'alert_level' => $level,
            ];
        }

        $flagged = array_values(array_filter($results, static fn (array $row): bool => $row['alert_level'] !== null));

        if ($dryRun) {
            return [
                'date' => $targetSunday->format('Y-m-d'),
                'processed' => count($members),
                'flagged' => count($flagged),
                'alerts' => $flagged,
            ];
        }

        $this->pdo->beginTransaction();

        try {
            $this->upsertAlerts($flagged);
            $this->resolveClearedAlerts(array_column($flagged, 'member_id'));
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return [
            'date' => $targetSunday->format('Y-m-d'),
            'processed' => count($members),
            'flagged' => count($flagged),
            'alerts' => $flagged,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeMembers(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, full_name, tent_id, join_date, created_at
             FROM members
             WHERE active_status = 'active'"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, bool>>
     */
    private function attendanceMap(DateTimeImmutable $targetSunday): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT member_id, attendance_date
             FROM attendance
             WHERE attendance_date <= ?"
        );
        $stmt->execute([$targetSunday->format('Y-m-d')]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $memberId = (int) $row['member_id'];
            $date = (string) $row['attendance_date'];
            $map[$memberId][$date] = true;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $member
     * @param array<int, array<string, bool>> $attendanceMap
     */
    private function missedConsecutiveSundays(array $member, array $attendanceMap, DateTimeImmutable $targetSunday): int
    {
        $startDate = $this->memberStartDate($member);
        if ($startDate > $targetSunday) {
            return 0;
        }

        $missedCount = 0;
        $cursor = $targetSunday;
        $memberAttendance = $attendanceMap[(int) $member['id']] ?? [];

        while ($cursor >= $startDate) {
            $sunday = $cursor->format('Y-m-d');

            if (isset($memberAttendance[$sunday])) {
                break;
            }

            $missedCount++;
            $cursor = $cursor->modify('-7 days');
        }

        return $missedCount;
    }

    /**
     * @param array<string, mixed> $member
     */
    private function memberStartDate(array $member): DateTimeImmutable
    {
        $raw = (string) ($member['join_date'] ?: substr((string) $member['created_at'], 0, 10));
        $date = new DateTimeImmutable($raw);
        $dayOfWeek = (int) $date->format('N');

        if ($dayOfWeek === 7) {
            return $date;
        }

        return $date->modify('next sunday');
    }

    private function alertLevelForCount(int $count): ?string
    {
        return match (true) {
            $count >= 4 => 'Critical',
            $count === 3 => 'Follow-Up Required',
            $count === 2 => 'Early Warning',
            default => null,
        };
    }

    /**
     * @param array<int, array<string, mixed>> $alerts
     */
    private function upsertAlerts(array $alerts): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO absentee_alerts (
                member_id, missed_count, alert_level, resolved, resolved_at
             ) VALUES (
                ?, ?, ?, 0, NULL
             )
             ON DUPLICATE KEY UPDATE
                missed_count = VALUES(missed_count),
                alert_level = VALUES(alert_level),
                resolved = 0,
                resolved_at = NULL,
                updated_at = CURRENT_TIMESTAMP"
        );

        foreach ($alerts as $alert) {
            $stmt->execute([
                $alert['member_id'],
                $alert['missed_count'],
                $alert['alert_level'],
            ]);
        }
    }

    /**
     * @param array<int, int> $activeAlertMemberIds
     */
    private function resolveClearedAlerts(array $activeAlertMemberIds): void
    {
        if ($activeAlertMemberIds === []) {
            $this->pdo->exec(
                "UPDATE absentee_alerts
                 SET resolved = 1,
                     resolved_at = COALESCE(resolved_at, CURRENT_TIMESTAMP),
                     updated_at = CURRENT_TIMESTAMP
                 WHERE resolved = 0"
            );
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($activeAlertMemberIds), '?'));
        $stmt = $this->pdo->prepare(
            "UPDATE absentee_alerts
             SET resolved = 1,
                 resolved_at = COALESCE(resolved_at, CURRENT_TIMESTAMP),
                 updated_at = CURRENT_TIMESTAMP
             WHERE resolved = 0
               AND member_id NOT IN ({$placeholders})"
        );
        $stmt->execute($activeAlertMemberIds);
    }

    private function normalizeSunday(?string $date = null): DateTimeImmutable
    {
        $value = $date !== null && $date !== '' ? new DateTimeImmutable($date) : new DateTimeImmutable('today');
        $dayOfWeek = (int) $value->format('N');

        if ($dayOfWeek === 7) {
            return $value;
        }

        return $value->modify('last sunday');
    }
}
