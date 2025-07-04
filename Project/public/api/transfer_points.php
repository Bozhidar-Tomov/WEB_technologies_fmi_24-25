<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Database/Database.php';

use App\Models\User;
use App\Database\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Determine request data source (supports JSON fetch as well as regular form submission)
$rawInput = file_get_contents('php://input');
$requestData = [];
if ($rawInput && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $requestData = $decoded;
    }
}
// Fallback to traditional form data if JSON not provided
if (empty($requestData)) {
    $requestData = $_POST;
}

$fromUserId = $_SESSION['user']['id'];
$toUsername = trim($requestData['recipient'] ?? '');
$amount     = isset($requestData['amount']) ? (int)$requestData['amount'] : 0;
$message    = trim($requestData['message'] ?? '');

// Collect validation errors for better feedback
$validationErrors = [];
if (!$toUsername) {
    $validationErrors[] = 'recipient';
}
if ($amount <= 0) {
    $validationErrors[] = 'amount';
}

if (!empty($validationErrors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing or invalid fields',
        'fields'  => $validationErrors
    ]);
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
    
    if ($result['success']) {
        // Get updated points balance
        $stmt = $db->query(
            "SELECT points FROM users WHERE id = ?",
            [$fromUserId]
        );
        $updatedSender = $stmt->fetch(PDO::FETCH_ASSOC);
        $newBalance = $updatedSender['points'];
        
        // Update session data
        $_SESSION['user']['points'] = $newBalance;
        
        $result['newBalance'] = $newBalance;
        $result['message'] = "Successfully transferred $amount points to $toUsername";
    }
    
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}