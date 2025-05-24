<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/ValidationService.php';

use App\Models\User;
use App\Services\ValidationService;

class RegisterController extends BaseController
{
    public function showForm()
    {
        $this->render('register');
    }

    public function index()
    {
        $this->render('index', ['title' => 'Crowd Pulse']);
    }

    public function handleRegistration()
    {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'gender'   => $_POST['gender'] ?? '',
            'role'     => $_POST['role'] ?? '',
            'tags'     => $_POST['tags'] ?? ''
        ];

        $errors = ValidationService::validateRegistration($data);

        if (empty($errors) && User::findByUsername($data['username'])) {
            $errors[] = 'Username is already taken.';
        }

        if (!empty($errors)) {
            $this->render('register', [
                'errors' => $errors,
                'old' => [
                    'username' => htmlspecialchars($data['username']),
                    'gender'   => htmlspecialchars($data['gender']),
                    'role'     => htmlspecialchars($data['role']),
                    'tags'     => htmlspecialchars($data['tags'])
                ]
            ]);
            return;
        }

        $user = new User();
        $user->username = $data['username'];
        $user->role = $data['role'];
        $user->gender = $data['gender'];
        $user->tags = array_filter(array_map('trim', explode(',', $data['tags'])));
        $user->points = 0;
        $user->groups = [];
        $user->arrivalTime = null;
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);

        if ($user->save()) {
            $_SESSION['user'] = [
                'id'       => (string) $user->id,
                'username' => $user->username,
                'role'     => $user->role,
                'points'   => $user->points,
                'groups'   => $user->groups,
                'tags'     => $user->tags,
                'gender'   => $user->gender
            ];

            $this->render('room', [
                'username' => htmlspecialchars($user->username),
                'role'     => $user->role,
                'points'   => $user->points,
                'groups'   => $user->groups,
                'tags'     => $user->tags,
                'gender'   => $user->gender
            ]);
        } else {
            $errors[] = 'Failed to save user. Please try again.';
            $this->render('register', [
                'errors' => $errors,
                'old' => [
                    'username' => htmlspecialchars($data['username']),
                    'gender'   => htmlspecialchars($data['gender']),
                    'role'     => htmlspecialchars($data['role']),
                    'tags'     => htmlspecialchars($data['tags'])
                ]
            ]);
        }
    }
}
