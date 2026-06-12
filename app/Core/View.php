<?php

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
