<?php

namespace App\Models;
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../Database/Database.php';

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

    private static string $table = 'users';

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function save(): bool
    {
        $pdo = \App\Database::getConnection();

        // Encode complex fields as JSON
        $groupsJson = json_encode($this->groups, JSON_UNESCAPED_UNICODE);
        $tagsJson   = json_encode($this->tags, JSON_UNESCAPED_UNICODE);

        // Insert or update
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE `" . self::$table . "` SET username = :username, role = :role, `groups` = :groups, points = :points, gender = :gender, tags = :tags, password = :password WHERE id = :id");
            return $stmt->execute([
                ':username' => $this->username,
                ':role'     => $this->role,
                ':groups'   => $groupsJson,
                ':points'   => $this->points,
                ':gender'   => $this->gender,
                ':tags'     => $tagsJson,
                ':password' => $this->password,
                ':id'       => $this->id,
            ]);
        }

        $stmt = $pdo->prepare("INSERT INTO `" . self::$table . "` (username, role, `groups`, points, gender, tags, password, created_at) VALUES (:username, :role, :groups, :points, :gender, :tags, :password, NOW())");
        $success = $stmt->execute([
            ':username' => $this->username,
            ':role'     => $this->role,
            ':groups'   => $groupsJson,
            ':points'   => $this->points,
            ':gender'   => $this->gender,
            ':tags'     => $tagsJson,
            ':password' => $this->password,
        ]);

        if ($success) {
            $this->id = $pdo->lastInsertId();
        }
        return $success;
    }

    public static function findByUsername($username): ?self
    {
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::$table . "` WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        if (!$row) return null;

        // Decode JSON fields
        $row['groups'] = json_decode($row['groups'] ?? '[]', true);
        $row['tags']   = json_decode($row['tags'] ?? '[]', true);

        $user = new self($row);
        return $user;
    }

    public static function loadUsers(): array
    {
        $pdo = \App\Database::getConnection();
        $rows = $pdo->query("SELECT * FROM `" . self::$table . "`")->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $users[$row['id']] = [
                'username'   => $row['username'],
                'role'       => $row['role'],
                'groups'     => json_decode($row['groups'] ?? '[]', true),
                'points'     => (int)($row['points'] ?? 0),
                'gender'     => $row['gender'],
                'tags'       => json_decode($row['tags'] ?? '[]', true),
                'password'   => $row['password'],
                'created_at' => $row['created_at'],
            ];
        }
        return $users;
    }

    public static function addPoints($userId, $pointsToAdd): bool
    {
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `" . self::$table . "` SET points = points + :pts WHERE id = :id");
        return $stmt->execute([':pts' => $pointsToAdd, ':id' => $userId]);
    }

    public static function transferPoints($fromUserId, $toUsername, $amount): array
    {
        $pdo = \App\Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Get sender row with lock
            $stmt = $pdo->prepare("SELECT id, points FROM `" . self::$table . "` WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $fromUserId]);
            $fromRow = $stmt->fetch();
            if (!$fromRow) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Sender not found'];
            }

            if ((int)$fromRow['points'] < $amount) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Insufficient points'];
            }

            // Get recipient id
            $stmt = $pdo->prepare("SELECT id FROM `" . self::$table . "` WHERE LOWER(username) = LOWER(:uname) LIMIT 1 FOR UPDATE");
            $stmt->execute([':uname' => $toUsername]);
            $toRow = $stmt->fetch();
            if (!$toRow) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Recipient not found'];
            }

            // Update balances
            $stmt = $pdo->prepare("UPDATE `" . self::$table . "` SET points = points - :amt WHERE id = :id");
            $stmt->execute([':amt' => $amount, ':id' => $fromUserId]);

            $stmt = $pdo->prepare("UPDATE `" . self::$table . "` SET points = points + :amt WHERE id = :id");
            $stmt->execute([':amt' => $amount, ':id' => $toRow['id']]);

            $pdo->commit();
            return ['success' => true, 'points' => $fromRow['points'] - $amount];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
