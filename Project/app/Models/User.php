<?php

namespace App\Models;
require_once __DIR__ . '/../utils.php';

class User
{
    public $id;
    public $username;
    public $role; // participant, viewer, leader, admin
    public $groups = [];
    public $points = 0;
    public $gender;
    public $tags = [];
    public $password;
    public $created_at;

    private static $dataFile = __DIR__ . '/../../app/Database/users.json';

    public function save(): bool
    {
        $dataDir = dirname(self::$dataFile);
        ensureDirectoryExists($dataDir);

        $users = self::loadUsers();
        
        if (!$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        $userData = [
            'username' => $this->username,
            'role' => $this->role,
            'groups' => $this->groups,
            'points' => $this->points,
            'gender' => $this->gender,
            'tags' => $this->tags,
            'password' => $this->password,
            'created_at' => $this->created_at
        ];

        try {
            if(!$this->id) $this->id = uniqid();

            $users[$this->id] = $userData;

            return self::saveUsers($users);
        } catch (Exception $e) {
            $_SESSION['error'] = "User save failed: " . $e->getMessage();
            return false;
        }
    }

    public static function findByUsername($username)
    {
        $users = self::loadUsers();

        foreach ($users as $id => $userData) {
            if ($userData['username'] === $username) {
                $user = new self();
                $user->id = $id;
                $user->username = $userData['username'];
                $user->role = $userData['role'] ?? 'participant';
                $user->groups = $userData['groups'] ?? [];
                $user->points = $userData['points'] ?? 0;
                $user->gender = $userData['gender'] ?? null;
                $user->tags = $userData['tags'] ?? [];
                $user->password = $userData['password'] ?? null;
                $user->created_at = $userData['created_at'] ?? null;
                return $user;
            }
        }
        
        return null;
    }
    private static function loadUsers(): array
    {
        return readJsonFile(self::$dataFile) ?? [];
    }

    private static function saveUsers(array $users): bool
    {
        return saveJsonFile(self::$dataFile, $users);
    }
    
}
