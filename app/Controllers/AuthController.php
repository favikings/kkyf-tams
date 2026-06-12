<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Redirect;
use App\Core\View;
use App\Services\AuthService;

final class AuthController
{
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

        Redirect::to('/dashboard');
    }

    public function logout(): string
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Redirect::to('/dashboard');
        }

        AuthService::logout();
        Redirect::to('/login');
    }
}
