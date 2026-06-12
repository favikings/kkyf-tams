<?php

namespace App\Core;

final class Router
{
    /** @var array<string, callable|array{0: class-string, 1: string}> */
    private array $getRoutes = [];

    /** @var array<string, callable|array{0: class-string, 1: string}> */
    private array $postRoutes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->getRoutes[$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->postRoutes[$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $uri, string $basePath = ''): void
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $basePath = $this->normalizePath($basePath);

        if ($basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
            $path = $this->normalizePath($path);
        }

        $routes = match ($method) {
            'GET' => $this->getRoutes,
            'POST' => $this->postRoutes,
            default => [],
        };

        if (!isset($routes[$path])) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $handler = $routes[$path];

        if (is_array($handler)) {
            [$className, $methodName] = $handler;
            echo (new $className())->{$methodName}();
            return;
        }

        echo $handler();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
