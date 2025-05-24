<?php
require_once 'BaseController.php';

use App\Models\User;
use App\Services\ValidationService;

class LoginController extends BaseController
{
    public function showForm()
    {
        $this->render('login');
    }

    public function handleLogin()
    {
        $data = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];
        $errors = ValidationService::validateLogin($data);
        $username = $data['username'];
        $password = $data['password'];

        if (empty($errors)) {
            try {
                $user = User::findByUsername($username);
                if ($user && password_verify($password, $user->password)) {
                    $this->render('login_success', ['username' => htmlspecialchars($username)]);
                    return;
                } else {
                    $errors[] = 'Invalid username or password.';
                }
            } catch (\Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }

        $this->render('login', [
            'errors' => $errors,
            'old' => ['username' => htmlspecialchars($username)]
        ]);
    }
}
