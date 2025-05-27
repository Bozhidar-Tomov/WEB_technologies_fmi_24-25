<?php
require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController
{
    public function index()
    {
        // use this to generate a password hash to hardcode in the database
        // $pass = password_hash('b', PASSWORD_DEFAULT);
        // echo $pass;
        $this->render('home');
    }
}
