<?php

class BaseController
{
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';

        ob_start();
        if (file_exists($viewFile)) {
            $viewName = str_contains($view, '/') ? substr($view, 0, strpos($view, '/')) : $view;
            $viewStyle = "{$basePath}/css/views/{$viewName}.css";
            extract($data);
            require $viewFile;
        } else {
            $view = '404';
            $viewFile = __DIR__ . '/../Views/404.php';
            $viewStyle = "{$basePath}/css/views/404.css";
            require_once $viewFile;
        }
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
    
    protected function getBasePath()
    {
        return defined('BASE_PATH') ? BASE_PATH : '';
    }
}
