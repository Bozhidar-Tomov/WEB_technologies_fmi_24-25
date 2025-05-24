<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/ValidationService.php';

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
                    $roomData = [
                        'username' => htmlspecialchars($user->username),
                        'role' => $user->role ?? '',
                        'points' => $user->points ?? 0,
                        'groups' => $user->groups ?? [],
                        'tags' => $user->tags ?? [],
                        'gender' => $user->gender ?? '',
                    ];
                    $this->render('room', $roomData);
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
