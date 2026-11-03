<?php
// api/get_content.php
// Returns the full store page HTML only to sessions that passed the device filter.
// Used by the shell page's AJAX call after JS fingerprint verification succeeds.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['_device_verified'])) {
    http_response_code(403);
    echo '';
    exit;
}

require_once __DIR__ . '/../db_config.php';

$view = $_GET['view'] ?? 'main';
$url_redirecionamento = 'https://vt.tiktok.com/ZS9UkARU7enBp-Ww30g/';

define('INCLUDED_BY_RENDER', true);

// render_page.php outputs the full HTML document
include __DIR__ . '/../render_page.php';
