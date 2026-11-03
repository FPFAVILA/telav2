<?php
// Ficheiro: admin/config_api.php - Configurações de Credenciais (Gateway e Marketing)

// 1. Inicia a sessão
session_start();

// 2. VERIFICAÇÃO DE SEGURANÇA: Se o usuário NÃO estiver logado, redireciona para o login.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit; // Crucial: Termina a execução do script imediatamente
}

// Variáveis de sessão já definidas pelo login.php
$admin_username = $_SESSION['admin_username'] ?? 'Usuário Desconhecido';
$admin_level = $_SESSION['admin_level'] ?? 'Gerente';

// O restante do código da página começa aqui, após a proteção.
include '../db_config.php';


// --- NOVO: LÓGICA DE SALVAMENTO DAS CONFIGURAÇÕES DO GATEWAY (ZeroOne) ---
$mensagem_gateway = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_gateway'])) {

    $zeroone_api_token = trim($_POST['zeroone_api_token'] ?? '');
    $zeroone_api_url = trim($_POST['zeroone_api_url'] ?? 'https://api.zeroonepay.com.br');
    $zeroone_offer_hash = trim($_POST['zeroone_offer_hash'] ?? '');
    $zeroone_product_hash = trim($_POST['zeroone_product_hash'] ?? '');
    $gateway_ativo_pix = 'zeroone'; // Valor fixo

    // Dicionário de chaves e valores a serem salvos
    $gateway_configs = [
        'zeroone_api_token' => $zeroone_api_token,
        'zeroone_api_url' => $zeroone_api_url,
        'zeroone_offer_hash' => $zeroone_offer_hash,
        'zeroone_product_hash' => $zeroone_product_hash,
        'gateway_ativo_pix' => $gateway_ativo_pix
    ];

    $pdo->beginTransaction();

    try {
        // Usa INSERT ON CONFLICT DO UPDATE (PostgreSQL) para salvar as chaves
        $sql = "INSERT INTO config_gateway (chave, valor) VALUES (:chave, :valor)
                ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor";
        $stmt = $pdo->prepare($sql);

        foreach ($gateway_configs as $chave => $valor) {
            $stmt->execute([':chave' => $chave, ':valor' => $valor]);
        }

        $pdo->commit();
        $mensagem_gateway = "Configurações da ZeroOne Pay salvas com sucesso! 🎉";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem_gateway = "Erro ao salvar configurações do Gateway: " . $e->getMessage();
    }
}
// --- FIM LÓGICA DE SALVAMENTO DO GATEWAY ---


// --- NOVO: LÓGICA DE SALVAMENTO DAS CONFIGURAÇÕES DE MARKETING (Google Tag) ---
$mensagem_marketing = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_marketing'])) {

    $google_tag_id = trim($_POST['google_tag_id'] ?? '');
    $google_conversion_label = trim($_POST['google_conversion_label'] ?? '');
    $google_currency = trim($_POST['google_currency'] ?? 'BRL');

    // Dicionário de chaves e valores a serem salvos na tabela 'configuracoes_marketing'
    $marketing_configs = [
        'google_tag_id' => $google_tag_id,
        'google_conversion_label' => $google_conversion_label,
        'google_currency' => strtoupper($google_currency) // Garantir maiúsculas para o padrão da moeda
    ];

    $pdo->beginTransaction();

    try {
        // Usa INSERT ON CONFLICT DO UPDATE (PostgreSQL)
        $sql = "INSERT INTO configuracoes_marketing (chave, valor) VALUES (:chave, :valor)
                ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor";
        $stmt = $pdo->prepare($sql);

        foreach ($marketing_configs as $chave => $valor) {
            $stmt->execute([':chave' => $chave, ':valor' => $valor]);
        }

        $pdo->commit();
        $mensagem_marketing = "Configurações de Marketing (Google) salvas com sucesso! 🚀";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem_marketing = "Erro ao salvar configurações de Marketing: " . $e->getMessage();
    }
}
// --- FIM LÓGICA DE SALVAMENTO DO MARKETING ---


// --- LÓGICA DE EXIBIÇÃO (FETCH) ---

// 1. Configurações do Gateway para exibição
$gateway_stmt = $pdo->query("SELECT chave, valor FROM config_gateway");
$gateway_config_raw = $gateway_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$gateway_config = array_merge([
    'zeroone_api_token' => '',
    'zeroone_api_url' => 'https://api.zeroonepay.com.br',
    'zeroone_offer_hash' => '',
    'zeroone_product_hash' => '',
    'gateway_ativo_pix' => 'zeroone'
], $gateway_config_raw);


// 2. Configurações de Marketing para exibição
$marketing_stmt = $pdo->query("SELECT chave, valor FROM configuracoes_marketing");
$marketing_config_raw = $marketing_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$marketing_config = array_merge([
    'google_tag_id' => '',
    'google_conversion_label' => '',
    'google_currency' => 'BRL',
], $marketing_config_raw);

// --- FIM LÓGICA DE EXIBIÇÃO ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Configurar API e Marketing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FE2C55;
            --secondary-color: #69c9d4;
            --text-color: #f1f1f1;
            --light-text-color: #a1a1a1;
            --sidebar-color: #000000;
            --background-color: #121212;
            --glass-background: rgba(255, 255, 255, 0.08);
            --border-color: rgba(255, 255, 255, 0.1);
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 250px; padding: 2rem; }
        #particles-js { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .content-header { margin-bottom: 2rem; background: var(--glass-background); padding: 1.5rem 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); backdrop-filter: blur(5px); }
        .content-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--primary-color); margin: 0 0 0.25rem 0; }
        .content-header p { font-size: 1rem; color: var(--light-text-color); margin: 0; }
        form { background: var(--glass-background); border: 1px solid var(--border-color); padding: 2rem; margin-bottom: 2rem; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        h2 { color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; font-size: 1.5rem; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--light-text-color); font-size: 0.9rem; }
        input[type="text"], input[type="number"], input[type="file"], textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: var(--border-radius); background-color: rgba(0, 0, 0, 0.2); color: var(--text-color); margin-bottom: 1rem; font-family: 'Poppins', sans-serif; }
        button[type="submit"] { background-color: var(--primary-color); color: var(--sidebar-color); font-weight: 600; font-size: 0.9rem; padding: 12px 20px; border: none; border-radius: var(--border-radius); cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; margin-top: 1rem; }
        button[type="submit"]:hover { background-color: var(--secondary-color); color: var(--text-color); box-shadow: 0 4px 10px rgba(254, 44, 85, 0.3); }
        .mensagem { padding: 1rem; margin-bottom: 2rem; border-radius: var(--border-radius); font-weight: 500; }
        .mensagem.success { background-color: rgba(40, 167, 69, 0.2); color: var(--text-color); border: 1px solid var(--success-color); }
        .mensagem.error { background-color: rgba(220, 53, 69, 0.2); color: var(--text-color); border: 1px solid var(--error-color); }
        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .grid-2-col { grid-template-columns: 1fr; gap: 1rem; }
        }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>
    <div id="particles-js"></div>
    <main class="main-content">
        <div class="content-header">
            <h1>Configurações de Credenciais e Marketing</h1>
            <p>Gerencie chaves de API para Gateways de Pagamento e IDs de rastreamento.</p>
        </div>

        <form action="config_api.php" method="POST" id="config-gateway">
            <h2>🔐 Credenciais ZeroOne Pay (PIX)</h2>
            <p style="color: var(--light-text-color); margin-top: -1rem; margin-bottom: 2rem;">Chaves de acesso e hashes para comunicação com a ZeroOne Pay.</p>

            <?php if ($mensagem_gateway): ?>
                <div class="mensagem <?php echo strpos($mensagem_gateway, 'sucesso') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($mensagem_gateway); ?>
                </div>
            <?php endif; ?>

            <label for="zeroone_api_token">ZeroOne **API Token**:</label>
            <input type="text" id="zeroone_api_token" name="zeroone_api_token"
                    value="<?php echo htmlspecialchars($gateway_config['zeroone_api_token']); ?>"
                    placeholder="Ex: 8a67c588e734c38d8213b288d6c79a95" required>

            <label for="zeroone_api_url">ZeroOne **API URL**:</label>
            <input type="text" id="zeroone_api_url" name="zeroone_api_url"
                    value="<?php echo htmlspecialchars($gateway_config['zeroone_api_url']); ?>"
                    placeholder="Ex: https://api.zeroonepay.com.br" required>

            <div class="grid-2-col" style="margin-top: 1rem;">
                <div>
                    <label for="zeroone_offer_hash">Hash da Oferta Padrão:</label>
                    <input type="text" id="zeroone_offer_hash" name="zeroone_offer_hash"
                            value="<?php echo htmlspecialchars($gateway_config['zeroone_offer_hash']); ?>"
                            placeholder="Hash de Oferta" required>
                </div>
                <div>
                    <label for="zeroone_product_hash">Hash do Produto Padrão (Item do Carrinho):</label>
                    <input type="text" id="zeroone_product_hash" name="zeroone_product_hash"
                            value="<?php echo htmlspecialchars($gateway_config['zeroone_product_hash']); ?>"
                            placeholder="Hash de Produto" required>
                </div>
            </div>

            <label for="gateway_ativo_pix" style="margin-top: 1rem;">Gateway PIX Ativo:</label>
            <input type="text" id="gateway_ativo_pix" name="gateway_ativo_pix" value="zeroone" readonly style="background-color: rgba(0, 0, 0, 0.5); cursor: not-allowed;">

            <button type="submit" name="salvar_gateway">Salvar Configurações do Gateway</button>
        </form>

        <hr style="border-color: var(--border-color); margin: 3rem 0;">

        <form action="config_api.php" method="POST" id="config-marketing">
            <h2>📈 Configurações de Marketing (Google Tag)</h2>
            <p style="color: var(--light-text-color); margin-top: -1rem; margin-bottom: 2rem;">IDs e Rótulos para rastreamento de conversões (usando a tabela `configuracoes_marketing`).</p>

            <?php if ($mensagem_marketing): ?>
                <div class="mensagem <?php echo strpos($mensagem_marketing, 'sucesso') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($mensagem_marketing); ?>
                </div>
            <?php endif; ?>

            <div class="grid-2-col">
                <div>
                    <label for="google_tag_id">Google **Tag ID** (AW-XXXXXXXX):</label>
                    <input type="text" id="google_tag_id" name="google_tag_id"
                            value="<?php echo htmlspecialchars($marketing_config['google_tag_id']); ?>"
                            placeholder="Ex: AW-123456789" required>
                </div>
                <div>
                    <label for="google_conversion_label">Rótulo/Label de Conversão:</label>
                    <input type="text" id="google_conversion_label" name="google_conversion_label"
                            value="<?php echo htmlspecialchars($marketing_config['google_conversion_label']); ?>"
                            placeholder="Ex: _AbCdEfGHIjK-lMNOpq" required>
                </div>
            </div>

            <label for="google_currency" style="margin-top: 1rem;">Moeda da Conversão (3 letras maiúsculas):</label>
            <input type="text" id="google_currency" name="google_currency"
                    value="<?php echo htmlspecialchars($marketing_config['google_currency']); ?>"
                    placeholder="Ex: BRL" maxlength="3" required>

            <button type="submit" name="salvar_marketing">Salvar Configurações de Marketing</button>
        </form>

    </main>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // JS Específico da Página (Particles)
        particlesJS('particles-js', {"particles":{"number":{"value":60,"density":{"enable":true,"value_area":800}},"color":{"value":"#69c9d4"},"shape":{"type":"circle"},"opacity":{"value":0.4,"random":false},"size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},"move":{"enable":true,"speed":1.5,"direction":"none","random":false,"straight":false,"out_mode":"out","bounce":false}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"repulse"},"onclick":{"enable":true,"mode":"push"},"resize":true}},"retina_detect":true});
    </script>
</body>
</html>