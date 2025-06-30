<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/ValidationService.php';
require_once __DIR__ . '/../Services/CommandService.php';

use App\Services\CommandService;

class AdminController extends BaseController
{   
    private function isAdmin()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = ['Unauthorized access'];
            http_response_code(403);
            header('Location: /');
            exit;
        }
    }
    
    public function index()
    {
        $this->isAdmin();
        $this->render('admin', data: ['title' => 'Admin Panel', 'user' => $_SESSION]);
    }
    
    public function broadcast()
    {
        $this->isAdmin();
        $type = $_POST['type'] ?? '';
        
        if (empty($type)) {
            $_SESSION['error'] = ['Command type is required'];
            header('Location: /admin');
            exit;
        }
        
        $commandData = [
            'id' => uniqid('cmd_'),
            'type' => $type,
            'intensity' => (int)($_POST['intensity'] ?? 50),
            'duration' => (int)($_POST['duration'] ?? 5),
            'countdown' => (int)($_POST['countdown'] ?? 3),
            'groups' => $_POST['groups'] ?? '',
            'message' => $_POST['message'] ?? '',
            'timestamp' => time()
        ];
        
        try {
            $commandService = new CommandService();
            $success = $commandService->broadcastCommand($commandData);
            
            if ($success) {
                $_SESSION['success'] = ['Command broadcast successfully'];
                $_SESSION['last_command'] = $commandData;
            } else {
                $_SESSION['error'] = ['Failed to broadcast command'];
                error_log("AdminController: Failed to broadcast command: " . json_encode($commandData));
            }
        } catch (Exception $e) {
            $_SESSION['error'] = ['Error broadcasting command: ' . $e->getMessage()];
            error_log("AdminController Exception: " . $e->getMessage());
        }
        
        header('Location: /admin');
        exit;
    }
}