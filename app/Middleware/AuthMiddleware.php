<?php

namespace App\Middleware;

use App\Core\Redirect;
use App\Services\AuthService;

final class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (!AuthService::check()) {
            Redirect::to('/login');
        }
    }
}
