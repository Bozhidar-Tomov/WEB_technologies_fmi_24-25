<?php
session_start();

// Load application configuration
$config = require_once __DIR__ . '/../config/app.php';

// Fix for PHP built-in server to handle static files
if (php_sapi_name() == 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    
    // Serve static files directly
    if (is_file($file)) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                // Add cache control headers to prevent caching issues with JavaScript
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
                break;
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
                header('Content-Type: image/' . $extension);
                break;
            case 'mp3':
                header('Content-Type: audio/mpeg');
                break;
            case 'wav':
                header('Content-Type: audio/wav');
                break;
        }
        readfile($file);
        return true;
    }
}

// Configure session with app settings
if (isset($config['session'])) {
    if (isset($config['session']['name'])) {
        session_name($config['session']['name']);
    }
    if (isset($config['session']['lifetime'])) {
        ini_set('session.gc_maxlifetime', $config['session']['lifetime']);
    }
}

require_once __DIR__ . '/../routes/Router.php';

$router = new Router();
require_once __DIR__ . '/../routes/routes.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
