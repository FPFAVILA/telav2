<?php
// api/create_payment.php
// VERSÃO ATUALIZADA: Salva o pedido na tabela 'pedidos' antes de responder

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');

// --- 0. Validação de Sessão (Importante para salvar o user_id) ---
if (empty($_SESSION['_device_verified']) || !isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sessão não verificada. Acesso negado.']);
    exit;
}
$user_id_sessao = (int)$_SESSION['user_id'];

// --- 1. CONFIGURAÇÃO E DB ---
require_once '../db_config.php';
$all_gateway_config = [];

try {
    $stmt_config = $pdo->query("SELECT chave, valor FROM config_gateway");
    $all_gateway_config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Falha ao ler config_gateway: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível iniciar o pagamento.']);
    exit;
}

$ZEROONE_API_TOKEN = $all_gateway_config['zeroone_api_token'] ?? null;
$ZEROONE_API_URL = $all_gateway_config['zeroone_api_url'] ?? null;
$ZEROONE_OFFER_HASH = $all_gateway_config['zeroone_offer_hash'] ?? null;
$ZEROONE_PRODUCT_HASH = $all_gateway_config['zeroone_product_hash'] ?? null;

// --- 2. PROCESSAMENTO ---
$input = json_decode(file_get_contents('php://input'), true);

$product_id = (int)($input['product_id'] ?? 1);
$quantity = (int)($input['quantity'] ?? 1);
if ($quantity <= 0) $quantity = 1;

$customer_name = trim($input['customer_name'] ?? '');
$customer_email = trim($input['customer_email'] ?? '');
$customer_cpf = preg_replace('/[^0-9]/', '', $input['customer_cpf'] ?? '');
$customer_phone = preg_replace('/[^0-9]/', '', $input['customer_phone'] ?? '');

try {
    // 4. Validar dados do cliente
    if (empty($customer_name) || empty($customer_email) || strlen($customer_cpf) !== 11 || strlen($customer_phone) < 10) {
        throw new Exception("Dados do cliente (Nome, Email, CPF ou Telefone) estão faltando ou são inválidos.");
    }

    // 5. Buscar dados do Produto
    $stmt_prod = $pdo->prepare("SELECT nome, preco_atual FROM produtos WHERE id = ?");
    $stmt_prod->execute([$product_id]);
    $produto = $stmt_prod->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        throw new Exception("Produto (ID: $product_id) não encontrado.");
    }

    $preco_unitario_float = (float)$produto['preco_atual'];

    $promo_config = ['enabled' => true, 'coupon_enabled' => true, 'coupon_amount' => 10];
    try {
        $promo_stmt = $pdo->query("SELECT enabled, coupon_enabled, coupon_amount FROM public.promo_config WHERE id = 1");
        $promo_row = $promo_stmt ? $promo_stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($promo_row) {
            $promo_config['enabled'] = ($promo_row['enabled'] === true || $promo_row['enabled'] === 't' || $promo_row['enabled'] === 1);
            $promo_config['coupon_enabled'] = ($promo_row['coupon_enabled'] === true || $promo_row['coupon_enabled'] === 't' || $promo_row['coupon_enabled'] === 1);
            $promo_config['coupon_amount'] = (float)$promo_row['coupon_amount'];
        }
    } catch (PDOException $e) {}
    $promo_discount = 0.0;
    if (!empty($promo_config['enabled']) && !empty($promo_config['coupon_enabled'])) {
        $promo_discount = min($preco_unitario_float, max(0, (float)$promo_config['coupon_amount']));
    }
    $preco_com_desconto = max(0, $preco_unitario_float - $promo_discount);

    $valor_total_centavos = (int)round($preco_com_desconto * $quantity * 100);

    // 6. Preparar dados para a API ZeroOne
    $cart_items_api = [
        [
            'product_hash' => $ZEROONE_PRODUCT_HASH,
            'title' => $produto['nome'],
            'price' => (int)round($preco_com_desconto * 100),
            'quantity' => $quantity,
            'operation_type' => 1,
            'tangible' => false,
        ]
    ];

    // 7. Validar Configs da API
    if (!$ZEROONE_API_TOKEN || !$ZEROONE_API_URL || !$ZEROONE_OFFER_HASH || !$ZEROONE_PRODUCT_HASH) {
        throw new Exception('Configurações essenciais da ZeroOne faltando na config_gateway.');
    }

    // --- 8. CHAMAR A API (cURL) ---
    $api_url_full = rtrim($ZEROONE_API_URL, '/') . "/api/public/v1/transactions?api_token=" . $ZEROONE_API_TOKEN;

    $payload = json_encode([
        'amount' => $valor_total_centavos,
        'offer_hash' => $ZEROONE_OFFER_HASH,
        'payment_method' => 'pix',
        'customer' => [
            'name' => $customer_name,
            'email' => $customer_email,
            'phone_number' => $customer_phone,
            'document' => $customer_cpf
        ],
        'cart' => $cart_items_api,
        'installments' => 1,
        'expire_in_days' => 1,
        'transaction_origin' => 'api',
    ]);

    $ch = curl_init();
    // ... (Configurações do cURL - sem alteração) ...
    curl_setopt($ch, CURLOPT_URL, $api_url_full);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 && $http_code !== 201) {
        $error_data = json_decode($response, true);
        $error_message = $error_data['message'] ?? $response;
        throw new Exception("Gateway falhou. HTTP $http_code. Resposta: $error_message");
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['pix']['pix_qr_code']) && isset($responseData['hash'])) {
        $pix_code_final = $responseData['pix']['pix_qr_code'];
        $gateway_txid_final = $responseData['hash'];
    } else {
        throw new Exception("Resposta inesperada (ZeroOne PIX): " . ($responseData['message'] ?? $response));
    }

    // --- 9. SALVAR O PEDIDO NO NOSSO BANCO ---
    $pdo->beginTransaction();

    $sql_insert = "INSERT INTO pedidos
        (user_id, gateway_txid, status, customer_name, customer_email, customer_cpf, customer_phone, product_name, quantity, total_amount_centavos, pix_code)
        VALUES
        (:user_id, :txid, 'PENDENTE', :name, :email, :cpf, :phone, :prod_name, :qty, :total, :pix_code)
        RETURNING id"; // RETURNING id é específico do PostgreSQL para pegar o ID inserido

    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        'user_id' => $user_id_sessao,
        'txid' => $gateway_txid_final,
        'name' => $customer_name,
        'email' => $customer_email,
        'cpf' => $customer_cpf,
        'phone' => $customer_phone,
        'prod_name' => $produto['nome'],
        'qty' => $quantity,
        'total' => $valor_total_centavos,
        'pix_code' => $pix_code_final
    ]);

    // Pega o ID local do pedido que acabamos de criar
    $localPedidoId = $stmt_insert->fetchColumn();

    $pdo->commit();

    // --- 10. SUCESSO ---
    $expiracao_timestamp = strtotime('+10 minutes');
    $expira_em_iso_js = date(DATE_ATOM, $expiracao_timestamp);

    // Retorna o ID LOCAL, não o do gateway
    echo json_encode([
        'status' => 'success',
        'pix_code' => $pix_code_final,
        'pedidoId' => $localPedidoId, // <-- MUDANÇA CRÍTICA
        'expira_em' => $expira_em_iso_js
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Falha ao criar pagamento: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível criar o pagamento.']);
}
?>