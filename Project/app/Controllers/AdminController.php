<?php

require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController
{
    public function showPanel()
    {
        // TODO: Render admin panel view
        include_once __DIR__ . '/../Views/admin.php';
    }
}
