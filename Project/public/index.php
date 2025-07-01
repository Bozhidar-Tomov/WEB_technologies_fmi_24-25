<?php
session_start();

// Define base path constant for easier file referencing
define('BASE_PATH', dirname(__DIR__));

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Simple and direct approach to routing
// Extract the path from REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'];

// Debug information
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
    echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
    echo "</pre>";
}

// Remove query string if present
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

// Simplify path extraction - just use the parsed path directly
$path = parse_url($requestUri, PHP_URL_PATH);

// In built-in server, remove any script path part
if (php_sapi_name() == 'cli-server') {
    $path = '/' . ltrim($path, '/');
} 
// When running in Apache/Nginx without proper configuration
else {
    // Get script name without filename (e.g., "/Project/public")
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Only trim the script directory if it's at the beginning of the path
    if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
        $path = substr($path, strlen($scriptDir));
    }
    
    // Ensure path starts with a slash
    $path = '/' . ltrim($path, '/');
}

// This is now the path that our router should use
$_SERVER['PATH_INFO'] = $path;

// Debug information
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Final PATH_INFO: " . $_SERVER['PATH_INFO'] . "\n";
    echo "</pre>";
}

// Load router
require_once BASE_PATH . '/routes/Router.php';

$router = new Router();
require_once BASE_PATH . '/routes/routes.php';

// Use the extracted path info for routing
$router->dispatch($_SERVER['PATH_INFO'], $_SERVER['REQUEST_METHOD']);
