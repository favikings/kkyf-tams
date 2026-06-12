<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuthService;
use App\Services\DashboardService;

final class DashboardController
{
    private DashboardService $dashboard;

    public function __construct()
    {
        $this->dashboard = new DashboardService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();
        $user = AuthService::user() ?? [];

        return View::render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'metrics' => $this->dashboard->metricsFor($user),
        ]);
    }

    public function admin(): string
    {
        RoleMiddleware::requireRole('Super Admin');

        return View::render('dashboard/admin', [
            'title' => 'Super Admin',
            'user' => AuthService::user(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function unauthorized(): string
    {
        http_response_code(403);

        return View::render('errors/unauthorized', [
            'title' => 'Unauthorized',
            'user' => AuthService::user(),
        ]);
    }
}
