<?php

class Router
{
    private $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get($uri, $action)
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post($uri, $action)
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function dispatch($requestUri, $requestMethod)
    {
        $uri = $this->normalize(parse_url($requestUri, PHP_URL_PATH));
        $method = strtoupper($requestMethod);
        $action = $this->routes[$method][$uri] ?? null;
        if (!$action) {
            http_response_code(404);
            require_once __DIR__ . '/../app/Views/404.php';
            exit;
        }
        if (is_callable($action)) {
            return $action();
        }
        if (is_string($action)) {
            [$controller, $method] = explode('@', $action);
            $controllerClass = $controller;
            if (!class_exists($controllerClass)) {
                require_once __DIR__ . "/../app/Controllers/{$controller}.php";
            }
            $instance = new $controllerClass();
            return $instance->$method();
        }
    }

    private function normalize($uri)
    {
        return rtrim($uri, '/') ?: '/';
    }
}
