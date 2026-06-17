<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;

final class DashboardService
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
    public function metricsFor(array $user): array
    {
        if (($user['role'] ?? null) === 'Tent Admin') {
            return $this->tentAdminMetrics((int) ($user['tent_id'] ?? 0));
        }

        return $this->superAdminMetrics();
    }

    /**
     * @return array<string, mixed>
     */
    private function superAdminMetrics(): array
    {
        return [
            'scope' => 'global',
            'cards' => [
                ['label' => 'Total Members', 'value' => $this->count('members'), 'icon' => 'users', 'tone' => 'primary'],
                ['label' => 'Active Members', 'value' => $this->count('members', "active_status = 'active'"), 'icon' => 'user-check', 'tone' => 'light'],
                ['label' => 'Total Tents', 'value' => $this->visibleTentCount(), 'icon' => 'tent', 'tone' => 'light'],
                ['label' => 'Attendance Today', 'value' => $this->attendanceCount('CURDATE()'), 'icon' => 'calendar-check', 'tone' => 'light'],
                ['label' => 'This Month Attendance', 'value' => $this->monthAttendanceCount(), 'icon' => 'chart-column', 'tone' => 'light'],
            ],
            'absentee_summary' => $this->absenteeSummary(),
            'absentee_alerts' => $this->recentAbsenteeAlerts(),
            'recent_members' => $this->recentMembers(),
            'recent_attendance' => $this->recentAttendance(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tentAdminMetrics(int $tentId): array
    {
        return [
            'scope' => 'tent',
            'cards' => [
                ['label' => 'Tent Members', 'value' => $this->count('members', 'tent_id = ' . $tentId), 'icon' => 'users', 'tone' => 'primary'],
                ['label' => 'Active Members', 'value' => $this->count('members', "tent_id = {$tentId} AND active_status = 'active'"), 'icon' => 'user-check', 'tone' => 'light'],
                ['label' => 'Attendance Today', 'value' => $this->attendanceCount('CURDATE()', $tentId), 'icon' => 'calendar-check', 'tone' => 'light'],
                ['label' => 'Sunday Attendance', 'value' => $this->attendanceCount("'" . $this->currentSunday() . "'", $tentId), 'icon' => 'clipboard-check', 'tone' => 'light'],
            ],
            'absentee_summary' => $this->absenteeSummary($tentId),
            'absentee_alerts' => $this->recentAbsenteeAlerts($tentId),
            'recent_members' => $this->recentMembers($tentId),
            'recent_attendance' => $this->recentAttendance($tentId),
        ];
    }

    private function count(string $table, string $where = ''): int
    {
        $allowed = ['members', 'tents'];
        if (!in_array($table, $allowed, true)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM ' . $table;
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function visibleTentCount(): int
    {
        if ($this->hasLegacyTentMappings()) {
            return (int) $this->pdo->query(
                "SELECT COUNT(DISTINCT target_id)
                 FROM migration_logs
                 WHERE source_table = 'legacy_tents'
                   AND target_table = 'tents'
                   AND status IN ('success', 'skipped')
                   AND target_id IS NOT NULL"
            )->fetchColumn();
        }

        return $this->count('tents');
    }

    private function hasLegacyTentMappings(): bool
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(DISTINCT target_id)
             FROM migration_logs
             WHERE source_table = 'legacy_tents'
               AND target_table = 'tents'
               AND status IN ('success', 'skipped')
               AND target_id IS NOT NULL"
        )->fetchColumn() > 0;
    }

    private function attendanceCount(string $dateSql, ?int $tentId = null): int
    {
        $sql = "SELECT COUNT(*)
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                WHERE a.attendance_date = {$dateSql}";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ' . $tentId;
        }

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function monthAttendanceCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*)
             FROM attendance
             WHERE YEAR(attendance_date) = YEAR(CURDATE())
               AND MONTH(attendance_date) = MONTH(CURDATE())"
        )->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMembers(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT m.full_name, m.phone, m.active_status, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id";

        if ($tentId !== null) {
            $sql .= ' WHERE m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= ' ORDER BY m.created_at DESC LIMIT 5';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentAttendance(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT a.attendance_date, a.created_at, m.full_name, t.name AS tent_name
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                JOIN tents t ON t.id = m.tent_id";

        if ($tentId !== null) {
            $sql .= ' WHERE m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 5';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function currentSunday(): string
    {
        $today = new \DateTimeImmutable('today');

        return $today->modify($today->format('N') === '7' ? 'today' : 'last sunday')->format('Y-m-d');
    }

    /**
     * @return array<string, int>
     */
    private function absenteeSummary(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT
                    SUM(CASE WHEN aa.resolved = 0 THEN 1 ELSE 0 END) AS open_total,
                    SUM(CASE WHEN aa.resolved = 0 AND aa.alert_level = 'Critical' THEN 1 ELSE 0 END) AS critical_total
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id";

        if ($tentId !== null) {
            $sql .= ' WHERE m.tent_id = ?';
            $params[] = $tentId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'open_total' => (int) ($row['open_total'] ?? 0),
            'critical_total' => (int) ($row['critical_total'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentAbsenteeAlerts(?int $tentId = null): array
    {
        $params = [];
        $sql = "SELECT aa.missed_count, aa.alert_level, m.id AS member_id, m.full_name, t.name AS tent_name
                FROM absentee_alerts aa
                JOIN members m ON m.id = aa.member_id
                JOIN tents t ON t.id = m.tent_id
                WHERE aa.resolved = 0";

        if ($tentId !== null) {
            $sql .= ' AND m.tent_id = ?';
            $params[] = $tentId;
        }

        $sql .= " ORDER BY
                    FIELD(aa.alert_level, 'Critical', 'Follow-Up Required', 'Early Warning'),
                    aa.missed_count DESC,
                    aa.updated_at DESC
                  LIMIT 3";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
