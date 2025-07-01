<?php

class BaseController
{
    protected $basePath = '';
    
    public function __construct()
    {
        global $router;
        $this->basePath = isset($router) ? $router->getBasePath() : '';
    }
    
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        
        // Add base path to the data array for use in views
        $data['basePath'] = $this->basePath;

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
}
