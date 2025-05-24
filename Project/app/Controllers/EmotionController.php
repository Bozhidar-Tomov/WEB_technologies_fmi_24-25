<?php

require_once __DIR__ . '/BaseController.php';

class EmotionController extends BaseController
{
    public function showInterface()
    {
        $this->render('emotion', ['title' => 'Emotion Director']);
    }

    public function sendCommand()
    {
        // Get POST data
        $command = $_POST['command'] ?? null;
        $group = $_POST['group'] ?? 'all';
        $intensity = $_POST['intensity'] ?? 50;
        // TODO: Broadcast to sockets, save to DB, etc.
        // For now, just redirect back to the interface
        header('Location: /emotion');
        exit();
    }
}
