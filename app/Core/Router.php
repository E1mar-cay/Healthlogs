<?php

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $path, string $method): void
    {
        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        [$class, $action] = $handler;
        if (!class_exists($class)) {
            http_response_code(500);
            echo 'Controller not found.';
            return;
        }

        $controller = new $class();
        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo 'Action not found.';
            return;
        }

        $controller->$action();
    }
}
