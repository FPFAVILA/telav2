<?php
// Ficheiro: main_content.php (COMPLETO: CARROSSEL, PREÇO, PARCELAMENTO, TÍTULO/RATING, FRETE, PROTEÇÃO, OFERTAS)
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
// Este ficheiro espera que a $pdo já exista (vinda da index.php).

// --- LÓGICA DE BUSCAR DADOS ---
$produto_id = 1;
$promo_config = [
    'enabled' => true,
    'coupon_enabled' => true,
    'coupon_amount' => 10,
    'coupon_label' => 'Cupom exclusivo',
    'urgency_label' => 'Oferta relâmpago',
    'timer_minutes' => 10
];
try {
    $promo_stmt = $pdo->query("SELECT enabled, coupon_enabled, coupon_amount, coupon_label, urgency_label, timer_minutes FROM public.promo_config WHERE id = 1");
    $promo_row = $promo_stmt ? $promo_stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($promo_row) {
        $promo_config['enabled'] = ($promo_row['enabled'] === true || $promo_row['enabled'] === 't' || $promo_row['enabled'] === 1);
        $promo_config['coupon_enabled'] = ($promo_row['coupon_enabled'] === true || $promo_row['coupon_enabled'] === 't' || $promo_row['coupon_enabled'] === 1);
        $promo_config['coupon_amount'] = (float)$promo_row['coupon_amount'];
        $promo_config['coupon_label'] = $promo_row['coupon_label'];
        $promo_config['urgency_label'] = $promo_row['urgency_label'];
        $promo_config['timer_minutes'] = (int)$promo_row['timer_minutes'];
    }
} catch (PDOException $e) {}
$promo_enabled = !empty($promo_config['enabled']);
$coupon_enabled = !empty($promo_config['coupon_enabled']);
$coupon_amount = max(0, (float)$promo_config['coupon_amount']);
$promo_timer_minutes = max(1, (int)$promo_config['timer_minutes']);

try {
    // 1. Busca colunas relevantes para o carrossel, preço e o novo bloco.
    $sql = "SELECT
                nome, imagem_principal, imagens_galeria,
                preco_atual, preco_antigo, rating, rating_count, sold_count
            FROM public.produtos WHERE id = ?";
    $produto_stmt = $pdo->prepare($sql);
    $produto_stmt->execute([$produto_id]);
    $produto = $produto_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        throw new Exception("Produto com ID {$produto_id} não encontrado.");
    }

    // CORREÇÃO: Usamos o nome vindo do DB como título
    $titulo_produto = htmlspecialchars($produto['nome'] ?? 'Polo Masculina...');

    // Preços
    $preco_atual = floatval($produto['preco_atual'] ?? 0);
    $preco_antigo = floatval($produto['preco_antigo'] ?? 0);

    // Dados de Rating e Vendas (com fallback, caso o DB retorne nulo)
    $rating = $produto['rating'] ?? '4.7';
    $rating_count = $produto['rating_count'] ?? '31';
    $sold_count = $produto['sold_count'] ?? '2873';

    // Tags (TEXTOS FIXOS)
    $tag_campanha = "Black Friday";

    // Calcula o percentual de desconto
    $desconto_percentual = 0;
    if ($preco_antigo > $preco_atual) {
        $desconto_percentual = round((($preco_antigo - $preco_atual) / $preco_antigo) * 100);
    }

    // 2. Processamento das imagens
    $imagens = [];
    if (!empty($produto['imagem_principal'])) {
        $imagens[] = $produto['imagem_principal'];
    }
    if (!empty($produto['imagens_galeria'])) {
        $galeria_string = trim($produto['imagens_galeria'], '{}');
        $galeria_string = str_replace('"', '', $galeria_string);

        if (!empty($galeria_string)) {
            $galeria_array = explode(',', $galeria_string);
            $imagens = array_merge($imagens, $galeria_array);
        }
    }

} catch (Exception $e) {
    // Dados de fallback para evitar quebrar o layout
    $preco_atual = 52.65; $preco_antigo = 100.00; $desconto_percentual = 47;
    $rating = '4.7'; $rating_count = '31'; $sold_count = '2873';
    $tag_campanha = "Black Friday";
    $titulo_produto = "Produto de Exemplo com Título Longo...";
    $imagens = ['placeholder.jpg'];
    $nome_produto = 'Erro ao carregar produto';
}

if (empty($imagens)) {
    $imagens[] = 'placeholder.jpg';
}

$total_imagens = count($imagens);

// --- CÁLCULO DINÂMICO DE DATAS (1 SEMANA A PARTIR DE HOJE) ---
$data_hoje = new DateTime('now');
$data_min = clone $data_hoje;
$data_max = clone $data_hoje;

$data_min->modify('+5 weekdays');
$data_max->modify('+10 weekdays');
$data_entrega_formatada = $data_min->format('j') . ' – ' . $data_max->format('j \d\e M');

// --- TEXTOS FIXOS DO LAYOUT ---
$texto_prefixo_preco = "";
$texto_novos_clientes = "Novos clientes";
$texto_apenas = "apenas";
// Calcula a porcentagem real do desconto do produto para exibir na faixa de cupom
$desconto_percentual_real = 0;
if ($preco_antigo > $preco_atual && $preco_antigo > 0) {
    $desconto_percentual_real = round((($preco_antigo - $preco_atual) / $preco_antigo) * 100);
}
$texto_cupom_desconto = $desconto_percentual_real . "% OFF - De R$ " . number_format($preco_antigo, 2, ',', '.') . " por R$ " . number_format($preco_atual, 2, ',', '.');
$texto_frete_gratis = "Frete grátis";
$texto_taxa_envio = "Taxa de envio: R$ 8,40";

// --- TEXTOS FIXOS DO BLOCO DE PROTEÇÃO E OFERTAS ---
$texto_protecao = "Proteção do cliente";
$vantagens = [
    "Devolução gratuita",
    "Reembolso automático por danos",
    "Pagamento seguro",
    "Cupom por atraso na coleta"
];
$titulo_ofertas = "Ofertas";
$titulo_cupom_envio = "Cupom de envio";
$descricao_cupom = "Desconto de R$ " . number_format($coupon_amount, 2, ',', '.') . " no frete em pedidos acima de R$ 29";
$texto_resgatar = "Resgatar";
?>

<style>
/* ========================================= */
/* --- CSS GERAL E CARROSSEL --- */
/* ========================================= */
:root {
    --header-max-width: 768px;
    --cor-vermelha-principal: #FE2C55;
    --cor-vermelha-escura: #E0234D;
    --cor-fundo-separador: #F5F5F5;
    --cor-texto-principal: #161823;
    --cor-texto-secundario: #6C6C80;
    --cor-tag-campanha-fundo: #161823;
    --cor-frete-verde: #00BFA5;
    --cor-fundo-cupom-pink: #FFF0F0;
    --cor-fundo-black-friday: #161823;
    --cor-escudo-protecao: #161823;
    --cor-check-protecao: #00BFA5;
    --tt-radius: 12px;
    --tt-radius-sm: 8px;
}

body {
    margin: 0;
}

.main-content-wrapper {
    width: 100%;
    margin: 0 auto;
    max-width: var(--header-max-width);
    background-color: #FFFFFF;
    position: relative;
    cursor: grab;
    box-shadow: none;
    border-radius: 12px 12px 0 0;
    overflow: hidden;
}
.main-content-wrapper:active {
    cursor: grabbing;
}

/* --- CARROSSEL --- */
.product-carousel {
    width: 100%;
    aspect-ratio: 1 / 1;
    position: relative;
    overflow: hidden;
    touch-action: pan-y;
    background-color: #F2F4F5;
    border-radius: 0;
}
/* TikTok Shop usa apenas o contador, sem dots */
.carousel-dots {
    display: none;
}
/* Badge de desconto no canto superior esquerdo do carrossel - estilo TikTok Shop */
.carousel-discount-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #FF6A00 0%, #FF8515 100%);
    color: #FFFFFF;
    font-size: 13px;
    font-weight: 800;
    padding: 5px 12px;
    border-radius: 8px;
    z-index: 5;
    box-shadow: 0 2px 8px rgba(255,106,0,0.3);
    letter-spacing: -0.3px;
}

.carousel-track {
    display: flex;
    width: <?php echo $total_imagens * 100; ?>%;
    height: 100%;
    will-change: transform;
}
.carousel-track.dragging {
    transition: none !important;
}
.carousel-slide {
    width: calc(100% / <?php echo $total_imagens; ?>);
    height: 100%;
    flex-shrink: 0;
    user-select: none;
}
.carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    pointer-events: none;
}
.pagination-badge {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background-color: rgba(0, 0, 0, 0.45);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    z-index: 5;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* ========================================= */
/* --- BLOCO DE PREÇO (TikTok Shop style) --- */
/* ========================================= */
.price-bar-red {
    background: linear-gradient(90deg, #FF4B1F 0%, #FF6A00 100%);
    color: #FFFFFF;
    padding: 8px 14px;
    position: relative;
    overflow: hidden;
    border: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-height: 48px;
    box-sizing: border-box;
}
.price-bar-red .tt-price-left {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-shrink: 1;
    min-width: 0;
}
.price-bar-red .tt-discount-badge {
    background: #FFFFFF;
    color: #FF6A00;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 5px;
    border-radius: 4px;
    line-height: 1.2;
    flex-shrink: 0;
    white-space: nowrap;
}
.price-bar-red .tt-price-group {
    display: flex;
    align-items: baseline;
    white-space: nowrap;
}
.price-bar-red .tt-price-group .tt-cur {
    font-size: 13px;
    font-weight: 700;
    margin-right: 1px;
}
.price-bar-red .tt-price-group .tt-num {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.8px;
    line-height: 1;
}
.price-bar-red .tt-old-price {
    font-size: 13px;
    text-decoration: line-through;
    color: rgba(255,255,255,0.7);
    font-weight: 500;
    flex-shrink: 0;
}
.price-bar-red .tt-timer {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    white-space: nowrap;
}
.price-bar-red .tt-timer svg {
    width: 13px;
    height: 13px;
    fill: #FFFFFF;
    flex-shrink: 0;
}
.price-bar-red .tt-timer .tt-timer-label {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,0.9);
}
.price-bar-red .tt-timer .tt-timer-time {
    font-size: 13px;
    font-weight: 700;
    color: #FFFFFF;
}

/* --- Cartão de cupom --- */
.promo-coupon-card {
    margin: 8px 14px 0;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border: 1px solid #ffd0d9;
    border-radius: 10px;
    background: linear-gradient(100deg, #fff5f7, #ffffff);
    animation: promoCardIn 0.5s ease both;
}
.promo-coupon-copy { min-width: 0; }
.promo-coupon-copy strong { display: block; color: #161823; font-size: 13px; }
.promo-coupon-copy span { display: block; margin-top: 2px; color: #6c6c80; font-size: 11px; }
.promo-coupon-button {
    border: 0;
    border-radius: 16px;
    padding: 7px 12px;
    background: #fe2c55;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 3px 10px rgba(254,44,85,0.2);
}
.promo-coupon-card.applied { border-color: #8bd8c8; background: #f0fffb; }
.promo-coupon-card.applied .promo-coupon-button { background: #00a884; }
@keyframes promoCardIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* --- Estilo legado (quando promo desativada) --- */
.price-bar-red-legacy {
    background-color: #FFFFFF;
    color: var(--cor-texto-principal);
    padding: 14px 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    position: relative;
    border-bottom: 1px solid #F0F0F0;
    margin-bottom: 0px;
}

/* Timer de oferta (estilo legado) */
.timer-container {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #FFF0F0 0%, #FFE0E5 100%);
    padding: 3px 8px;
    border-radius: 100px;
    z-index: 3;
    font-size: 11px;
    line-height: 1;
    color: var(--cor-vermelha-principal);
    gap: 4px;
    margin-bottom: 8px;
    font-weight: 600;
    border: 1px solid rgba(254,44,85,0.08);
}
.timer-container svg { width: 11px; height: 11px; fill: var(--cor-vermelha-principal); }
.timer-container .timer-time { font-weight: 700; letter-spacing: -0.3px; }

/* Layout dos detalhes de preço (legado) */
.price-details-top { display: flex; justify-content: flex-start; align-items: baseline; margin-bottom: 4px; position: relative; z-index: 2; gap: 8px; flex-wrap: wrap; }
.price-left { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }

/* Tag de desconto (legado) */
.discount-tag { background-color: var(--cor-vermelha-principal); color: #FFFFFF; font-size: 12px; font-weight: 800; padding: 3px 8px; border-radius: 5px; display: inline-flex; align-items: center; line-height: 1; letter-spacing: -0.3px; }
.discount-tag .discount-percent { font-size: 13px; font-weight: 800; }

/* Preço atual (legado) */
.current-price-group { display: flex; align-items: baseline; font-weight: 800; }
.current-price-group .prefix { font-size: 15px; margin-right: 2px; color: var(--cor-vermelha-principal); font-weight: 800; }
.current-price-group .price-value { font-size: 28px; line-height: 1; color: var(--cor-vermelha-principal); letter-spacing: -1.2px; font-weight: 800; }

/* Linha inferior do preço (legado) */
.price-details-bottom { display: flex; justify-content: flex-start; align-items: baseline; position: relative; z-index: 2; gap: 8px; }
.old-price { font-size: 14px; text-decoration: line-through; color: #999; line-height: 1; font-weight: 500; }
.apenas-text { font-size: 12px; font-weight: 400; color: var(--cor-texto-secundario); }
.customer-tag { font-size: 11px; font-weight: 500; display: flex; align-items: center; gap: 4px; color: var(--cor-texto-secundario); margin-top: 4px; }
.customer-tag svg { color: var(--cor-vermelha-principal); width: 14px; height: 14px; }
.current-price-group svg { display: none; }

/* --- Animação preço caindo --- */
@keyframes priceDrop {
    0% { opacity: 0; transform: translateY(-20px) scale(1.3); }
    15% { opacity: 1; transform: translateY(-14px) scale(1.15); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes priceReveal {
    0% { opacity: 0; transform: translateY(10px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes badgePop {
    0% { opacity: 0; transform: scale(0); }
    50% { opacity: 1; transform: scale(1.15); }
    100% { opacity: 1; transform: scale(1); }
}
@keyframes timerSlide {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ========================================= */
/* --- CSS BLOCO DE INFO AGRUPADO --- */
/* ========================================= */
.info-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 14px;
    border-bottom: none;
    cursor: pointer;
}

/* ========================================= */
/* --- SELETOR DE VARIANTES (barra + modal TikTok Shop) --- */
/* ========================================= */
.variant-selector-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 15px;
    background-color: #FFFFFF;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    border-bottom: 1px solid #F0F0F0;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}
.variant-bar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.variant-bar-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--cor-texto-principal);
}
.variant-bar-selected {
    font-size: 13px;
    color: var(--cor-vermelha-principal);
    font-weight: 500;
}
.variant-bar-chevron {
    width: 16px;
    height: 16px;
    color: #AAAAAA;
    transition: transform 0.2s ease;
}
.variant-selector-bar.open .variant-bar-chevron {
    transform: rotate(180deg);
}

/* --- MODAL DE VARIANTES (bottom sheet estilo TikTok Shop) --- */
.variant-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 3000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.variant-modal-overlay.show {
    opacity: 1;
    pointer-events: auto;
}
.variant-modal {
    position: fixed;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%) translateY(100%);
    width: 100%;
    max-width: var(--header-max-width);
    max-height: 85vh;
    background-color: #FFFFFF;
    border-radius: 16px 16px 0 0;
    z-index: 3001;
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
}
.variant-modal.show {
    transform: translateX(-50%) translateY(0);
}
.variant-modal-handle {
    width: 36px;
    height: 4px;
    border-radius: 2px;
    background-color: #E0E0E0;
    margin: 8px auto 0;
    flex-shrink: 0;
}
.variant-modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    border: none;
    background-color: #F5F5F5;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #6C6C80;
    line-height: 1;
    z-index: 1;
}
.variant-modal-close:active {
    background-color: #E0E0E0;
}
.variant-modal-product {
    display: flex;
    gap: 12px;
    padding: 12px 16px 16px;
    border-bottom: 1px solid #F0F0F0;
}
.variant-modal-product img {
    width: 64px;
    height: 64px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}
.variant-modal-product-info {
    flex: 1;
    min-width: 0;
}
.variant-modal-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
}
.variant-modal-price .vmp-current {
    font-size: 22px;
    font-weight: 800;
    color: var(--cor-vermelha-principal);
}
.variant-modal-price .vmp-old {
    font-size: 13px;
    text-decoration: line-through;
    color: #999;
}
.variant-modal-price .vmp-discount {
    background-color: var(--cor-vermelha-principal);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
}
.variant-modal-shipping {
    font-size: 12px;
    color: var(--cor-frete-verde);
    font-weight: 600;
    margin-top: 4px;
}
.variant-modal-section {
    padding: 16px;
    border-bottom: 1px solid #F0F0F0;
}
.variant-modal-section:last-of-type {
    border-bottom: none;
}
.variant-modal-section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--cor-texto-principal);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.variant-modal-section-title .required-dot {
    color: var(--cor-vermelha-principal);
    font-size: 10px;
}
.variant-modal-opts {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.variant-opt {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border: 2px solid #E0E0E0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--cor-texto-principal);
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    background-color: #FFFFFF;
}
.variant-opt:active {
    background-color: #F5F5F5;
}
.variant-opt.selected {
    border-color: var(--cor-vermelha-principal);
    background-color: #FFF7ED;
    color: var(--cor-vermelha-principal);
    font-weight: 600;
}
.variant-modal-footer {
    position: sticky;
    bottom: 0;
    background-color: #FFFFFF;
    padding: 12px 16px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    border-top: 1px solid #F0F0F0;
    z-index: 2;
}
.variant-modal-confirm {
    width: 100%;
    height: 46px;
    border: none;
    border-radius: 24px;
    background: linear-gradient(135deg, #FF6A00 0%, #FF8515 100%);
    color: #FFFFFF;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 2px 12px rgba(255, 106, 0, 0.25);
    transition: opacity 0.2s ease;
}
.variant-modal-confirm:disabled {
    opacity: 0.5;
    box-shadow: none;
}
.variant-modal-confirm:active {
    opacity: 0.85;
}
.variant-modal-error {
    display: none;
    text-align: center;
    font-size: 12px;
    color: var(--cor-vermelha-principal);
    margin-bottom: 8px;
    font-weight: 500;
}

.coupon-bar {
    background: linear-gradient(100deg, #FFF0F3 0%, #FFE0E5 100%);
    color: var(--cor-vermelha-principal);
    font-weight: 600;
    padding: 10px 14px;
    margin: 10px 15px;
    font-size: 13px;
    line-height: 1.3;
    border-radius: var(--tt-radius-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid rgba(254,44,85,0.08);
}
.info-icon-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.coupon-bar .info-icon-left svg {
    color: var(--cor-vermelha-principal);
    width: 16px;
    height: 16px;
}
.info-bar .chevron-right {
    font-size: 12px;
    color: #AAAAAA;
    margin-left: 10px;
}
.coupon-bar .chevron-right {
    color: var(--cor-vermelha-principal);
    font-weight: 700;
}
.separator-div {
    height: 10px;
    background-color: var(--cor-fundo-separador);
}

/* ========================================= */
/* --- BLOCO TÍTULO E AVALIAÇÃO --- */
/* ========================================= */
.title-rating-block {
    border-bottom: 1px solid #F0F0F0;
    background-color: white;
    padding: 14px 16px 12px;
    margin: 0;
}

.title-line {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}
.title-line h1 {
    font-size: 17px;
    color: var(--cor-texto-principal);
    line-height: 1.35;
    font-weight: 600;
    margin: 0;
    letter-spacing: -0.3px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.campaign-tag {
    background: linear-gradient(135deg, #161823 0%, #2C2C3A 100%);
    color: #FFFFFF;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-right: 6px;
    vertical-align: middle;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.bookmark-icon {
    flex-shrink: 0;
    padding: 2px;
}
.bookmark-icon svg {
    color: var(--cor-texto-secundario);
    width: 22px;
    height: 22px;
    cursor: pointer;
    transition: color 0.2s;
}
.bookmark-icon:active svg {
    color: var(--cor-vermelha-principal);
}
.rating-line {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--cor-texto-secundario);
    margin-top: 2px;
}
.rating-line .star {
    color: #FFC000;
    font-size: 15px;
}
.rating-line .rating-count {
    font-weight: 600;
    color: var(--cor-texto-principal);
}
.rating-line .sold-count {
    padding-left: 8px;
    border-left: 1px solid #E0E0E0;
    color: var(--cor-texto-secundario);
}
.verified-badge-inline {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
}

/* ========================================= */
/* --- CSS BLOCO DE ENVIO/FRETE --- */
/* ========================================= */
.shipping-block {
    padding: 14px 16px;
    background-color: #FFFFFF;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    display: flex;
    border-top: 1px solid #F0F0F0;
    border-bottom: 1px solid #F0F0F0;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.shipping-block:active {
    background-color: #F8F8F8;
}
.shipping-left {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.shipping-left svg {
    color: var(--cor-texto-secundario);
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}
.shipping-main-line {
    font-size: 14px;
    color: var(--cor-texto-principal);
    line-height: 1.2;
    margin-bottom: 5px;
}
.shipping-main-line .free-shipping-tag {
    color: var(--cor-frete-verde);
    font-weight: 600;
    margin-right: 5px;
    background-color: rgba(0, 191, 165, 0.1);
    padding: 1px 3px;
    border-radius: 2px;
}
.shipping-details-line {
    font-size: 12px;
    color: var(--cor-texto-secundario);
    line-height: 1.4;
}
.shipping-details-line .shipping-cost-old {
    text-decoration: line-through;
}
.shipping-block .chevron-right {
    color: #AAAAAA;
}

/* ========================================= */
/* --- CSS BLOCO PROTEÇÃO DO CLIENTE --- */
/* ========================================= */
.customer-protection-block {
    padding: 14px 16px;
    background-color: #F0FBF9;
    border-bottom: 1px solid #F0F0F0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.protection-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.protection-header-title {
    display: flex;
    align-items: center;
    font-weight: 600;
    font-size: 15px;
    color: var(--cor-texto-principal);
}
.protection-header-title svg {
    width: 20px;
    height: 20px;
    stroke: var(--cor-frete-verde);
    fill: none;
    margin-right: 8px;
}
.protection-header .chevron-right {
    font-size: 12px;
    color: #AAAAAA;
}
.protection-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 15px;
    font-size: 12px;
    color: var(--cor-texto-principal);
}
.protection-item {
    display: flex;
    align-items: center;
}
.protection-item .check-mark {
    color: var(--cor-frete-verde);
    font-weight: bold;
    margin-right: 6px;
    font-size: 14px;
}

/* ========================================= */
/* --- CSS BLOCO DE OFERTAS/CUPONS --- */
/* ========================================= */

.offers-section {
    padding: 14px 16px;
    background-color: #FFFFFF;
    border-bottom: 1px solid #F0F0F0;
}
.offers-section h2 {
    font-size: 16px;
    font-weight: 600;
    color: var(--cor-texto-principal);
    margin: 0 0 12px 0;
}

.shipping-coupon-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #F0FBF9 0%, #E0F7F4 100%);
    border-radius: var(--tt-radius);
    padding: 14px 16px;
    box-shadow: 0 1px 4px rgba(0,191,165,0.08);
}

.coupon-details {
    flex-grow: 1;
    color: var(--cor-texto-principal);
    padding-right: 15px;
}
.coupon-details h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 5px 0;
}
.coupon-details p {
    font-size: 12px;
    color: var(--cor-texto-secundario);
    margin: 0;
}

.btn-resgatar {
    background: linear-gradient(135deg, #FE2C55 0%, #E0234D 100%);
    color: #FFFFFF;
    font-size: 14px;
    font-weight: 700;
    padding: 9px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 3px 10px rgba(254,44,85,0.2);
    transition: transform 0.15s ease;
}
.btn-resgatar:active {
    transform: scale(0.96);
    box-shadow: 0 1px 4px rgba(254,44,85,0.15);
}

/* ========================================= */
/* --- CSS DO POP-UP (MODAL) - TIKTOK SHOP --- */
/* ========================================= */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.65);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.35s ease;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.modal-overlay.modal-show {
    opacity: 1;
    pointer-events: auto;
}

.modal-content {
    background-color: #FFFFFF;
    padding: 36px 30px 28px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    max-width: 85%;
    transform: scale(0.7) translateY(20px);
    transition: transform 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    width: 290px;
    position: relative;
    overflow: hidden;
}

.modal-overlay.modal-show .modal-content {
    transform: scale(1) translateY(0);
}

/* Círculo de fundo do ícone */
.modal-icon-circle {
    width: 72px;
    height: 72px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E8FBF5 0%, #D4F5EC 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: iconCirclePop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.15s both;
}
@keyframes iconCirclePop {
    0%   { opacity: 0; transform: scale(0.5); }
    100% { opacity: 1; transform: scale(1); }
}

.modal-content h3 {
    color: #161823;
    font-size: 20px;
    font-weight: 700;
    margin-top: 0;
    animation: modalTextIn 0.4s ease 0.3s both;
}
.modal-content p {
    color: var(--cor-texto-secundario);
    font-size: 14px;
    line-height: 1.5;
    margin: 8px 0 0;
    animation: modalTextIn 0.4s ease 0.4s both;
}
@keyframes modalTextIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.modal-content button {
    margin-top: 24px;
    background: linear-gradient(135deg, #FE2C55 0%, #E0234D 100%);
    color: #FFFFFF;
    padding: 12px 32px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    box-shadow: 0 4px 14px rgba(254,44,85,0.25);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    animation: modalBtnIn 0.4s ease 0.5s both;
}
.modal-content button:active {
    transform: scale(0.96);
    box-shadow: 0 2px 8px rgba(254,44,85,0.2);
}
@keyframes modalBtnIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Ícone de sucesso (check verde estilo TikTok) */
.modal-success-icon {
    width: 40px;
    height: 40px;
    stroke: #00BFA5;
    stroke-width: 2.5;
    fill: none;
    stroke-dasharray: 30;
    stroke-dashoffset: 30;
    animation: iconCheckDraw 0.7s ease 0.3s forwards;
}
@keyframes iconCheckDraw {
    0%   { stroke-dashoffset: 30; opacity: 0; }
    20%  { opacity: 1; }
    100% { stroke-dashoffset: 0; opacity: 1; }
}
</style>

<main class="main-content-wrapper" id="visao-geral">

    <div class="product-carousel">
        <div class="carousel-track" id="carouselTrack">

            <?php
            $current_image_index = 1;
            foreach ($imagens as $url):
            ?>
                <div class="carousel-slide">
                    <img
                        src="<?php echo htmlspecialchars(trim($url)); ?>"
                        alt="Imagem <?php echo $current_image_index; ?> de <?php echo $nome_produto; ?>"
                    >
                </div>
            <?php
                $current_image_index++;
            endforeach;
            ?>

        </div>

        <?php if ($desconto_percentual > 0): ?>
        <span class="carousel-discount-badge">-<?php echo htmlspecialchars($desconto_percentual); ?>% OFF</span>
        <?php endif; ?>

        <span class="pagination-badge" id="paginationBadge">
            1/<?php echo $total_imagens; ?>
        </span>
    </div>

    <?php if ($promo_enabled): ?>
    <div class="price-bar-red urgency-element">
        <div class="tt-price-left">
            <?php if ($desconto_percentual > 0): ?>
            <span class="tt-discount-badge">-<?php echo htmlspecialchars($desconto_percentual); ?>%</span>
            <?php endif; ?>
            <div class="tt-price-group">
                <span class="tt-cur">R$</span>
                <span class="tt-num"><?php echo number_format($preco_atual, 2, ',', '.'); ?></span>
            </div>
            <?php if ($preco_antigo > $preco_atual): ?>
            <span class="tt-old-price">R$ <?php echo number_format($preco_antigo, 2, ',', '.'); ?></span>
            <?php endif; ?>
        </div>
        <div class="tt-timer" id="offerTimer">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"/></svg>
            <span class="tt-timer-label"><?php echo htmlspecialchars($promo_config['urgency_label']); ?></span>
            <span class="tt-timer-time"><?php echo sprintf('%02d:00', $promo_timer_minutes); ?></span>
        </div>
    </div>
    <?php if ($coupon_enabled): ?>
    <div class="promo-coupon-card urgency-element" id="promoCouponCard" style="display:none;">
        <div class="promo-coupon-copy">
            <strong><?php echo htmlspecialchars($promo_config['coupon_label']); ?></strong>
            <span>R$ <?php echo number_format($coupon_amount, 2, ',', '.'); ?> de desconto aplicado</span>
        </div>
        <button type="button" class="promo-coupon-button" id="promoCouponButton" disabled>Cupom aplicado</button>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php
    // --- CARREGA VARIANTES DO BANCO DE DADOS ---
    $variantes_config_db = ['show_variants' => true, 'show_cor' => true, 'show_modelo' => true];
    $variantes_cores = [];
    $variantes_modelos = [];
    $is_enabled = static function ($value): bool {
        return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    };
    try {
        $vc_stmt = $pdo->query("SELECT show_variants, show_cor, show_modelo FROM public.variantes_config WHERE id = 1");
        $vc_row_db = $vc_stmt ? $vc_stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($vc_row_db) {
            $variantes_config_db = [
                'show_variants' => $is_enabled($vc_row_db['show_variants'] ?? false),
                'show_cor' => $is_enabled($vc_row_db['show_cor'] ?? false),
                'show_modelo' => $is_enabled($vc_row_db['show_modelo'] ?? false),
            ];
        }
        if ($variantes_config_db['show_variants'] && $variantes_config_db['show_cor']) {
            $cores_stmt = $pdo->query("SELECT nome, cor_hex FROM public.variantes WHERE tipo = 'cor' AND ativo = true ORDER BY ordem, id");
            $variantes_cores = $cores_stmt ? $cores_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        if ($variantes_config_db['show_variants'] && $variantes_config_db['show_modelo']) {
            $modelos_stmt = $pdo->query("SELECT nome FROM public.variantes WHERE tipo = 'modelo' AND ativo = true ORDER BY ordem, id");
            $variantes_modelos = $modelos_stmt ? $modelos_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    } catch (PDOException $e) {}

    $show_variants = $is_enabled($variantes_config_db['show_variants'] ?? true);
    $show_cor_variants = $is_enabled($variantes_config_db['show_cor'] ?? true);
    $show_modelo_variants = $is_enabled($variantes_config_db['show_modelo'] ?? false);
    $tem_cores = !empty($variantes_cores);
    $tem_modelos = !empty($variantes_modelos);
    $tem_variants = $show_variants && ($tem_cores || $tem_modelos);
    ?>
    <?php if ($tem_variants): ?>
    <div class="variant-selector-bar" id="variantSelectorBar">
        <div class="variant-bar-left">
            <span class="variant-bar-label">Escolher:</span>
            <span class="variant-bar-selected" id="variantSelectedSummary">
                <?php
                $parts = [];
                if ($tem_modelos) $parts[] = 'Modelo';
                if ($tem_cores) $parts[] = 'Cor';
                echo htmlspecialchars(implode(' · ', $parts));
                ?>
            </span>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="variant-bar-chevron">
            <path d="M4.5 6l3.5 4 3.5-4"/>
        </svg>
    </div>
    <?php endif; ?>

    <div class="coupon-bar urgency-element">
        <div class="info-icon-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M4 4.85v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9z"/>
                <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3zM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9z"/>
            </svg>
            <span><?php echo htmlspecialchars($texto_cupom_desconto); ?></span>
        </div>
        <span class="chevron-right">&rsaquo;</span>
    </div>


    <div class="title-rating-block">
        <div class="title-line">
            <h1>
                <span class="campaign-tag"><?php echo htmlspecialchars($tag_campanha); ?></span>
                <?php echo $titulo_produto; ?>
            </h1>
            <span class="bookmark-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 15.566V2a1 1 0 0 0-1-1z"/>
                </svg>
            </span>
        </div>
        <div class="rating-line">
            <span class="star">★</span>
            <span><?php echo htmlspecialchars($rating); ?></span>
            <span class="rating-count">(<?php echo htmlspecialchars($rating_count); ?>)</span>
            <span class="sold-count"><?php echo htmlspecialchars($sold_count); ?> vendidos</span>
            <span class="verified-badge-inline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-left: 4px;">
                    <path d="M12 2L14.5 4.5L18 4L19 7.5L22 9.5L20.5 12.5L22 15.5L19 17.5L18 21L14.5 20.5L12 23L9.5 20.5L6 21L5 17.5L2 15.5L3.5 12.5L2 9.5L5 7.5L6 4L9.5 4.5L12 2Z" fill="#00B4E6"/>
                    <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    </div>

    <div class="shipping-block">
        <div class="shipping-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
                <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
            </svg>
            <div>
                <div class="shipping-main-line">
                    <span class="free-shipping-tag"><?php echo htmlspecialchars($texto_frete_gratis); ?></span>
                    <span>Receba até <?php echo $data_entrega_formatada; ?></span>
                </div>
                <div class="shipping-details-line">
                    <span class="shipping-cost-old"><?php echo htmlspecialchars($texto_taxa_envio); ?></span>
                </div>
            </div>
        </div>
        <span class="chevron-right">></span>
    </div>

    <div class="customer-protection-block">
        <div class="protection-header">
            <div class="protection-header-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span><?php echo htmlspecialchars($texto_protecao); ?></span>
            </div>
            <span class="chevron-right">></span>
        </div>

        <div class="protection-details-grid">
            <?php foreach ($vantagens as $vantagem): ?>
                <div class="protection-item">
                    <span class="check-mark">✓</span>
                    <span><?php echo htmlspecialchars($vantagem); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="offers-section">
        <h2><?php echo htmlspecialchars($titulo_ofertas); ?></h2>

        <div class="shipping-coupon-card">
            <div class="coupon-details">
                <h3><?php echo htmlspecialchars($titulo_cupom_envio); ?></h3>
                <p><?php echo htmlspecialchars($descricao_cupom); ?></p>
            </div>
            <button class="btn-resgatar" id="btnResgatarCupom">
                <?php echo htmlspecialchars($texto_resgatar); ?>
            </button>
        </div>
    </div>
    <div class="modal-overlay" id="successModal">
        <div class="modal-content">
            <div class="modal-icon-circle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="modal-success-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3>Cupom Resgatado</h3>
            <p>O seu desconto de R$ 20 no frete foi aplicado à sua conta.</p>
            <button id="btnCloseModal">Entendi</button>
        </div>
    </div>

    <?php if ($tem_variants): ?>
    <div class="variant-modal-overlay" id="variantModalOverlay"></div>
    <div class="variant-modal" id="variantModal">
        <div class="variant-modal-handle"></div>
        <button class="variant-modal-close" id="variantModalClose" type="button">&times;</button>

        <div class="variant-modal-product">
            <img id="variantModalImg" src="<?php echo htmlspecialchars(!empty($imagens[0]) ? trim($imagens[0]) : 'placeholder.jpg'); ?>" alt="Produto">
            <div class="variant-modal-product-info">
                <div class="variant-modal-price">
                    <span class="vmp-current">R$ <?php echo number_format($preco_atual, 2, ',', '.'); ?></span>
                    <?php if ($preco_antigo > $preco_atual): ?>
                    <span class="vmp-old">R$ <?php echo number_format($preco_antigo, 2, ',', '.'); ?></span>
                    <?php endif; ?>
                    <?php if ($desconto_percentual > 0): ?>
                    <span class="vmp-discount">-<?php echo htmlspecialchars($desconto_percentual); ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="variant-modal-shipping"><?php echo htmlspecialchars($texto_frete_gratis); ?></div>
            </div>
        </div>

        <?php if ($tem_modelos): ?>
        <div class="variant-modal-section">
            <div class="variant-modal-section-title">Modelo <span class="required-dot">*</span></div>
            <div class="variant-modal-opts" id="variantModeloOpts">
                <?php foreach ($variantes_modelos as $m): ?>
                <div class="variant-opt" data-modelo="<?php echo htmlspecialchars($m['nome']); ?>"><?php echo htmlspecialchars($m['nome']); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tem_cores): ?>
        <div class="variant-modal-section">
            <div class="variant-modal-section-title">Cor <span class="required-dot">*</span></div>
            <div class="variant-modal-opts" id="variantCorOpts">
                <?php foreach ($variantes_cores as $c): ?>
                <div class="variant-opt" data-cor="<?php echo htmlspecialchars($c['nome']); ?>">
                    <?php if (!empty($c['cor_hex'])): ?>
                    <span class="variant-opt-swatch" style="background-color: <?php echo htmlspecialchars($c['cor_hex']); ?>;"></span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($c['nome']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="variant-modal-footer">
            <div class="variant-modal-error" id="variantModalError">Por favor, selecione todas as opções obrigatórias.</div>
            <button class="variant-modal-confirm" id="variantModalConfirm">Adicionar ao carrinho</button>
        </div>
    </div>
    <?php endif; ?>


    </main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    // --- ELEMENTOS GERAIS ---
    const carousel = document.querySelector('.product-carousel');
    const track = document.getElementById('carouselTrack');
    const badge = document.getElementById('paginationBadge');
    const totalSlides = <?php echo $total_imagens; ?>;

    // Timer elements
    // Timer element
    const timerElement = document.querySelector('.tt-timer-time');

    // --- ELEMENTOS DO MODAL DE SUCESSO (CUPOM) ---
    const btnResgatar = document.getElementById('btnResgatarCupom'); // Botão na Main Content
    const successModal = document.getElementById('successModal');
    const btnCloseModal = document.getElementById('btnCloseModal'); // Botão "Entendi"

    // --- ELEMENTOS DO SELETOR DE VARIANTES ---
    const variantBar = document.getElementById('variantSelectorBar');
    const variantModalOverlay = document.getElementById('variantModalOverlay');
    const variantModal = document.getElementById('variantModal');
    const variantModalClose = document.getElementById('variantModalClose');
    const variantModalConfirm = document.getElementById('variantModalConfirm');
    const variantModalError = document.getElementById('variantModalError');
    const variantSelectedSummary = document.getElementById('variantSelectedSummary');
    const variantCorOpts = document.getElementById('variantCorOpts');
    const variantModeloOpts = document.getElementById('variantModeloOpts');

    let selectedCor = null;
    let selectedModelo = null;
    let variantModalCallback = null;

    const openVariantModal = (callback) => {
        if (!variantModal) return;
        variantModalCallback = typeof callback === 'function' ? callback : null;
        variantModalOverlay.classList.add('show');
        variantModal.classList.add('show');
        if (body) body.classList.add('scroll-lock');
        if (carousel) stopAutoPlay();
    };

    const closeVariantModal = () => {
        if (!variantModal) return;
        variantModalOverlay.classList.remove('show');
        variantModal.classList.remove('show');
        if (body) body.classList.remove('scroll-lock');
        if (carousel) startAutoPlay();
        if (variantModalError) variantModalError.style.display = 'none';
    };

    const updateVariantSummary = () => {
        if (!variantSelectedSummary) return;
        const parts = [];
        if (selectedModelo) parts.push(selectedModelo);
        if (selectedCor) parts.push(selectedCor);
        variantSelectedSummary.textContent = parts.length > 0 ? parts.join(' · ') : (
            [variantModeloOpts ? 'Modelo' : null, variantCorOpts ? 'Cor' : null].filter(Boolean).join(' · ')
        );
    };

    const validateAndConfirm = () => {
        let valid = true;
        if (variantModeloOpts && !selectedModelo) valid = false;
        if (variantCorOpts && !selectedCor) valid = false;
        if (!valid) {
            if (variantModalError) variantModalError.style.display = 'block';
            return;
        }
        if (variantModalError) variantModalError.style.display = 'none';
        closeVariantModal();
        updateVariantSummary();
        if (typeof variantModalCallback === 'function') {
            variantModalCallback();
            variantModalCallback = null;
        }
    };

    if (variantBar) {
        variantBar.addEventListener('click', () => openVariantModal());
    }
    if (variantModalClose) {
        variantModalClose.addEventListener('click', (e) => { e.stopPropagation(); closeVariantModal(); });
    }
    if (variantModalOverlay) {
        variantModalOverlay.addEventListener('click', closeVariantModal);
    }
    if (variantModalConfirm) {
        variantModalConfirm.addEventListener('click', validateAndConfirm);
    }
    if (variantCorOpts) {
        variantCorOpts.querySelectorAll('.variant-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                variantCorOpts.querySelectorAll('.variant-opt').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                selectedCor = opt.dataset.cor;
                if (variantModalError) variantModalError.style.display = 'none';
            });
        });
    }
    if (variantModeloOpts) {
        variantModeloOpts.querySelectorAll('.variant-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                variantModeloOpts.querySelectorAll('.variant-opt').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                selectedModelo = opt.dataset.modelo;
                if (variantModalError) variantModalError.style.display = 'none';
            });
        });
    }

    // Expõe para que index.php possa abrir o modal antes do checkout
    window.openVariantModal = openVariantModal;
    window.hasVariantModal = () => !!variantModal;

    // --- ELEMENTOS DO PERFIL (UNIFICAÇÃO) ---
    const resgateButtonProfile = document.getElementById('resgateButton'); // Botão do Perfil
    const openStoreModalBtn = document.getElementById('openStoreModalBtn'); // Botão "Visitar"

    // --- VARIÁVEIS DE ESTADO ---
    let slideWidth = carousel ? carousel.clientWidth : 0;
    let currentSlide = 0;
    let isDragging = false;
    let startPos = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID;
    let autoPlayInterval;
    let countdownTimerId;
    let resgateProfileListenerAttached = false; // Flag para o listener do perfil

    // --- FUNÇÕES DE LÓGICA DO CARROSSEL (MANTIDAS) ---

    function moveToSlide(index) {
        if (!track || !carousel) return;

        if (index >= totalSlides) { index = 0; }
        else if (index < 0) { index = totalSlides - 1; }

        if (!isDragging) { track.style.transition = 'transform 0.3s ease-in-out'; }

        currentSlide = index;
        currentTranslate = currentSlide * -slideWidth;
        setTransform(track, currentTranslate);
        updateBadge();
    }

    function setTransform(element, translate) {
        element.style.transform = `translateX(${translate}px)`;
    }

    function updateBadge() {
        if (badge) badge.textContent = `${currentSlide + 1}/${totalSlides}`;
    }

    // --- AUTOPLAY (MANTIDO) ---

    function startAutoPlay() {
        if (!carousel) return;
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            moveToSlide(currentSlide + 1);
        }, 3000);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    // --- LÓGICA DO TIMER (MANTIDA) ---

    function startCountdown() {
        if (!timerElement) return;
        let duration = <?php echo $promo_timer_minutes * 60; ?>;
        clearInterval(countdownTimerId);
        const timerContainer = document.getElementById('offerTimer');

        function updateTimer() {
            if (duration < 0) {
                clearInterval(countdownTimerId);
                if (timerContainer) {
                     const label = timerContainer.querySelector('.tt-timer-label');
                     const time = timerContainer.querySelector('.tt-timer-time');
                     if (label) label.textContent = 'Oferta encerrada';
                     if (time) time.textContent = '';
                }
                return;
            }

            const minutes = Math.floor(duration / 60);
            const seconds = duration % 60;
            const display = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            timerElement.textContent = display;
            duration--;
        }

        updateTimer();
        countdownTimerId = setInterval(updateTimer, 1000);
    }

    // --- LÓGICA DO POP-UP (CUPOM) UNIFICADA E CORRIGIDA ---

    /**
     * @function hideModal
     * @description Oculta o modal de sucesso, garantindo o fechamento robusto.
     */
    const hideModal = () => {
        if (!successModal) return;

        successModal.classList.remove('modal-show'); // Usa a classe CSS original

        // CORREÇÃO: Remove a opacidade e força display: none após a transição
        successModal.style.opacity = '0';
        setTimeout(() => {
            successModal.style.display = 'none';
        }, 300);

        if (carousel) {
             startAutoPlay();
        }
    };

    /**
     * @function showModal
     * @description Exibe o modal de sucesso, garantindo a abertura robusta.
     */
    const showModal = () => {
        if (!successModal) return;

        if (carousel) {
            stopAutoPlay();
        }

        // CORREÇÃO: Força display: flex, aplica a opacidade e a classe de transição
        successModal.style.display = 'flex';
        successModal.style.opacity = '1';
        successModal.classList.add('modal-show');
    };

    /**
     * @function attachResgateProfileListener
     * @description Anexa o listener ao botão de resgate do perfil, se existir.
     */
    const attachResgateProfileListener = () => {
        const resgateButtonProfileElement = document.getElementById('resgateButton');

        // ANEXA O LISTENER DE CLIQUE, CHAMANDO showModal
        if (resgateButtonProfileElement && !resgateProfileListenerAttached) {
            resgateButtonProfileElement.addEventListener('click', showModal);
            resgateProfileListenerAttached = true;
        }
    };

    // --- EVENT LISTENERS UNIFICADOS DO MODAL ---

    // 1. Ouvinte para o botão de resgate da MAIN CONTENT (CORRIGIDO)
    if (btnResgatar) {
        btnResgatar.addEventListener('click', showModal);
    }

    // 2. Fechar o modal ao clicar no botão "Entendi" (CORRIGIDO)
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', (e) => {
            e.stopPropagation();
            hideModal();
        });
    }

    // 3. Fechar o modal ao clicar fora (no overlay) (CORRIGIDO)
    if (successModal) {
        successModal.addEventListener('click', (e) => {
            if (e.target.id === 'successModal') { // Usa o ID do overlay
                hideModal();
            }
        });
    }

    // 4. CONECTA O LISTENER DO BOTÃO DE RESGATE DO PERFIL
    // Se o modal do perfil existir, anexamos o listener na abertura dele.
    if (openStoreModalBtn) {
        openStoreModalBtn.addEventListener('click', () => {
             // Damos um pequeno tempo para o modal do perfil carregar o botão #resgateButton
             setTimeout(attachResgateProfileListener, 50);
        });
    }

    // --- INTERATIVIDADE (ARRASTAR/SWIPE - MANTIDO) ---

    if (carousel) {
        carousel.addEventListener('mousedown', dragStart);
        carousel.addEventListener('touchstart', dragStart);
        carousel.addEventListener('mousemove', drag);
        carousel.addEventListener('touchmove', drag);
        carousel.addEventListener('mouseup', dragEnd);
        carousel.addEventListener('touchend', dragEnd);
        carousel.addEventListener('mouseleave', dragEnd);

        window.addEventListener('resize', () => {
            slideWidth = carousel.clientWidth;
            moveToSlide(currentSlide);
        });
    }

    function dragStart(event) {
        if (!carousel || (successModal && successModal.classList.contains('modal-show'))) return;

        event.preventDefault();
        event.stopPropagation();

        stopAutoPlay();

        if (track) track.style.transition = 'none';
        if (track) track.classList.add('dragging');

        isDragging = true;
        startPos = event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
        prevTranslate = currentTranslate;
        animationID = requestAnimationFrame(animation);
    }

    function drag(event) {
        if (!isDragging) return;

        const currentPosition = event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
        const distance = currentPosition - startPos;
        currentTranslate = prevTranslate + distance;
    }

    function animation() {
        if (track) setTransform(track, currentTranslate);
        if (isDragging) requestAnimationFrame(animation);
    }

    function dragEnd(event) {
        if (!isDragging) return;

        cancelAnimationFrame(animationID);
        isDragging = false;
        if (track) track.classList.remove('dragging');

        const movedBy = currentTranslate - prevTranslate;
        const threshold = slideWidth * 0.2;

        if (movedBy < -threshold) {
            moveToSlide(currentSlide + 1);
        } else if (movedBy > threshold) {
            moveToSlide(currentSlide - 1);
        } else {
            moveToSlide(currentSlide);
        }

        startAutoPlay();
    }

    // --- INICIALIZAÇÃO GERAL ---
    if (carousel) {
        startAutoPlay();
    }
    if (timerElement) {
        startCountdown();
    }
    // Tenta anexar o listener do perfil na inicialização como fallback
    attachResgateProfileListener();
});
</script>