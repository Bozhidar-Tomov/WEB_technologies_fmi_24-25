<?php

// Check if running in browser or CLI
$isBrowser = php_sapi_name() !== 'cli';
if ($isBrowser) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Verification</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            h1, h2 { color: #333; }
            hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table, th, td { border: 1px solid #ddd; }
            th, td { padding: 10px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Database Verification</h1>
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

try {
    // Connect to the database
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    output("Connected to database successfully.", 'success');
    
    // Get list of all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    output("Found " . count($tables) . " tables in the database.", 'success');
    
    if ($isBrowser) {
        echo "<h2>Tables</h2>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Rows</th></tr>";
    } else {
        echo "\nTables:\n";
        echo "--------------------------------\n";
        echo "Table Name\tRows\n";
        echo "--------------------------------\n";
    }
    
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $rowCount = $countStmt->fetchColumn();
        
        if ($isBrowser) {
            echo "<tr><td>{$table}</td><td>{$rowCount}</td></tr>";
        } else {
            echo "{$table}\t{$rowCount}\n";
        }
    }
    
    if ($isBrowser) {
        echo "</table>";
    } else {
        echo "--------------------------------\n";
    }
    
    // Specifically check user_categories table
    if (in_array('user_categories', $tables)) {
        output("Found user_categories table.", 'success');
        
        // Show structure of user_categories table
        $stmt = $pdo->query("DESCRIBE user_categories");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($isBrowser) {
            echo "<h2>Structure of user_categories table</h2>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        } else {
            echo "\nStructure of user_categories table:\n";
            echo "--------------------------------\n";
            echo "Field\tType\tNull\tKey\tDefault\tExtra\n";
            echo "--------------------------------\n";
        }
        
        foreach ($columns as $column) {
            if ($isBrowser) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            } else {
                echo "{$column['Field']}\t{$column['Type']}\t{$column['Null']}\t{$column['Key']}\t{$column['Default']}\t{$column['Extra']}\n";
            }
        }
        
        if ($isBrowser) {
            echo "</table>";
        } else {
            echo "--------------------------------\n";
        }
    } else {
        output("user_categories table not found!", 'error');
    }
    
} catch (PDOException $e) {
    output("Database verification failed: " . $e->getMessage(), 'error');
}

if ($isBrowser) {
    echo "
        <hr>
        <p><a href='../'>Back to Home</a></p>
        </div>
    </body>
    </html>";
} 