<?php

namespace App\Middleware;

use App\Core\Redirect;
use App\Services\AuthService;

final class RoleMiddleware
{
    public static function requireRole(string $role): void
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user();
        if (($user['role'] ?? null) !== $role) {
            Redirect::to('/unauthorized');
        }
    }
}
