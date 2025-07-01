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
            $basePath = $this->getBasePath();
            header("Location: {$basePath}/");
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

        // Group assignment logic
        $groups = [];
        // Add gender as a group
        if (!empty($data['gender'])) {
            $groups[] = $data['gender'];
        }
        // Add tags as groups
        if (!empty($data['tags'])) {
            $groups = array_merge($groups, array_filter(array_map('trim', explode(',', $data['tags']))));
        }
        // Assign to A, B, C, or D group in round-robin fashion
        $allUsers = \App\Models\User::loadUsers();
        $userCount = count($allUsers);
        $groupNames = ['A', 'B', 'C', 'D'];
        $groups[] = 'Group ' . $groupNames[$userCount % 4];

        $user = new User([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'gender'   => $data['gender'],
            'role'     => $data['role'],
            'tags'     => array_filter(array_map('trim', explode(',', $data['tags']))),
            'points'   => 0,
            'groups'   => $groups
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

            $basePath = $this->getBasePath();
            $redirectPath = $user->role === 'admin' ? "{$basePath}/admin" : "{$basePath}/room";
            header("Location: {$redirectPath}");
            exit;
        }

        $this->render('register', ['title'  => 'Register','errors' => ['Failed to save user. Please try again.']]);
    }
}
