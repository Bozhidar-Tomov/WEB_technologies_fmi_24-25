<?php
require_once __DIR__ . '/BaseController.php';

class RoomController extends BaseController
{
    public function showRoom()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        $user = $_SESSION['user'];
        $roomData = [
            'title' => 'Room View - Audience Control',
            'id' => $user['id'],
            'username' => htmlspecialchars($user['username']),
            'role' => $user['role'] ?? '',
            'points' => $user['points'] ?? 0,
            'groups' => $user['groups'] ?? [],
            'tags' => $user['tags'] ?? [],
            'gender' => $user['gender'] ?? '',
        ];
        $this->render('room', $roomData);
    }
} 