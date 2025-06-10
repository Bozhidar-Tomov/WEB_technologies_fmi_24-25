<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Models/User.php';
use App\Models\User;

function log_mic_error($msg, $data = null) {
    $logFile = __DIR__ . '/../../app/Database/mic_results_errors.log';
    $entry = date('c') . ' | ' . $msg;
    if ($data !== null) {
        $entry .= ' | ' . json_encode($data);
    }
    file_put_contents($logFile, $entry . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    log_mic_error('Method not allowed', $_SERVER);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$required = ['userId', 'commandId', 'intensity', 'volume', 'reactionAccuracy'];
$missing = [];
foreach ($required as $field) {
    if (!array_key_exists($field, $data)) {
        $missing[] = $field;
    }
}
if (!$data || count($missing) > 0) {
    http_response_code(400);
    log_mic_error('Missing required fields: ' . implode(',', $missing), $data);
    echo json_encode(['success' => false, 'error' => 'Missing required fields', 'missing' => $missing]);
    exit;
}

$resultsFile = __DIR__ . '/../../app/Database/mic_results.json';
if (!is_dir(dirname($resultsFile))) {
    mkdir(dirname($resultsFile), 0777, true);
}

$results = [];
if (file_exists($resultsFile)) {
    $json = file_get_contents($resultsFile);
    $results = json_decode($json, true) ?: [];
}

$key = $data['userId'] . '_' . $data['commandId'];
$results[$key] = [
    'userId' => $data['userId'],
    'commandId' => $data['commandId'],
    'intensity' => $data['intensity'],
    'volume' => $data['volume'],
    'reactionAccuracy' => $data['reactionAccuracy'],
    'timestamp' => time()
];

file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));

// Award points
$basePoints = 5;
$accuracyBonus = round($data['reactionAccuracy'] * 0.5); // up to 50
// Get target intensity from active command
$activeCmdFile = __DIR__ . '/../../app/Database/commands/active_command.json';
$intensityBonus = 0;
$simAudienceFlag = __DIR__ . '/../../app/Database/sim_audience_on.flag';
if (file_exists($activeCmdFile)) {
    $activeCmd = json_decode(file_get_contents($activeCmdFile), true);
    $targetIntensity = isset($activeCmd['intensity']) ? (int)$activeCmd['intensity'] : 0;
    $userIntensity = isset($data['intensity']) ? (int)$data['intensity'] : 0;
    $diff = abs($userIntensity - $targetIntensity);
    // More forgiving: within 10 gets most points, falls off gently
    $intensityBonus = max(0, 45 - round(($diff / 100) * 45 * 1.5));
}
if (file_exists($simAudienceFlag)) {
    $totalPoints = $basePoints + $accuracyBonus + $intensityBonus;
} else {
    $totalPoints = $basePoints + $accuracyBonus;
}

// Add points to user
User::addPoints($data['userId'], $totalPoints);

// Get new points value
$newPoints = 0;
$users = User::loadUsers();
if (isset($users[$data['userId']])) {
    $newPoints = $users[$data['userId']]['points'];
}

echo json_encode(['success' => true, 'points' => $newPoints]); 