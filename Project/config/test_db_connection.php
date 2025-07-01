<?php

/**
 * Database Connection Test Script
 * 
 * This script tests the connection to the MySQL server and reports any issues.
 * Use this to diagnose connection problems.
 */

// Check if already in a web page context (when included from setup.php)
$inPageContext = isset($tool);

// Check if running in browser or CLI
$isBrowser = php_sapi_name() !== 'cli';
if ($isBrowser && !$inPageContext) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            h1 { color: #333; }
            hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
            pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow: auto; }
            .next-steps { background: #e9f7ef; padding: 15px; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Database Connection Test</h1>
            <hr>";
}

function test_output($message, $type = 'info') {
    global $isBrowser, $inPageContext;
    
    if ($isBrowser) {
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : ''));
        echo "<p" . ($class ? " class='$class'" : "") . ">$message</p>";
    } else {
        echo $message . "\n";
    }
}

// Load configuration
if (file_exists(__DIR__ . '/database.php')) {
    test_output("✓ Database configuration file found", 'success');
    $config = require __DIR__ . '/database.php';
} else {
    test_output("✗ Database configuration file not found at " . __DIR__ . '/database.php', 'error');
    exit(1);
}

// Check MySQL service
test_output("Testing MySQL connection to {$config['host']}:{$config['port']}...");

// Test connection without database
try {
    $dsn = "mysql:host={$config['host']};charset={$config['charset']};port={$config['port']}";
    $pdo = new PDO(
        $dsn, 
        $config['username'], 
        $config['password'], 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    test_output("✓ Successfully connected to MySQL server", 'success');
    
    // Check if the database exists
    test_output("Checking if database '{$config['database']}' exists...");
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['database']}'");
    $database_exists = $stmt->fetchColumn();
    
    if ($database_exists) {
        test_output("✓ Database '{$config['database']}' exists", 'success');
        
        // Connect to the specific database
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']};port={$config['port']}";
            $db_pdo = new PDO(
                $dsn, 
                $config['username'], 
                $config['password'], 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            test_output("✓ Successfully connected to '{$config['database']}' database", 'success');
            
            // Check if essential tables exist
            $tables_to_check = ['users', 'commands', 'settings'];
            $missing_tables = [];
            
            foreach ($tables_to_check as $table) {
                $stmt = $db_pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() === 0) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                test_output("✓ All essential tables found", 'success');
                
                // Test a simple query
                try {
                    $stmt = $db_pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'sim_audience_on' LIMIT 1");
                    $value = $stmt->fetchColumn();
                    test_output("✓ Successfully ran a test query", 'success');
                } catch (PDOException $e) {
                    test_output("✗ Error running test query: " . $e->getMessage(), 'error');
                }
                
            } else {
                test_output("✗ Some essential tables are missing: " . implode(", ", $missing_tables), 'warning');
                
                if ($inPageContext) {
                    test_output("You need to run the database initialization script: <a href='setup.php?tool=init'>Initialize Database</a>", 'warning');
                } else {
                    test_output("You need to run the database initialization script: <a href='init_db.php'>Initialize Database</a>", 'warning');
                }
            }
            
        } catch (PDOException $e) {
            test_output("✗ Error connecting to the specific database: " . $e->getMessage(), 'error');
        }
        
    } else {
        test_output("✗ Database '{$config['database']}' does not exist", 'warning');
        
        if ($inPageContext) {
            test_output("You need to run the database initialization script: <a href='setup.php?tool=init'>Initialize Database</a>", 'warning');
        } else {
            test_output("You need to run the database initialization script: <a href='init_db.php'>Initialize Database</a>", 'warning');
        }
    }
    
} catch (PDOException $e) {
    test_output("✗ MySQL connection failed: " . $e->getMessage(), 'error');
    
    // Check if this is a common error and provide suggestions
    if (strpos($e->getMessage(), "Connection refused") !== false) {
        test_output("Possible causes:", 'warning');
        test_output("- MySQL service is not running", 'warning');
        test_output("- MySQL is running on a different port", 'warning');
        test_output("Solution: Make sure MySQL is started from your XAMPP control panel", 'warning');
    }
    else if (strpos($e->getMessage(), "Access denied") !== false) {
        test_output("Possible causes:", 'warning');
        test_output("- Wrong username or password", 'warning');
        test_output("Solution: Check your database credentials in config/database.php", 'warning');
    }
}

if ($isBrowser && !$inPageContext) {
    echo "<div class='next-steps'>";
    echo "<h2>Next Steps:</h2>";
    echo "<ul>";
    echo "<li>If all tests passed, you can <a href='../'>return to the application</a>.</li>";
    echo "<li>If you see warnings about missing database or tables, <a href='init_db.php'>run the initialization script</a>.</li>";
    echo "<li>If there are connection errors, check that MySQL is running and the credentials are correct.</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div></body></html>";
} else if ($isBrowser && $inPageContext) {
    echo "<div class='next-steps'>";
    echo "<h2>Next Steps:</h2>";
    echo "<ul>";
    echo "<li>If all tests passed, you can <a href='/'>return to the application</a>.</li>";
    echo "<li>If you see warnings about missing database or tables, <a href='setup.php?tool=init'>run the initialization script</a>.</li>";
    echo "<li>If there are connection errors, check that MySQL is running and the credentials are correct.</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "\nTest completed.\n";
} 