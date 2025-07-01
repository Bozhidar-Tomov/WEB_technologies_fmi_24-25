<?php

class BaseController
{
    protected function render($view, $data = [])
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';

        ob_start();
        if (file_exists($viewFile)) {
            $viewName = str_contains($view, '/') ? substr($view, 0, strpos($view, '/')) : $view;
            $viewStyle = "css/views/{$viewName}.css";
            extract($data);
            require $viewFile;
        } else {
            $view = '404';
            $viewFile = BASE_PATH . '/app/Views/404.php';
            $viewStyle = "css/views/404.css";
            require_once $viewFile;
        }
        $content = ob_get_clean();
        require BASE_PATH . '/app/Views/layout.php';
    }
}
