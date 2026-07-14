<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Env.php';
require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/Logger.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Services/AbsenteeService.php';

use App\Core\Env;
use App\Services\AbsenteeService;

Env::load(dirname(__DIR__) . '/.env');

$options = getopt('', ['date::', 'dry-run']);
$date = is_string($options['date'] ?? null) ? $options['date'] : null;
$dryRun = array_key_exists('dry-run', $options);

try {
    $service = new AbsenteeService();
    $result = $service->calculateForSunday($date, $dryRun);

    echo 'Absentee calculation complete for ' . $result['date'] . PHP_EOL;
    echo 'Processed members: ' . $result['processed'] . PHP_EOL;
    echo 'Flagged alerts: ' . $result['flagged'] . PHP_EOL;

    foreach (array_slice($result['alerts'], 0, 20) as $alert) {
        echo '- ' . $alert['full_name'] . ' | ' . $alert['missed_count'] . ' missed | ' . $alert['alert_level'] . PHP_EOL;
    }

    if ($dryRun) {
        echo '[dry-run] No database rows were changed.' . PHP_EOL;
    }

    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Absentee calculation failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
