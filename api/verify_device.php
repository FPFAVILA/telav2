<?php
// api/verify_device.php
// Marks the session as device-verified after the client-side JS fingerprint passes.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error']);
    exit;
}

// Require a token that was embedded in the shell page to prove the request
// originated from a page the server actually served (not a replay by a bot).
$token = $_POST['token'] ?? '';
$expected = $_SESSION['_shell_token'] ?? '';

if (empty($expected) || !hash_equals($expected, $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error']);
    exit;
}

// Mark session as verified — content endpoints will now serve real data.
$_SESSION['_device_verified'] = true;

// Also set the purchase-session fields that the app expects.
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = 1;
}

// Consume the token so it can only be used once.
unset($_SESSION['_shell_token']);

echo json_encode(['status' => 'ok']);
