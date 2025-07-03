<?php
// public/setup.php

$script = __DIR__ . '/../update_htaccess.php';
if (file_exists($script)) {
    ob_start();
    include $script;
    $output = ob_get_clean();
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    echo '<p>RewriteBase updated! You can now delete this file for security.</p>';
} else {
    echo 'update_htaccess.php not found.';
} 