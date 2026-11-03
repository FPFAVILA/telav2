<?php
// api/check_payment.php
// Script de polling simples para verificar o status na ZeroOne.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');

// --- 0. Validação de Sessão ---
// (Mantendo a segurança do seu arquivo de referência)
if (empty($_SESSION['_device_verified']) || !isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id']) || !isset($_GET['pedido_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'ERRO', 'message' => 'Acesso não autorizado.']);
    exit;
}

$pedido_id = (int)$_GET['pedido_id'];
$user_id_sessao = (int)$_SESSION['user_id'];

// --- 1. Conexão e Configs ---
require_once '../db_config.php';

$zeroone_config = [];
try {
    // Usando 'config_gateway' como no resto do projeto
    $stmt_config = $pdo->query("SELECT chave, valor FROM config_gateway WHERE chave LIKE 'zeroone_%'");
    $zeroone_config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erro check_payment_status (config): " . $e->getMessage());
    echo json_encode(['status' => 'ERRO_DB', 'message' => 'Erro interno ao ler config.']);
    exit;
}

$ZEROONE_API_TOKEN = $zeroone_config['zeroone_api_token'] ?? null;
$ZEROONE_API_URL = $zeroone_config['zeroone_api_url'] ?? null;

// --- 2. Lógica Principal de Verificação ---
try {

    // Busca o pedido no NOSSO banco
    $stmt = $pdo->prepare("
        SELECT status, gateway_txid
        FROM pedidos
        WHERE id = :pedido_id AND user_id = :user_id
    ");
    $stmt->execute([
        ':pedido_id' => $pedido_id,
        ':user_id' => $user_id_sessao
    ]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['status' => 'NAO_ENCONTRADO', 'message' => 'Pedido não encontrado.']);
        exit;
    }

    // Se o status local já está APROVADO, apenas retorne.
    if ($pedido['status'] === 'APROVADO') {
        echo json_encode(['status' => 'APROVADO']);
        exit();
    }

    // Se o status ainda for PENDENTE, vamos verificar na ZeroOne
    if ($pedido['status'] === 'PENDENTE') {

        if (empty($ZEROONE_API_TOKEN) || empty($ZEROONE_API_URL) || empty($pedido['gateway_txid'])) {
            throw new Exception("Configuração da ZeroOnePay incompleta ou TXID do pedido não encontrado.");
        }

        // Usa o 'hash' (gateway_txid) para consultar a API
        $txid = $pedido['gateway_txid'];

        // URL CORRETA DA DOC (com /api/public/)
        $api_url = rtrim($ZEROONE_API_URL, '/') . "/api/public/v1/transactions/{$txid}?api_token={$ZEROONE_API_TOKEN}";

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status !== 200) {
            // Se a API falhar, não faz nada, só retorna PENDENTE
            error_log("Falha ao consultar ZeroOne (Pedido $pedido_id). HTTP: {$http_status}");
            echo json_encode(['status' => 'PENDENTE']); // Tenta de novo em 3s
            exit;
        }

        $responseData = json_decode($response, true);
        $api_status = $responseData['payment_status'] ?? 'UNKNOWN';

        // Se a API da ZeroOne retornar 'paid' (APROVADO)
        if (strtolower($api_status) === 'paid') {

            // ATUALIZA O STATUS DO PEDIDO no nosso banco
            $stmtUpdate = $pdo->prepare("UPDATE pedidos SET status = 'APROVADO' WHERE id = ? AND status = 'PENDENTE'");
            $stmtUpdate->execute([$pedido_id]);

            // (Não temos lógica de e-mail neste projeto simples)

            // Retorna o novo status para o frontend
            echo json_encode(['status' => 'APROVADO']);
            exit;

        } else {
            // Se a API retornar 'pending', 'expired', etc.
            echo json_encode(['status' => 'PENDENTE']);
            exit;
        }
    }

    // Se o status for qualquer outra coisa (CANCELADO, etc.), apenas retorne
    echo json_encode(['status' => $pedido['status']]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro check_payment_status: " . $e->getMessage());
    echo json_encode(['status' => 'ERRO_DB', 'message' => 'Erro interno do servidor.']);
}
?>