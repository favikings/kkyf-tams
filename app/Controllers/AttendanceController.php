<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AttendanceService;
use App\Services\AuthService;
use App\Services\TentService;
use RuntimeException;

final class AttendanceController
{
    private AttendanceService $attendance;
    private TentService $tents;

    public function __construct()
    {
        $this->attendance = new AttendanceService();
        $this->tents = new TentService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $query = trim($_GET['q'] ?? '');
        $tentId = (int) ($_GET['tent_id'] ?? 0);

        return View::render('attendance/index', [
            'title' => 'Attendance',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'members' => $this->attendance->searchableMembers($user, $query, $tentId > 0 ? $tentId : null),
            'summary' => $this->attendance->sundaySummary($user, $tentId > 0 ? $tentId : null),
            'tents' => $this->availableTents($user),
            'query' => $query,
            'selectedTentId' => $tentId,
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function checkIn(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        try {
            $this->attendance->checkIn(AuthService::user() ?? [], (int) ($_POST['member_id'] ?? 0));
            $_SESSION['flash_success'] = 'Attendance marked.';
        } catch (RuntimeException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
        }

        Redirect::to('/attendance');
    }

    public function history(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $date = trim($_GET['date'] ?? '');

        return View::render('attendance/history', [
            'title' => 'Attendance History',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'records' => $this->attendance->history($user, $tentId > 0 ? $tentId : null, $date ?: null),
            'summary' => $this->attendance->sundaySummary($user, $tentId > 0 ? $tentId : null),
            'tents' => $this->availableTents($user),
            'selectedTentId' => $tentId,
            'selectedDate' => $date,
        ]);
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
            Redirect::to('/attendance');
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
