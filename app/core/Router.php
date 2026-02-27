<?php
// app/core/Router.php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /** Register a GET route */
    public function get(string $path, string $action, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $action, $middleware);
    }

    /** Register a POST route */
    public function post(string $path, string $action, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $action, $middleware);
    }

    private function addRoute(string $method, string $path, string $action, array $middleware): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => '#^' . $pattern . '$#',
            'action'     => $action,
            'middleware' => $middleware,
        ];
    }

    /** Dispatch the current request */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path if running in subdirectory
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named params
                $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    $this->runMiddleware($mw);
                }

                // Dispatch controller
                [$controllerName, $actionName] = explode('@', $route['action']);
                $controllerClass = "App\\Controllers\\{$controllerName}";

                if (!class_exists($controllerClass)) {
                    $this->abort(500, "Controller {$controllerClass} not found");
                    return;
                }

                $controller = new $controllerClass();
                if (!method_exists($controller, $actionName)) {
                    $this->abort(500, "Action {$actionName} not found");
                    return;
                }

                call_user_func_array([$controller, $actionName], $params);
                return;
            }
        }

        $this->abort(404, 'Página no encontrada');
    }

    private function runMiddleware(string $name): void
    {
        match ($name) {
            'auth'       => Auth::requireLogin(),
            'guest'      => Auth::requireGuest(),
            'superadmin' => Auth::requireRole('superadmin'),
            'admin'      => Auth::requireRole(['superadmin', 'admin']),
            'asesor'     => Auth::requireRole(['superadmin', 'admin', 'asesor']),
            default      => null,
        };
    }

    public function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $view = new View();
        $view->render('errors/' . $code, ['message' => $message], null);
        exit;
    }
}