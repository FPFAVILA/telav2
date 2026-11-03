<?php
// Arquivo: process_cpf.php
// Objetivo: Receber o CPF via POST (AJAX) e salvá-lo na sessão do PHP.

// Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define o cabeçalho para retornar uma resposta JSON
header('Content-Type: application/json');

// Verifica se a sessão foi verificada pelo filtro de dispositivo
if (empty($_SESSION['_device_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {

    $cpf = trim($_POST['cpf']);

    // Validação de CPF (O JS já faz a validação principal, mas esta é uma boa prática de segurança)
    if (preg_match('/^\d{11}$/', $cpf)) {

        // Salva o CPF na sessão (sem mencionar no front-end)
        $_SESSION['user_cpf'] = $cpf;

        // Retorna sucesso com mensagem simples
        // 5. AJUSTE: Mensagem de sucesso simples para o popup
        echo json_encode(['success' => true, 'message' => 'CPF processado com sucesso.']);
        exit;

    } else {
        // Retorna erro se o formato não estiver correto
        echo json_encode(['success' => false, 'message' => 'Formato de CPF inválido.']);
        exit;
    }
}

// Retorna erro se a requisição for inválida
echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
?>