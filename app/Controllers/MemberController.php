<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\MemberService;
use App\Services\NotificationService;
use App\Services\TentService;
use Throwable;

final class MemberController
{
    private MemberService $members;
    private TentService $tents;
    private ActivityLogService $logs;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->members = new MemberService();
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
        $status = trim((string) ($_GET['status'] ?? ''));

        return View::render('members/index', [
            'title' => 'Members',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'members' => $this->members->search($user, $query, $tentId > 0 ? $tentId : null, $status !== '' ? $status : null),
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
        $member = $this->members->findScoped($user, (int) ($_GET['id'] ?? 0));

        if ($member === null) {
            http_response_code(404);
            return View::render('errors/not-found', ['title' => 'Member not found']);
        }

        return View::render('members/show', [
            'title' => $member['full_name'],
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'member' => $member,
            'tents' => $this->availableTents($user),
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function photo(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $member = $this->members->findScoped($user, (int) ($_GET['id'] ?? 0));

        if ($member === null || empty($member['profile_photo'])) {
            http_response_code(404);
            return '';
        }

        $relativePath = str_replace(['..', '\\'], ['', '/'], $member['profile_photo']);
        $absolutePath = dirname(__DIR__, 2) . '/' . ltrim($relativePath, '/');
        $storageRoot = realpath(dirname(__DIR__, 2) . '/storage/uploads/member-photos');
        $resolvedPath = realpath($absolutePath);

        if ($storageRoot === false || $resolvedPath === false || !str_starts_with($resolvedPath, $storageRoot)) {
            http_response_code(404);
            return '';
        }

        $mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(404);
            return '';
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($resolvedPath));
        readfile($resolvedPath);

        return '';
    }

    public function create(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        $user = AuthService::user() ?? [];
        $data = $this->validatedInput($user);
        $data['profile_photo'] = $this->storeProfilePhoto();

        if ($data['full_name'] === '' || (int) $data['tent_id'] <= 0) {
            $_SESSION['flash_error'] = 'Full name and tent are required.';
            Redirect::to('/members');
        }

        try {
            $memberId = $this->members->create($data);
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'member.created',
                'member',
                $memberId,
                [
                    'full_name' => $data['full_name'],
                    'tent_id' => $data['tent_id'],
                    'occupation' => $data['occupation'],
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'member.created',
                'member',
                'New member added',
                trim((string) ($data['full_name'] ?? 'A member')) . ' was added to the member directory.',
                '/members/show?id=' . $memberId,
                'member',
                $memberId,
                [
                    'full_name' => $data['full_name'],
                    'tent_id' => $data['tent_id'],
                ],
                'member-created:' . $memberId
            );
            $_SESSION['flash_success'] = 'Member created.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to create member. Check for duplicate phone number.';
        }

        Redirect::to('/members');
    }

    public function update(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        $user = AuthService::user() ?? [];
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $this->members->findScoped($user, $id);

        if ($existing === null) {
            $_SESSION['flash_error'] = 'Member not found or outside your tent.';
            Redirect::to('/members');
        }

        $data = $this->validatedInput($user);
        $data['profile_photo'] = $this->storeProfilePhoto() ?: trim($_POST['existing_profile_photo'] ?? '');

        if ($data['full_name'] === '' || (int) $data['tent_id'] <= 0) {
            $_SESSION['flash_error'] = 'Full name and tent are required.';
            Redirect::to('/members/show?id=' . $id);
        }

        try {
            $this->members->update($id, $data);
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'member.updated',
                'member',
                $id,
                [
                    'full_name_before' => $existing['full_name'] ?? null,
                    'full_name_after' => $data['full_name'],
                    'tent_id_before' => $existing['tent_id'] ?? null,
                    'tent_id_after' => $data['tent_id'],
                    'status_after' => $data['active_status'],
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'member.updated',
                'member',
                'Member record updated',
                trim((string) ($data['full_name'] ?? 'A member')) . '\'s profile was updated.',
                '/members/show?id=' . $id,
                'member',
                $id,
                [
                    'full_name' => $data['full_name'],
                    'tent_id' => $data['tent_id'],
                    'status' => $data['active_status'],
                ],
                'member-updated:' . $id . ':' . date('Y-m-d-H-i-s')
            );
            $notesBefore = trim((string) ($existing['notes'] ?? ''));
            $notesAfter = trim((string) ($data['notes'] ?? ''));
            if ($notesAfter !== '' && $notesAfter !== $notesBefore) {
                $this->notifications->notifyAllUsers(
                    $user,
                    'member.notes_updated',
                    'follow_up',
                    'Member notes updated',
                    trim((string) ($data['full_name'] ?? 'A member')) . ' has updated follow-up notes in the member profile.',
                    '/members/show?id=' . $id,
                    'member',
                    $id,
                    [
                        'full_name' => $data['full_name'],
                    ],
                    'member-notes:' . $id . ':' . md5($notesAfter)
                );
            }
            $_SESSION['flash_success'] = 'Member updated.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to update member. Check for duplicate phone number.';
        }

        Redirect::to('/members/show?id=' . $id);
    }

    public function deactivate(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        $user = AuthService::user() ?? [];
        $id = (int) ($_POST['id'] ?? 0);
        $member = $this->members->findScoped($user, $id);

        if ($member !== null) {
            $this->members->deactivate($id);
            $this->logs->log(
                (int) ($user['id'] ?? 0),
                'member.deactivated',
                'member',
                $id,
                [
                    'full_name' => $member['full_name'] ?? null,
                    'tent_id' => $member['tent_id'] ?? null,
                ]
            );
            $this->notifications->notifyAllUsers(
                $user,
                'member.deactivated',
                'member',
                'Member deactivated',
                trim((string) ($member['full_name'] ?? 'A member')) . ' was deactivated.',
                '/members',
                'member',
                $id,
                [
                    'full_name' => $member['full_name'] ?? null,
                ],
                'member-deactivated:' . $id
            );
            $_SESSION['flash_success'] = 'Member deactivated.';
        }

        Redirect::to('/members');
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
    private function validatedInput(array $user): array
    {
        $tentId = (int) ($_POST['tent_id'] ?? 0);
        if (($user['role'] ?? null) === 'Tent Admin') {
            $tentId = (int) ($user['tent_id'] ?? 0);
        }

        $occupation = $_POST['occupation'] ?? 'Student';
        if (!in_array($occupation, ['Student', 'Worker', 'Alumni'], true)) {
            $occupation = 'Student';
        }

        $activeStatus = $_POST['active_status'] ?? 'active';
        if (!in_array($activeStatus, ['active', 'inactive'], true)) {
            $activeStatus = 'active';
        }

        return [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'date_of_birth' => $this->monthDayValue($_POST['birth_month'] ?? '', $_POST['birth_day'] ?? ''),
            'occupation' => $occupation,
            'school_name' => trim($_POST['school_name'] ?? ''),
            'tent_id' => $tentId,
            'join_date' => trim($_POST['join_date'] ?? ''),
            'profile_photo' => '',
            'notes' => trim($_POST['notes'] ?? ''),
            'active_status' => $activeStatus,
        ];
    }

    private function storeProfilePhoto(): string
    {
        if (!isset($_FILES['profile_photo']) || !is_array($_FILES['profile_photo'])) {
            return '';
        }

        $file = $_FILES['profile_photo'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Profile photo upload failed.';
            Redirect::to('/members');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            $_SESSION['flash_error'] = 'Profile photo must be 2MB or smaller.';
            Redirect::to('/members');
        }

        $tmpName = $file['tmp_name'] ?? '';
        $mimeType = is_uploaded_file($tmpName) ? mime_content_type($tmpName) : false;
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
            $_SESSION['flash_error'] = 'Profile photo must be a JPG, PNG, or WebP image.';
            Redirect::to('/members');
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/storage/uploads/member-photos';
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
        $destination = $uploadDirectory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            $_SESSION['flash_error'] = 'Unable to save profile photo.';
            Redirect::to('/members');
        }

        return 'storage/uploads/member-photos/' . $filename;
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

    private function verifyCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to('/members');
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
