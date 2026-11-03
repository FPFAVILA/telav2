<?php
// api/get_comments.php
// Serves comments.json only to sessions that passed the device filter.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['_device_verified'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$file = __DIR__ . '/../comments.json';
if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

echo file_get_contents($file);
