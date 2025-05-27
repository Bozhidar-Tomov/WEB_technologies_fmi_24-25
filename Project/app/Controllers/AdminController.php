<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/ValidationService.php';

class AdminController extends BaseController
{
    public function index()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            $this->render('login');
            exit;
        }
        $this->render('admin', $_SESSION);
    }

    public function sendCommand()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $command = [
            'type' => $_POST['type'] ?? '',
            'intensity' => (int)($_POST['intensity'] ?? 50),
            'duration' => (int)($_POST['duration'] ?? 5),
            'targetGroups' => isset($_POST['groups']) ? explode(',', $_POST['groups']) : [],
            'countdown' => (int)($_POST['countdown'] ?? 3),
            'message' => $_POST['message'] ?? ''
        ];

        // Validate command
        if (empty($command['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Command type is required']);
            exit;
        }

        // TODO: Implement WebSocket broadcast later
        // For now, just return success
        echo json_encode([
            'success' => true,
            'command' => $command
        ]);
    }

    public function showPanel()
    {
        // TODO: Render admin panel view
        include_once __DIR__ . '/../Views/admin.php';
    }
}