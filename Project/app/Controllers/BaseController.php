<?php

class BaseController
{
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        ob_start();
        if (file_exists($viewFile)) {
            $viewStyle = "/css/views/{$view}.css";
            extract($data);
            require $viewFile;
        } else {
            $view = '404';
            $viewFile = __DIR__ . '/../Views/404.php';
            $viewStyle = "/css/views/404.css";
            require_once $viewFile;
        }
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
}
