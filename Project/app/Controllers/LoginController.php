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
        if (isset($_SESSION['user'])) {
            header('Location: /');
            exit;
        }
        $this->render('login');
    }

    public function handleLogin()
    {
        $data = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];

        // TODO: TESTMODE - uncomment this to perform validation
        // $errors = ValidationService::validateLogin($data);
        $errors = [];
        
        $username = $data['username'];
        $password = $data['password'];

        if (empty($errors)) {
            try {
                $user = User::findByUsername($username);
                if ($user && password_verify($password, $user->password)) {
                    if ($user->role === 'admin') {
                        $_SESSION['user'] = [
                            'title' => 'Admin Panel',
                            'id' => (string) $user->id,
                            'username' => htmlspecialchars($user->username),
                            'role' => $user->role,
                            'points' => $user->points,
                            'groups' => $user->groups,
                            'tags' => $user->tags,
                            'gender' => $user->gender
                        ];
                        header('Location: /admin');
                        exit;
                    }
                    
                    $_SESSION['user'] = [
                        'id' => (string) $user->id,
                        'username' => htmlspecialchars($user->username),
                        'role' => $user->role ?? '',
                        'points' => $user->points ?? 0,
                        'groups' => $user->groups ?? [],
                        'tags' => $user->tags ?? [],
                        'gender' => $user->gender ?? '',
                    ];
                    header('Location: /room');
                    exit;
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
