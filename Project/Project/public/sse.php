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
        $commandService->registerActiveUser($userId);
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
    // Get the active command using the CommandService
    $command = $commandService->getActiveCommand();
    
    if (!$command) {
        return null;
    }
    
    // Check if this is a new command the user hasn't seen
    $commandId = $command['id'] ?? '';
    if (empty($commandId)) {
        return null;
    }
    
    // Check if the command has expired based on its timestamp and duration
    $timestamp = $command['timestamp'] ?? 0;
    $duration = $command['duration'] ?? 0;
    $currentTime = time();
    
    if ($duration > 0 && $currentTime > ($timestamp + $duration)) {
        // Command has expired, don't show it
        return null;
    }
    
    return $command;
} 