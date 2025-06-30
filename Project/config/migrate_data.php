<?php

require_once __DIR__ . '/../app/Database/Database.php';

use App\Database\Database;

function migrateData() {
    $db = Database::getInstance();
    
    // Create database and tables
    $sqlFile = file_get_contents(__DIR__ . '/schema.sql');
    $queries = explode(';', $sqlFile);
    
    foreach ($queries as $query) {
        if (trim($query)) {
            try {
                $db->getConnection()->exec($query);
                echo "Executed query successfully.\n";
            } catch (PDOException $e) {
                echo "Error executing query: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Migrate users
    migrateUsers($db);
    
    // Migrate active users
    migrateActiveUsers($db);
    
    // Migrate commands
    migrateCommands($db);
    
    // Migrate mic results
    migrateMicResults($db);
    
    // Migrate settings
    migrateSettings($db);
    
    echo "Migration completed successfully!\n";
}

function migrateUsers(Database $db) {
    echo "Migrating users...\n";
    
    $usersFile = __DIR__ . '/../app/Database/users.json';
    $users = readJsonFile($usersFile);
    
    if (!$users) {
        echo "No users to migrate.\n";
        return;
    }
    
    $db->beginTransaction();
    
    try {
        foreach ($users as $id => $userData) {
            // Insert user
            $stmt = $db->query(
                "INSERT INTO users (id, username, role, points, gender, password, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    $userData['username'],
                    $userData['role'],
                    $userData['points'],
                    $userData['gender'],
                    $userData['password'],
                    $userData['created_at']
                ]
            );
            
            // Insert user groups
            if (!empty($userData['groups'])) {
                foreach ($userData['groups'] as $group) {
                    $db->query(
                        "INSERT INTO user_groups (user_id, group_name) VALUES (?, ?)",
                        [$id, $group]
                    );
                }
            }
            
            // Insert user tags
            if (!empty($userData['tags'])) {
                foreach ($userData['tags'] as $tag) {
                    $db->query(
                        "INSERT INTO user_tags (user_id, tag) VALUES (?, ?)",
                        [$id, $tag]
                    );
                }
            }
        }
        
        $db->commit();
        echo "Users migrated successfully.\n";
    } catch (PDOException $e) {
        $db->rollback();
        echo "Error migrating users: " . $e->getMessage() . "\n";
    }
}

function migrateActiveUsers(Database $db) {
    echo "Migrating active users...\n";
    
    $activeUsersFile = __DIR__ . '/../app/Database/active_users.json';
    $activeUsers = readJsonFile($activeUsersFile);
    
    if (!$activeUsers) {
        echo "No active users to migrate.\n";
        return;
    }
    
    $db->beginTransaction();
    
    try {
        foreach ($activeUsers as $userId => $userData) {
            $db->query(
                "INSERT INTO active_users (user_id, last_seen, user_agent) VALUES (?, ?, ?)",
                [
                    $userId,
                    $userData['lastSeen'],
                    $userData['userAgent']
                ]
            );
        }
        
        $db->commit();
        echo "Active users migrated successfully.\n";
    } catch (PDOException $e) {
        $db->rollback();
        echo "Error migrating active users: " . $e->getMessage() . "\n";
    }
}

function migrateCommands(Database $db) {
    echo "Migrating commands...\n";
    
    $commandsDir = __DIR__ . '/../app/Database/commands/command_history';
    $activeCommandFile = __DIR__ . '/../app/Database/commands/active_command.json';
    
    // First migrate active command
    $activeCommand = readJsonFile($activeCommandFile);
    
    if ($activeCommand) {
        try {
            $db->query(
                "INSERT INTO commands (id, command_type, command_data, is_active, timestamp) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $activeCommand['id'],
                    $activeCommand['type'] ?? '',
                    json_encode($activeCommand),
                    true,
                    $activeCommand['timestamp']
                ]
            );
            
            echo "Active command migrated successfully.\n";
        } catch (PDOException $e) {
            echo "Error migrating active command: " . $e->getMessage() . "\n";
        }
    }
    
    // Now migrate command history
    if (!is_dir($commandsDir)) {
        echo "Command history directory not found.\n";
        return;
    }
    
    $files = glob($commandsDir . '/*.json');
    
    if (empty($files)) {
        echo "No command history to migrate.\n";
        return;
    }
    
    $db->beginTransaction();
    
    try {
        foreach ($files as $file) {
            $command = readJsonFile($file);
            
            if ($command) {
                // Skip if this is the active command (already inserted)
                if ($activeCommand && $command['id'] === $activeCommand['id']) {
                    continue;
                }
                
                $db->query(
                    "INSERT INTO commands (id, command_type, command_data, is_active, timestamp) 
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $command['id'],
                        $command['type'] ?? '',
                        json_encode($command),
                        false,
                        $command['timestamp']
                    ]
                );
            }
        }
        
        $db->commit();
        echo "Command history migrated successfully.\n";
    } catch (PDOException $e) {
        $db->rollback();
        echo "Error migrating command history: " . $e->getMessage() . "\n";
    }
}

function migrateMicResults(Database $db) {
    echo "Migrating mic results...\n";
    
    $micResultsFile = __DIR__ . '/../app/Database/mic_results.json';
    $micResults = readJsonFile($micResultsFile);
    
    if (!$micResults) {
        echo "No mic results to migrate.\n";
        return;
    }
    
    $db->beginTransaction();
    
    try {
        foreach ($micResults as $key => $result) {
            $db->query(
                "INSERT INTO mic_results (user_id, command_id, intensity, volume, reaction_accuracy, timestamp) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $result['userId'],
                    $result['commandId'],
                    $result['intensity'],
                    $result['volume'],
                    $result['reactionAccuracy'],
                    $result['timestamp']
                ]
            );
        }
        
        $db->commit();
        echo "Mic results migrated successfully.\n";
    } catch (PDOException $e) {
        $db->rollback();
        echo "Error migrating mic results: " . $e->getMessage() . "\n";
    }
}

function migrateSettings(Database $db) {
    echo "Migrating settings...\n";
    
    $simAudienceFile = __DIR__ . '/../app/Database/sim_audience_on.flag';
    
    if (file_exists($simAudienceFile)) {
        try {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [
                    'sim_audience_on',
                    '1'
                ]
            );
            
            echo "Simulated audience setting migrated successfully.\n";
        } catch (PDOException $e) {
            echo "Error migrating simulated audience setting: " . $e->getMessage() . "\n";
        }
    } else {
        try {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [
                    'sim_audience_on',
                    '0'
                ]
            );
            
            echo "Simulated audience setting migrated successfully.\n";
        } catch (PDOException $e) {
            echo "Error migrating simulated audience setting: " . $e->getMessage() . "\n";
        }
    }
}

// Run the migration
migrateData(); 