<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\ActivityLogService;
use App\Services\AttendanceService;
use App\Services\AuthService;
use App\Services\TentService;
use RuntimeException;

final class AttendanceController
{
    private AttendanceService $attendance;
    private TentService $tents;
    private ActivityLogService $logs;

    public function __construct()
    {
        $this->attendance = new AttendanceService();
        $this->tents = new TentService();
        $this->logs = new ActivityLogService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $query = trim($_GET['q'] ?? '');
        $isSuperAdmin = ($user['role'] ?? null) === 'Super Admin';
        $checkedInRecords = $this->attendance->checkedInMembersForSunday($user);

        return View::render('attendance/index', [
            'title' => 'Attendance',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'members' => $isSuperAdmin ? [] : $this->attendance->searchableMembers($user, $query),
            'summary' => $this->attendance->sundaySummary($user),
            'tentOverview' => $isSuperAdmin ? $this->attendance->sundayTentOverview() : [],
            'checkedInRecords' => $checkedInRecords,
            'checkedInByTent' => $this->attendance->groupCheckedInByTent($checkedInRecords),
            'assignedTent' => ($user['role'] ?? null) === 'Tent Admin'
                ? $this->tents->find((int) ($user['tent_id'] ?? 0))
                : null,
            'query' => $query,
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function checkIn(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();
        $user = AuthService::user() ?? [];
        $memberId = (int) ($_POST['member_id'] ?? 0);

        try {
            $this->attendance->checkIn($user, $memberId);
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'attendance.checked_in',
                'member',
                $memberId,
                [
                    'source' => 'web',
                    'role' => $user['role'] ?? null,
                ]
            );
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

    public function syncOffline(): string
    {
        AuthMiddleware::requireAuth();

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $token = is_array($payload) ? ($payload['csrf_token'] ?? null) : null;

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'Security token expired. Please refresh and try again.',
                'results' => [],
            ], 419);
        }

        $records = is_array($payload['records'] ?? null) ? $payload['records'] : [];
        $user = AuthService::user() ?? [];
        $results = $this->attendance->syncQueuedCheckIns($user, $records);
        $successCount = count(array_filter($results, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'success'));
        $duplicateCount = count(array_filter($results, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'duplicate'));
        $errorCount = count(array_filter($results, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'error'));
        $this->logs->log(
            (int) ($user['id'] ?? 0),
            'attendance.offline_sync',
            'attendance_queue',
            count($records),
            [
                'queued_records' => count($records),
                'synced' => $successCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount,
            ]
        );

        return $this->jsonResponse([
            'ok' => true,
            'message' => 'Offline attendance sync completed.',
            'results' => $results,
        ]);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    private function availableTents(array $user): array
    {
        return $this->tents->availableForUser($user);
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

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
