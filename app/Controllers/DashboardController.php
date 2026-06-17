<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AnniversaryService;
use App\Services\AuthService;
use App\Services\BirthdayService;
use App\Services\DashboardService;

final class DashboardController
{
    private DashboardService $dashboard;
    private BirthdayService $birthdays;
    private AnniversaryService $anniversaries;

    public function __construct()
    {
        $this->dashboard = new DashboardService();
        $this->birthdays = new BirthdayService();
        $this->anniversaries = new AnniversaryService();
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
            'upcomingBirthdays' => $this->birthdays->upcomingBirthdaysForUser($user, 7),
            'upcomingAnniversaries' => $this->anniversaries->upcomingAnniversariesForUser($user, 7),
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
