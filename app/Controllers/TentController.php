<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Services\AuthService;
use App\Services\TentService;
use Throwable;

final class TentController
{
    private TentService $tents;

    public function __construct()
    {
        $this->tents = new TentService();
    }

    public function index(): string
    {
        RoleMiddleware::requireRole('Super Admin');
        $tents = $this->tents->all();

        return View::render('tents/index', [
            'title' => 'Tents',
            'user' => AuthService::user(),
            'csrfToken' => Csrf::token(),
            'tents' => $tents,
            'overview' => $this->tents->overview($tents),
            'tentAdmins' => $this->tents->tentAdmins(),
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function create(): string
    {
        RoleMiddleware::requireRole('Super Admin');
        $this->verifyCsrf();

        $data = $this->validatedInput();
        $data['banner'] = $this->storeBannerUpload();
        if ($data['name'] === '') {
            $_SESSION['flash_error'] = 'Tent name is required.';
            Redirect::to('/tents');
        }

        try {
            $this->tents->create($data);
            $_SESSION['flash_success'] = 'Tent created.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to create tent. Check for duplicate names.';
        }

        Redirect::to('/tents');
    }

    public function update(): string
    {
        RoleMiddleware::requireRole('Super Admin');
        $this->verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->validatedInput();
        $data['banner'] = $this->storeBannerUpload() ?: trim($_POST['existing_banner'] ?? '');

        if ($id <= 0 || $data['name'] === '') {
            $_SESSION['flash_error'] = 'Tent name is required.';
            Redirect::to('/tents');
        }

        try {
            $this->tents->update($id, $data);
            $_SESSION['flash_success'] = 'Tent updated.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = 'Unable to update tent. Check for duplicate names.';
        }

        Redirect::to('/tents');
    }

    public function deactivate(): string
    {
        RoleMiddleware::requireRole('Super Admin');
        $this->verifyCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->tents->deactivate($id);
            $_SESSION['flash_success'] = 'Tent deactivated.';
        }

        Redirect::to('/tents');
    }

    public function assignAdmin(): string
    {
        RoleMiddleware::requireRole('Super Admin');
        $this->verifyCsrf();

        $tentId = (int) ($_POST['tent_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($tentId <= 0 || $userId <= 0) {
            $_SESSION['flash_error'] = 'Choose a tent and an active Tent Admin.';
            Redirect::to('/tents');
        }

        $this->tents->assignAdmin($tentId, $userId);
        $_SESSION['flash_success'] = 'Tent Admin assigned.';

        Redirect::to('/tents');
    }

    public function mine(): string
    {
        RoleMiddleware::requireRole('Tent Admin');

        $user = AuthService::user();

        return View::render('tents/mine', [
            'title' => 'My Tent',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'tent' => $this->tents->findAssignedToUser((int) ($user['id'] ?? 0)),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function validatedInput(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'banner' => '',
            'color' => $this->validColor(trim($_POST['color'] ?? '')) ? trim($_POST['color']) : '#00bd06',
            'leader_name' => trim($_POST['leader_name'] ?? ''),
            'leader_phone' => trim($_POST['leader_phone'] ?? ''),
            'whatsapp_link' => trim($_POST['whatsapp_link'] ?? ''),
            'status' => in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true)
                ? $_POST['status']
                : 'active',
        ];
    }

    private function storeBannerUpload(): string
    {
        if (!isset($_FILES['banner']) || !is_array($_FILES['banner'])) {
            return '';
        }

        $file = $_FILES['banner'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Banner upload failed. Please try another image.';
            Redirect::to('/tents');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            $_SESSION['flash_error'] = 'Banner image must be 2MB or smaller.';
            Redirect::to('/tents');
        }

        $tmpName = $file['tmp_name'] ?? '';
        $mimeType = is_uploaded_file($tmpName) ? mime_content_type($tmpName) : false;
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
            $_SESSION['flash_error'] = 'Banner must be a JPG, PNG, WebP, or GIF image.';
            Redirect::to('/tents');
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/storage/uploads/tent-banners';
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
        $destination = $uploadDirectory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            $_SESSION['flash_error'] = 'Unable to save banner image.';
            Redirect::to('/tents');
        }

        return 'storage/uploads/tent-banners/' . $filename;
    }

    private function validColor(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    private function verifyCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to('/tents');
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
