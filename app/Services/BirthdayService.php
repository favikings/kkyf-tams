<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;

final class BirthdayService
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
    public function upcomingBirthdaysForUser(array $user, int $days = 30): array
    {
        $days = max(1, min($days, 60));
        $today = new DateTimeImmutable('today');
        $cutoff = $today->add(new DateInterval('P' . $days . 'D'));
        $rows = $this->candidateMembers($user);
        $birthdays = [];

        foreach ($rows as $row) {
            $monthDay = trim((string) ($row['date_of_birth'] ?? ''));
            $nextOccurrence = $this->nextOccurrence($monthDay, $today);

            if ($nextOccurrence === null || $nextOccurrence > $cutoff) {
                continue;
            }

            $row['next_birthday_date'] = $nextOccurrence->format('Y-m-d');
            $row['birthday_label'] = $nextOccurrence->format('F j');
            $row['days_until_birthday'] = (int) $today->diff($nextOccurrence)->format('%a');
            $row['is_today_birthday'] = $row['days_until_birthday'] === 0;
            $birthdays[] = $row;
        }

        usort($birthdays, static function (array $left, array $right): int {
            $leftDays = (int) ($left['days_until_birthday'] ?? 9999);
            $rightDays = (int) ($right['days_until_birthday'] ?? 9999);

            if ($leftDays === $rightDays) {
                return strcmp((string) ($left['full_name'] ?? ''), (string) ($right['full_name'] ?? ''));
            }

            return $leftDays <=> $rightDays;
        });

        return $birthdays;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, int>
     */
    public function summaryForUser(array $user): array
    {
        $nextSeven = $this->upcomingBirthdaysForUser($user, 7);
        $nextThirty = $this->upcomingBirthdaysForUser($user, 30);

        return [
            'today_total' => count(array_filter($nextThirty, static fn (array $row): bool => !empty($row['is_today_birthday']))),
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
        $sql = "SELECT m.id, m.full_name, m.phone, m.date_of_birth, m.occupation, m.tent_id, t.name AS tent_name
                FROM members m
                JOIN tents t ON t.id = m.tent_id
                WHERE m.active_status = 'active'
                  AND m.date_of_birth IS NOT NULL
                  AND m.date_of_birth <> ''";

        if (($user['role'] ?? null) === 'Tent Admin') {
            $sql .= ' AND m.tent_id = ?';
            $params[] = (int) ($user['tent_id'] ?? 0);
        }

        $sql .= ' ORDER BY m.full_name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function nextOccurrence(string $monthDay, DateTimeImmutable $today): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{2}-\d{2}$/', $monthDay)) {
            return null;
        }

        [$month, $day] = array_map('intval', explode('-', $monthDay));
        $year = (int) $today->format('Y');

        if ($month === 2 && $day === 29) {
            $candidateYear = $year;

            while (!$this->isLeapYear($candidateYear) || new DateTimeImmutable(sprintf('%04d-02-29', $candidateYear)) < $today) {
                $candidateYear++;
            }

            return new DateTimeImmutable(sprintf('%04d-02-29', $candidateYear));
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $candidate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        if ($candidate < $today) {
            $candidate = $candidate->modify('+1 year');
        }

        return $candidate;
    }

    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
