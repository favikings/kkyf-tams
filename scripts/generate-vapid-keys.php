<?php

declare(strict_types=1);

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Install Composer dependencies before generating VAPID keys.\n");
    exit(1);
}

require_once $autoloadPath;

try {
    $keys = Minishlink\WebPush\VAPID::createVapidKeys();
} catch (Throwable $exception) {
    fwrite(STDERR, "Could not generate VAPID keys: {$exception->getMessage()}\n");
    exit(1);
}

echo "Generate these once, store them only in the production .env, and keep the private key secret.\n\n";
echo 'VAPID_PUBLIC_KEY=' . $keys['publicKey'] . PHP_EOL;
echo 'VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;
echo "VAPID_SUBJECT=mailto:noreply@kkyfglobal.org\n";
