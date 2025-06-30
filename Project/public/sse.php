<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

set_time_limit(0); // No time limit
if (ob_get_level()) ob_end_clean();

if (!isset($_SESSION['user'])) {
    echo "event: error\n";
    echo "data: {\"message\": \"Unauthorized\"}\n\n";
    flush();
    exit;
}

require_once __DIR__ . '/../app/Services/CommandService.php';
use App\Services\CommandService;

$userId = $_SESSION['user']['id'];

$commandService = new CommandService();
$commandService->registerActiveUser($userId);

echo "event: connected\n";
echo "data: {\"message\": \"Connected to SSE server\", \"userId\": \"" . $userId . "\"}\n\n";
flush();

// Close session to prevent blocking other requests
session_write_close();

// Keep connection alive with periodic heartbeats
$lastEventTime = time();
$lastCheckTime = time();
$lastActiveUpdateTime = time();

// The script will run for 30 minutes max before client needs to reconnect
$endTime = time() + (30 * 60);

while (time() < $endTime) {
    // Update active status every 15 seconds
    if (time() - $lastActiveUpdateTime >= 15) {
        // Ensure user still exists before registering them as active
        $success = $commandService->registerActiveUser($userId);
        if (!$success) {
            // If user registration fails, exit the loop and terminate the connection
            echo "event: error\n";
            echo "data: {\"message\": \"User session invalid\"}\n\n";
            flush();
            break;
        }
        $lastActiveUpdateTime = time();
    }
    
    // Check for new commands every 2 seconds to reduce file system load
    if (time() - $lastCheckTime >= 2) {
        $command = checkForNewCommands($commandService, $userId);
        $lastCheckTime = time();
        
        if ($command) {
            echo "event: command\n";
            echo "data: " . json_encode($command) . "\n\n";
            flush();
            $lastEventTime = time();
        }
    }
    
    // Send heartbeat every 15 seconds
    if (time() - $lastEventTime >= 15) {
        $activeUsers = $commandService->getActiveUserCount();
        echo "event: heartbeat\n";
        echo "data: {\"time\": " . time() . ", \"activeUsers\": " . $activeUsers . "}\n\n";
        flush();
        $lastEventTime = time();
    }
    
    // Sleep to prevent CPU hogging
    usleep(500000); // Sleep for 0.5 seconds instead of 1 second for more responsive updates
}

$commandService->removeActiveUser($userId);


function checkForNewCommands($commandService, $userId) {
    try {
        // Get the active command using the CommandService
        $command = $commandService->getActiveCommand();
        
        if (!$command || !is_array($command)) {
            return null;
        }
        
        // Check if this is a new command the user hasn't seen
        $commandId = $command['id'] ?? '';
        if (empty($commandId)) {
            error_log("Active command missing ID");
            return null;
        }
        
        // Ensure all required fields exist
        $required = ['type', 'intensity', 'timestamp'];
        foreach ($required as $field) {
            if (!isset($command[$field])) {
                error_log("Active command missing required field: $field");
                return null;
            }
        }
        
        // Format command data for frontend
        $command['duration'] = $command['duration'] ?? 5;
        $command['countdown'] = $command['countdown'] ?? 3;
        $command['message'] = $command['message'] ?? '';
        $command['groups'] = $command['groups'] ?? '';
        
        // Check if the command has expired
        $timestamp = (int)$command['timestamp'];
        $duration = (int)$command['duration'];
        $currentTime = time();
        
        if ($duration > 0 && $currentTime > ($timestamp + $duration + 10)) {
            // Command has expired (with 10s buffer), don't show it
            return null;
        }
        
        return $command;
    } catch (\Exception $e) {
        error_log("Error in checkForNewCommands: " . $e->getMessage());
        return null;
    }
} 