<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\FirstTimerService;
use App\Services\NotificationService;
use App\Services\TentService;
use App\Services\ActivityLogService;
use Throwable;

final class FirstTimerController
{
    private FirstTimerService $firstTimers;
    private TentService $tents;
    private ActivityLogService $logs;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->firstTimers = new FirstTimerService();
        $this->tents = new TentService();
        $this->logs = new ActivityLogService();
        $this->notifications = new NotificationService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $query = trim($_GET['q'] ?? '');
        $tentId = (int) ($_GET['tent_id'] ?? 0);
        $status = trim($_GET['status'] ?? '');

        return View::render('first-timers/index', [
            'title' => 'First Timers',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'firstTimers' => $this->firstTimers->search($user, $query, $tentId > 0 ? $tentId : null, $status),
            'tents' => $this->availableTents($user),
            'query' => $query,
            'selectedTentId' => $tentId,
            'selectedStatus' => $status,
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function show(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $record = $this->firstTimers->findScoped($user, (int) ($_GET['id'] ?? 0));

        if ($record === null) {
            http_response_code(404);
            return View::render('errors/not-found', ['title' => 'First-timer not found']);
        }

        return View::render('first-timers/show', [
            'title' => $record['full_name'],
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'record' => $record,
            'tents' => $this->availableTents($user),
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function create(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf('/first-timers');

        $user = AuthService::user() ?? [];
        $data = $this->validatedInput($user);

        if ($data['full_name'] === '' || (int) $data['tent_id'] <= 0 || $data['first_visit_date'] === '') {
            $_SESSION['flash_error'] = 'Full name, tent, and first visit date are required.';
            Redirect::to('/first-timers');
        }

        try {
            $this->firstTimers->create($data, (int) ($user['id'] ?? 0));
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'first_timer.created',
                'first_timer',
                $data['phone'] !== '' ? $data['phone'] : $data['full_name'],
                [
                    'full_name' => $data['full_name'],
                    'tent_id' => $data['tent_id'],
                    'status' => $data['status'],
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'first_timer.created',
                'first_timer',
                'New first-timer added',
                trim((string) ($data['full_name'] ?? 'A first-timer')) . ' was added to the follow-up list.',
                '/first-timers',
                'first_timer',
                $data['phone'] !== '' ? $data['phone'] : $data['full_name'],
                [
                    'full_name' => $data['full_name'],
                    'tent_id' => $data['tent_id'],
                ],
                'first-timer-created:' . md5((string) $data['full_name'] . '|' . (string) $data['phone'] . '|' . (string) $data['first_visit_date'])
            );
            $_SESSION['flash_success'] = 'First-timer record created.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to create first-timer record right now.';
        }

        Redirect::to('/first-timers');
    }

    public function update(): string
    {
        AuthMiddleware::requireAuth();
        $id = (int) ($_POST['id'] ?? 0);
        $redirect = '/first-timers/show?id=' . $id;
        $this->verifyCsrf($redirect);

        $user = AuthService::user() ?? [];
        $existing = $this->firstTimers->findScoped($user, $id);

        if ($existing === null) {
            $_SESSION['flash_error'] = 'First-timer not found or outside your tent.';
            Redirect::to('/first-timers');
        }

        $data = $this->validatedInput($user, true);

        if ($data['full_name'] === '' || (int) $data['tent_id'] <= 0 || $data['first_visit_date'] === '') {
            $_SESSION['flash_error'] = 'Full name, tent, and first visit date are required.';
            Redirect::to($redirect);
        }

        if (($existing['converted_member_id'] ?? null) !== null || ($existing['status'] ?? '') === 'Converted') {
            $data['status'] = 'Converted';
        }

        try {
            $this->firstTimers->update($id, $data);
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'first_timer.updated',
                'first_timer',
                $id,
                [
                    'full_name_before' => $existing['full_name'] ?? null,
                    'full_name_after' => $data['full_name'],
                    'status_after' => $data['status'],
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'first_timer.updated',
                'first_timer',
                'First-timer updated',
                trim((string) ($data['full_name'] ?? 'A first-timer')) . '\'s follow-up record was updated.',
                '/first-timers/show?id=' . $id,
                'first_timer',
                $id,
                [
                    'full_name' => $data['full_name'],
                    'status' => $data['status'],
                ],
                'first-timer-updated:' . $id . ':' . date('Y-m-d-H-i-s')
            );
            $notesBefore = trim((string) ($existing['followup_notes'] ?? ''));
            $notesAfter = trim((string) ($data['followup_notes'] ?? ''));
            if ($notesAfter !== '' && $notesAfter !== $notesBefore) {
                $this->notifications->notifyAllUsers(
                    $user,
                    'first_timer.followup_updated',
                    'follow_up',
                    'Follow-up note added',
                    trim((string) ($data['full_name'] ?? 'A first-timer')) . ' has a new follow-up note.',
                    '/first-timers/show?id=' . $id,
                    'first_timer',
                    $id,
                    [
                        'full_name' => $data['full_name'],
                    ],
                    'first-timer-note:' . $id . ':' . md5($notesAfter)
                );
            }
            $_SESSION['flash_success'] = 'First-timer record updated.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to update first-timer record right now.';
        }

        Redirect::to($redirect);
    }

    public function convert(): string
    {
        AuthMiddleware::requireAuth();

        $id = (int) ($_POST['id'] ?? 0);
        $redirect = '/first-timers/show?id=' . $id;
        $this->verifyCsrf($redirect);

        $user = AuthService::user() ?? [];
        $existing = $this->firstTimers->findScoped($user, $id);

        if ($existing === null) {
            $_SESSION['flash_error'] = 'First-timer not found or outside your tent.';
            Redirect::to('/first-timers');
        }

        try {
            $memberId = $this->firstTimers->convertToMember($id, $this->validatedConversionInput());
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'first_timer.converted',
                'first_timer',
                $id,
                [
                    'full_name' => $existing['full_name'] ?? null,
                    'member_id' => $memberId,
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'first_timer.converted',
                'first_timer',
                'First-timer converted',
                trim((string) ($existing['full_name'] ?? 'A first-timer')) . ' was converted into a full member profile.',
                '/members/show?id=' . $memberId,
                'member',
                $memberId,
                [
                    'member_id' => $memberId,
                    'full_name' => $existing['full_name'] ?? null,
                ],
                'first-timer-converted:' . $id
            );
            $_SESSION['flash_success'] = 'First-timer converted into a member profile.';
            Redirect::to('/members/show?id=' . $memberId);
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Conversion failed. Check for duplicate member data and required fields.';
        }

        Redirect::to($redirect);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    private function availableTents(array $user): array
    {
        return $this->tents->availableForUser($user);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, string|int|null>
     */
    private function validatedInput(array $user, bool $allowStatusChanges = false): array
    {
        $tentId = (int) ($_POST['tent_id'] ?? 0);
        if (($user['role'] ?? null) === 'Tent Admin') {
            $tentId = (int) ($user['tent_id'] ?? 0);
        }

        $status = trim($_POST['status'] ?? 'Pending');
        $allowedStatuses = $allowStatusChanges
            ? ['Pending', 'Called', 'Not Returning', 'Converted']
            : ['Pending', 'Called', 'Not Returning'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Pending';
        }

        return [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'tent_id' => $tentId,
            'first_visit_date' => $this->normalizedDate($_POST['first_visit_date'] ?? ''),
            'status' => $status,
            'followup_notes' => trim($_POST['followup_notes'] ?? ''),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function validatedConversionInput(): array
    {
        $occupation = trim($_POST['occupation'] ?? 'Student');
        if (!in_array($occupation, ['Student', 'Worker', 'Alumni'], true)) {
            $occupation = 'Student';
        }

        return [
            'occupation' => $occupation,
            'school_name' => trim($_POST['school_name'] ?? '') ?: null,
            'join_date' => $this->normalizedDate($_POST['join_date'] ?? '') ?: null,
            'date_of_birth' => $this->monthDayValue($_POST['birth_month'] ?? '', $_POST['birth_day'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];
    }

    private function normalizedDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : '';
    }

    private function monthDayValue(string $month, string $day): string
    {
        $monthNumber = (int) $month;
        $dayNumber = (int) $day;

        if ($monthNumber < 1 || $monthNumber > 12 || $dayNumber < 1 || $dayNumber > 31) {
            return '';
        }

        if (!checkdate($monthNumber, $dayNumber, 2000)) {
            return '';
        }

        return sprintf('%02d-%02d', $monthNumber, $dayNumber);
    }

    private function verifyCsrf(string $redirect): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to($redirect);
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
