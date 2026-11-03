<?php
// Arquivo: profile.php
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
// Objetivo: Restaurar o design original do cupom destacável e corrigir o JS para o botão Resgatar e Fechamento do Modal de Sucesso.

// A variável $pdo é presumida estar disponível no escopo global.
global $pdo;

// ID do produto base para buscar os dados do vendedor (AJUSTAR ID)
$produto_id_profile = 1;

// Variáveis de inicialização
$vendor_name = "Vendedor Padrão";
$sold_count = 0;
$logo_url = "https://placehold.co/60x60/CCCCCC/666666?text=Logo";
$coupon_value = "x2";
$coupon_min_order = "R$ 9";
$products = [];

if (isset($pdo)) {
    try {
        // 1. Busca os dados do vendedor (nome, vendas, logo)
        $sql_profile = "SELECT nome_vendedor, sold_count, url_logo_vendedor
                         FROM public.produtos
                         WHERE id = :product_id";
        $stmt_profile = $pdo->prepare($sql_profile);
        $stmt_profile->execute([':product_id' => $produto_id_profile]);
        $vendor_data = $stmt_profile->fetch(PDO::FETCH_ASSOC);

        if ($vendor_data) {
            $vendor_name = htmlspecialchars($vendor_data['nome_vendedor']);
            $sold_count = number_format($vendor_data['sold_count'], 0, ',', '.');
            $logo_url = htmlspecialchars($vendor_data['url_logo_vendedor']);
        }

        // 2. Busca TODOS OS PRODUTOS
        $sql_products = "SELECT
                             id, nome, descricao, preco_atual, preco_antigo, imagem_principal,
                             rating, sold_count
                           FROM
                             public.produtos
                           ORDER BY
                             id DESC
                           LIMIT 10";
        $stmt_products = $pdo->prepare($sql_products);
        $stmt_products->execute();
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erro ao buscar dados: " . $e->getMessage());
        $vendor_name = "Erro de Conexão";
        $sold_count = "N/A";
        // Fallback
        $products = [
            ['id' => 999, 'nome' => 'Cadeira gamer Ergonômica Reclinável Altura Ajustável Reclina Couro Sintético...', 'preco_atual' => 413.75, 'preco_antigo' => 599.00, 'imagem_principal' => 'https://placehold.co/150x150/FF6347/FFFFFF?text=PROD+FALLBACK', 'rating' => 5.0, 'sold_count' => 122]
        ];
    }
}
?>
<style>
/* Estilos gerais e base do modal */
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #F4F4F4; }
.store-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: #FFFFFF; z-index: 9999; display: none; flex-direction: column; transform: translateY(100%); transition: transform 0.3s ease-out; }
.store-modal.show { display: flex; transform: translateY(0); }

/* Perfil Gatilho */
.vendor-profile-section { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background-color: #FFFFFF; border-bottom: 8px solid #F5F5F5; }
.vendor-details { display: flex; align-items: center; flex-grow: 1; }
.vendor-logo { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #EEEEEE; }
.vendor-info h3 { margin: 0; font-size: 15px; font-weight: 600; color: #161823; line-height: 1.2; display: flex; align-items: center; gap: 5px; }
.vendor-info p { margin: 3px 0 0 0; font-size: 12px; color: #6C6C80; }
.visit-button { background-color: #F4F4F4; color: #161823; border: none; padding: 8px 18px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; text-decoration: none; text-align: center; flex-shrink: 0; transition: background-color 0.15s ease; }
.visit-button:active { background-color: #E8E8E8; }

/* Selo de Loja Verificada TikTok Shop */
.verified-badge { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; flex-shrink: 0; }
.verified-badge svg { width: 100%; height: 100%; }

/* Header do Modal */
.store-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; background-color: #FFFFFF; box-shadow: 0 1px 3px rgba(0,0,0,0.05); flex-shrink: 0; }
.store-header .icon-btn { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; color: #222222; cursor: pointer; flex-shrink: 0; }
.store-header .search-bar-wrapper { flex-grow: 1; margin: 0 10px; position: relative; }
.store-header .search-input { width: 100%; padding: 8px 15px 8px 35px; border: none; background-color: #F0F0F0; border-radius: 7px; font-size: 14px; height: 35px; box-sizing: border-box; }
.store-header .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #999; }
.store-header .action-icons-right { display: flex; gap: 5px; }
.store-header .share-icon svg { width: 24px; height: 24px; }
.store-header .cart-icon svg { width: 18px; height: 18px; }
.store-header .back-btn { font-size: 24px; padding: 0 5px; font-weight: 600; }

/* Detalhes do Vendedor (Dentro do Modal) */
.store-profile-area { display: flex; align-items: flex-start; justify-content: space-between; padding: 16px; background-color: #FFFFFF; flex-shrink: 0; }
.store-profile-area .vendor-details-header { display: flex; align-items: center; margin-right: 15px; }
.store-profile-area .vendor-logo-large { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #EEEEEE; flex-shrink: 0; }
.store-profile-area .vendor-info-header { align-self: flex-start; }
.store-profile-area .vendor-info-header h3 { margin: 0; font-size: 17px; font-weight: 700; color: #161823; display: flex; align-items: center; gap: 6px; }
.store-profile-area .vendor-info-header p { margin: 2px 0 0 0; font-size: 13px; color: #6C6C80; }
.store-profile-area .action-buttons-group { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
.store-profile-area .action-btn-header { padding: 8px 12px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; text-align: center; line-height: 1.2; min-width: 90px; }
.store-profile-area .follow-btn { background-color: #FE2C55; color: white; border: none; }
.store-profile-area .message-btn { background-color: #FFFFFF; color: #222222; border: 1px solid #DDDDDD; }
.store-profile-area .follow-btn.followed { background-color: #EAEAEA; color: #666666; border: 1px solid #DDDDDD; }

/* --- CSS: CUPOM DESTACÁVEL ORIGINAL (RESTAURADO) --- */
.store-coupon-bar { position: relative; overflow: hidden; padding: 10px 15px; background-color: #F8FFFF; font-size: 13px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; border: 1px dashed #BFEBE8; border-radius: 8px; margin: 10px 15px; box-shadow: none; }
.store-coupon-bar::before { content: ''; position: absolute; top: 50%; left: -8px; transform: translateY(-50%); width: 16px; height: 16px; border-radius: 50%; background-color: #FFFFFF; border-right: 1px dashed #BFEBE8; z-index: 1; }
.store-coupon-bar::after { content: ''; position: absolute; top: 50%; right: -8px; transform: translateY(-50%); width: 16px; height: 16px; border-radius: 50%; background-color: #FFFFFF; border-left: 1px dashed #BFEBE8; z-index: 1; }
.store-coupon-bar .coupon-info { line-height: 1.4; color: #444444; }
.store-coupon-bar strong { color: #00CC99; font-weight: 700; }
/* Botão Resgatar do cupom */
.store-coupon-bar .resgate-btn { background-color: #00CC99; color: white; padding: 8px 15px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; flex-shrink: 0; min-width: 80px; box-shadow: 0 2px 4px rgba(0, 204, 153, 0.4); }

/* Navegação por Abas e Lista de Produtos */
.product-content-area { flex-grow: 1; background-color: #F4F4F4; overflow-y: auto; }
.tabs-navigation { display: flex; align-items: center; background-color: #FFFFFF; border-bottom: 1px solid #EEEEEE; padding: 0 10px; position: sticky; top: 0; z-index: 900; }
.tabs-navigation .tab-item { padding: 10px 10px 8px 10px; font-size: 14px; font-weight: 500; color: #666666; cursor: pointer; position: relative; transition: color 0.2s; flex-shrink: 0; }
.tabs-navigation .tab-item.active { color: #222222; font-weight: 600; }
.tabs-navigation .tab-item.active::after { content: ''; position: absolute; bottom: 0; left: 10%; width: 80%; height: 3px; background-color: #FE2C55; border-radius: 2px; }
.tabs-navigation .view-toggle { margin-left: auto; padding: 5px; cursor: pointer; color: #666; }

/* Lista de Produtos */
.product-list-container { padding: 0 10px; background-color: #FFFFFF; }
.product-list-item { display: flex; padding: 10px 0; border-bottom: 1px solid #EEEEEE; }
.product-list-item:last-child { border-bottom: none; }
.product-image-area { width: 120px; height: 120px; flex-shrink: 0; margin-right: 10px; border-radius: 6px; overflow: hidden; }
.product-image-area img { width: 100%; height: 100%; object-fit: cover; display: block; }
.product-details { flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
.product-title { font-size: 14px; font-weight: 500; line-height: 1.3; margin: 0 0 5px 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.tags-and-stats { font-size: 12px; color: #666; margin-bottom: 5px; }
.tag-off { display: inline-block; background-color: #FF5A5A; color: white; font-weight: 600; padding: 2px 4px; border-radius: 3px; margin-right: 5px; line-height: 1; }
.stats-line { margin-top: 3px; }
.stats-line span { margin-right: 8px; }
.stats-line span:first-child { color: #FFC000; }
.price-and-action { display: flex; justify-content: space-between; align-items: flex-end; }
.price-details { display: flex; flex-direction: column; }
.current-price { font-size: 18px; font-weight: 700; color: #FE2C55; margin: 0; line-height: 1.2; }
.old-price { font-size: 12px; color: #999; text-decoration: line-through; margin-top: 2px; }
.buy-button { display: flex; align-items: center; background: linear-gradient(135deg, #FE2C55 0%, #E0234D 100%); color: white; border: none; padding: 8px 14px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; text-decoration: none; line-height: 1; flex-shrink: 0; transition: transform 0.15s ease, background-color 0.2s; box-shadow: 0 2px 8px rgba(254,44,85,0.15); }
.buy-button:active { transform: scale(0.96); }
.buy-button:hover { background: linear-gradient(135deg, #E0234D 0%, #D6234D 100%); }
.buy-button svg { margin-right: 5px; }
.no-more-products { text-align: center; padding: 30px; color: #999; font-size: 14px; }

/* --- CSS: POPUP/MODAL DE SUCESSO DO CUPOM --- */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s;
}
.modal-overlay.show { display: flex; opacity: 1; }
.modal-content {
    background-color: #FFFFFF; border-radius: 12px; padding: 30px; text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); transform: scale(0.8); transition: transform 0.3s ease-out;
}
.modal-overlay.show .modal-content { transform: scale(1); }
.modal-success-icon { width: 60px; height: 60px; color: #00CC99; stroke-width: 1.5; margin-bottom: 15px; }
.modal-content h3 { margin: 0 0 5px 0; font-size: 20px; font-weight: 700; color: #222; }
.modal-content p { margin: 0 0 20px 0; font-size: 14px; color: #666; }
#btnCloseModal { background-color: #FE2C55; color: white; border: none; padding: 10px 20px; font-size: 16px; font-weight: 600; border-radius: 6px; cursor: pointer; }

</style>

<div class="vendor-profile-section">
    <div class="vendor-details">
        <img src="<?php echo $logo_url; ?>" alt="Logo do Vendedor" class="vendor-logo">
        <div class="vendor-info">
            <h3><?php echo $vendor_name; ?>
                <span class="verified-badge">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L14.5 4.5L18 4L19 7.5L22 9.5L20.5 12.5L22 15.5L19 17.5L18 21L14.5 20.5L12 23L9.5 20.5L6 21L5 17.5L2 15.5L3.5 12.5L2 9.5L5 7.5L6 4L9.5 4.5L12 2Z" fill="#00B4E6"/>
                        <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </h3>
            <p><?php echo $sold_count; ?> vendido(s)</p>
        </div>
    </div>
    <button class="visit-button" id="openStoreModalBtn">Visitar</button>
</div>

<div class="store-modal" id="storeModal">

    <div class="store-header">
        <span class="icon-btn back-btn" id="closeStoreModalBtn">&lt;</span>
        <div class="search-bar-wrapper">
            <input type="text" placeholder="Pesquisar" class="search-input">
            <span class="search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
            </span>
        </div>
        <div class="action-icons-right">
            <span class="icon-btn share-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 48 48" fill="currentColor">
                    <path d="M23.82 3.5A2 2 0 0 0 20.5 5v10.06C8.7 15.96 1 25.32 1 37a2 2 0 0 0 3.41 1.41c4.14-4.13 10.4-5.6 16.09-5.88v9.97a2 2 0 0 0 3.3 1.52l21.5-18.5a2 2 0 0 0 .02-3.02z"></path>
                </svg>
            </span>
            <span class="icon-btn cart-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l1.25 5h8.22l1.25-5zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"></path>
                </svg>
            </span>
        </div>
    </div>

    <div class="store-profile-area">
        <div class="vendor-details-header">
            <img src="<?php echo $logo_url; ?>" alt="Logo do Vendedor" class="vendor-logo-large">
            <div class="vendor-info-header">
                <h3><?php echo $vendor_name; ?>
                    <span class="verified-badge">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L14.5 4.5L18 4L19 7.5L22 9.5L20.5 12.5L22 15.5L19 17.5L18 21L14.5 20.5L12 23L9.5 20.5L6 21L5 17.5L2 15.5L3.5 12.5L2 9.5L5 7.5L6 4L9.5 4.5L12 2Z" fill="#00B4E6"/>
                            <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </h3>
                <p><?php echo $sold_count; ?> vendido(s)</p>
            </div>
        </div>
        <div class="action-buttons-group">
            <button class="action-btn-header follow-btn" id="followButton">Seguir</button>
            <button class="action-btn-header message-btn">Mensagem</button>
        </div>
    </div>

    <div class="store-coupon-bar">
        <div class="coupon-info">
            Cupom de envio <strong><?php echo $coupon_value; ?></strong><br>
            em pedidos acima de R$ 9
        </div>
        <button class="resgate-btn" id="resgateButton">Resgatar</button>
    </div>

    <div class="product-content-area">

        <div class="tabs-navigation">
            <span class="tab-item active" data-tab="recommended">Recomendado</span>
            <span class="tab-item" data-tab="sold">Mais Vendidos</span>
            <span class="tab-item" data-tab="new">Lançamentos</span>
            <span class="view-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-filter-square" viewBox="0 0 16 16">
                    <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"></path>
                    <path d="M6 11.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"></path>
                </svg>
            </span>
        </div>

        <div class="product-list-container" id="productContainer">

            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product):
                    $discount = 0;
                    if (isset($product['preco_antigo']) && $product['preco_antigo'] && $product['preco_antigo'] > $product['preco_atual']) {
                        $discount = round(($product['preco_antigo'] - $product['preco_atual']) / $product['preco_antigo'] * 100);
                    }
                ?>
                    <div class="product-list-item" data-product-id="<?php echo $product['id']; ?>">

                        <div class="product-image-area">
                            <img src="<?php echo htmlspecialchars($product['imagem_principal']); ?>" alt="<?php echo htmlspecialchars($product['nome']); ?>">
                        </div>

                        <div class="product-details">

                            <div>
                                <h4 class="product-title"><?php echo htmlspecialchars($product['nome']); ?></h4>
                                <div class="tags-and-stats">
                                    <?php if ($discount > 0): ?>
                                        <span class="tag-off"><?php echo $discount; ?>% OFF</span>
                                    <?php endif; ?>
                                    <span class="tag-off" style="background-color: #A0A0A0;"><?php echo rand(3, 6); ?>x sem juros</span>
                                </div>
                                <div class="stats-line">
                                    <span>★</span>
                                    <span><?php echo number_format($product['rating'], 1, ',', '.'); ?></span>
                                    <span>| <?php echo $product['sold_count']; ?> vendido(s)</span>
                                </div>
                            </div>

                            <div class="price-and-action">
                                <div class="price-details">
                                    <p class="current-price">R$ <?php echo number_format($product['preco_atual'], 2, ',', '.'); ?></p>
                                    <?php if (isset($product['preco_antigo']) && $product['preco_antigo'] > $product['preco_atual']): ?>
                                        <p class="old-price">R$ <?php echo number_format($product['preco_antigo'], 2, ',', '.'); ?></p>
                                    <?php endif; ?>
                                </div>

                                <a href="index.php?product_id=<?php echo $product['id']; ?>" class="buy-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9z"/>
                                        <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                    </svg>
                                    Comprar
                                </a>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-more-products">Não há mais produtos</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="modal-overlay" id="successModal">
    <div class="modal-content">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="modal-success-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
        </svg>
        <h3>Cupom Resgatado</h3>
        <p>O cupom de envio **<?php echo $coupon_value; ?>** foi aplicado à sua conta!</p>
        <button id="btnCloseModal">Entendi</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const openBtn = document.getElementById('openStoreModalBtn');
    const storeModal = document.getElementById('storeModal');
    const closeBtn = document.getElementById('closeStoreModalBtn');
    const followBtn = document.getElementById('followButton');
    const tabs = document.querySelectorAll('.tab-item');

    // --- Elementos do Cupom ---
    const resgateBtn = document.getElementById('resgateButton');
    const successModalOverlay = document.getElementById('successModal');
    const closeModalBtn = document.getElementById('btnCloseModal');

    // --- Funções de Controle do Modal de Sucesso ---

    /**
     * @function showModal
     * @description Exibe o modal de sucesso do cupom adicionando a classe 'show'.
     */
    const showModal = () => {
        // Primeiro, garante que o display é flex para que a transição de opacidade funcione
        successModalOverlay.style.display = 'flex'; // <--- CORREÇÃO: Força a visibilidade
        setTimeout(() => {
            successModalOverlay.classList.add('show');
        }, 10); // Pequeno atraso para garantir que a transição de opacity ocorra
    };

    /**
     * @function hideModal
     * @description Oculta o modal de sucesso do cupom removendo a classe 'show' e forçando o display: none.
     */
    const hideModal = () => {
        // Remove a classe 'show' para iniciar a transição de opacity
        successModalOverlay.classList.remove('show');

        // CORREÇÃO FINAL: Espera a transição (0.3s no CSS) terminar para aplicar display: none
        // Isso garante que ele não esteja mais visível no fluxo da página
        setTimeout(() => {
            successModalOverlay.style.display = 'none'; // <--- CORREÇÃO: Força o display: none
        }, 300); // 300ms = Duração da transição CSS (opacity 0.3s)
    };

    // Lógica do Botão Resgatar
    if (resgateBtn) {
        resgateBtn.addEventListener('click', () => {
            console.log('Botão Resgatar Clicado. Mostrando Popup.');
            showModal();
        });
    }

    // Lógica CORRIGIDA: Fechar o modal ao clicar no botão "Entendi"
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            hideModal();
        });
    }

    // Lógica CORRIGIDA: Fechar o modal clicando fora (no overlay)
    if (successModalOverlay) {
        successModalOverlay.addEventListener('click', (e) => {
            if (e.target.id === 'successModal') {
                hideModal();
            }
        });
    }

    // --- Funções de Controle do Modal Principal (Mantidas) ---
    const closeStoreModal = () => {
        storeModal.classList.remove('show');
        document.body.style.overflow = '';
    };

    if (openBtn) {
        openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            hideModal();
            storeModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeStoreModal();
        });
    }

    // Lógica do Botão Seguir
    if (followBtn) {
        followBtn.addEventListener('click', () => {
            const isFollowed = followBtn.classList.contains('followed');

            if (isFollowed) {
                followBtn.classList.remove('followed');
                followBtn.textContent = 'Seguir';
            } else {
                followBtn.classList.add('followed');
                followBtn.textContent = 'Seguindo';
            }
        });
    }

    // Lógica de Navegação por Abas (Visual)
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });
});
</script>