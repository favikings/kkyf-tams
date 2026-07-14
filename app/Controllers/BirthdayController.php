<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\BirthdayService;

final class BirthdayController
{
    private BirthdayService $birthdays;

    public function __construct()
    {
        $this->birthdays = new BirthdayService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $days = (int) ($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30], true)) {
            $days = 30;
        }

        return View::render('birthdays/index', [
            'title' => 'Birthdays',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'days' => $days,
            'birthdays' => $this->birthdays->upcomingBirthdaysForUser($user, $days),
            'summary' => $this->birthdays->summaryForUser($user),
        ]);
    }
}
