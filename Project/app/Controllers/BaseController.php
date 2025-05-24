<?php

class BaseController
{
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        $viewStyle = "/css/views/{$view}.css";

        ob_start();
        if (file_exists($viewFile)) {
            extract($data);
            require $viewFile;
        } else {
            $viewFile = __DIR__ . '/../Views/404.php';
            require $viewFile;
        }
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
}
