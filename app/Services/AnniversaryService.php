<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;

final class AnniversaryService
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
    public function upcomingAnniversariesForUser(array $user, int $days = 30): array
    {
        $days = max(1, min($days, 60));
        $today = new DateTimeImmutable('today');
        $cutoff = $today->add(new DateInterval('P' . $days . 'D'));
        $rows = $this->candidateMembers($user);
        $anniversaries = [];

        foreach ($rows as $row) {
            $joinDate = trim((string) ($row['join_date'] ?? ''));
            $nextOccurrence = $this->nextOccurrence($joinDate, $today);

            if ($nextOccurrence === null || $nextOccurrence > $cutoff) {
                continue;
            }

            $joinYear = (int) substr($joinDate, 0, 4);
            $celebratingYears = max(1, (int) $nextOccurrence->format('Y') - $joinYear);

            $row['next_anniversary_date'] = $nextOccurrence->format('Y-m-d');
            $row['anniversary_label'] = $nextOccurrence->format('F j');
            $row['days_until_anniversary'] = (int) $today->diff($nextOccurrence)->format('%a');
            $row['is_today_anniversary'] = $row['days_until_anniversary'] === 0;
            $row['celebrating_years'] = $celebratingYears;
            $anniversaries[] = $row;
        }

        usort($anniversaries, static function (array $left, array $right): int {
            $leftDays = (int) ($left['days_until_anniversary'] ?? 9999);
            $rightDays = (int) ($right['days_until_anniversary'] ?? 9999);

            if ($leftDays === $rightDays) {
                return strcmp((string) ($left['full_name'] ?? ''), (string) ($right['full_name'] ?? ''));
            }

            return $leftDays <=> $rightDays;
        });

        return $anniversaries;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, int>
     */
    public function summaryForUser(array $user): array
    {
        $nextSeven = $this->upcomingAnniversariesForUser($user, 7);
        $nextThirty = $this->upcomingAnniversariesForUser($user, 30);

        return [
            'today_total' => count(array_filter($nextThirty, static fn (array $row): bool => !empty($row['is_today_anniversary']))),
            'next_7_days_total' => count($nextSeven),
            'next_30_days_total' => count($nextThirty),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    private function candidateMembers(array $user): array
    {
        $params = [];
        $sql = "SELECT m.id, m.full_name, m.phone, m.join_date, m.occupation, m.tent_id, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                WHERE m.active_status = 'active'
                  AND m.join_date IS NOT NULL
                  AND m.join_date <> ''";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql .= ' ORDER BY m.full_name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function nextOccurrence(string $joinDate, DateTimeImmutable $today): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $joinDate)) {
            return null;
        }

        $month = (int) substr($joinDate, 5, 2);
        $day = (int) substr($joinDate, 8, 2);
        $year = (int) $today->format('Y');

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $candidate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        if ($candidate < $today) {
            $candidate = $candidate->modify('+1 year');
        }

        return $candidate;
    }
}
