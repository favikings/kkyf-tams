<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AnniversaryService;
use App\Services\AuthService;

final class AnniversaryController
{
    private AnniversaryService $anniversaries;

    public function __construct()
    {
        $this->anniversaries = new AnniversaryService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $days = (int) ($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30], true)) {
            $days = 30;
        }

        return View::render('anniversaries/index', [
            'title' => 'Anniversaries',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'days' => $days,
            'anniversaries' => $this->anniversaries->upcomingAnniversariesForUser($user, $days),
            'summary' => $this->anniversaries->summaryForUser($user),
        ]);
    }
}
