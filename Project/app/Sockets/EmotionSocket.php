<?php

namespace App\Sockets;

class EmotionSocket
{
    public function handleSignal($command, $users)
    {
        foreach ($users as $user) {
            // Each user plays a random sample with varying intensity
            echo "Playing sound for {$user->id} with intensity {$user->getIntensity()}";
        }
    }
}
