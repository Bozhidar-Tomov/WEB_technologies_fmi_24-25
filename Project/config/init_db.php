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
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : '');
        echo "<p" . ($class ? " class='$class'" : "") . ">$message</p>";
    } else {
        echo $message . "\n";
    }
}

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$charset = 'utf8mb4';
$collation = 'utf8mb4_unicode_ci';
$dbName = 'audience_reaction_app';

try {
    // Connect to MySQL server without selecting a database
    $pdo = new PDO(
        "mysql:host=$host;charset=$charset",
`        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    output("Connected to MySQL server successfully.", 'success');
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE $collation");
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