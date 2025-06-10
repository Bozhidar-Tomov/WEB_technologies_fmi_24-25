<?php
class LogoutController
{
    public function logout()
    {
        session_unset();
        session_destroy();
        session_abort();
        header('Location: /login');
        exit;
    }
}
