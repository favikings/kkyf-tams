<?php

declare(strict_types=1);

/**
 * Minimal .env loader — no Composer dependency.
 * Parses KEY=VALUE lines (skips blank lines and # comments) into the process
 * environment via putenv() + $_ENV. Never overwrites an already-set variable.
 * Idempotent — subsequent calls are no-ops.
 */
function loadEnv(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envFile = dirname(__DIR__, 2) . '/.env';

    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $eq = strpos($line, '=');
                if ($eq === false) {
                    continue;
                }
                $key = trim(substr($line, 0, $eq));
                $value = trim(substr($line, $eq + 1));
                if ($key === '' || getenv($key) !== false || array_key_exists($key, $_ENV)) {
                    continue;
                }
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    $loaded = true;
}

/**
 * Reads a config value from the environment, falling back to $default.
 * Values are returned as strings; boolean flags must be interpreted by the
 * caller (see APP_DEBUG handling in app.php) so values like "false" aren't
 * misread as truthy.
 */
function env(string $key, mixed $default = null): mixed
{
    loadEnv();

    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? null;
    }

    return $value === null ? $default : (string) $value;
}

/**
 * Returns the single shared PDO connection (created once per request).
 * This is the ONLY place a PDO connection is opened in the app.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    loadEnv();

    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_NAME', '');
    $user = (string) env('DB_USER', 'root');
    $pass = (string) env('DB_PASS', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Keep raw driver details out of the browser (TECH_SPEC §7).
        throw new RuntimeException('Database connection failed. Check .env and database availability.', 0, $e);
    }

    return $pdo;
}
