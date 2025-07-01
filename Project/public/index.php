<?php
session_start();

require_once __DIR__ . '/../routes/Router.php';

// Detect the base path
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptName !== '/' ? $scriptName : '';

// Create router with detected base path
$router = new Router($basePath);
require_once __DIR__ . '/../routes/routes.php';

// Make the base path available for the application
define('BASE_PATH', $basePath);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
