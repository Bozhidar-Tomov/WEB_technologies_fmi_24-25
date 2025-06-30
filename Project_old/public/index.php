<?php
session_start();

require_once __DIR__ . '/../routes/Router.php';

$router = new Router();
require_once __DIR__ . '/../routes/routes.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
