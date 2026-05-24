<?php

declare(strict_types=1);

namespace App\Services;

class Router
{
    private array $routes = [];

    public function get(
        string $uri,
        array|callable $action,
        array $middleware = []
    ): void {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(
        string $uri,
        array|callable $action,
        array $middleware = []
    ): void {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    private function addRoute(
        string $method,
        string $uri,
        array|callable $action,
        array $middleware
    ): void {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(
        string $requestUri,
        string $requestMethod
    ): void {
        $path = is_string($requestUri) && !str_contains($requestUri, '://')
            ? (rtrim($requestUri, '/') ?: '/')
            : request_path($requestUri);

        foreach ($this->routes as $route) {
            $params = $this->match($route['uri'], $path);

            if ($params === null || $route['method'] !== $requestMethod) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $middleware::handle();
            }

            $action = $route['action'];

            if (is_callable($action)) {
                call_user_func($action, $params);
                return;
            }

            [$controller, $method] = $action;
            $instance = new $controller();
            $instance->$method(...array_values($params));

            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function match(string $routeUri, string $path): ?array
    {
        $routePattern = preg_replace(
            '/\{([a-zA-Z_]+)\}/',
            '([^/]+)',
            $routeUri
        );

        $routePattern = '#^' . $routePattern . '$#';

        if (!preg_match($routePattern, $path, $matches)) {
            return null;
        }

        array_shift($matches);

        preg_match_all('/\{([a-zA-Z_]+)\}/', $routeUri, $keys);

        $params = [];

        foreach ($keys[1] as $index => $key) {
            $params[$key] = $matches[$index] ?? null;
        }

        return $params;
    }
}
