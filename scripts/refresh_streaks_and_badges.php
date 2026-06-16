<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Env.php';
require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/Logger.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Services/StreakBadgeService.php';

use App\Core\Env;
use App\Services\StreakBadgeService;

Env::load(dirname(__DIR__) . '/.env');

try {
    $service = new StreakBadgeService();
    $result = $service->refreshAll();

    echo 'Streak and badge refresh complete.' . PHP_EOL;
    echo 'Processed members: ' . $result['processed'] . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Refresh failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
