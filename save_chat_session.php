<?php
session_start();
header('Content-Type: application/json');

// Verifica se a sessão foi verificada pelo filtro de dispositivo
if (empty($_SESSION['_device_verified'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

// Recebe o JSON do JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (isset($data['sender_type']) && isset($data['message_content'])) {
    $new_message = [
        'sender_type' => $data['sender_type'],
        'message_content' => $data['message_content'],
        'created_at' => date('Y-m-d H:i:s') // Adiciona timestamp
    ];

    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }

    // Adiciona a nova mensagem ao histórico da sessão
    $_SESSION['chat_history'][] = $new_message;

    // Resposta de sucesso (opcional)
    echo json_encode(['status' => 'success', 'message' => 'Mensagem salva na sessão.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
?>