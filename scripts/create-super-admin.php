#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Creates a Super Admin account. Run via: php scripts/create-super-admin.php
 *
 * Re-runnable by design — the app supports multiple Super Admins, so this
 * script never refuses to run just because one already exists. It only
 * refuses when the given email is already taken.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/config/db.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);

    return trim($value === false ? '' : $value);
}

/**
 * Prompts for a password with input hidden when the terminal supports it
 * (stty on Linux/macOS). Falls back to visible input otherwise.
 */
function promptPassword(string $label): string
{
    fwrite(STDOUT, $label);

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($isWindows) {
        $value = fgets(STDIN);

        return trim($value === false ? '' : $value);
    }

    $sttyPath = trim((string) shell_exec('which stty'));
    if ($sttyPath === '') {
        $value = fgets(STDIN);

        return trim($value === false ? '' : $value);
    }

    $originalMode = shell_exec('stty -g');
    shell_exec('stty -echo');
    $value = fgets(STDIN);
    shell_exec('stty ' . escapeshellarg(trim((string) $originalMode)));
    fwrite(STDOUT, "\n");

    return trim($value === false ? '' : $value);
}

echo "=== KKYF Portal — Create Super Admin ===\n";

$name = prompt('Full name: ');
if ($name === '') {
    fwrite(STDERR, "Error: name is required.\n");
    exit(1);
}

$email = strtolower(prompt('Email: '));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: a valid email is required.\n");
    exit(1);
}

$phone = prompt('Phone: ');
if ($phone === '') {
    fwrite(STDERR, "Error: phone is required.\n");
    exit(1);
}

$password = promptPassword('Password: ');
if (strlen($password) < 8) {
    fwrite(STDERR, "Error: password must be at least 8 characters.\n");
    exit(1);
}

$confirmPassword = promptPassword('Confirm password: ');
if ($password !== $confirmPassword) {
    fwrite(STDERR, "Error: passwords do not match.\n");
    exit(1);
}

$pdo = db();

$existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$existing->execute([$email]);
if ($existing->fetch() !== false) {
    fwrite(STDERR, "Error: a user with this email already exists.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$insert = $pdo->prepare(
    'INSERT INTO users (name, email, phone, password_hash, role, tent_id, status, is_active)
     VALUES (?, ?, ?, ?, ?, NULL, ?, 1)'
);
$insert->execute([$name, $email, $phone, $passwordHash, 'super_admin', 'approved']);

echo "Super Admin created successfully (id: {$pdo->lastInsertId()}).\n";
