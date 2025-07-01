<?php

class BaseController
{
    protected $basePath = '';
    protected $config = [];
    
    public function __construct()
    {
        global $router;
        $this->config = require_once __DIR__ . '/../../config/app.php';
        $this->basePath = isset($router) ? $router->getBasePath() : $this->config['app']['base_path'];
    }
    
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        
        // Add base path and config to the data array for use in views
        $data['basePath'] = $this->basePath;
        $data['config'] = $this->config;

        ob_start();
        if (file_exists($viewFile)) {
            $viewName = str_contains($view, '/') ? substr($view, 0, strpos($view, '/')) : $view;
            $viewStyle = $this->basePath . "/css/views/{$viewName}.css";
            extract($data);
            require $viewFile;
        } else {
            $view = '404';
            $viewFile = __DIR__ . '/../Views/404.php';
            $viewStyle = $this->basePath . "/css/views/404.css";
            require_once $viewFile;
        }
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
    
    protected function redirect($path)
    {
        $path = ltrim($path, '/');
        header("Location: {$this->basePath}/{$path}");
        exit;
    }
    
    protected function getConfig()
    {
        return $this->config;
    }
}
