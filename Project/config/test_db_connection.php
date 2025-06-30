<?php

/**
 * Database Connection Test Script
 * 
 * This script tests the connection to the MySQL database.
 * Run this script to verify that your database configuration is correct.
 */

require_once __DIR__ . '/../app/Database/Database.php';
use App\Database\Database;

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Test connection
    $stmt = $db->query("SELECT 'Connection successful!' as message");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ " . $result['message'] . "\n";
    
    // Test database schema
    $tables = [
        'users',
        'user_groups',
        'user_tags',
        'active_users',
        'commands',
        'mic_results',
        'point_transfers',
        'settings'
    ];
    
    echo "\nChecking database tables:\n";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
            
            // Count records
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   - Records: $count\n";
        } else {
            echo "❌ Table '$table' does not exist\n";
        }
    }
    
    echo "\nDatabase connection test completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    
    echo "\nPossible solutions:\n";
    echo "1. Make sure MySQL server is running (check XAMPP control panel)\n";
    echo "2. Verify database configuration in 'config/database.php'\n";
    echo "3. Run the database initialization script: php config/init_db.php\n";
} 