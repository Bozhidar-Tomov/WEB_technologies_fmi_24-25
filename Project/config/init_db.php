<?php

/**
 * Database Initialization Script
 * 
 * This script creates the necessary database and tables for the application.
 * Run this script once to set up the database structure.
 */

// Check if running in browser or CLI
$isBrowser = php_sapi_name() !== 'cli';
if ($isBrowser) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Setup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            h1 { color: #333; }
            hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
            .next-steps { background: #e9f7ef; padding: 15px; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Database Setup</h1>
            <hr>";
}

function output($message, $type = 'info') {
    global $isBrowser;
    
    if ($isBrowser) {
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : ''));
        echo "<p" . ($class ? " class='$class'" : "") . ">$message</p>";
    } else {
        echo $message . "\n";
    }
}

// Get database configuration
$config = require_once __DIR__ . '/database.php';
$host = $config['host'];
$username = $config['username'];
$password = $config['password'];
$charset = $config['charset'];
$dbName = $config['database'];
$options = $config['options'];

try {
    // Connect to MySQL server without selecting a database
    $pdo = new PDO(
        "mysql:host=$host;charset=$charset",
        $username,
        $password,
        $options
    );
    
    output("Connected to MySQL server successfully.", 'success');
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
    output("Database '$dbName' created or already exists.", 'success');
    
    // Select the database
    $pdo->exec("USE `$dbName`");
    output("Using database '$dbName'.", 'success');
    
    // Execute SQL schema
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    output("Database schema created successfully.", 'success');
    
    // Check for legacy tables and migrate data if they exist
    migrateFromLegacyTables($pdo);
    
    // Update command data if needed
    updateCommandData($pdo);
    
    output("Database initialization completed successfully!", 'success');
    
    if ($isBrowser) {
        echo "<div class='next-steps'>";
        echo "<h2>Next Steps:</h2>";
        echo "<p>1. You can now close this page and return to your application.</p>";
        echo "<p>2. Go to <a href='../'><strong>Homepage</strong></a> to start using the application.</p>";
        echo "</div>";
    } else {
        echo "\nNext steps:\n";
        echo "1. Start the application and navigate to the homepage.\n";
    }
    
} catch (PDOException $e) {
    output("Database initialization failed: " . $e->getMessage(), 'error');
}

if ($isBrowser) {
    echo "</div></body></html>";
}

/**
 * Migrate data from legacy tables if they exist
 */
function migrateFromLegacyTables($pdo) {
    try {
        // Check for user_groups table
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_groups'");
        $hasUserGroups = $stmt->rowCount() > 0;
        
        if ($hasUserGroups) {
            output("Found legacy user_groups table. Checking columns...", 'info');
            
            // Check columns in user_groups table
            $stmt = $pdo->query("DESCRIBE user_groups");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('group_name', $columns) && in_array('user_id', $columns)) {
                output("Migrating data from user_groups to user_categories...", 'info');
                
                // Migrate data from user_groups to user_categories
                $pdo->exec("
                    INSERT IGNORE INTO user_categories (user_id, category)
                    SELECT user_id, group_name FROM user_groups
                ");
                output("Data from user_groups migrated successfully.", 'success');
            } else {
                output("Could not find expected columns in user_groups table.", 'warning');
            }
        }
        
        // Check for user_tags table
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_tags'");
        $hasUserTags = $stmt->rowCount() > 0;
        
        if ($hasUserTags) {
            output("Found legacy user_tags table. Checking columns...", 'info');
            
            // Check columns in user_tags table
            $stmt = $pdo->query("DESCRIBE user_tags");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            if (in_array('user_id', $columns)) {
                // Find the tag column name (might be tag_name, tag, or something else)
                $tagColumn = null;
                $possibleTagColumns = ['tag_name', 'tag', 'name', 'value'];
                foreach ($possibleTagColumns as $column) {
                    if (in_array($column, $columns)) {
                        $tagColumn = $column;
                        break;
                    }
                }
                
                if ($tagColumn) {
                    output("Migrating data from user_tags to user_categories...", 'info');
                    
                    // Migrate data from user_tags to user_categories
                    $pdo->exec("
                        INSERT IGNORE INTO user_categories (user_id, category)
                        SELECT user_id, {$tagColumn} FROM user_tags
                    ");
                    output("Data from user_tags migrated successfully.", 'success');
                } else {
                    output("Could not find tag column in user_tags table.", 'warning');
                }
            } else {
                output("Could not find expected columns in user_tags table.", 'warning');
            }
        }
    } catch (PDOException $e) {
        output("Migration from legacy tables failed: " . $e->getMessage(), 'warning');
        // Continue with initialization even if migration fails
    }
}

/**
 * Update command data to use consolidated categories
 */
function updateCommandData($pdo) {
    try {
        // Check for commands table
        $stmt = $pdo->query("SHOW TABLES LIKE 'commands'");
        $hasCommands = $stmt->rowCount() > 0;
        
        if ($hasCommands) {
            output("Updating command data to use consolidated categories...", 'info');
            
            // Get all commands
            $stmt = $pdo->query("SELECT id, command_data FROM commands");
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            // Begin transaction for command updates
            $pdo->beginTransaction();
            
            foreach ($commands as $command) {
                $commandData = json_decode($command['command_data'], true);
                if (!$commandData) continue; // Skip if JSON decode fails
                
                $updated = false;
                
                // Check if command has targetGroups or targetTags
                if (isset($commandData['targetGroups']) || isset($commandData['targetTags'])) {
                    $categories = [];
                    
                    // Merge groups into categories
                    if (isset($commandData['targetGroups'])) {
                        $groups = is_array($commandData['targetGroups']) ? 
                            $commandData['targetGroups'] : 
                            explode(',', $commandData['targetGroups']);
                        
                        foreach ($groups as $group) {
                            $group = trim($group);
                            if (!empty($group) && !in_array($group, $categories)) {
                                $categories[] = $group;
                            }
                        }
                        
                        // Remove old field
                        unset($commandData['targetGroups']);
                    }
                    
                    // Merge tags into categories
                    if (isset($commandData['targetTags'])) {
                        $tags = is_array($commandData['targetTags']) ? 
                            $commandData['targetTags'] : 
                            explode(',', $commandData['targetTags']);
                        
                        foreach ($tags as $tag) {
                            $tag = trim($tag);
                            if (!empty($tag) && !in_array($tag, $categories)) {
                                $categories[] = $tag;
                            }
                        }
                        
                        // Remove old field
                        unset($commandData['targetTags']);
                    }
                    
                    // Set the new targetCategories field
                    if (!empty($categories)) {
                        $commandData['targetCategories'] = $categories;
                    }
                    
                    // Update the command in the database
                    $updatedJson = json_encode($commandData);
                    $stmt = $pdo->prepare("UPDATE commands SET command_data = ? WHERE id = ?");
                    $stmt->execute([$updatedJson, $command['id']]);
                    
                    $updateCount++;
                    $updated = true;
                }
            }
            
            $pdo->commit();
            
            if ($updateCount > 0) {
                output("Updated {$updateCount} commands to use consolidated categories.", 'success');
            } else {
                output("No commands needed updating.", 'info');
            }
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        output("Command data update failed: " . $e->getMessage(), 'warning');
        // Continue with initialization even if command update fails
    }
} 