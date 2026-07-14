<?php

namespace App\Controllers;

use App\Core\Redirect;
use App\Services\AuthService;

final class HomeController
{
    public function index(): string
    {
        if (AuthService::check()) {
            Redirect::to('/dashboard');
        }

        Redirect::to('/login');
    }
}
