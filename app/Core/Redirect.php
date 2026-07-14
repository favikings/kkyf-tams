<?php

namespace App\Core;

final class Redirect
{
    public static function to(string $path): void
    {
        $basePath = rtrim(Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
        $target = $basePath . '/' . ltrim($path, '/');

        header('Location: ' . $target);
        exit;
    }
}
