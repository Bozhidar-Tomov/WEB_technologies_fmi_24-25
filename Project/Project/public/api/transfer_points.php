<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Models/User.php';
use App\Models\User;

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

$users = User::loadUsers();
if (!isset($users[$fromUserId])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Sender not found']);
    exit;
}
$fromUser = $users[$fromUserId];
if (($fromUser['points'] ?? 0) < $amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Insufficient points']);
    exit;
}
// Find recipient by username
$toUserId = null;
foreach ($users as $uid => $u) {
    if (isset($u['username']) && strtolower($u['username']) === strtolower($toUsername)) {
        $toUserId = $uid;
        break;
    }
}
if (!$toUserId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Recipient not found']);
    exit;
}
// Transfer points
$result = User::transferPoints($fromUserId, $toUsername, $amount);
// Optionally log the transfer
if ($result['success']) {
    $logFile = __DIR__ . '/../../app/Database/point_transfers.log';
    $logEntry = date('c') . " | from: $fromUserId | to: $toUsername | amount: $amount | message: $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
echo json_encode($result); 