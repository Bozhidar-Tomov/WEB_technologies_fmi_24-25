<?php
// public/setup.php

$script = __DIR__ . '/../update_htaccess.php';
if (file_exists($script)) {
    ob_start();
    include $script;
    $output = ob_get_clean();
    
    // Show the output briefly
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    echo '<p>RewriteBase updated! Redirecting to public directory...</p>';
    
    // Detect the correct redirect path
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $redirectPath = $scriptName !== '/' ? $scriptName : '';
    
    // Redirect to the public directory after a short delay
    echo '<script>
        setTimeout(function() {
            window.location.href = "' . $redirectPath . '";
        }, 1000);
    </script>';
    
    echo '<p><a href="' . $redirectPath . '">Click here if you are not redirected automatically</a></p>';
} else {
    echo 'update_htaccess.php not found.';
} 