<?php

class Router
{
    private $routes = [
        'GET' => [],
        'POST' => [],
    ];
    
    private $basePath = '';
    private $config = [];

    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../config/app.php';
        $this->basePath = $this->config['app']['base_path'] ?: $this->detectBasePath();
    }

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
        // Remove the base path from the request URI
        $uri = str_replace($this->basePath, '', $this->normalize(parse_url($requestUri, PHP_URL_PATH)));
        
        // If URI is empty after removing base path, treat it as root
        if (empty($uri)) {
            $uri = '/';
        }
        
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
    
    public function getBasePath()
    {
        return $this->basePath;
    }
    
    public function getConfig()
    {
        return $this->config;
    }
    
    private function detectBasePath()
    {
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = $scriptName === '/' ? '' : $scriptName;
        return $basePath;
    }
}
