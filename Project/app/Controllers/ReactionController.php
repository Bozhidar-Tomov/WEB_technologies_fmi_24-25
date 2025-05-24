<?php
require_once __DIR__ . '/BaseController.php';

class ReactionController extends BaseController
{
    public function sendCommand($command, $targetGroup = null)
    {
        // Example: Use ReactionSocket to broadcast
        $socket = new \App\Sockets\ReactionSocket();
        $socket->broadcastCommand($command, $targetGroup);
        // Optionally log or store command
    }
    public function simulateReaction($reactionType, $intensity = 50)
    {
        // Example: Simulate audience reaction (randomized playback)
        // This could trigger a simulation event to all clients
        $socket = new \App\Sockets\ReactionSocket();
        $socket->broadcastCommand("simulate:$reactionType", null);
    }
}
