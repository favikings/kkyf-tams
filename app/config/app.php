<?php

declare(strict_types=1);

/**
 * Bootstrap config: environment, timezone, constants, error reporting, session.
 * Every page must include this file first (before auth.php / header.php).
 */

require_once __DIR__ . '/db.php';

loadEnv();

date_default_timezone_set((string) env('TIMEZONE', 'Africa/Lagos'));

define('APP_NAME', (string) env('APP_NAME', 'KKYF Portal'));
define('APP_URL', (string) env('APP_URL', 'http://localhost'));

// TECH_SPEC §7: hide errors in production; APP_DEBUG=true in .env re-enables them locally.
$appDebug = in_array(strtolower((string) env('APP_DEBUG', '')), ['1', 'true', 'yes', 'on'], true);

error_reporting(E_ALL);
if ($appDebug) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// 8-hour session, httponly + SameSite=Lax, secure only over HTTPS (TECH_SPEC §8 / build prompt Step 1).
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name('kkyf_session');
    session_set_cookie_params([
        'lifetime' => 8 * 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
