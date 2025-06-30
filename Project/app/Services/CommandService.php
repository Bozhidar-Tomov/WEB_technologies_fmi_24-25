<?php

namespace App\Services;

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Models/User.php';

use App\Database\Database;
use App\Models\User;
use PDO;
use PDOException;

class CommandService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function broadcastCommand(array $commandData): bool
    {
        try {
            $this->db->beginTransaction();
            
            $commandData['id'] ??= uniqid('cmd_');
            $commandData['timestamp'] = time();
            
            // Reset all active commands
            $this->db->query("UPDATE commands SET is_active = 0");
            
            // Insert the new command
            $this->db->query(
                "INSERT INTO commands (id, command_type, command_data, is_active, timestamp) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $commandData['id'],
                    $commandData['type'] ?? '',
                    json_encode($commandData),
                    true,
                    $commandData['timestamp']
                ]
            );
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getActiveCommand(): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT command_data FROM commands WHERE is_active = 1 ORDER BY timestamp DESC LIMIT 1"
            );
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['command_data'])) {
                return json_decode($result['command_data'], true);
            }
            
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function registerActiveUser(string $userId): bool
    {
        $this->cleanupInactiveUsers();
        
        try {
            $stmt = $this->db->query(
                "SELECT user_id FROM active_users WHERE user_id = ?",
                [$userId]
            );
            
            if ($stmt->rowCount() > 0) {
                // Update existing active user
                $this->db->query(
                    "UPDATE active_users SET last_seen = ? WHERE user_id = ?",
                    [time(), $userId]
                );
            } else {
                // Insert new active user
                $this->db->query(
                    "INSERT INTO active_users (user_id, last_seen) VALUES (?, ?)",
                    [$userId, time()]
                );
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeActiveUser(string $userId): bool
    {
        try {
            $this->db->query(
                "DELETE FROM active_users WHERE user_id = ?",
                [$userId]
            );
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getActiveUsers(): array
    {
        $users = [];
        
        try {
            $stmt = $this->db->query("SELECT * FROM active_users");
            $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activeUsers as $user) {
                $users[$user['user_id']] = [
                    'lastSeen' => $user['last_seen']
                ];
            }
            
            return $users;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getActiveUserCount(): int
    {
        $this->cleanupInactiveUsers();
        
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM active_users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function cleanupInactiveUsers(): void
    {
        try {
            $timeout = 60;
            $cutoff = time() - $timeout;
            
            $this->db->query(
                "DELETE FROM active_users WHERE last_seen < ?",
                [$cutoff]
            );
        } catch (PDOException $e) {
            // Silently fail
        }
    }
    
    /**
     * Check if a user matches the target filters for a command
     * 
     * @param string $userId The user ID to check
     * @param array $commandData The command data containing filters
     * @return bool True if the user matches the filters or if no filters are set
     */
    public function userMatchesCommandFilters(string $userId, array $commandData): bool
    {
        // If no filters are set, all users match
        if (empty($commandData['targetGroups']) && 
            empty($commandData['targetTags']) && 
            empty($commandData['targetGender'])) {
            return true;
        }
        
        try {
            // Get user data
            $stmt = $this->db->query(
                "SELECT u.*, 
                        GROUP_CONCAT(DISTINCT ug.group_name) as groups_concat, 
                        GROUP_CONCAT(DISTINCT ut.tag) as tags_concat
                 FROM users u
                 LEFT JOIN user_groups ug ON u.id = ug.user_id
                 LEFT JOIN user_tags ut ON u.id = ut.user_id
                 WHERE u.id = ?
                 GROUP BY u.id",
                [$userId]
            );
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return false;
            }
            
            // Process user groups
            $userGroups = [];
            if (!empty($userData['groups_concat'])) {
                $userGroups = explode(',', $userData['groups_concat']);
            }
            
            // Process user tags
            $userTags = [];
            if (!empty($userData['tags_concat'])) {
                $userTags = explode(',', $userData['tags_concat']);
            }
            
            // Check gender filter
            if (!empty($commandData['targetGender']) && $userData['gender'] !== $commandData['targetGender']) {
                return false;
            }
            
            // Check groups filter
            if (!empty($commandData['targetGroups'])) {
                $targetGroups = is_array($commandData['targetGroups']) 
                    ? $commandData['targetGroups'] 
                    : explode(',', $commandData['targetGroups']);
                
                $hasMatchingGroup = false;
                foreach ($targetGroups as $group) {
                    $group = trim($group);
                    if (in_array($group, $userGroups)) {
                        $hasMatchingGroup = true;
                        break;
                    }
                }
                
                if (!$hasMatchingGroup) {
                    return false;
                }
            }
            
            // Check tags filter
            if (!empty($commandData['targetTags'])) {
                $targetTags = is_array($commandData['targetTags']) 
                    ? $commandData['targetTags'] 
                    : explode(',', $commandData['targetTags']);
                
                $hasMatchingTag = false;
                foreach ($targetTags as $tag) {
                    $tag = trim($tag);
                    if (in_array($tag, $userTags)) {
                        $hasMatchingTag = true;
                        break;
                    }
                }
                
                if (!$hasMatchingTag) {
                    return false;
                }
            }
            
            // User matches all filters
            return true;
        } catch (PDOException $e) {
            // In case of error, default to including the user
            return true;
        }
    }
}
