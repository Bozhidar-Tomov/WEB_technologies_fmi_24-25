<?php
session_start();

// Fix for PHP built-in server to handle static files
if (php_sapi_name() == 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    $pathInfo = pathinfo($file);
    
    // Special handling for setup pages
    if ($url['path'] === '/setup.php' || $url['path'] === '/setup_config.php') {
        require_once __DIR__ . $url['path'];
        return true;
    }
    
    // Handle config files specially when directly accessed
    if (strpos($url['path'], '/config/') === 0) {
        // Try to serve from project root
        $configFile = dirname(__DIR__) . $url['path'];
        if (file_exists($configFile) && is_file($configFile)) {
            // For PHP files, include them
            if (pathinfo($configFile, PATHINFO_EXTENSION) === 'php') {
                include $configFile;
                return true;
            }
            
            // For other files, serve them directly
            readfile($configFile);
            return true;
        }
    }
    
    // Serve static files directly
    if (is_file($file)) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
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

require_once __DIR__ . '/../routes/Router.php';

$router = new Router();
require_once __DIR__ . '/../routes/routes.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
