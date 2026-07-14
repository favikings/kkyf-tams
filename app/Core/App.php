<?php

namespace App\Core;

final class App
{
    public static function bootstrap(string $basePath): Config
    {
        Env::load($basePath . '/.env');

        $config = new Config($basePath . '/config');
        Logger::init($config->get('app.log_path', $basePath . '/storage/logs/app.log'));

        error_reporting(E_ALL);
        ini_set('display_errors', $config->get('app.debug', false) ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $config->get('app.log_path', $basePath . '/storage/logs/app.log'));

        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_name($config->get('app.session_name', 'KKYF_V2_SESSION'));
            session_start();
        }

        set_exception_handler(function (\Throwable $exception) use ($config): void {
            Logger::error('Unhandled exception.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            http_response_code(500);
            echo $config->get('app.debug', false)
                ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
                : 'Application error.';
        });

        return $config;
    }
}
