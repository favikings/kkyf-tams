<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AbsenteeService;
use App\Services\AuthService;
use App\Services\TentService;

final class AbsenteeController
{
    private AbsenteeService $absentees;
    private TentService $tents;

    public function __construct()
    {
        $this->absentees = new AbsenteeService();
        $this->tents = new TentService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $level = trim($_GET['level'] ?? '');
        $resolved = trim($_GET['resolved'] ?? 'open');

        return View::render('absentees/index', [
            'title' => 'Absentee Alerts',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'alerts' => $this->absentees->alerts($user, $tentId > 0 ? $tentId : null, $level, $resolved),
            'summary' => $this->absentees->summary($user),
            'tents' => $this->availableTents($user),
            'selectedTentId' => $tentId,
            'selectedLevel' => $level,
            'selectedResolved' => $resolved,
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function resolve(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        $user = AuthService::user() ?? [];
        $id = (int) ($_POST['id'] ?? 0);

        if ($this->absentees->findScoped($user, $id) === null) {
            $_SESSION['flash_error'] = 'Absentee alert not found or outside your tent.';
            Redirect::to('/absentees');
        }

        $this->absentees->resolve($id);
        $_SESSION['flash_success'] = 'Absentee alert marked resolved.';
        Redirect::to('/absentees');
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    private function availableTents(array $user): array
    {
        if (($user['role'] ?? null) === 'Tent Admin') {
            $tent = $this->tents->find((int) ($user['tent_id'] ?? 0));
            return $tent === null ? [] : [$tent];
        }

        return $this->tents->all();
    }

    private function verifyCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to('/absentees');
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
