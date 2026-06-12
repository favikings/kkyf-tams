<?php

namespace App\Core;

final class Logger
{
    private static string $logPath;

    public static function init(string $logPath): void
    {
        self::$logPath = $logPath;

        $directory = dirname($logPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        $path = self::$logPath ?? dirname(__DIR__, 2) . '/storage/logs/app.log';
        $payload = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        $line = sprintf("[%s] %s: %s%s\n", date('Y-m-d H:i:s'), $level, $message, $payload);

        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
