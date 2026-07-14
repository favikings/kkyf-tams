<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Services\ActivityLogService;
use App\Services\AuthService;

final class AuthController
{
    private ActivityLogService $logs;

    public function __construct()
    {
        $this->logs = new ActivityLogService();
    }

    public function showLogin(): string
    {
        if (AuthService::check()) {
            Redirect::to('/dashboard');
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return View::render('auth/login', [
            'title' => 'Sign in',
            'error' => $error,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function login(): string
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            Redirect::to('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Email and password are required.';
            Redirect::to('/login');
        }

        unset($_SESSION['flash_error']);

        if (!(new AuthService())->attempt($email, $password)) {
            $_SESSION['flash_error'] = 'Invalid credentials or inactive account.';
            Redirect::to('/login');
        }

        $user = AuthService::user() ?? [];
        $this->logs->log(
            isset($user['id']) ? (int) $user['id'] : null,
            'auth.login',
            'user',
            $user['id'] ?? null,
            [
                'email' => $email,
                'role' => $user['role'] ?? null,
            ]
        );

        Redirect::to('/dashboard');
    }

    public function logout(): string
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Redirect::to('/dashboard');
        }

        $user = AuthService::user() ?? [];
        $this->logs->log(
            isset($user['id']) ? (int) $user['id'] : null,
            'auth.logout',
            'user',
            $user['id'] ?? null,
            [
                'email' => $user['email'] ?? null,
                'role' => $user['role'] ?? null,
            ]
        );

        AuthService::logout();
        Redirect::to('/login');
    }
}
