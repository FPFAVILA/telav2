<?php
// Arquivo: pay.php
// OBJETIVO: Fragmento de código para ser incluído na render_page.php.
// Inclui: Lógica de busca PDO, layout do produto, cards de ação, modais E TAMBÉM O CHECKOUT FINAL (PIX, BOTÃO, TERMOS).
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['_device_verified']) || empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
global $pdo;
$produto_id_base = 1;
$produto_checkout = null;
$promo_config = [
    'enabled' => true,
    'coupon_enabled' => true,
    'coupon_amount' => 10,
    'coupon_label' => 'Cupom exclusivo',
    'urgency_label' => 'Oferta relâmpago',
    'timer_minutes' => 10
];


try {
    if (!isset($pdo) || !$pdo) { throw new Exception("Conexão PDO não disponível. Verifique o db_config.php na index."); }
    $sql_checkout = "SELECT nome, nome_vendedor, rating, preco_atual, preco_antigo, imagem_principal
                     FROM public.produtos
                     WHERE id = :product_id_base";
    $stmt_checkout = $pdo->prepare($sql_checkout);
    $stmt_checkout->execute([':product_id_base' => $produto_id_base]);
    $produto_checkout = $stmt_checkout->fetch(PDO::FETCH_ASSOC);

    if (!$produto_checkout) { throw new Exception("Produto principal (ID: {$produto_id_base}) não encontrado no DB."); }

} catch (Exception $e) {
    error_log("ERRO PDO/Busca em pay.php: " . $e->getMessage());
    // FALLBACK DATA (USADO PARA TESTES)
    $produto_checkout = [
        'nome' => 'Cadeira Gamer Stillus Ergonômica Com Ap...',
        'nome_vendedor' => 'OficialWebshop',
        'rating' => 5.0,
        'preco_atual' => 434.90, // Subtotal
        'preco_antigo' => 549.90, // Preço Original
        'imagem_principal' => 'https://via.placeholder.com/80x80?text=PROD',
    ];
}

// LÓGICA DE CÁLCULO E FORMATAÇÃO (BLOCO ATUALIZADO COM DESCONTO DINÂMICO)
$desconto_produto_valor = 0.00; // Valor da diferença real
$porcentagem_desconto = 0;
$diferenca_total = 0.00;

if (isset($produto_checkout['preco_antigo']) && $produto_checkout['preco_antigo'] > $produto_checkout['preco_atual']) {
    $preco_antigo = $produto_checkout['preco_antigo'];
    $preco_atual = $produto_checkout['preco_atual'];
    $diferenca_total = $preco_antigo - $preco_atual;
    $desconto_produto_valor = $diferenca_total;
    $porcentagem_desconto = round(($diferenca_total / $preco_antigo) * 100);
}

// DEFINIÇÃO DAS VARIÁVEIS DE EXIBIÇÃO
$desconto_cupom_exibicao = $diferenca_total;
$subtotal_produto = (float)$produto_checkout['preco_atual'];
$promo_coupon_value = 0.00;
if (!empty($promo_config['enabled']) && !empty($promo_config['coupon_enabled'])) {
    $promo_coupon_value = min($subtotal_produto, max(0, (float)$promo_config['coupon_amount']));
}
$total_pedido = max(0, $subtotal_produto - $promo_coupon_value);
$total_economizado = $diferenca_total + $promo_coupon_value;

// Formatação
$preco_original_formatado = number_format($produto_checkout['preco_antigo'], 2, ',', '.');
$subtotal_formatado = number_format($subtotal_produto, 2, ',', '.');
$desconto_tiktok_formatado = number_format($desconto_cupom_exibicao, 2, ',', '.');
$desconto_cupom_formatado = number_format($desconto_cupom_exibicao, 2, ',', '.');
$promo_coupon_formatado = number_format($promo_coupon_value, 2, ',', '.');
$total_economizado_formatado = number_format($total_economizado, 2, ',', '.');
$total_pedido_formatado = number_format($total_pedido, 2, ',', '.');
$preco_atual_formatado = number_format($produto_checkout['preco_atual'], 2, ',', '.');
$preco_antigo_formatado = number_format($produto_checkout['preco_antigo'], 2, ',', '.');

$pix_payment_id = 1;
?>
<style>
    /* CSS BASE e GERAL (Mantidos) */
    body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; background-color: #F8F8F8; max-width: 768px; margin: 0 auto; }
    .header-checkout { display: flex; align-items: center; height: 50px; background-color: #FFFFFF; border-bottom: 1px solid #EEEEEE; position: sticky; top: 0; z-index: 1000; }
    .back-button { padding: 0 15px; cursor: pointer; color: #222222; font-size: 24px; font-weight: 500; line-height: 1; text-decoration: none; }
    .header-content-wrapper { flex-grow: 1; text-align: center; margin-right: 50px; }
    .header-title { font-size: 16px; font-weight: 700; color: #222222; margin: 0; padding-bottom: 0; }
    .action-container { padding: 0 15px; margin-bottom: 15px; }
    .action-card { display: flex; align-items: center; padding: 15px 20px; cursor: pointer; width: 100%; box-sizing: border-box; background-color: #f2f2f2; border: 1px solid #EEEEEE; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .action-card .plus-icon { font-size: 30px; font-weight: 500; color: #222222; margin-right: 15px; line-height: 1; }
    .action-card span { font-size: 15px; color: #222222; font-weight: 700; }

    /* --- ESTILOS DO PRODUTO (Refinados para a Imagem) --- */
    .container-produto { background-color: #FFFFFF; padding: 15px; margin-bottom: 10px; }
    .header-produto { display: flex; justify-content: space-between; align-items: center; padding: 0; margin-bottom: 10px; }
    .vendedor-nome { font-size: 15px; font-weight: 700; color: #222; }
    .adicionar-nota { font-size: 14px; color: #777; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 2px; }
    .produto-card { display: flex; padding-top: 5px; padding-bottom: 0; border-bottom: none; }
    .produto-imagem { width: 80px; height: 80px; margin-right: 15px; object-fit: contain; }
    .produto-detalhes { flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .rating { display: flex; align-items: center; color: #FFC107; font-size: 14px; font-weight: 500; margin-bottom: 5px; line-height: 1.2; }
    .rating .star { color: #FFC107; font-size: 16px; margin-right: 4px; line-height: 1; }
    .rating span { color: #333; margin-left: 5px; font-weight: 700; }
    .produto-nome { font-size: 15px; font-weight: 500; color: #222; line-height: 1.2; }
    .produto-cor { font-size: 13px; color: #888; margin: 5px 0 10px 0; }
    .tag-devolucao { display: inline-flex; align-items: center; background-color: #FFF0F5; color: #fe3c47; font-size: 11px; font-weight: 500; padding: 3px 6px; border-radius: 4px; margin-bottom: 5px; line-height: 1.2; }
    .tag-devolucao svg { margin-right: 4px; color: #fe3c47; width: 12px; height: 12px; }

    /* --- PREÇOS E QUANTIDADE (Alinhamento e Estilo) --- */
    .precos-container { display: flex; justify-content: space-between; align-items: center; margin-top: 0; }
    .desconto-info { display: flex; align-items: flex-start; flex-direction: column; }
    .preco-atual { font-size: 18px; font-weight: 700; color: #fe3c47; margin-right: 10px; display: block; }
    .preco-antigo-wrapper { display: flex; align-items: center; }
    .preco-antigo { font-size: 12px; color: #888; text-decoration: line-through; margin-right: 5px; }
    .porcentagem-desconto { font-size: 12px; font-weight: 700; color: #fe3c47; padding: 1px 3px; background-color: #FFF0F5; border-radius: 4px; }
    .quantidade-container { display: flex; align-items: center; border: 1px solid #CCC; border-radius: 4px; overflow: hidden; height: 30px; }
    .qtd-btn { background-color: #F8F8F8; border: none; padding: 0 10px; font-size: 16px; cursor: pointer; color: #555; height: 100%; }
    .qtd-valor { padding: 0 12px; font-size: 14px; font-weight: 500; border-left: 1px solid #CCC; border-right: 1px solid #CCC; background-color: #FFF; line-height: 30px; }

    /* --- DESCONTO TIKTOK SHOP (Mais fiel à imagem) --- */
    .desconto-tiktok-wrapper { padding: 15px 0 0 0; border-top: 1px solid #EEE; margin-top: 15px; }
    .desconto-tiktok-div { display: flex; justify-content: space-between; align-items: center; background-color: #FFFFFF; }
    .desconto-label { font-size: 15px; font-weight: 600; color: #333; display: flex; align-items: center; }
    .desconto-label svg { margin-right: 5px; color: #fe3c47; width: 18px; height: 18px; flex-shrink: 0;}
    .desconto-valor-negativo { color: #fe3c47; font-weight: 500; font-size: 15px;}

    /* --- ESTILOS DO RESUMO DO PEDIDO --- (NOVOS ESTILOS) */
    /* .resumo-container já existe, mas o de baixo é o do checkout. Vamos renomear o do PEDIDO. */
    .resumo-pedido-container { background-color: #FFFFFF; padding: 15px; margin-top: 10px; margin-bottom: 10px; }
    .resumo-header { font-size: 16px; font-weight: 700; color: #222; margin-bottom: 15px; }
    .resumo-linha { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; }
    .resumo-linha-label { font-size: 14px; color: #555; }
    .resumo-linha-valor { font-size: 14px; font-weight: 500; color: #222; }
    .resumo-linha-desconto-valor { color: #fe3c47; font-weight: 500; }
    .resumo-linha-total-label { font-size: 16px; font-weight: 700; color: #222; }
    .resumo-linha-total-valor { font-size: 16px; font-weight: 700; color: #222; }
    .impostos-info { font-size: 12px; color: #888; text-align: right; margin-top: -5px; }
    .economia-final { display: flex; align-items: center; background-color: #FFF0F5; color: #fe3c47; font-size: 14px; font-weight: 500; padding: 10px 15px; margin: 0 -15px -15px -15px; border-radius: 0 0 8px 8px; }
    .economia-final svg { margin-right: 5px; width: 16px; height: 16px; flex-shrink: 0; }

    /* MODAIS (Seu CSS, sem mudanças) */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: none; z-index: 2000; opacity: 0; transition: opacity 0.3s ease; }
    .modal-content { position: absolute; bottom: 0; left: 0; width: 100%; background-color: #FFFFFF; border-top-left-radius: 20px; border-top-right-radius: 20px; padding: 20px; box-sizing: border-box; transform: translateY(100%); transition: transform 0.3s ease; }
    .modal-overlay.active { display: block; opacity: 1; }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-title { font-size: 18px; font-weight: 700; flex-grow: 1; text-align: center; }
    .modal-close { font-size: 24px; cursor: pointer; color: #555; position: absolute; right: 20px; top: 20px; }
    .modal-info { color: #888; font-size: 14px; margin-bottom: 15px; text-align: center; }
    .cpf-input { width: 100%; padding: 15px 10px; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 16px; box-sizing: border-box; text-align: center; }
    .confirm-button { width: 100%; padding: 15px; background-color: #FFB6C1; color: #FFFFFF; font-size: 16px; font-weight: 500; border: none; border-radius: 8px; margin-top: 20px; cursor: not-allowed; transition: background-color 0.3s ease; }
    .confirm-button:not(:disabled) { background-color: #fe3c47; cursor: pointer; }
    .address-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: #F8F8F8; z-index: 2500; transform: translateX(100%); transition: transform 0.3s ease; padding: 0; overflow-y: auto; }
    .address-modal.active { transform: translateX(0); }
    .address-modal .header-checkout { border-bottom: 1px solid #F0F0F0; }
    .address-modal .header-title { text-align: center; font-size: 16px; }
    .address-modal .back-button { position: absolute; left: 0; }
    .address-content { padding: 0 0 15px 0; }
    .section-title { color: #888; font-size: 14px; font-weight: 500; margin: 20px 15px 10px 15px; }
    .address-content-block { background-color: #FFFFFF; padding: 0 15px; margin-bottom: 10px; }
    .phone-input-container { display: flex; align-items: center; border-bottom: 1px solid #E0E0E0; padding: 10px 0; margin-bottom: 15px; }
    .phone-prefix { font-size: 15px; color: #AAAAAA; margin-right: 5px; }
    .phone-input-item { flex-grow: 1; }
    .phone-input-item input { border: none; padding: 0; font-size: 15px; width: 100%; background-color: transparent; }
    .input-group { display: flex; gap: 10px; margin-bottom: 15px; }
    .input-item { flex-grow: 1; }
    .address-input, .address-select { width: 100%; padding: 10px 0; border: none; border-bottom: 1px solid #E0E0E0; font-size: 15px; box-sizing: border-box; background-color: #FFFFFF; border-radius: 0; }
    .address-input::placeholder { color: #AAAAAA; }
    .address-input[readonly] { background-color: #FFFFFF; color: #222222; }
    .address-content-block .input-item:last-child .address-input { border-bottom: none; }
    .config-section { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-top: 1px solid #E0E0E0; margin-top: 0; }
    .config-label { font-size: 16px; font-weight: 500; color: #222; }
    .toggle-switch { width: 40px; height: 20px; background-color: #CCC; border-radius: 10px; position: relative; cursor: pointer; transition: background-color 0.3s; }
    .toggle-switch:before { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background-color: #FFF; border-radius: 50%; transition: transform 0.3s; }
    #defaultToggle:checked + .toggle-switch { background-color: #00B050; }
    .hidden-checkbox { display: none; }
    .policy-text { margin-top: 5px; padding: 0 15px; font-size: 12px; color: #888; }
    .policy-link { color: #FE3C47; text-decoration: none; font-weight: 500; }
    .save-button-container { position: fixed; bottom: 0; width: 100%; max-width: 768px; background-color: #FFFFFF; padding: 10px 15px; box-shadow: 0 -2px 5px rgba(0,0,0,0.05); box-sizing: border-box; }
    .save-button { width: 100%; padding: 15px; background-color: #FE3C47; color: #FFFFFF; font-size: 16px; font-weight: 500; border: none; border-radius: 8px; cursor: pointer; }
    .error-message { color: red; font-size: 12px; margin-top: 5px; }

    /* POPUPS (Seu CSS, sem mudanças) */
    #successPopup, #errorPopup { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #FFFFFF; padding: 15px 25px; border-radius: 10px; z-index: 3000; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; font-size: 15px; font-weight: 500; }
    #successPopup { background-color: #333333; }
    #errorPopup { background-color: #D32F2F; }
    #successPopup.show, #errorPopup.show { opacity: 1; visibility: visible; }


    /* ---------------------------------------------------------------------- */
    /* --- CSS DO CHECKOUT.PHP (AGORA INCORPORADO) --- */
    /* ---------------------------------------------------------------------- */
    .main-content-wrapper {
        /* Garante que o conteúdo principal não fique atrás do rodapé fixo */
        padding-bottom: 150px;
    }
    .checkout-module-container {
        padding: 0 15px;
        margin-top: 10px;
    }
    /* 1. Estilos da Forma de Pagamento (Pix) */
    .resumo-container {
        background-color: #FFFFFF;
        padding: 15px;
        margin-top: 10px;
        margin-bottom: 10px;
        border-radius: 8px;
    }
    /* (resumo-header, resumo-linha, etc. já definidos acima, serão reutilizados) */
    .pix-svg-wrapper {
        margin-right: 12px;
        display: flex;
        align-items: center;
        width: 58px;
        height: 21px;
    }

    /* 2. Estilo do Texto dos Termos de Uso */
    .termos-uso-texto {
        font-size: 13px;
        color: #555;
        line-height: 1.4;
        padding: 15px;
        background-color: #F8F8F8;
        text-align: left;
    }
    .termos-uso-texto b {
        color: #222;
        font-weight: 700;
    }

    /* 3. Estilo do Rodapé Fixo FINAL (Botão e Total) */
    .footer-checkout {
        position: fixed;
        bottom: 0;
        width: 100%;
        max-width: 768px;
        background-color: #FFFFFF;
        padding: 10px 15px;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
        box-sizing: border-box;
        z-index: 1500;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        left: 50%;
        transform: translateX(-50%);
    }
    .footer-checkout .total-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin-bottom: 8px;
    }
    .footer-checkout .total-label {
        font-size: 15px;
        color: #222;
        font-weight: 500;
    }
    .footer-checkout .total-value {
        font-size: 18px;
        font-weight: 700;
        color: #fe3c47;
    }
    .footer-checkout button {
        width: 100%;
        padding: 15px;
        background-color: #fe3c47;
        color: #FFFFFF;
        font-size: 16px;
        font-weight: 500;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s, opacity 0.3s;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    /* Botão desabilitado (para quando a API estiver processando) */
    .footer-checkout button:disabled {
        background-color: #FFB6C1;
        cursor: not-allowed;
    }
    #cupomTimer {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 3px;
    }

    /* 4. ESTILOS DO POPUP DE VALIDAÇÃO (Reutilizado para Erros) */
    .checkout-notification-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none; /* Inicia oculto */
        justify-content: center;
        align-items: center;
        z-index: 3000; /* Deve estar acima de tudo */
    }
    .checkout-notification-content {
        background-color: #FFFFFF;
        padding: 25px;
        margin: 20px;
        border-radius: 12px;
        max-width: 200px; /* Ajustado */
        text-align: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .modal-alert-title {
        color: #000000ff;
        font-size: 18px;
        font-weight: 700;
        margin-top: 0;
    }
    .checkout-notification-content p {
        font-size: 14px;
        color: #333;
        margin-bottom: 20px;
    }
    .modal-ok-button {
        background-color: #fe3c47;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    /* 5. (NOVO) OVERLAY DE LOADING PARA API */
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        display: none; /* Inicia oculto */
        justify-content: center;
        align-items: center;
        z-index: 3500;
    }
    /* (Adicione um GIF ou CSS spinner aqui se desejar) */
    .loader-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #fe3c47;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    /* NOVO CSS: Para o Popup de Pagamento Aprovado */
    .payment-success-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none; /* Inicia oculto */
        justify-content: center;
        align-items: center;
        z-index: 4000; /* Acima de tudo */
    }
    .payment-success-content {
        background-color: #FFFFFF;
        padding: 25px;
        margin: 20px;
        border-radius: 12px;
        max-width: 250px;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .success-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #E8F5E9; /* Verde claro */
        color: #4CAF50; /* Verde escuro */
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 15px auto;
        font-size: 30px;
        font-weight: bold;
    }
    .success-title {
        font-size: 18px;
        font-weight: 700;
        color: #222;
        margin-bottom: 10px;
    }
    .success-message {
        font-size: 14px;
        color: #555;
        line-height: 1.4;
        margin-bottom: 20px;
    }
    .success-ok-button {
        width: 100%;
        padding: 12px;
        background-color: #fe3c47;
        color: #FFFFFF;
        font-size: 16px;
        font-weight: 500;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

</style>

<div class="header-checkout">
    <a href="index.php" class="back-button">&lt;</a>
    <div class="header-content-wrapper">
        <div class="header-text-group">
            <div class="header-title">Resumo do pedido</div>
        </div>
    </div>
</div>

<div class="main-content-wrapper"> <div style="padding-top: 15px;">
        <div class="action-container">
            <div class="action-card" id="addressCard">
                <span class="plus-icon" id="addressIcon">+</span>
                <span id="addressText">Adicionar endereço de entrega</span>
            </div>
        </div>
        <div class="action-container">
            <div class="action-card" id="cpfCard">
                <span class="plus-icon" id="cpfIcon">+</span>
                <span id="cpfText">Adicionar CPF</span>
                <span id="cpfValue" style="margin-left: auto; color: #888; font-weight: 400;"></span>
            </div>
        </div>
    </div>

    <div class="container-produto">
        <div class="header-produto">
            <span class="vendedor-nome"><?php echo htmlspecialchars($produto_checkout['nome_vendedor']); ?></span>
            <a href="#" class="adicionar-nota">Adicionar nota <span>&gt;</span></a>
        </div>
        <div class="produto-card">
            <div>
                <img src="<?php echo htmlspecialchars($produto_checkout['imagem_principal'] ?? 'https://via.placeholder.com/80x80?text=PROD'); ?>" alt="Imagem do Produto" class="produto-imagem">
            </div>
            <div class="produto-detalhes">
                <div>
                    <div class="rating">
                        <span class="star">★</span> Muito bem avaliado! <span><?php echo htmlspecialchars(number_format($produto_checkout['rating'], 1, '/', ',')); ?></span>
                    </div>
                    <div class="produto-nome">
                        <?php echo htmlspecialchars($produto_checkout['nome']); ?>
                    </div>
                    <div class="produto-cor">
                        Preto
                    </div>
                    <div class="tag-devolucao">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-coin" viewBox="0 0 16 16"> <path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518z"/> <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/> <path d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11m0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12"/></svg>
                        Devolução gratuita
                    </div>
                </div>
                <div class="precos-container">
                    <div class="desconto-info">
                        <span class="preco-atual">
                            R$ <?php echo $preco_atual_formatado; ?>
                        </span>
                        <?php if ($porcentagem_desconto > 0): ?>
                            <div class="preco-antigo-wrapper">
                                <span class="preco-antigo">
                                    R$ <?php echo $preco_antigo_formatado; ?>
                                </span>
                                <span class="porcentagem-desconto">
                                    -<?php echo $porcentagem_desconto; ?>%
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="quantidade-container">
                        <button class="qtd-btn">-</button>
                        <span class="qtd-valor">1</span>
                        <button class="qtd-btn">+</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="desconto-tiktok-wrapper">
            <div class="desconto-tiktok-div">
                <div class="desconto-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M4 4.85v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9z"></path>
                        <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3zM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9z"></path>
                    </svg>
                    Desconto do TikTok Shop
                </div>
                <span class="desconto-valor-negativo">
                    - R$ <?php echo $desconto_tiktok_formatado; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="resumo-pedido-container"> <div class="resumo-header">Resumo do pedido</div>
        <div class="resumo-linha">
            <div class="resumo-linha-label">Subtotal do produto</div>
            <div class="resumo-linha-valor">R$ <?php echo $subtotal_formatado; ?></div>
        </div>
        <div class="resumo-linha" style="margin-top: -5px;">
            <div class="resumo-linha-label">Preço original</div>
            <div class="resumo-linha-valor">R$ <?php echo $preco_original_formatado; ?></div>
        </div>
        <div class="resumo-linha">
            <div class="resumo-linha-label">Cupons do TikTok Shop</div>
            <div class="resumo-linha-valor resumo-linha-desconto-valor">- R$ <?php echo $desconto_cupom_formatado; ?></div>
        </div>
        <?php if ($promo_coupon_value > 0): ?>
        <div class="resumo-linha">
            <div class="resumo-linha-label"><?php echo htmlspecialchars($promo_config['coupon_label']); ?></div>
            <div class="resumo-linha-valor resumo-linha-desconto-valor">- R$ <?php echo $promo_coupon_formatado; ?></div>
        </div>
        <?php endif; ?>
        <div style="border-top: 1px solid #EEE; margin: 10px 0;"></div>
        <div class="resumo-linha">
            <div class="resumo-linha-total-label">Total</div>
            <div class="resumo-linha-total-valor">R$ <?php echo $total_pedido_formatado; ?></div>
        </div>
        <div class="impostos-info">Impostos inclusos</div>
        <div class="economia-final">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-emoji-smile" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"></path>
                <path d="M4.285 9.567a.5.5 0 0 1 .683.183A3.5 3.5 0 0 0 8 11.5a3.5 3.5 0 0 0 3.032-1.75.5.5 0 1 1 .866.5A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1-3.898-2.25.5.5 0 0 1 .183-.683M7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5m4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5"></path>
            </svg>
            Você está economizando R$ <?php echo $total_economizado_formatado; ?> nesse pedido.
        </div>
    </div>

    <div class="checkout-module-container" style="margin-top:0;">
        <div class="resumo-container"> <div class="resumo-header">Forma de pagamento</div>
            <div class="resumo-linha">
                <div class="resumo-linha-label">
                    <div class="pix-svg-wrapper">
                        <svg width="58" height="21" viewBox="0 0 58 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_953_757)"><path d="M23.9424 19.4426V7.61063C23.9424 5.42983 25.7056 3.66663 27.8864 3.66663H31.378C33.5472 3.66663 35.2988 5.42983 35.2988 7.59903V10.1162C35.2988 12.297 33.5356 14.0602 31.3548 14.0602H26.4248" stroke="#939598" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M36.2734 3.67822H37.793C38.6862 3.67822 39.4054 4.39742 39.4054 5.29062V14.1298" stroke="#939598" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M39.0805 2.30941L38.3961 1.62501C38.2221 1.45101 38.2221 1.17261 38.3961 1.01021L39.0805 0.325812C39.2545 0.151813 39.5329 0.151813 39.6953 0.325812L40.3797 1.01021C40.5537 1.18421 40.5537 1.46261 40.3797 1.62501L39.6953 2.30941C39.5213 2.47181 39.2429 2.47181 39.0805 2.30941Z" fill="#32BCAD"></path><path d="M42.3057 3.66663H43.8021C44.5793 3.66663 45.3101 3.96823 45.8669 4.52503L49.3817 8.03983C49.8341 8.49223 50.5765 8.49223 51.0289 8.03983L54.5321 4.53663C55.0773 3.99143 55.8197 3.67823 56.5969 3.67823H57.8149" stroke="#939598" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M42.3057 14.0486H43.8021C44.5793 14.0486 45.3101 13.747 45.8669 13.1902L49.3817 9.67542C49.8341 9.22302 50.5765 9.22302 51.0289 9.67542L54.5321 13.1786C55.0773 13.7238 55.8197 14.037 56.5969 14.037H57.8149" stroke="#939598" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M14.9872 16.2874C14.2332 16.2874 13.5256 15.9974 12.992 15.4638L10.1152 12.587C9.91798 12.3898 9.55838 12.3898 9.36118 12.587L6.47278 15.4754C5.93918 16.009 5.23158 16.299 4.47758 16.299H3.90918L7.56318 19.953C8.69998 21.0898 10.556 21.0898 11.6928 19.953L15.3584 16.2874H14.9872Z" fill="#32BCAD"></path><path d="M4.46672 6.10267C5.22072 6.10267 5.92832 6.39267 6.46192 6.92627L9.35032 9.81467C9.55912 10.0235 9.89552 10.0235 10.1043 9.81467L12.9927 6.93787C13.5263 6.40427 14.2339 6.11427 14.9879 6.11427H15.3359L11.6703 2.44867C10.5335 1.31187 8.67752 1.31187 7.54072 2.44867L3.88672 6.10267H4.46672Z" fill="#32BCAD"></path><path d="M18.3629 9.14181L16.1473 6.92621C16.1009 6.94941 16.0429 6.96101 15.9849 6.96101H14.9757C14.4537 6.96101 13.9433 7.16981 13.5837 7.54101L10.7069 10.4178C10.4401 10.6846 10.0805 10.8238 9.73246 10.8238C9.37286 10.8238 9.02486 10.6846 8.75806 10.4178L5.86966 7.52941C5.49846 7.15821 4.98806 6.94941 4.47766 6.94941H3.23646C3.17846 6.94941 3.13206 6.93781 3.08566 6.91461L0.858459 9.14181C-0.278341 10.2786 -0.278341 12.1346 0.858459 13.2714L3.07406 15.487C3.12046 15.4638 3.16686 15.4522 3.22486 15.4522H4.46606C4.98806 15.4522 5.49846 15.2434 5.85806 14.8722L8.74646 11.9838C9.26846 11.4618 10.1849 11.4618 10.7069 11.9838L13.5837 14.8606C13.9549 15.2318 14.4653 15.4406 14.9757 15.4406H15.9849C16.0429 15.4406 16.0893 15.4522 16.1473 15.4754L18.3629 13.2598C19.4997 12.123 19.4997 10.2786 18.3629 9.14181Z" fill="#32BCAD"></path><path d="M26.9346 18.4334C26.7722 18.4334 26.5866 18.4682 26.3894 18.5146V19.2338C26.517 19.2802 26.6678 19.3034 26.807 19.3034C27.1666 19.3034 27.3406 19.1758 27.3406 18.8626C27.3522 18.5726 27.213 18.4334 26.9346 18.4334ZM26.2734 19.779V18.3522H26.3778L26.3894 18.4102C26.5518 18.3754 26.7838 18.3174 26.9578 18.3174C27.097 18.3174 27.2246 18.3406 27.329 18.4218C27.4566 18.5262 27.4914 18.6886 27.4914 18.8626C27.4914 19.0482 27.4334 19.2222 27.2594 19.3266C27.1434 19.3962 26.981 19.4194 26.8418 19.4194C26.691 19.4194 26.5518 19.3962 26.4126 19.3498V19.7674H26.2734V19.779Z" fill="#939598"></path><path d="M28.3967 18.4334C28.0371 18.4334 27.8747 18.5494 27.8747 18.8626C27.8747 19.1758 28.0371 19.3034 28.3967 19.3034C28.7563 19.3034 28.9187 19.1874 28.9187 18.8742C28.9071 18.5726 28.7563 18.4334 28.3967 18.4334ZM28.8607 19.315C28.7447 19.3962 28.5823 19.431 28.3967 19.431C28.2111 19.431 28.0487 19.4078 27.9327 19.315C27.8051 19.2222 27.7471 19.0714 27.7471 18.8742C27.7471 18.6886 27.8051 18.5262 27.9327 18.4334C28.0487 18.3522 28.2111 18.3174 28.3967 18.3174C28.5823 18.3174 28.7447 18.3406 28.8607 18.4334C28.9999 18.5262 29.0463 18.6886 29.0463 18.8742C29.0463 19.0598 28.9883 19.2222 28.8607 19.315Z" fill="#939598"></path><path d="M30.5663 19.3962L30.1603 18.5262H30.1487L29.7543 19.3962H29.6499L29.2207 18.3522H29.3599L29.7195 19.2222H29.7311L30.1139 18.3522H30.2299L30.6243 19.2222H30.6359L30.9839 18.3522H31.1115L30.6823 19.3962H30.5663Z" fill="#939598"></path><path d="M31.8884 18.4334C31.552 18.4334 31.436 18.5842 31.4244 18.793H32.364C32.3408 18.561 32.2248 18.4334 31.8884 18.4334ZM31.8768 19.4194C31.6796 19.4194 31.552 19.3962 31.4476 19.3034C31.32 19.199 31.2852 19.0482 31.2852 18.8742C31.2852 18.7118 31.3432 18.5262 31.4824 18.4334C31.5984 18.3522 31.7376 18.329 31.8884 18.329C32.0276 18.329 32.1784 18.3406 32.306 18.4334C32.4568 18.5378 32.4916 18.7118 32.4916 18.909H31.4244C31.4244 19.1294 31.494 19.315 31.9 19.315C32.0972 19.315 32.2712 19.2802 32.4336 19.257V19.3614C32.2596 19.3846 32.0624 19.4194 31.8768 19.4194Z" fill="#939598"></path><path d="M32.8281 19.3962V18.3522H32.9325L32.9441 18.4102C33.1645 18.3522 33.2689 18.3174 33.4661 18.3174H33.4777V18.4334H33.4429C33.2805 18.4334 33.1761 18.4566 32.9557 18.5146V19.3846L32.8281 19.3962Z" fill="#939598"></path><path d="M34.1852 18.4334C33.8488 18.4334 33.7328 18.5842 33.7212 18.793H34.6608C34.6376 18.561 34.5216 18.4334 34.1852 18.4334ZM34.1736 19.4194C33.9764 19.4194 33.8488 19.3962 33.7444 19.3034C33.6168 19.199 33.582 19.0482 33.582 18.8742C33.582 18.7118 33.64 18.5262 33.7792 18.4334C33.8952 18.3522 34.0344 18.329 34.1852 18.329C34.3244 18.329 34.4752 18.3406 34.6028 18.4334C34.7536 18.5378 34.7884 18.7118 34.7884 18.909H33.7212C33.7212 19.1294 33.7908 19.315 34.1968 19.315C34.394 19.315 34.568 19.2802 34.7304 19.257V19.3614C34.5564 19.3846 34.3592 19.4194 34.1736 19.4194Z" fill="#939598"></path><path d="M36.1109 18.503C35.9833 18.4566 35.8325 18.4334 35.6933 18.4334C35.3337 18.4334 35.1597 18.561 35.1597 18.8742C35.1597 19.1758 35.2989 19.3034 35.5773 19.3034C35.7397 19.3034 35.9253 19.2686 36.1225 19.2222V18.503H36.1109ZM36.1341 19.3962L36.1225 19.3382C35.9601 19.373 35.7281 19.431 35.5541 19.431C35.4149 19.431 35.2873 19.4078 35.1829 19.3266C35.0553 19.2222 35.0205 19.0598 35.0205 18.8858C35.0205 18.7002 35.0785 18.5262 35.2525 18.4334C35.3685 18.3638 35.5309 18.3406 35.6701 18.3406C35.8093 18.3406 35.9601 18.3638 36.0993 18.4102V17.9346H36.2269V19.4194L36.1341 19.3962Z" fill="#939598"></path><path d="M38.0245 18.4334C37.8621 18.4334 37.6765 18.4682 37.4793 18.5146V19.2338C37.6069 19.2802 37.7577 19.3034 37.8969 19.3034C38.2565 19.3034 38.4305 19.1758 38.4305 18.8626C38.4305 18.5726 38.2913 18.4334 38.0245 18.4334ZM38.3377 19.3266C38.2217 19.3962 38.0593 19.4194 37.9201 19.4194C37.7693 19.4194 37.6069 19.3962 37.4561 19.3382L37.4445 19.3846H37.3633V17.8998H37.4909V18.3986C37.6533 18.3638 37.8853 18.3174 38.0477 18.3174C38.1869 18.3174 38.3145 18.3406 38.4189 18.4218C38.5465 18.5262 38.5813 18.6886 38.5813 18.8626C38.5697 19.0598 38.5001 19.2338 38.3377 19.3266Z" fill="#939598"></path><path d="M38.7213 19.7906V19.6746C38.7793 19.6862 38.8373 19.6862 38.8721 19.6862C39.0229 19.6862 39.1157 19.6398 39.1969 19.4658L39.2317 19.3846L38.6865 18.3406H38.8257L39.2897 19.2454H39.3013L39.7421 18.3406H39.8813L39.2897 19.5122C39.1853 19.721 39.0693 19.7906 38.8489 19.7906C38.8257 19.8022 38.7793 19.8022 38.7213 19.7906Z" fill="#939598"></path><path d="M41.5747 18.793H41.1687V19.1642H41.5747C41.8531 19.1642 41.9575 19.1294 41.9575 18.9786C41.9691 18.8162 41.8183 18.793 41.5747 18.793ZM41.5051 18.2014H41.1803V18.5726H41.5167C41.7951 18.5726 41.8995 18.5378 41.8995 18.387C41.8879 18.2246 41.7487 18.2014 41.5051 18.2014ZM42.1315 19.2918C41.9807 19.3846 41.8067 19.3962 41.4703 19.3962H40.8555V17.981H41.4587C41.7371 17.981 41.9111 17.981 42.0619 18.0738C42.1663 18.1318 42.2011 18.2362 42.2011 18.3522C42.2011 18.503 42.1431 18.5958 41.9807 18.6654V18.677C42.1663 18.7234 42.2823 18.8162 42.2823 19.0134C42.2823 19.141 42.2359 19.2338 42.1315 19.2918Z" fill="#939598"></path><path d="M43.5235 18.9554C43.3959 18.9438 43.2799 18.9438 43.1523 18.9438C42.9435 18.9438 42.8623 18.9902 42.8623 19.083C42.8623 19.1758 42.9203 19.2222 43.0827 19.2222C43.2219 19.2222 43.3843 19.1874 43.5235 19.1642V18.9554ZM43.5815 19.3962L43.5699 19.3382C43.3959 19.3846 43.1871 19.431 43.0015 19.431C42.8855 19.431 42.7695 19.4194 42.6883 19.3498C42.6071 19.2918 42.5723 19.199 42.5723 19.0946C42.5723 18.9786 42.6187 18.8626 42.7463 18.8162C42.8507 18.7698 43.0015 18.7582 43.1407 18.7582C43.2451 18.7582 43.3959 18.7698 43.5235 18.7698V18.7466C43.5235 18.5842 43.4191 18.5262 43.1175 18.5262C43.0015 18.5262 42.8623 18.5378 42.7347 18.5494V18.3406C42.8855 18.329 43.0479 18.3174 43.1871 18.3174C43.3727 18.3174 43.5583 18.329 43.6743 18.4102C43.7903 18.4914 43.8135 18.6074 43.8135 18.7698V19.3846L43.5815 19.3962Z" fill="#939598"></path><path d="M45.1706 19.3962V18.8162C45.1706 18.6306 45.0778 18.561 44.9038 18.561C44.7762 18.561 44.6138 18.5958 44.4746 18.6306V19.3962H44.1846V18.3522H44.4166L44.4282 18.4218C44.6138 18.3754 44.811 18.329 44.985 18.329C45.1126 18.329 45.2402 18.3522 45.3446 18.4334C45.4258 18.503 45.4606 18.6074 45.4606 18.7582V19.3962H45.1706Z" fill="#939598"></path><path d="M46.2845 19.4194C46.1453 19.4194 46.0061 19.3962 45.9017 19.315C45.7741 19.2106 45.7393 19.0482 45.7393 18.8742C45.7393 18.7118 45.7973 18.5262 45.9481 18.4334C46.0757 18.3522 46.2381 18.329 46.4121 18.329C46.5281 18.329 46.6441 18.3406 46.7833 18.3522V18.5726C46.6789 18.561 46.5513 18.5494 46.4469 18.5494C46.1685 18.5494 46.0409 18.6306 46.0409 18.8742C46.0409 19.0946 46.1337 19.199 46.3657 19.199C46.4933 19.199 46.6557 19.1758 46.8065 19.141V19.3614C46.6325 19.3846 46.4469 19.4194 46.2845 19.4194Z" fill="#939598"></path><path d="M47.653 18.5378C47.3746 18.5378 47.2586 18.619 47.2586 18.8626C47.2586 19.0946 47.3746 19.199 47.653 19.199C47.9314 19.199 48.0474 19.1178 48.0474 18.8742C48.0474 18.6422 47.9314 18.5378 47.653 18.5378ZM48.1518 19.315C48.0242 19.3962 47.8618 19.4194 47.653 19.4194C47.4442 19.4194 47.2818 19.3962 47.1542 19.315C47.015 19.2222 46.957 19.0598 46.957 18.8742C46.957 18.6886 47.0034 18.5262 47.1542 18.4334C47.2818 18.3522 47.4442 18.329 47.653 18.329C47.8618 18.329 48.0242 18.3522 48.1518 18.4334C48.291 18.5262 48.349 18.6886 48.349 18.8742C48.349 19.0598 48.291 19.2222 48.1518 19.315Z" fill="#939598"></path><path d="M50.0316 19.4194C49.8576 19.4194 49.6604 19.3962 49.5212 19.2686C49.3472 19.1294 49.3008 18.909 49.3008 18.677C49.3008 18.4682 49.3704 18.2246 49.5908 18.0738C49.7648 17.9578 49.9736 17.9346 50.194 17.9346C50.3564 17.9346 50.5072 17.9462 50.6928 17.9578V18.213C50.542 18.2014 50.3564 18.1898 50.2172 18.1898C49.8112 18.1898 49.6488 18.3406 49.6488 18.6654C49.6488 19.0018 49.8112 19.1526 50.1012 19.1526C50.2984 19.1526 50.5072 19.1178 50.7276 19.0714V19.3266C50.4956 19.373 50.2636 19.4194 50.0316 19.4194Z" fill="#939598"></path><path d="M51.5617 18.503C51.3181 18.503 51.2253 18.5842 51.2137 18.7466H51.9213C51.9097 18.5842 51.8053 18.503 51.5617 18.503ZM51.5153 19.4194C51.3413 19.4194 51.1905 19.3962 51.0745 19.3034C50.9469 19.199 50.9121 19.0482 50.9121 18.8626C50.9121 18.7002 50.9585 18.5262 51.1093 18.4218C51.2369 18.329 51.3993 18.3174 51.5617 18.3174C51.7125 18.3174 51.8865 18.329 52.0141 18.4218C52.1765 18.5378 52.1997 18.7234 52.1997 18.9322H51.2137C51.2253 19.0946 51.3065 19.199 51.5965 19.199C51.7821 19.199 51.9793 19.1758 52.1533 19.141V19.3498C51.9445 19.3846 51.7241 19.4194 51.5153 19.4194Z" fill="#939598"></path><path d="M53.4997 19.3962V18.8162C53.4997 18.6306 53.4069 18.561 53.2329 18.561C53.1053 18.561 52.9429 18.5958 52.8037 18.6306V19.3962H52.5137V18.3522H52.7457L52.7573 18.4218C52.9429 18.3754 53.1401 18.329 53.3141 18.329C53.4417 18.329 53.5693 18.3522 53.6737 18.4334C53.7549 18.503 53.7897 18.6074 53.7897 18.7582V19.3962H53.4997Z" fill="#939598"></path><path d="M54.6127 19.4193C54.4735 19.4193 54.3459 19.3845 54.2763 19.2685C54.2299 19.1989 54.1951 19.0945 54.1951 18.9553V18.5609H53.9863V18.3405H54.1951L54.2299 18.0273H54.4851V18.3405H54.8911V18.5609H54.4851V18.8973C54.4851 18.9785 54.4967 19.0481 54.5083 19.0945C54.5431 19.1641 54.6127 19.1873 54.6939 19.1873C54.7635 19.1873 54.8447 19.1757 54.9027 19.1641V19.3729C54.8215 19.4077 54.7055 19.4193 54.6127 19.4193Z" fill="#939598"></path><path d="M55.1816 19.3962V18.3522H55.4136L55.4252 18.4218C55.6224 18.3638 55.7616 18.329 55.9472 18.329H55.982V18.5726H55.8776C55.7384 18.5726 55.6224 18.5842 55.4716 18.6306V19.3962H55.1816Z" fill="#939598"></path><path d="M57.0373 18.9554C56.9097 18.9438 56.7937 18.9438 56.6661 18.9438C56.4573 18.9438 56.3761 18.9902 56.3761 19.083C56.3761 19.1758 56.4341 19.2222 56.5965 19.2222C56.7357 19.2222 56.8981 19.1874 57.0373 19.1642V18.9554ZM57.1069 19.3962L57.0953 19.3382C56.9213 19.3846 56.7125 19.431 56.5269 19.431C56.4109 19.431 56.2949 19.4194 56.2137 19.3498C56.1325 19.2918 56.0977 19.199 56.0977 19.0946C56.0977 18.9786 56.1441 18.8626 56.2717 18.8162C56.3761 18.7698 56.5269 18.7582 56.6661 18.7582C56.7705 18.7582 56.9213 18.7698 57.0489 18.7698V18.7466C57.0489 18.5842 56.9445 18.5262 56.6429 18.5262C56.5269 18.5262 56.3877 18.5378 56.2601 18.5494V18.3406C56.4109 18.329 56.5733 18.3174 56.7125 18.3174C56.8981 18.3174 57.0837 18.329 57.1997 18.4102C57.3157 18.4914 57.3389 18.6074 57.3389 18.7698V19.3846L57.1069 19.3962Z" fill="#939598"></path><path d="M57.71 17.9114H58V19.3962H57.71V17.9114Z" fill="#939598"></path></g><defs><clipPath id="clip0_953_757"><rect width="58" height="20.6248" fill="white" transform="translate(0 0.187622)"></rect></clipPath></defs>
                        </svg>
                    </div>
                </div>
                <input type="radio" name="payment_method" value="<?php echo $pix_payment_id; ?>" checked style="width: 20px; height: 20px; accent-color: #fe3c47;">
            </div>
        </div>
    </div>

    <div class="termos-uso-texto">
        Ao fazer um pedido, você concorda com <b>Termos de uso e venda do TikTok Shop</b> e reconhece que leu e concordou com a <b>Política de privacidade do TikTok</b>.
    </div>

</div> <div class="footer-checkout">
    <div class="total-info">
        <span class="total-label">Total (1 item)</span>
        <span class="total-value">R$ <?php echo $total_pedido_formatado ?? '0,00'; ?></span>
    </div>
    <button id="fazerPedidoBtn">
        Fazer pedido
        <span id="cupomTimer">O cupom expira em 10:00</span>
    </button>
</div>

<div class="checkout-notification-overlay" id="validationModalOverlay">
    <div class="checkout-notification-content">
        <h3 class="modal-alert-title" id="initialModalTitle">Atenção! Dados Faltando</h3>
        <p id="initialModalText">Para finalizar o pedido, você precisa primeiro preencher o **CPF** e o **Endereço de entrega**.</p>

        <div id="validationModalButtons" style="display: flex; justify-content: space-between; width: 100%; gap: 10px; margin-top: 15px;">
            <button id="closeValidationModal" class="modal-ok-button"
                    style="background-color: transparent; color: #777; border: 1px solid #CCC; flex-grow: 1; padding: 10px; font-weight: 500; display: none;">Mais tarde</button>

            <button id="initialModalAddButton" class="modal-ok-button"
                    style="flex-grow: 1; padding: 10px;">OK</button>
        </div>
    </div>
</div>

<div id="loadingOverlay">
    <div class="loader-spinner"></div>
</div>

<div class="modal-overlay" id="cpfModalOverlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Adicionar CPF</div>
            <span class="modal-close" id="closeModalBtn">&times;</span>
        </div>
        <form id="cpfForm">
            <div class="modal-info">O CPF será usado para emitir faturas</div>
            <input type="tel" id="cpfInput" name="cpf" class="cpf-input"
                   placeholder="Insira o número de CPF de 11 dígitos"
                   maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" required>
            <button type="submit" class="confirm-button" id="confirmCpfBtn" disabled>Confirmar</button>
        </form>
    </div>
</div>
<div class="payment-success-overlay" id="paymentSuccessModal">
    <div class="payment-success-content">
        <div class="success-icon-wrapper">
            <span>&#10003;</span> </div>
        <h3 class="success-title">Pagamento Aprovado!</h3>
        <p class="success-message">
            Seu pedido foi confirmado. Enviaremos um e-mail com todos os detalhes em breve.
        </p>
        <button class="success-ok-button" id="closeSuccessModalBtn">OK</button>
    </div>
</div>

<div class="address-modal" id="addressModal">
    <div class="header-checkout">
        <a href="#" class="back-button" id="closeAddressModalBtn">&lt;</a>
        <div class="header-content-wrapper">
            <div class="header-title">Adicionar o novo endereço</div>
        </div>
    </div>
    <div class="address-content">
        <form id="addressForm">
            <div class="section-title">Informações de contato</div>
            <div class="address-content-block">
                <div class="input-item">
                    <input type="text" id="contactName" name="contactName" class="address-input" placeholder="Nome completo" required>
                </div>
                <div class="phone-input-container">
                    <span class="phone-prefix">BR +55</span>
                    <div class="phone-input-item">
                        <input type="tel" id="contactPhone" name="contactPhone" placeholder="Telefone" required>
                    </div>
                </div>
                <div class="input-item">
                    <input type="email" id="contactEmail" name="contactEmail" class="address-input" placeholder="E-mail">
                </div>
            </div>
            <div class="section-title">Informações de endereço</div>
            <div class="address-content-block">
                <div class="input-item">
                    <input type="text" id="cepInput" name="cepInput" class="address-input" placeholder="CEP/Código postal" maxlength="9" required>
                </div>
                <div class="input-group">
                    <div class="input-item">
                        <input type="text" id="ufInput" name="ufInput" class="address-input" placeholder="Estado/UF" readonly>
                    </div>
                    <div class="input-item">
                        <input type="text" id="cityInput" name="cityInput" class="address-input" placeholder="Cidade" readonly>
                    </div>
                </div>
                <div class="input-item">
                    <input type="text" id="neighborhoodInput" name="neighborhoodInput" class="address-input" placeholder="Bairro/Distrito" readonly>
                </div>
                <div class="input-item">
                    <input type="text" id="streetInput" name="streetInput" class="address-input" placeholder="Endereço" readonly>
                </div>
                <div class="input-group" style="margin-bottom: 0;">
                    <div class="input-item" style="flex-grow: 0.5;">
                        <input type="text" name="address_number" class="address-input" placeholder="Nº da residência. Use 's/n' se nenhum" required>
                    </div>
                    <div class="input-item">
                        <input type="text" name="address_complement" class="address-input" placeholder="Apartamento, bloco, unidade etc. (Opcional)">
                    </div>
                </div>
            </div>
            <div class="section-title">Configurações</div>
            <div class="address-content-block" style="padding: 0 15px; margin-bottom: 0;">
                <div class="config-section" style="margin-top: 0; border-top: none;">
                    <div class="config-label">Definir como padrão</div>
                    <label class="toggle-container">
                        <input type="checkbox" id="defaultToggle" name="defaultToggle" class="hidden-checkbox">
                        <div class="toggle-switch"></div>
                    </label>
                </div>
            </div>
            <div class="policy-text">
                Leia a <a href="#" class="policy-link">Política de privacidade do TikTok</a> para saber mais sobre como usamos suas informações pessoais.
            </div>
        </form>
    </div>
    <div class="save-button-container">
        <button type="submit" form="addressForm" class="save-button">Salvar</button>
    </div>
</div>

<div id="successPopup">Endereço salvo com sucesso!</div>
<div id="errorPopup">CPF Inválido. Por favor, verifique.</div>


<style>
    /* Estilos para o modal do PIX (baseado no modal de CPF) */
    .pix-modal-content {
        padding: 20px;
        text-align: center;
    }
    #pixQrCode {
        width: 220px;
        height: 220px;
        background-color: #EEE;
        margin: 15px auto;
        display: block;
        border: 1px solid #CCC;
        border-radius: 8px;
    }
    #pixCodeInput {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        border: 1px solid #DDD;
        border-radius: 8px;
        box-sizing: border-box;
        text-align: center;
        margin-bottom: 10px;
        color: #555;
    }
    #copyPixBtn {
        width: 100%;
        padding: 15px;
        background-color: #fe3c47;
        color: #FFFFFF;
        font-size: 16px;
        font-weight: 500;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    #pixTimer {
        font-size: 14px;
        font-weight: 500;
        color: #fe3c47;
        margin-top: 10px;
    }
</style>

<div class="modal-overlay" id="pixModalOverlay">
    <div class="modal-content pix-modal-content">
        <div class="modal-header">
            <div class="modal-title">Efetue o pagamento PIX</div>
            <span class="modal-close" id="closePixModalBtn">&times;</span>
        </div>
        <p>Escaneie o QR Code ou copie o código abaixo:</p>

        <div id="pixQrCodeContainer" style="width: 220px; height: 220px; margin: 15px auto; padding: 10px; background: white; border-radius: 8px; border: 1px solid #CCC; display: flex; justify-content: center; align-items: center;">
            </div>

        <div id="pixTimer">Expira em 10:00</div>

        <textarea id="pixCodeInput" rows="3" readonly></textarea>

        <button id="copyPixBtn">Copiar Código PIX</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    // --- ESTADOS GLOBAIS ---
    window.isAddressSaved = false;
    window.isCpfSaved = false;
    window.savedCustomerName = null;
    window.savedCustomerPhone = null;
    window.savedCustomerEmail = null;
    window.savedCustomerCPF = null;
    window.selectedShippingId = 1;
    var qrcodeObject = null;

    // <-- NOVO: Variável para controlar o polling -->
    var paymentPollingInterval = null;

    document.addEventListener('DOMContentLoaded', function() {

        // --- ELEMENTOS DE CONTROLE ---
        const openCpfModalBtn = document.getElementById('cpfCard');
        const openAddressModalBtn = document.getElementById('addressCard');
        const cpfModalOverlay = document.getElementById('cpfModalOverlay');
        const addressModal = document.getElementById('addressModal');
        const cpfForm = document.getElementById('cpfForm');
        const cpfInput = document.getElementById('cpfInput');
        const confirmCpfBtn = document.getElementById('confirmCpfBtn');
        const contactPhoneInput = document.getElementById('contactPhone');
        const addressForm = document.getElementById('addressForm');
        const cepInput = document.getElementById('cepInput');
        const ufInput = document.getElementById('ufInput');
        const cityInput = document.getElementById('cityInput');
        const neighborhoodInput = document.getElementById('neighborhoodInput');
        const streetInput = document.getElementById('streetInput');
        const addressText = document.getElementById('addressText');
        const cpfText = document.getElementById('cpfText');
        const cpfValue = document.getElementById('cpfValue');

        // --- ELEMENTOS DO CHECKOUT ---
        const fazerPedidoBtn = document.getElementById('fazerPedidoBtn');
        const timerDisplay = document.getElementById('cupomTimer');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // --- ELEMENTOS DO MODAL PIX ---
        const pixModalOverlay = document.getElementById('pixModalOverlay');
        const closePixModalBtn = document.getElementById('closePixModalBtn');
        const pixQrCodeContainer = document.getElementById('pixQrCodeContainer');
        const pixCodeInput = document.getElementById('pixCodeInput');
        const copyPixBtn = document.getElementById('copyPixBtn');
        const pixTimer = document.getElementById('pixTimer');

        // <-- NOVO: Elementos do Modal de Sucesso -->
        const paymentSuccessModal = document.getElementById('paymentSuccessModal');
        const closeSuccessModalBtn = document.getElementById('closeSuccessModalBtn');

        // --- POPUPS DE FEEDBACK (Formulários) ---
        const successPopup = document.getElementById('successPopup');
        const errorPopup = document.getElementById('errorPopup');
        function showSuccessPopup(message) { successPopup.textContent = message; successPopup.classList.add('show'); setTimeout(() => { successPopup.classList.remove('show'); }, 2000); }
        function showErrorPopup(message) { errorPopup.textContent = message; errorPopup.classList.add('show'); setTimeout(() => { errorPopup.classList.remove('show'); }, 3000); }

        // --- FUNÇÕES DE MÁSCARA E VALIDAÇÃO (Sem alteração) ---
        function applyPhoneMask(value) {
            value = value.replace(/\D/g, ''); value = value.substring(0, 11); value = value.replace(/^(\d{2})(\d)/g, '($1) $2'); value = value.replace(/(\d{5})(\d)/, '$1-$2'); return value;
        }
        contactPhoneInput.addEventListener('input', function(e) { e.target.value = applyPhoneMask(e.target.value); });
        function applyCpfMask(value) { value = value.replace(/\D/g, ''); value = value.replace(/(\d{3})(\d)/, '$1.$2'); value = value.replace(/(\d{3})(\d)/, '$1.$2'); value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); return value; }
        function validateCpf(cpf) {
            cpf = cpf.replace(/[^\d]/g, ''); if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false; let sum, remainder; sum = 0; for (let i = 1; i <= 9; i++) sum += parseInt(cpf.substring(i - 1, i)) * (11 - i); remainder = (sum * 10) % 11; if ((remainder === 10) || (remainder === 11)) remainder = 0; if (remainder !== parseInt(cpf.substring(9, 10))) return false; sum = 0; for (let i = 1; i <= 10; i++) sum += parseInt(cpf.substring(i - 1, i)) * (12 - i); remainder = (sum * 10) % 11; if ((remainder === 10) || (remainder === 11)) remainder = 0; if (remainder !== parseInt(cpf.substring(10, 11))) return false; return true;
        }
        function checkCpfInput() { if (cpfInput.value.length === 14) { confirmCpfBtn.disabled = false; } else { confirmCpfBtn.disabled = true; } }
        cpfInput.addEventListener('input', function(e) { e.target.value = applyCpfMask(e.target.value); checkCpfInput(); });
        function applyCepMask(value) { value = value.replace(/\D/g, ''); value = value.replace(/^(\d{5})(\d)/, '$1-$2'); return value; }
        function clearAddressFields() { ufInput.value = ''; cityInput.value = ''; neighborhoodInput.value = ''; streetInput.value = ''; }
        function fillAddressFields(data) {
            if (!("erro" in data) && data.cep) { ufInput.value = data.uf; cityInput.value = data.localidade; neighborhoodInput.value = data.bairro; streetInput.value = data.logradouro; } else { showErrorPopup("CEP não encontrado."); clearAddressFields(); }
        }
        function searchCep() {
            let cep = cepInput.value.replace(/\D/g, ''); if (cep.length === 8) { clearAddressFields(); fetch(`https://viacep.com.br/ws/${cep}/json/`).then(response => response.json()).then(data => { fillAddressFields(data); }).catch(() => { showErrorPopup("Erro ao consultar a API de CEP."); }); }
        }
        cepInput.addEventListener('input', function(e) { e.target.value = applyCepMask(e.target.value); });
        cepInput.addEventListener('blur', searchCep);

        // --- ABERTURA/FECHAMENTO DE MODAIS ---
        openCpfModalBtn.addEventListener('click', () => { cpfModalOverlay.classList.add('active'); checkCpfInput(); });
        document.getElementById('closeModalBtn').addEventListener('click', () => cpfModalOverlay.classList.remove('active'));
        openAddressModalBtn.addEventListener('click', () => { addressModal.classList.add('active'); });
        document.getElementById('closeAddressModalBtn').addEventListener('click', (e) => { e.preventDefault(); addressModal.classList.remove('active'); });

        // <-- MODIFICADO: Fechar o Modal PIX também para o Polling -->
        if(closePixModalBtn) {
            closePixModalBtn.addEventListener('click', () => {
                pixModalOverlay.classList.remove('active');
                // Para o polling se o usuário fechar manualmente
                if (paymentPollingInterval) clearInterval(paymentPollingInterval);
                console.log("Polling interrompido pelo usuário.");
            });
        }

        // <-- NOVO: Fechar o Modal de Sucesso -->
        if(closeSuccessModalBtn) {
            closeSuccessModalBtn.addEventListener('click', () => {
                paymentSuccessModal.style.display = 'none';
                // (Opcional) Redirecionar para a página inicial
                // window.location.href = 'index.php';
            });
        }

        // --- PROCESSAMENTO DE FORMULÁRIOS (Sem alteração) ---
        // Salva CPF na variável 'window'
        cpfForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const rawCpf = cpfInput.value.replace(/[^\d]/g, '');
            if (!validateCpf(rawCpf)) {
                showErrorPopup('CPF Inválido. Por favor, verifique.');
                window.isCpfSaved = false; window.savedCustomerCPF = null; return;
            }
            window.isCpfSaved = true; window.savedCustomerCPF = rawCpf;
            showSuccessPopup('CPF salvo com sucesso!');
            cpfModalOverlay.classList.remove('active');
            cpfValue.textContent = cpfInput.value;
            cpfText.textContent = 'Alterar CPF';
        });

        // Salva Endereço/Contato na variável 'window'
        addressForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const contactName = document.getElementById('contactName').value.trim();
            const contactPhoneRaw = document.getElementById('contactPhone').value.trim().replace(/\D/g, '');
            const contactEmail = document.getElementById('contactEmail').value.trim();
            const cepInputVal = document.getElementById('cepInput').value.trim();
            const addressNumber = document.querySelector('input[name="address_number"]').value.trim();
            if (contactName === '' || contactPhoneRaw.length < 10 || cepInputVal.length < 9 || addressNumber === '') {
                showErrorPopup('Preencha nome, telefone, CEP e número da residência.');
                window.isAddressSaved = false; return;
            }
            if (ufInput.value.trim() === '' || cityInput.value.trim() === '' || streetInput.value.trim() === '') {
                 showErrorPopup('Verifique e preencha o CEP corretamente para carregar o endereço completo.');
                 window.isAddressSaved = false; return;
            }
            window.isAddressSaved = true;
            window.savedCustomerName = contactName;
            window.savedCustomerPhone = contactPhoneRaw;
            window.savedCustomerEmail = contactEmail;
            showSuccessPopup('Endereço salvo com sucesso!');
            setTimeout(() => { addressModal.classList.remove('active'); }, 50);
            addressText.textContent = 'Alterar endereço de entrega';
        });


        // ----------------------------------------------------------------------
        // --- LÓGICA DO CHECKOUT (TIMER, API ERROR, API CALL) ---
        // ----------------------------------------------------------------------

        // 1. Lógica do Timer (Sem alteração)
        if (timerDisplay && !window.cupomTimerInterval) {
            let tempoRestante = 600;
            window.cupomTimerInterval = setInterval(function() {
                if (tempoRestante <= 0) {
                    clearInterval(window.cupomTimerInterval);
                    timerDisplay.textContent = "Cupom expirado";
                    if (fazerPedidoBtn) { fazerPedidoBtn.disabled = true; }
                } else {
                    tempoRestante--;
                    let minutos = Math.floor(tempoRestante / 60);
                    let segundos = tempoRestante % 60;
                    minutos = minutos < 10 ? '0' + minutos : minutos;
                    segundos = segundos < 10 ? '0' + segundos : segundos;
                    timerDisplay.textContent = `O cupom expira em ${minutos}:${segundos}`;
                }
            }, 1000);
        }

        // 2. Função para mostrar erros (Sem alteração)
        function showValidationError(title, message) {
            const validationModal = document.getElementById('validationModalOverlay');
            const initialModalTitle = document.getElementById('initialModalTitle');
            const initialModalText = document.getElementById('initialModalText');
            const closeValidationModalBtn = document.getElementById('closeValidationModal');
            let initialModalAddButton = document.getElementById('initialModalAddButton');
            if (!validationModal || !initialModalTitle || !initialModalText) return;
            initialModalTitle.textContent = title;
            initialModalText.innerHTML = message.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
            if(closeValidationModalBtn) closeValidationModalBtn.style.display = 'none';
            if(initialModalAddButton) {
                initialModalAddButton.textContent = 'OK';
                initialModalAddButton.style.display = 'block';
                const okBtnClone = initialModalAddButton.cloneNode(true);
                initialModalAddButton.parentNode.replaceChild(okBtnClone, initialModalAddButton);
                okBtnClone.addEventListener('click', function() {
                    validationModal.style.display = 'none';
                    if(closeValidationModalBtn) closeValidationModalBtn.style.display = 'flex';
                }, { once: true });
            }
            validationModal.style.display = 'flex';
        }

        // 3. Função para exibir o Modal do PIX (MODIFICADA)
        // Agora recebe 'localPedidoId' e inicia o polling
        function showPixPaymentModal(pixCode, localPedidoId, expiraEmIso) {

            // Gera o QR Code (Sem alteração)
            if (qrcodeObject) {
                qrcodeObject.clear();
                pixQrCodeContainer.innerHTML = '';
            }
            try {
                qrcodeObject = new QRCode(pixQrCodeContainer, {
                    text: pixCode, width: 200, height: 200,
                    colorDark : "#000000", colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            } catch (err) {
                console.error("Erro ao gerar QR Code:", err);
                pixQrCodeContainer.innerHTML = "Erro ao gerar QR Code.";
            }

            pixCodeInput.value = pixCode;
            pixModalOverlay.classList.add('active');
            startPixExpirationTimer(expiraEmIso); // Inicia o timer de expiração do PIX

            // <-- NOVO: Inicia o Polling de Pagamento -->
            startPaymentPolling(localPedidoId);
        }

        // 4. Funções do Timer do PIX e Copiar (Sem alteração)
        let pixExpirationInterval;
        function startPixExpirationTimer(expiraEmIso) {
            if (pixExpirationInterval) clearInterval(pixExpirationInterval);
            const dataExpiracao = new Date(expiraEmIso).getTime();
            pixExpirationInterval = setInterval(() => {
                const agora = new Date().getTime();
                const distancia = dataExpiracao - agora;
                if (distancia < 0) {
                    clearInterval(pixExpirationInterval);
                    pixTimer.textContent = "PIX EXPIRADO";
                    copyPixBtn.disabled = true;
                    copyPixBtn.textContent = "Pagamento Expirado";
                } else {
                    const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
                    const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
                    pixTimer.textContent = `Expira em ${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }
        copyPixBtn.addEventListener('click', function() {
            pixCodeInput.select();
            try {
                document.execCommand('copy');
                showSuccessPopup("Código PIX copiado!");
            } catch (err) {
                showErrorPopup("Não foi possível copiar.");
            }
        });


        // <-- NOVO: 5. Função de Início do Polling -->
        function startPaymentPolling(pedidoId) {
            // Limpa qualquer polling anterior
            if (paymentPollingInterval) clearInterval(paymentPollingInterval);

            console.log("Iniciando verificação para o Pedido ID: " + pedidoId);

            // Inicia o novo polling
            paymentPollingInterval = setInterval(() => {
                // Chama a função de verificação
                checkPaymentStatus(pedidoId);
            }, 3000); // Verifica a cada 3 segundos
        }

        // <-- NOVO: 6. Função de Checagem de Status (Polling) -->
        async function checkPaymentStatus(pedidoId) {
            // Esta função será chamada a cada 3 segundos
            console.log("Verificando status...");
            try {
                // Chama o script 'check_payment.php' que busca o status do pedido
                const response = await fetch(`api/check_payment.php?pedido_id=${pedidoId}`);

                if (!response.ok) {
                    // Se a resposta for um erro HTTP (401, 500, etc.), paramos de tentar
                    console.error("Erro no polling. Servidor respondeu com: " + response.status);
                    clearInterval(paymentPollingInterval);
                    return;
                }

                const result = await response.json();

                if (result.status === 'APROVADO') {
                    console.log("Pagamento APROVADO! Redirecionando para a página de agradecimento.");

                    // 1. PARA o polling
                    if (paymentPollingInterval) clearInterval(paymentPollingInterval);

                    // 2. PARA o timer de expiração do PIX
                    if (pixExpirationInterval) clearInterval(pixExpirationInterval);

                    // 3. FECHA o modal do PIX (por segurança)
                    pixModalOverlay.classList.remove('active');

                    // 4. REDIRECIONA para a página thanks.php com o ID do pedido
                    window.location.href = 'thanks.php?pedido_id=' + pedidoId; // <-- MUDANÇA CRÍTICA

                } else if (result.status === 'PENDENTE') {
                    // Continua...
                    console.log("Pagamento ainda PENDENTE.");
                } else {
                    // Se for 'NAO_ENCONTRADO', 'ERRO', 'CANCELADO', etc.
                    console.error("Erro ou Status Final Inesperado: " + (result.message || result.status));
                    clearInterval(paymentPollingInterval); // Para em caso de erro
                }

            } catch (error) {
                console.error("Erro fatal no polling (ex: JSON inválido ou erro de rede): ", error);
                // Para o polling se houver um erro grave
                if (paymentPollingInterval) clearInterval(paymentPollingInterval);
            }
        }

        // 7. Função PRINCIPAL de clique do botão "Fazer Pedido" (Sem alteração)
        async function handleFazerPedidoClick(e) {
            e.preventDefault();

            if (!window.isAddressSaved || !window.isCpfSaved) {
                let missingFields = [];
                if (!window.isAddressSaved) { missingFields.push('**Endereço/Contato**'); }
                if (!window.isCpfSaved) { missingFields.push('**CPF**'); }
                let fieldsList = missingFields.join(' e ');
                showValidationError( 'Atenção! Dados Faltando', `Para finalizar o pedido, você precisa primeiro preencher o ${fieldsList}.` );
                return;
            }

            if (loadingOverlay) loadingOverlay.style.display = 'flex';
            fazerPedidoBtn.disabled = true;
            fazerPedidoBtn.innerHTML = 'Processando...<span id="cupomTimer" style="font-size: 12px; color: rgba(255, 255, 255, 0.8);">Aguarde</span>';

            const dataToSend = {
                product_id: <?php echo $produto_id_base; ?>, // Pega o ID do produto do PHP
                quantity: 1,
                customer_name: window.savedCustomerName,
                customer_email: window.savedCustomerEmail,
                customer_cpf: window.savedCustomerCPF,
                customer_phone: window.savedCustomerPhone
            };

            try {
                const response = await fetch('api/create_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(dataToSend)
                });
                const result = await response.json();
                if (loadingOverlay) loadingOverlay.style.display = 'none';

                if (response.ok && result.status === 'success') {
                    // SUCESSO!
                    // Agora 'result.pedidoId' é o ID do NOSSO banco
                    showPixPaymentModal(result.pix_code, result.pedidoId, result.expira_em);
                } else {
                    // ERRO DA API
                    showValidationError('Ops! Algo deu errado', result.message || 'Ocorreu um erro desconhecido.');
                    fazerPedidoBtn.disabled = false;
                    fazerPedidoBtn.innerHTML = 'Fazer pedido<span id="cupomTimer">' + (timerDisplay?.textContent || '') + '</span>';
                }
            } catch (error) {
                if (loadingOverlay) loadingOverlay.style.display = 'none';
                console.error('Erro na chamada da API:', error);
                showValidationError('Erro Crítico no Servidor', 'Não foi possível processar seu pedido. O servidor respondeu com um erro.');
                fazerPedidoBtn.disabled = false;
                fazerPedidoBtn.innerHTML = 'Fazer pedido<span id="cupomTimer">' + (timerDisplay?.textContent || '') + '</span>';
            }
        }

        // 8. Adiciona o listener ao botão principal (Sem alteração)
        if (fazerPedidoBtn) {
            fazerPedidoBtn.addEventListener('click', handleFazerPedidoClick);
        }

    }); // Fim do DOMContentLoaded
</script>