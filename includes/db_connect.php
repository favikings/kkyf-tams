<?php
// includes/db_connect.php

// 1. Determine Environment
// Check if running on localhost (XAMPP)
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($isLocal) {
    // --- LOCALHOST (XAMPP) ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'kkyf_tams');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // Subfolder on XAMPP (e.g., localhost/kkyf-tams)
    define('BASE_PATH', '/kkyf-tams');
} else {
    // --- PRODUCTION (cPanel) ---
    // FILL THESE IN WITH YOUR CPANEL DETAILS
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'youtewrv_kkyf_tams');
    define('DB_USER', 'youtewrv_kkyf_tams_admin');
    define('DB_PASS', '!kkyf_tams_admin!');

    // Root domain usually needs empty string, or / if preferred.
    // If installed in a subfolder online, change this.
    define('BASE_PATH', '/kkyftams');
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
            // Reload page to connect
            header("Refresh:0");
            exit;
        } catch (PDOException $e2) {
            die("Database Error: " . $e2->getMessage());
        }
    }

    // On Production, just die with error (don't try to create DB as root)
    die("Connection Failed: " . $e->getMessage());
}
?>