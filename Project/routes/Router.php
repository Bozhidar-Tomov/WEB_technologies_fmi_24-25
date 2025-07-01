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
        $uri = $this->normalize($requestUri);
        $method = strtoupper($requestMethod);
        
        // Debug information if debug parameter is set
        if (isset($_GET['debug'])) {
            echo "<pre>";
            echo "Router trying to match: {$method} {$uri}\n";
            echo "Available routes:\n";
            foreach ($this->routes[$method] as $route => $handler) {
                echo "  {$method} {$route} => " . (is_string($handler) ? $handler : 'closure') . "\n";
            }
            echo "</pre>";
        }
        
        $action = $this->routes[$method][$uri] ?? null;
        
        if (!$action) {
            // Debug before showing 404
            if (isset($_GET['debug'])) {
                echo "<pre>";
                echo "No route found for {$method} {$uri}\n";
                echo "Showing 404 page\n";
                echo "</pre>";
            }
            
            http_response_code(404);
            require_once BASE_PATH . '/app/Views/404.php';
            exit;
        }
        
        if (is_callable($action)) {
            return $action();
        }
        
        if (is_string($action)) {
            [$controller, $method] = explode('@', $action);
            $controllerClass = $controller;
            
            if (!class_exists($controllerClass)) {
                require_once BASE_PATH . "/app/Controllers/{$controller}.php";
            }
            
            // Debug controller loading
            if (isset($_GET['debug'])) {
                echo "<pre>";
                echo "Loading controller: {$controllerClass}::{$method}\n";
                echo "</pre>";
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
