<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\NotificationService;

final class NotificationController
{
    private NotificationService $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationService();
    }

    public function index(): string
    {
        AuthMiddleware::requireAuth();

        $user = AuthService::user() ?? [];
        $limit = (int) ($_GET['limit'] ?? 12);

        return $this->jsonResponse($this->notifications->feedForUser($user, $limit));
    }

    public function markRead(): string
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            return $this->jsonResponse(['ok' => false, 'message' => 'Security token expired.'], 419);
        }

        $user = AuthService::user() ?? [];
        $notificationId = (int) ($_POST['id'] ?? 0);
        $this->notifications->markRead($user, $notificationId);

        return $this->jsonResponse(['ok' => true] + $this->notifications->feedForUser($user, 12));
    }

    public function markAllRead(): string
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            return $this->jsonResponse(['ok' => false, 'message' => 'Security token expired.'], 419);
        }

        $user = AuthService::user() ?? [];
        $this->notifications->markAllRead($user);

        return $this->jsonResponse(['ok' => true] + $this->notifications->feedForUser($user, 12));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
