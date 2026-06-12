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

$options = getopt('', ['dry-run']);

if (!isset($options['dry-run'])) {
    fwrite(STDERR, "Usage: php database/legacy_migration_check.php --dry-run\n");
    exit(1);
}

$config = App::bootstrap(dirname(__DIR__));
$pdo = Database::connect($config);

$sourceTables = [
    'legacy_tents',
    'legacy_admin_user',
    'legacy_members',
    'legacy_attendance_log',
];

$targetTables = [
    'users',
    'tents',
    'members',
    'attendance',
    'migration_logs',
];

$legacyFallbackTables = [
    'admin_user',
    'attendance_log',
];

$missing = [];

foreach (array_merge($sourceTables, $targetTables) as $table) {
    if (!tableExists($pdo, $table)) {
        $missing[] = $table;
    }
}

echo "Legacy migration dry-run check\n";
echo str_repeat('-', 31) . "\n";

if ($missing !== []) {
    echo "Missing isolated source/target tables:\n";

    foreach ($missing as $table) {
        echo "- {$table}\n";
    }

    echo "\n";
    echo "Expected source table names are prefixed with legacy_ to avoid mixing old data with v2 tables.\n";
    echo "Import or rename the legacy tables as:\n";
    echo "- Tents => legacy_tents\n";
    echo "- Admin_User => legacy_admin_user\n";
    echo "- Members => legacy_members\n";
    echo "- Attendance_Log => legacy_attendance_log\n";
    echo "\n";
}

echo "Detected table counts:\n";

foreach (array_merge($sourceTables, $targetTables, $legacyFallbackTables) as $table) {
    if (!tableExists($pdo, $table)) {
        continue;
    }

    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo "- {$table}: {$count}\n";
}

echo "\n";
echo "Dry run complete. No rows were written.\n";

if ($missing !== []) {
    exit(1);
}

exit(0);

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?'
    );
    $statement->execute([$table]);

    return (int) $statement->fetchColumn() > 0;
}
