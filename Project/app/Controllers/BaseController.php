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
            include $viewFile;
        } else {
            $viewFile = __DIR__ . '/../Views/notFound.php';
            include $viewFile;
        }
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }
}
