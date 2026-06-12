<?php

use App\Core\Env;

$logPath = Env::get('LOG_PATH', 'storage/logs/app.log');
if (!str_starts_with($logPath, '/') && !preg_match('/^[A-Za-z]:[\/\\\\]/', $logPath)) {
    $logPath = dirname(__DIR__) . '/' . $logPath;
}

return [
    'name' => Env::get('APP_NAME', 'KKYF Membership Portal v2'),
    'env' => Env::get('APP_ENV', 'local'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => Env::get('APP_URL', 'http://localhost/kkyf-tams-1/public'),
    'base_path' => Env::get('BASE_PATH', '/kkyf-tams-1/public'),
    'log_path' => $logPath,
    'session_name' => Env::get('SESSION_NAME', 'KKYF_V2_SESSION'),
];
