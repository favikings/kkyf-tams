<?php
// includes/db_connect.php

// 1. Load Environment Variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// 2. Determine Environment
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($isLocal) {
    // --- LOCALHOST (XAMPP) ---
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'kkyf_tams');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');

    // Email Configuration
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'mail.kkyfglobal.org');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 465);
    define('SMTP_USER', $_ENV['SMTP_USER'] ?? 'noreply@kkyfglobal.org');
    define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
    define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@kkyfglobal.org');
    define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'KKYF Tent Manager');

    // Subfolder on XAMPP
    define('BASE_PATH', $_ENV['BASE_PATH'] ?? '/kkyf-tams');
} else {
    // --- PRODUCTION (cPanel) ---
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'youtewrv_kkyf_tams');
    define('DB_USER', $_ENV['DB_USER'] ?? 'youtewrv_kkyf_tams_admin');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');

    // Email Configuration
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'mail.kkyfglobal.org');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 465);
    define('SMTP_USER', $_ENV['SMTP_USER'] ?? 'noreply@kkyfglobal.org');
    define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
    define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@kkyfglobal.org');
    define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'KKYF Tent Manager');

    // Root domain
    define('BASE_PATH', $_ENV['BASE_PATH'] ?? '/kkyftams');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Smart Setup: If DB doesn't exist on LOCAL only, try to create it
    if ($isLocal && strpos($e->getMessage(), "Unknown database") !== false) {
        try {
            $pdoRoot = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $pdoRoot->exec("CREATE DATABASE " . DB_NAME);
            header("Refresh:0");
            exit;
        } catch (PDOException $e2) {
            die("Database Error: " . htmlspecialchars($e2->getMessage()));
        }
    }

    // Don't expose raw errors in production
    error_log("DB Connection Failed: " . $e->getMessage());
    die("Connection Failed. Please contact the administrator.");
}
?>
