<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;

final class StreakBadgeService
{
    private PDO $pdo;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshAll(): array
    {
        $members = $this->members();
        $processed = 0;

        $this->pdo->beginTransaction();

        try {
            foreach ($members as $member) {
                $this->refreshMemberInternal((int) $member['id'], $member);
                $processed++;
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return [
            'processed' => $processed,
        ];
    }

    public function refreshMember(int $memberId): void
    {
        $member = $this->member($memberId);
        if ($member === null) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $this->refreshMemberInternal($memberId, $member);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $member
     */
    private function refreshMemberInternal(int $memberId, array $member): void
    {
        $attendanceDates = $this->attendanceDatesForMember($memberId);
        [$currentStreak, $longestStreak, $totalAttendance, $lastAttendanceDate] = $this->calculateStreaks($attendanceDates);
        $this->upsertStreak($memberId, $currentStreak, $longestStreak, $totalAttendance, $lastAttendanceDate);
        $this->syncBadges($memberId, $member, $currentStreak, $longestStreak);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function members(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, join_date, created_at
             FROM members"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function member(int $memberId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, join_date, created_at
             FROM members
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();

        return $member ?: null;
    }

    /**
     * @return array<int, string>
     */
    private function attendanceDatesForMember(int $memberId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT attendance_date
             FROM attendance
             WHERE member_id = ?
             ORDER BY attendance_date ASC"
        );
        $stmt->execute([$memberId]);

        return array_map(
            static fn (array $row): string => (string) $row['attendance_date'],
            $stmt->fetchAll()
        );
    }

    /**
     * @param array<int, string> $attendanceDates
     * @return array{0:int,1:int,2:int,3:?string}
     */
    private function calculateStreaks(array $attendanceDates): array
    {
        $totalAttendance = count($attendanceDates);
        if ($attendanceDates === []) {
            return [0, 0, 0, null];
        }

        $longestStreak = 1;
        $runningStreak = 1;

        for ($index = 1, $count = count($attendanceDates); $index < $count; $index++) {
            $previous = new DateTimeImmutable($attendanceDates[$index - 1]);
            $current = new DateTimeImmutable($attendanceDates[$index]);

            if ($previous->add(new DateInterval('P7D'))->format('Y-m-d') === $current->format('Y-m-d')) {
                $runningStreak++;
            } else {
                $runningStreak = 1;
            }

            $longestStreak = max($longestStreak, $runningStreak);
        }

        $lastAttendanceDate = end($attendanceDates) ?: null;
        $currentStreak = 0;

        if ($lastAttendanceDate !== null) {
            $currentSunday = $this->currentSunday();
            if ($lastAttendanceDate === $currentSunday) {
                $currentStreak = 1;
                for ($index = count($attendanceDates) - 2; $index >= 0; $index--) {
                    $next = new DateTimeImmutable($attendanceDates[$index + 1]);
                    $current = new DateTimeImmutable($attendanceDates[$index]);

                    if ($current->add(new DateInterval('P7D'))->format('Y-m-d') === $next->format('Y-m-d')) {
                        $currentStreak++;
                        continue;
                    }

                    break;
                }
            }
        }

        return [$currentStreak, $longestStreak, $totalAttendance, $lastAttendanceDate];
    }

    private function upsertStreak(int $memberId, int $currentStreak, int $longestStreak, int $totalAttendance, ?string $lastAttendanceDate): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO streaks (
                member_id, current_streak, longest_streak, total_attendance, last_attendance_date
             ) VALUES (
                ?, ?, ?, ?, ?
             )
             ON DUPLICATE KEY UPDATE
                current_streak = VALUES(current_streak),
                longest_streak = VALUES(longest_streak),
                total_attendance = VALUES(total_attendance),
                last_attendance_date = VALUES(last_attendance_date),
                updated_at = CURRENT_TIMESTAMP"
        );

        $stmt->execute([
            $memberId,
            $currentStreak,
            $longestStreak,
            $totalAttendance,
            $lastAttendanceDate,
        ]);
    }

    /**
     * @param array<string, mixed> $member
     */
    private function syncBadges(int $memberId, array $member, int $currentStreak, int $longestStreak): void
    {
        $badgeIds = $this->badgeIds();
        $qualified = [];

        if ($longestStreak >= 1) {
            $qualified[] = $badgeIds['First Step'] ?? null;
        }
        if ($longestStreak >= 4) {
            $qualified[] = $badgeIds['On Fire'] ?? null;
        }
        if ($longestStreak >= 12) {
            $qualified[] = $badgeIds['Faithful'] ?? null;
        }
        if ($longestStreak >= 24) {
            $qualified[] = $badgeIds['Unstoppable'] ?? null;
        }

        $membershipMonths = $this->membershipMonths($member);
        if ($membershipMonths >= 3) {
            $qualified[] = $badgeIds['3-Month Member'] ?? null;
        }
        if ($membershipMonths >= 6) {
            $qualified[] = $badgeIds['6-Month Member'] ?? null;
        }
        if ($membershipMonths >= 12) {
            $qualified[] = $badgeIds['1-Year Member'] ?? null;
        }

        $qualified = array_values(array_unique(array_filter($qualified, static fn ($id): bool => is_int($id) || ctype_digit((string) $id))));
        $current = $this->currentBadgeIds($memberId);

        $toInsert = array_diff($qualified, $current);
        $toDelete = array_diff($current, $qualified);

        if ($toInsert !== []) {
            $insert = $this->pdo->prepare(
                "INSERT INTO member_badges (member_id, badge_id, awarded_at)
                 VALUES (?, ?, CURRENT_TIMESTAMP)"
            );

            foreach ($toInsert as $badgeId) {
                $insert->execute([$memberId, $badgeId]);
            }
        }

        if ($toDelete !== []) {
            $placeholders = implode(', ', array_fill(0, count($toDelete), '?'));
            $params = array_merge([$memberId], array_values($toDelete));
            $delete = $this->pdo->prepare(
                "DELETE FROM member_badges
                 WHERE member_id = ?
                   AND badge_id IN ({$placeholders})"
            );
            $delete->execute($params);
        }
    }

    /**
     * @return array<string, int>
     */
    private function badgeIds(): array
    {
        $stmt = $this->pdo->query("SELECT id, name FROM badges");
        $map = [];

        foreach ($stmt->fetchAll() as $row) {
            $map[(string) $row['name']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function currentBadgeIds(int $memberId): array
    {
        $stmt = $this->pdo->prepare("SELECT badge_id FROM member_badges WHERE member_id = ?");
        $stmt->execute([$memberId]);

        return array_map(
            static fn (array $row): int => (int) $row['badge_id'],
            $stmt->fetchAll()
        );
    }

    /**
     * @param array<string, mixed> $member
     */
    private function membershipMonths(array $member): int
    {
        $startRaw = (string) ($member['join_date'] ?: substr((string) $member['created_at'], 0, 10));
        $start = new DateTimeImmutable($startRaw);
        $today = new DateTimeImmutable('today');
        $interval = $start->diff($today);

        return max(0, ($interval->y * 12) + $interval->m);
    }

    private function currentSunday(): string
    {
        $today = new DateTimeImmutable('today');

        return $today->modify($today->format('N') === '7' ? 'today' : 'last sunday')->format('Y-m-d');
    }
}
