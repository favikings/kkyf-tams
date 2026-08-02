<?php

declare(strict_types=1);

/**
 * Shared helpers: output escaping and redirects.
 * Included by app/includes/auth.php and any page that renders user data.
 */

/**
 * HTML-escapes a value for safe output. Use on anything that came from the
 * database or from user input before printing it into markup.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Issues a Location redirect and stops execution. Paths are relative to the
 * web root (e.g. 'login.php', 'dashboard.php').
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * PLACEHOLDER audit trail (doc/DECISIONS.md R-37) for the single most
 * consequential action in the app — a Super Admin bulk-deleting every member
 * in a tent. Appends one timestamped line to a local log file since no
 * `activity_logs` table exists yet. Replace with a real table + UI when that
 * lands; do not build more on top of this file-based approach in the meantime.
 */
function logDangerZoneAction(string $line): void
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($dir . '/danger-zone.log', '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Returns the current service Sunday as 'Y-m-d': today when today is Sunday,
 * otherwise the most recent past Sunday.
 */
function currentSunday(): string
{
    $today = new DateTimeImmutable('today');
    if ((int) $today->format('N') !== 7) {
        $today = $today->modify('last sunday');
    }

    return $today->format('Y-m-d');
}

/**
 * Validates member input per TECH_SPEC §8. The single source of truth for
 * member create/edit — called by the manual add form, member edit, the
 * check-in quick-add API, and the CSV importer. Never duplicate this logic.
 *
 * Returns ['errors' => string[], 'clean' => array] where 'clean' keys match
 * DB columns: full_name, phone, occupation, school_name, birth_month,
 * birth_day, tent_id.
 */
function validateMember(array $input): array
{
    $errors = [];

    $fullName = trim((string) ($input['full_name'] ?? ''));
    $phone = preg_replace('/[\s-]+/', '', (string) ($input['phone'] ?? ''));
    $occupation = (string) ($input['occupation'] ?? '');
    $schoolName = trim((string) ($input['school_name'] ?? ''));
    $birthMonth = (string) ($input['birth_month'] ?? '');
    $birthDay = (string) ($input['birth_day'] ?? '');
    $tentId = (int) ($input['tent_id'] ?? 0);

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullName) > 200) {
        $errors[] = 'Full name must be 200 characters or fewer.';
    }

    if ($phone !== '') {
        if (!preg_match('/^[0-9+]+$/', $phone)) {
            $errors[] = 'Phone may only contain digits (a leading plus is allowed).';
        } elseif (mb_strlen($phone) > 20) {
            $errors[] = 'Phone must be 20 characters or fewer.';
        }
    }

    if (!in_array($occupation, ['student', 'worker'], true)) {
        $errors[] = 'Occupation must be student or worker.';
    }

    if (mb_strlen($schoolName) > 200) {
        $errors[] = 'School name must be 200 characters or fewer.';
    }

    $cleanMonth = null;
    if ($birthMonth !== '') {
        if (!ctype_digit($birthMonth) || (int) $birthMonth < 1 || (int) $birthMonth > 12) {
            $errors[] = 'Birth month must be between 1 and 12.';
        } else {
            $cleanMonth = (int) $birthMonth;
        }
    }

    $cleanDay = null;
    if ($birthDay !== '') {
        if (!ctype_digit($birthDay) || (int) $birthDay < 1 || (int) $birthDay > 31) {
            $errors[] = 'Birth day must be between 1 and 31.';
        } else {
            $cleanDay = (int) $birthDay;
        }
    }

    if (($cleanMonth === null) !== ($cleanDay === null)) {
        $errors[] = 'Birth month and day must be provided together (or both left blank).';
    }

    if ($tentId < 1) {
        $errors[] = 'Tent is required.';
    } else {
        $tentStmt = db()->prepare('SELECT id FROM tents WHERE id = ? AND is_active = 1');
        $tentStmt->execute([$tentId]);
        if ($tentStmt->fetch() === false) {
            $errors[] = 'Selected tent does not exist or is inactive.';
        }
    }

    return [
        'errors' => $errors,
        'clean' => [
            'full_name' => $fullName,
            'phone' => $phone !== '' ? $phone : null,
            'occupation' => $occupation,
            'school_name' => $occupation === 'student' && $schoolName !== '' ? $schoolName : null,
            'birth_month' => $cleanMonth,
            'birth_day' => $cleanDay,
            'tent_id' => $tentId,
        ],
    ];
}
