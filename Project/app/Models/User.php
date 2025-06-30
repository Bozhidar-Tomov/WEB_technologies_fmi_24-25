<?php

namespace App\Models;
require_once __DIR__ . '/../Database/Database.php';

use App\Database\Database;
use PDO;
use PDOException;

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

    private $db;

    public function __construct($data = [])
    {
        $this->db = Database::getInstance();
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function save(): bool
    {
        try {
            $this->db->beginTransaction();
            
            if (!$this->created_at) {
                $this->created_at = date('Y-m-d H:i:s');
            }
            
            if (!$this->id) {
                $this->id = uniqid();
            }
            
            // Check if user exists
            $stmt = $this->db->query("SELECT id FROM users WHERE id = ?", [$this->id]);
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                // Update existing user
                $this->db->query(
                    "UPDATE users SET username = ?, role = ?, points = ?, gender = ?, password = ? WHERE id = ?",
                    [
                        $this->username,
                        $this->role,
                        $this->points,
                        $this->gender,
                        $this->password,
                        $this->id
                    ]
                );
            } else {
                // Insert new user
                $this->db->query(
                    "INSERT INTO users (id, username, role, points, gender, password, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $this->id,
                        $this->username,
                        $this->role,
                        $this->points,
                        $this->gender,
                        $this->password,
                        $this->created_at
                    ]
                );
            }
            
            // Update groups
            $this->db->query("DELETE FROM user_groups WHERE user_id = ?", [$this->id]);
            
            if (!empty($this->groups)) {
                foreach ($this->groups as $group) {
                    $this->db->query(
                        "INSERT INTO user_groups (user_id, group_name) VALUES (?, ?)",
                        [$this->id, $group]
                    );
                }
            }
            
            // Update tags
            $this->db->query("DELETE FROM user_tags WHERE user_id = ?", [$this->id]);
            
            if (!empty($this->tags)) {
                foreach ($this->tags as $tag) {
                    $this->db->query(
                        "INSERT INTO user_tags (user_id, tag) VALUES (?, ?)",
                        [$this->id, $tag]
                    );
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            $_SESSION['error'] = "User save failed: " . $e->getMessage();
            return false;
        }
    }

    public static function findByUsername($username)
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "SELECT u.*, 
                    GROUP_CONCAT(DISTINCT ug.group_name) as groups_concat, 
                    GROUP_CONCAT(DISTINCT ut.tag) as tags_concat
             FROM users u
             LEFT JOIN user_groups ug ON u.id = ug.user_id
             LEFT JOIN user_tags ut ON u.id = ut.user_id
             WHERE u.username = ?
             GROUP BY u.id",
            [$username]
        );
        
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        $user = new self();
        $user->id = $userData['id'];
        $user->username = $userData['username'];
        $user->role = $userData['role'] ?? 'participant';
        $user->points = (int)$userData['points'] ?? 0;
        $user->gender = $userData['gender'];
        $user->password = $userData['password'];
        $user->created_at = $userData['created_at'];
        
        // Process groups
        $user->groups = [];
        if (!empty($userData['groups_concat'])) {
            $user->groups = explode(',', $userData['groups_concat']);
        }
        
        // Process tags
        $user->tags = [];
        if (!empty($userData['tags_concat'])) {
            $user->tags = explode(',', $userData['tags_concat']);
        }
        
        return $user;
    }
    
    public static function loadUsers(): array
    {
        $db = Database::getInstance();
        $users = [];
        
        $stmt = $db->query("SELECT * FROM users ORDER BY created_at");
        $userRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($userRows as $userData) {
            $userId = $userData['id'];
            
            // Get groups
            $groupStmt = $db->query(
                "SELECT group_name FROM user_groups WHERE user_id = ?",
                [$userId]
            );
            $groups = $groupStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get tags
            $tagStmt = $db->query(
                "SELECT tag FROM user_tags WHERE user_id = ?",
                [$userId]
            );
            $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $users[$userId] = [
                'username' => $userData['username'],
                'role' => $userData['role'],
                'groups' => $groups,
                'points' => (int)$userData['points'],
                'gender' => $userData['gender'],
                'tags' => $tags,
                'password' => $userData['password'],
                'created_at' => $userData['created_at']
            ];
        }
        
        return $users;
    }
    
    public static function addPoints($userId, $pointsToAdd) {
        $db = Database::getInstance();
        
        try {
            $db->query(
                "UPDATE users SET points = points + ? WHERE id = ?",
                [(int)$pointsToAdd, $userId]
            );
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function transferPoints($fromUserId, $toUsername, $amount) {
        $db = Database::getInstance();
        $amount = (int)$amount;
        
        try {
            $db->beginTransaction();
            
            // Check if sender exists and has enough points
            $stmt = $db->query(
                "SELECT id, points FROM users WHERE id = ?",
                [$fromUserId]
            );
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sender) {
                return ['success' => false, 'error' => 'Sender not found'];
            }
            
            if ($sender['points'] < $amount) {
                return ['success' => false, 'error' => 'Insufficient points'];
            }
            
            // Find recipient by username
            $stmt = $db->query(
                "SELECT id FROM users WHERE LOWER(username) = LOWER(?)",
                [$toUsername]
            );
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recipient) {
                return ['success' => false, 'error' => 'Recipient not found'];
            }
            
            $toUserId = $recipient['id'];
            
            // Update sender's points
            $db->query(
                "UPDATE users SET points = points - ? WHERE id = ?",
                [$amount, $fromUserId]
            );
            
            // Update recipient's points
            $db->query(
                "UPDATE users SET points = points + ? WHERE id = ?",
                [$amount, $toUserId]
            );
            
            // Log the transfer
            $db->query(
                "INSERT INTO point_transfers (from_user_id, to_user_id, amount, timestamp) 
                 VALUES (?, ?, ?, ?)",
                [$fromUserId, $toUserId, $amount, time()]
            );
            
            // Get updated points for sender
            $stmt = $db->query(
                "SELECT points FROM users WHERE id = ?",
                [$fromUserId]
            );
            $updatedSender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $db->commit();
            
            return [
                'success' => true, 
                'points' => (int)$updatedSender['points']
            ];
        } catch (PDOException $e) {
            $db->rollback();
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
}
