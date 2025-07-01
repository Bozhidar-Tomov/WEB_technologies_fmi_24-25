<?php
/**
 * Database Setup Bridge
 * 
 * This file serves as a bridge to access database setup tools from the web server
 * when the public directory is set as the document root.
 */

// Available tools
$tool = $_GET['tool'] ?? 'menu';

// Header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Database Setup Tools</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h1, h2 { color: #333; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow: auto; }
        .next-steps { background: #e9f7ef; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .tool-menu { display: flex; gap: 10px; margin: 20px 0; }
        .tool-menu a { 
            display: inline-block; 
            padding: 10px 15px; 
            background: #007bff; 
            color: white; 
            text-decoration: none;
            border-radius: 4px;
        }
        .tool-menu a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup Tools</h1>
        <hr>
        
        <div class="tool-menu">
            <a href="setup.php?tool=init">Initialize Database</a>
            <a href="setup.php?tool=test">Test Connection</a>
            <a href="/">Back to Home</a>
        </div>
        <hr>';

// Handle tool selection
switch ($tool) {
    case 'init':
        // Include the database initialization script
        include_once __DIR__ . '/../config/init_db.php';
        break;
        
    case 'test':
        // Include the database test script
        include_once __DIR__ . '/../config/test_db_connection.php';
        break;
        
    default:
        // Menu / instructions
        echo '<h2>Database Setup Instructions</h2>';
        echo '<p>Use these tools to set up and test your database connection:</p>';
        echo '<ol>';
        echo '<li><strong>Test Connection</strong>: Check if your database connection is working correctly</li>';
        echo '<li><strong>Initialize Database</strong>: Create the database and required tables</li>';
        echo '</ol>';
        
        echo '<p class="warning">Note: Make sure MySQL is running in your XAMPP control panel before using these tools.</p>';
        
        echo '<div class="next-steps">';
        echo '<h2>Setup Process:</h2>';
        echo '<ol>';
        echo '<li>First, click "Test Connection" to verify your MySQL connection</li>';
        echo '<li>Then, click "Initialize Database" to create the database and tables</li>';
        echo '<li>Finally, return to the home page to start using the application</li>';
        echo '</ol>';
        echo '</div>';
}

// Footer
echo '</div>
</body>
</html>'; 