<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/ValidationService.php';

use App\Models\User;
use App\Services\ValidationService;

class RegisterController extends BaseController
{
    public function showForm(): void
    {
        if (!empty($_SESSION['user'])) {
            $_SESSION['warning'] = ['You are already logged in.'];
            http_response_code(303);
            header('Location: /');
            exit;
        }

        $this->render('register', ['title' => 'Register']);
    }

    public function handleRegistration(): void
    {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'gender'   => $_POST['gender'] ?? '',
            'role'     => $_POST['role'] ?? '',
            'tags'     => $_POST['tags'] ?? ''
        ];

        $errors = [];

        // Uncomment to enable validation
        // $errors = ValidationService::validateRegistration($data);

        if (empty($errors) && User::findByUsername($data['username'])) {
            $errors[] = 'Username is already taken.';
        }

        if (!empty($errors)) {
            $this->render('register', ['title'  => 'Register','errors' => $errors]);
            return;
        }

        $user = new User([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'gender'   => $data['gender'],
            'role'     => $data['role'],
            'tags'     => array_filter(array_map('trim', explode(',', $data['tags']))),
            'points'   => 0,
            'groups'   => []
        ]);

        if ($user->save()) {
            $_SESSION['user'] = [
                'id'       => (string) $user->id,
                'username' => htmlspecialchars($user->username),
                'role'     => $user->role,
                'points'   => $user->points,
                'groups'   => $user->groups,
                'tags'     => $user->tags,
                'gender'   => $user->gender,
                'title'    => $user->role === 'admin' ? 'Admin Panel' : 'Room View'
            ];

            header('Location: ' . ($user->role === 'admin' ? '/admin' : '/room'));
            exit;
        }

        $this->render('register', ['title'  => 'Register','errors' => ['Failed to save user. Please try again.']]);
    }
}
