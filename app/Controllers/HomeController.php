<?php

namespace App\Controllers;

final class HomeController
{
    public function index(): string
    {
        $title = 'KKYF Membership Portal v2';
        $statusItems = [
            'Environment configuration',
            'Database connection service',
            'Error logging',
            'Basic routing',
            'Phase 0 migrations',
        ];

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/home.php';

        return (string) ob_get_clean();
    }
}
