<?php

/**
 * Database Initialization Script
 * 
 * This script creates the necessary database and tables for the application.
 * Run this script once to set up the database structure.
 */

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
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE $collation");
    echo "Database '$dbName' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE `$dbName`");
    echo "Using database '$dbName'.\n";
    
    // Execute SQL schema
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "Database schema created successfully.\n";
    echo "Database initialization completed successfully!\n";
    
    echo "\nNext steps:\n";
    echo "2. Update your application configuration if needed.\n";
    
} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
} 