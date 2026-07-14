<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use PDO;

final class AuthService
{
    private PDO $pdo;

    public function __construct()
    {
        $config = new Config(dirname(__DIR__, 2) . '/config');
        $this->pdo = Database::connect($config);
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, uuid, full_name, email, password_hash, role, tent_id, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['v2_user'] = [
            'id' => (int) $user['id'],
            'uuid' => $user['uuid'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tent_id' => $user['tent_id'] === null ? null : (int) $user['tent_id'],
        ];

        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['v2_user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        unset($_SESSION['v2_user']);
        session_regenerate_id(true);
    }
}
