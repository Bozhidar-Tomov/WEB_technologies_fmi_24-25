<?php

/**
 * Application Configuration
 * 
 * This file contains the main configuration settings for the application
 */

return [
    // Server settings
    'server' => [
        'port' => 8080,            // The port on which the application runs
        'host' => 'localhost',     // The host on which the application runs
    ],
    
    // Application settings
    'app' => [
        'base_path' => '',         // Base path if app is not running in root directory
        'debug' => true,           // Whether the application is in debug mode
    ],
    
    // Session settings
    'session' => [
        'name' => 'room_app',      // Session name
        'lifetime' => 3600,        // Session lifetime in seconds
    ],
]; 