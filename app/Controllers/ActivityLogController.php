<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Services\ActivityLogService;
use App\Services\AuthService;

final class ActivityLogController
{
    private ActivityLogService $logs;

    public function __construct()
    {
        $this->logs = new ActivityLogService();
    }

    public function index(): string
    {
        RoleMiddleware::requireRole('Super Admin');

        $user = AuthService::user() ?? [];
        $filters = [
            'query' => trim((string) ($_GET['q'] ?? '')),
            'action' => trim((string) ($_GET['action'] ?? '')),
            'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
            'user_id' => (int) ($_GET['user_id'] ?? 0),
        ];

        return View::render('activity-logs/index', [
            'title' => 'Activity Logs',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'logs' => $this->logs->recent($filters),
            'actors' => $this->logs->actors(),
            'filters' => $filters,
        ]);
    }
}
