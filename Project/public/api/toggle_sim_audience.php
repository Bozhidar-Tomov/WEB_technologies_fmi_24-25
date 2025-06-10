<?php
$flagFile = __DIR__ . '/../../app/Database/sim_audience_on.flag';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sim_audience'])) {
        file_put_contents($flagFile, 'on');
        echo json_encode(['success' => true, 'enabled' => true]);
        exit;
    } else {
        if (file_exists($flagFile)) unlink($flagFile);
        echo json_encode(['success' => true, 'enabled' => false]);
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $enabled = file_exists($flagFile);
    echo json_encode(['success' => true, 'enabled' => $enabled]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request']); 