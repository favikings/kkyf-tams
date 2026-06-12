<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Core\App;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool must be run from the command line.\n");
    exit(1);
}

$options = getopt('', ['dry-run', 'write']);
$dryRun = isset($options['dry-run']);
$write = isset($options['write']);

if ($dryRun === $write) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php database/migrate_legacy_to_v2.php --dry-run\n");
    fwrite(STDERR, "  php database/migrate_legacy_to_v2.php --write\n");
    exit(1);
}

$config = App::bootstrap(dirname(__DIR__));
$pdo = Database::connect($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$requiredTables = [
    'legacy_tents',
    'legacy_admin_user',
    'legacy_members',
    'legacy_attendance_log',
    'users',
    'tents',
    'members',
    'attendance',
    'migration_logs',
];

foreach ($requiredTables as $table) {
    if (!tableExists($pdo, $table)) {
        fwrite(STDERR, "Missing required table: {$table}\n");
        exit(1);
    }
}

$stats = [
    'tents' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
    'users' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
    'members' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
    'attendance' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
];

if ($write) {
    $pdo->beginTransaction();
}

try {
    migrateTents($pdo, $write, $stats);
    migrateUsers($pdo, $write, $stats);
    migrateMembers($pdo, $write, $stats);
    migrateAttendance($pdo, $write, $stats);

    if ($write) {
        $pdo->commit();
    }
} catch (Throwable $exception) {
    if ($write && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

echo ($write ? "WRITE" : "DRY RUN") . " legacy migration summary\n";
echo str_repeat('-', 34) . "\n";

foreach ($stats as $name => $counts) {
    echo "{$name}: created {$counts['created']}, skipped {$counts['skipped']}, errors {$counts['errors']}\n";
}

echo "\n";
echo $write ? "Write migration complete.\n" : "Dry run complete. No rows were written.\n";

function migrateTents(PDO $pdo, bool $write, array &$stats): void
{
    $rows = $pdo->query('SELECT * FROM legacy_tents ORDER BY Tent_ID')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        try {
            $existingId = findId($pdo, 'tents', 'name', $row['Tent_Name']);

            if ($existingId !== null) {
                $stats['tents']['skipped']++;
                logMigration($pdo, $write, 'legacy_tents', (string) $row['Tent_ID'], 'tents', $existingId, 'skipped', 'Tent already exists by name.');
                continue;
            }

            $stats['tents']['created']++;

            if (!$write) {
                continue;
            }

            $statement = $pdo->prepare(
                'INSERT INTO tents (uuid, name, banner, color, leader_name, leader_phone, whatsapp_link, status, created_at, updated_at)
                 VALUES (UUID(), ?, NULL, ?, NULL, NULL, NULL, "active", NOW(), NOW())'
            );
            $statement->execute([$row['Tent_Name'], '#00bd06']);
            logMigration($pdo, true, 'legacy_tents', (string) $row['Tent_ID'], 'tents', (int) $pdo->lastInsertId(), 'success', 'Tent migrated.');
        } catch (Throwable $exception) {
            $stats['tents']['errors']++;
            logMigration($pdo, $write, 'legacy_tents', (string) $row['Tent_ID'], 'tents', null, 'error', $exception->getMessage());
        }
    }
}

function migrateUsers(PDO $pdo, bool $write, array &$stats): void
{
    $rows = $pdo->query('SELECT * FROM legacy_admin_user ORDER BY ID')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        try {
            $email = trim((string) ($row['Email'] ?? ''));
            $existingId = $email !== '' ? findId($pdo, 'users', 'email', $email) : null;

            if ($existingId !== null) {
                $stats['users']['skipped']++;
                logMigration($pdo, $write, 'legacy_admin_user', (string) $row['ID'], 'users', $existingId, 'skipped', 'User already exists by email.');
                continue;
            }

            $tentId = null;

            if ($row['Assigned_Tent_ID'] !== null) {
                $tentId = $write
                    ? findMappedTargetId($pdo, 'legacy_tents', (string) $row['Assigned_Tent_ID'], 'tents')
                    : 1;
            }

            $stats['users']['created']++;

            if (!$write) {
                continue;
            }

            $statement = $pdo->prepare(
                'INSERT INTO users (uuid, full_name, email, phone, password_hash, role, tent_id, status, created_at, updated_at)
                 VALUES (UUID(), ?, ?, NULL, ?, ?, ?, "active", ?, NOW())'
            );
            $statement->execute([
                $row['Username'],
                $email !== '' ? $email : null,
                $row['Password_Hash'],
                $row['Role'],
                $tentId,
                $row['Created_At'] ?? date('Y-m-d H:i:s'),
            ]);
            logMigration($pdo, true, 'legacy_admin_user', (string) $row['ID'], 'users', (int) $pdo->lastInsertId(), 'success', 'Admin user migrated.');
        } catch (Throwable $exception) {
            $stats['users']['errors']++;
            logMigration($pdo, $write, 'legacy_admin_user', (string) $row['ID'], 'users', null, 'error', $exception->getMessage());
        }
    }
}

function migrateMembers(PDO $pdo, bool $write, array &$stats): void
{
    $rows = $pdo->query('SELECT * FROM legacy_members ORDER BY Member_ID')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        try {
            $existingId = findExistingMemberId($pdo, $row);

            if ($existingId !== null) {
                $stats['members']['skipped']++;
                logMigration($pdo, $write, 'legacy_members', (string) $row['Member_ID'], 'members', $existingId, 'skipped', 'Member already exists by uuid, phone, or name.');
                continue;
            }

            $tentId = $write
                ? findMappedTargetId($pdo, 'legacy_tents', (string) $row['Current_Tent_ID'], 'tents')
                : simulatedTentId($pdo, (string) $row['Current_Tent_ID']);

            if ($tentId === null) {
                throw new RuntimeException('No migrated v2 tent found for legacy tent ' . $row['Current_Tent_ID']);
            }

            $stats['members']['created']++;

            if (!$write) {
                continue;
            }

            $statement = $pdo->prepare(
                'INSERT INTO members (uuid, full_name, phone, date_of_birth, occupation, school_name, tent_id, join_date, profile_photo, notes, active_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, "active", NOW(), NOW())'
            );
            $statement->execute([
                $row['Member_UUID'],
                $row['Full_Name'],
                normalizedPhone($row['Phone'] ?? null),
                birthMonthDay($row['Birthdate'] ?? null),
                $row['Status'],
                $row['School'],
                $tentId,
                dateOnly($row['Join_Date'] ?? null),
                $row['Address'] ? 'Legacy address: ' . $row['Address'] : null,
            ]);
            logMigration($pdo, true, 'legacy_members', (string) $row['Member_ID'], 'members', (int) $pdo->lastInsertId(), 'success', 'Member migrated.');
        } catch (Throwable $exception) {
            $stats['members']['errors']++;
            logMigration($pdo, $write, 'legacy_members', (string) $row['Member_ID'], 'members', null, 'error', $exception->getMessage());
        }
    }
}

function migrateAttendance(PDO $pdo, bool $write, array &$stats): void
{
    $rows = $pdo->query('SELECT * FROM legacy_attendance_log ORDER BY Log_ID')->fetchAll(PDO::FETCH_ASSOC);
    $checkedBy = defaultCheckedByUserId($pdo);

    foreach ($rows as $row) {
        try {
            $memberId = findMemberIdForAttendance($pdo, $row['Member_UUID']);

            if ($memberId === null) {
                if ($write) {
                    throw new RuntimeException('No v2 member found for legacy member UUID ' . $row['Member_UUID']);
                }

                if (!legacyMemberExists($pdo, $row['Member_UUID'])) {
                    throw new RuntimeException('No legacy member found for attendance UUID ' . $row['Member_UUID']);
                }

                $stats['attendance']['created']++;
                continue;
            }

            if (attendanceExists($pdo, $memberId, $row['Attendance_Date'])) {
                $stats['attendance']['skipped']++;
                logMigration($pdo, $write, 'legacy_attendance_log', (string) $row['Log_ID'], 'attendance', null, 'skipped', 'Attendance already exists for member/date.');
                continue;
            }

            $stats['attendance']['created']++;

            if (!$write) {
                continue;
            }

            $statement = $pdo->prepare(
                'INSERT INTO attendance (member_id, attendance_date, service_type, checked_by, source, created_at)
                 VALUES (?, ?, "Sunday Service", ?, "offline", ?)'
            );
            $statement->execute([
                $memberId,
                $row['Attendance_Date'],
                $checkedBy,
                $row['Check_In_Time'] ?? date('Y-m-d H:i:s'),
            ]);
            logMigration($pdo, true, 'legacy_attendance_log', (string) $row['Log_ID'], 'attendance', (int) $pdo->lastInsertId(), 'success', 'Attendance migrated.');
        } catch (Throwable $exception) {
            $stats['attendance']['errors']++;
            logMigration($pdo, $write, 'legacy_attendance_log', (string) $row['Log_ID'], 'attendance', null, 'error', $exception->getMessage());
        }
    }
}

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $statement->execute([$table]);

    return (int) $statement->fetchColumn() > 0;
}

function findId(PDO $pdo, string $table, string $column, mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $statement = $pdo->prepare("SELECT id FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
    $statement->execute([$value]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function findMappedTargetId(PDO $pdo, string $sourceTable, string $sourceId, string $targetTable): ?int
{
    $statement = $pdo->prepare(
        'SELECT target_id FROM migration_logs
         WHERE source_table = ?
           AND source_id = ?
           AND target_table = ?
           AND status IN ("success", "skipped")
           AND target_id IS NOT NULL
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([$sourceTable, $sourceId, $targetTable]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function findExistingMemberId(PDO $pdo, array $row): ?int
{
    $uuidId = findId($pdo, 'members', 'uuid', $row['Member_UUID']);

    if ($uuidId !== null) {
        return $uuidId;
    }

    $phone = normalizedPhone($row['Phone'] ?? null);
    $phoneId = findId($pdo, 'members', 'phone', $phone);

    if ($phoneId !== null) {
        return $phoneId;
    }

    return findId($pdo, 'members', 'full_name', $row['Full_Name']);
}

function findMemberIdByUuid(PDO $pdo, string $uuid): ?int
{
    return findId($pdo, 'members', 'uuid', $uuid);
}

function findMemberIdForAttendance(PDO $pdo, string $uuid): ?int
{
    $memberId = findMemberIdByUuid($pdo, $uuid);

    if ($memberId !== null) {
        return $memberId;
    }

    $statement = $pdo->prepare('SELECT Member_ID FROM legacy_members WHERE Member_UUID = ? LIMIT 1');
    $statement->execute([$uuid]);
    $legacyMemberId = $statement->fetchColumn();

    if ($legacyMemberId === false) {
        return null;
    }

    return findMappedTargetId($pdo, 'legacy_members', (string) $legacyMemberId, 'members');
}

function simulatedTentId(PDO $pdo, string $legacyTentId): ?int
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM legacy_tents WHERE Tent_ID = ?');
    $statement->execute([$legacyTentId]);

    return (int) $statement->fetchColumn() > 0 ? 1 : null;
}

function legacyMemberExists(PDO $pdo, string $uuid): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM legacy_members WHERE Member_UUID = ?');
    $statement->execute([$uuid]);

    return (int) $statement->fetchColumn() > 0;
}

function attendanceExists(PDO $pdo, int $memberId, string $date): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ? AND attendance_date = ?');
    $statement->execute([$memberId, $date]);

    return (int) $statement->fetchColumn() > 0;
}

function defaultCheckedByUserId(PDO $pdo): int
{
    $id = $pdo->query('SELECT id FROM users WHERE role = "Super Admin" ORDER BY id LIMIT 1')->fetchColumn();

    if ($id === false) {
        $id = $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
    }

    if ($id === false) {
        throw new RuntimeException('No v2 user exists to use as checked_by for attendance migration.');
    }

    return (int) $id;
}

function normalizedPhone(?string $phone): ?string
{
    $phone = trim((string) $phone);

    return $phone === '' ? null : $phone;
}

function birthMonthDay(?string $date): ?string
{
    if ($date === null || trim($date) === '' || str_starts_with($date, '0000-00-00')) {
        return null;
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? null : date('m-d', $timestamp);
}

function dateOnly(?string $date): ?string
{
    if ($date === null || trim($date) === '' || str_starts_with($date, '0000-00-00')) {
        return null;
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function logMigration(PDO $pdo, bool $write, string $sourceTable, string $sourceId, ?string $targetTable, ?int $targetId, string $status, string $message): void
{
    if (!$write) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO migration_logs (source_table, source_id, target_table, target_id, status, message, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $statement->execute([$sourceTable, $sourceId, $targetTable, $targetId, $status, $message]);
}
