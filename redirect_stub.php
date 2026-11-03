<?php
// redirect_stub.php — minimal redirect page for blocked direct access attempts.
// Included by content files when accessed directly (not via index.php/render_page.php).
http_response_code(403);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>Acesso Negado</title></head><body style="margin:0;background:#F5F5F5;"></body></html>';
