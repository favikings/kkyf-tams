<?php

namespace App\Core;

final class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function __construct(string $configPath)
    {
        foreach (glob(rtrim($configPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
