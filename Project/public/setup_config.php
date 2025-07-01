<?php
/**
 * Database Configuration Setup
 * 
 * This file allows users to configure database connection settings.
 */

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $config_file = __DIR__ . '/../config/database.php';
    
    $new_config = [
        'host' => $_POST['host'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'database' => $_POST['database'],
        'charset' => $_POST['charset'],
        'port' => (int)$_POST['port'],
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
    
    // Create PHP code for the config file
    $config_content = "<?php\n\nreturn " . var_export($new_config, true) . ";\n";
    
    // Write to the config file
    if (file_put_contents($config_file, $config_content)) {
        $success_message = "Configuration saved successfully!";
    } else {
        $error_message = "Failed to save configuration file. Check file permissions.";
    }
}

// Load current config
$config_file = __DIR__ . '/../config/database.php';
if (file_exists($config_file)) {
    $config = require $config_file;
} else {
    $config = [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'audience_reaction_app',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
}

// Header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Database Configuration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .warning { color: orange; }
        h1 { color: #333; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="number"] { 
            width: 100%; 
            padding: 8px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background: #45a049;
        }
        .form-group { margin-bottom: 15px; }
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
        .note { 
            background: #fff3cd; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 20px 0; 
            border-left: 5px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Configuration</h1>
        <hr>
        
        <div class="tool-menu">
            <a href="setup.php">Setup Menu</a>
            <a href="setup.php?tool=test">Test Connection</a>
            <a href="/">Back to Home</a>
        </div>
        
        <hr>';

// Display messages
if (isset($success_message)) {
    echo '<div class="success">' . $success_message . '</div>';
}

if (isset($error_message)) {
    echo '<div class="error">' . $error_message . '</div>';
}

// Configuration form
echo '<form method="post" action="setup_config.php">
    <div class="form-group">
        <label for="host">Database Host:</label>
        <input type="text" id="host" name="host" value="' . htmlspecialchars($config['host']) . '" required>
    </div>
    
    <div class="form-group">
        <label for="port">Port:</label>
        <input type="number" id="port" name="port" value="' . $config['port'] . '" required>
    </div>
    
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="' . htmlspecialchars($config['username']) . '" required>
    </div>
    
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" value="' . htmlspecialchars($config['password']) . '">
    </div>
    
    <div class="form-group">
        <label for="database">Database Name:</label>
        <input type="text" id="database" name="database" value="' . htmlspecialchars($config['database']) . '" required>
    </div>
    
    <div class="form-group">
        <label for="charset">Charset:</label>
        <input type="text" id="charset" name="charset" value="' . htmlspecialchars($config['charset']) . '" required>
    </div>
    
    <input type="submit" name="update_config" value="Save Configuration">
</form>

<div class="note">
    <p><strong>Common XAMPP MySQL Settings:</strong></p>
    <ul>
        <li>Host: localhost</li>
        <li>Port: 3306</li>
        <li>Username: root</li>
        <li>Password: (may be empty or set during XAMPP installation)</li>
    </ul>
    <p>If you\'re getting "Access Denied" errors, try the following:</p>
    <ul>
        <li>Double check your MySQL root password (may be empty by default)</li>
        <li>Make sure MySQL service is running in XAMPP control panel</li>
        <li>Try using "127.0.0.1" instead of "localhost" as the host</li>
    </ul>
</div>

</div>
</body>
</html>'; 