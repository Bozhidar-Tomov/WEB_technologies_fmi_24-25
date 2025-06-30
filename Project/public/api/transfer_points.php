<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Database/Database.php';

use App\Models\User;
use App\Database\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$fromUserId = $data['fromUserId'] ?? '';
$toUsername = trim($data['toUsername'] ?? '');
$amount = (int)($data['amount'] ?? 0);
$message = trim($data['message'] ?? '');

if (!$fromUserId || !$toUsername || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if sender exists and has enough points
    $stmt = $db->query(
        "SELECT id, points FROM users WHERE id = ?",
        [$fromUserId]
    );
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Sender not found']);
        exit;
    }
    
    if ($sender['points'] < $amount) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Insufficient points']);
        exit;
    }
    
    // Find recipient by username
    $stmt = $db->query(
        "SELECT id FROM users WHERE LOWER(username) = LOWER(?)",
        [$toUsername]
    );
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recipient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recipient not found']);
        exit;
    }
    
    // Transfer points
    $result = User::transferPoints($fromUserId, $toUsername, $amount);
    
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} 