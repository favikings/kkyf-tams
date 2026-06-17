<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SmsService;
use Throwable;

final class SmsController
{
    private SmsService $sms;

    public function __construct()
    {
        $this->sms = new SmsService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];

        return View::render('sms/index', [
            'title' => 'SMS Communication',
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'members' => $this->sms->membersForUser($user),
            'tents' => $this->sms->tentsForUser($user),
            'logs' => $this->sms->recentLogsForUser($user),
            'selectedScope' => $this->selectedScope(),
            'selectedMemberId' => (int) ($_GET['member_id'] ?? 0),
            'selectedTentId' => (int) ($_GET['tent_id'] ?? 0),
            'prefilledMessage' => trim((string) ($_GET['message'] ?? '')),
            'error' => $this->consumeFlash('flash_error'),
            'success' => $this->consumeFlash('flash_success'),
        ]);
    }

    public function send(): string
    {
        AuthMiddleware::requireAuth();
        $this->verifyCsrf();

        $user = AuthService::user() ?? [];
        $scope = trim($_POST['scope'] ?? 'member');
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $tentId = (int) ($_POST['tent_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        try {
            $this->sms->send($user, $scope, $memberId > 0 ? $memberId : null, $tentId > 0 ? $tentId : null, $message);
            $_SESSION['flash_success'] = 'SMS request processed and logged.';
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
        }

        Redirect::to('/sms');
    }

    private function verifyCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to('/sms');
        }
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }

    private function selectedScope(): string
    {
        $scope = trim($_GET['scope'] ?? 'member');

        return in_array($scope, ['member', 'tent', 'bulk'], true) ? $scope : 'member';
    }
}
