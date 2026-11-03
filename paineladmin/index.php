<?php
// Ficheiro: admin/index.php - O Dashboard Principal (VERSÃO SOMENTE CARTÕES)

// --- 1. LÓGICA PHP E AUTENTICAÇÃO ---
session_start();

// 1. **PROTEÇÃO CORRIGIDA:** Se não estiver logado, redireciona.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. **DEFINIÇÃO DO NOME:** Obtém o nome da sessão logada.
$admin_nome_display = $_SESSION['admin_username'] ?? 'Administrador';

// Define a página atual (CRUCIAL para a Sidebar)
$current_page = basename($_SERVER['PHP_SELF']);
$current_page_base = basename($_SERVER['PHP_SELF']);

// *** AS LINHAS DE SIMULAÇÃO ABAIXO FORAM REMOVIDAS DO SEU CÓDIGO ORIGINAL: ***
// $_SESSION['admin_logged_in'] = true;
// $_SESSION['admin_username'] = 'Super Admin';
// **************************************************************************


// Inclui a configuração do banco de dados (Necessário para métricas reais)
include '../db_config.php';

// Definimos o status de sucesso para a contagem
$STATUS_APROVADO = 'APROVADO';
$STATUS_PENDENTE = 'PENDENTE';

// --- Funções Helper ---
if (!function_exists('formatCurrency')) {
    function formatCurrency($value) {
        $numericValue = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($numericValue === false) { $numericValue = 0.00; }
        return 'R$ ' . number_format($numericValue, 2, ',', '.');
    }
}


// --- 2. CONSULTAS REAIS AO BANCO DE DADOS (PostgreSQL) ---
$data_hoje_ts = date('Y-m-d 00:00:00');
$mensagem_db_erro = null;

try {
    // A. Pedidos Pendentes (Total Geral, Todos os Tempos)
    $stmt_pendentes = $pdo->prepare("SELECT COUNT(id) AS total_pedidos FROM pedidos WHERE status = ?");
    $stmt_pendentes->execute([$STATUS_PENDENTE]);
    $total_pedidos_pendentes = $stmt_pendentes->fetchColumn();


    // B. Faturamento Aprovado HOJE
    $stmt_faturamento_hoje = $pdo->prepare("
        SELECT
            COALESCE(SUM(total_amount_centavos), 0) AS faturamento_hoje_centavos,
            COUNT(id) AS pedidos_aprovados_hoje
        FROM
            pedidos
        WHERE
            status = ? AND created_at >= ?
    ");
    $stmt_faturamento_hoje->execute([$STATUS_APROVADO, $data_hoje_ts]);
    $faturamento_data = $stmt_faturamento_hoje->fetch(PDO::FETCH_ASSOC);

    $faturamento_hoje = $faturamento_data['faturamento_hoje_centavos'] / 100;
    $pedidos_aprovados_hoje = $faturamento_data['pedidos_aprovados_hoje'];


    // C. Média de Avaliações do Produto Principal (ID 1)
    $stmt_rating = $pdo->query("SELECT rating FROM produtos WHERE id = 1");
    $media_avaliacoes = $stmt_rating->fetchColumn() ?: 0.0;

    // D. Contagem total de vídeos (Criadores)
    $stmt_videos = $pdo->query("SELECT COUNT(id) FROM videos_criadores");
    $total_videos = $stmt_videos->fetchColumn();


} catch (PDOException $e) {
    $mensagem_db_erro = "Erro ao buscar dados do DB: " . $e->getMessage();
    $total_pedidos_pendentes = 0;
    $faturamento_hoje = 0;
    $pedidos_aprovados_hoje = 0;
    $media_avaliacoes = 0;
    $total_videos = 0;
}


// --- FIM DA LÓGICA PHP ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <style>
        /* CSS Básico (Mantido) */
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
            --status-aprovado: #28a745;
            --status-pendente: #ffc107;
        }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .content-header {
            margin-bottom: 2rem;
            background: var(--glass-background);
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(5px);
        }
        .content-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--primary-color); margin: 0 0 0.25rem 0; }
        .content-header p { font-size: 1rem; color: var(--light-text-color); margin: 0; }
        #particles-js { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }

        /* Estilos do Dashboard (Cartões) */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .stat-card {
            background: var(--glass-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--box-shadow);
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .stat-card-header h4 { font-size: 1rem; color: var(--light-text-color); margin: 0; font-weight: 500; }
        .stat-card-header .icon { font-size: 1.5rem; color: var(--primary-color); }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--text-color); line-height: 1.2; }
        .stat-card .card-footer { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); }
        .stat-card .card-footer a { color: var(--primary-color); text-decoration: none; font-size: 0.9em; font-weight: 500; }
        .stat-card .card-footer a:hover { text-decoration: underline; }

        /* Cores de Destaque */
        .stat-card.highlight { border-color: var(--status-pendente); background: rgba(255, 193, 7, 0.2); }
        .stat-card.highlight .icon, .stat-card.highlight .value { color: var(--status-pendente); }
        .stat-card.success .icon, .stat-card.success .value { color: var(--status-aprovado); }
        .stat-card.secondary .icon, .stat-card.secondary .value { color: var(--secondary-color); }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

    <?php
    // O sidebar usará a variável $admin_nome_display (obtida da sessão)
    include 'admin_sidebar.php';
    ?>

    <div id="particles-js"></div>

    <main class="main-content">
        <div class="content-header">
            <h1>Dashboard Principal</h1>
            <p>Visão geral em tempo real do seu checkout e das configurações principais.</p>
            <?php if ($mensagem_db_erro): ?>
                <div style="color: var(--error-color); margin-top: 10px; font-weight: 600;">⚠️ Atenção: <?php echo htmlspecialchars($mensagem_db_erro); ?></div>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">

            <div class="stat-card <?php echo ($total_pedidos_pendentes > 0) ? 'highlight' : ''; ?>">
                <div class="stat-card-header">
                    <h4>Pedidos Pendentes (Total)</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16"><path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443-.377-.443-.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"></path></svg></span>
                </div>
                <div class="value"><?php echo number_format($total_pedidos_pendentes, 0, ',', '.'); ?></div>
                <div class="card-footer">
                    <a href="relatorios.php?data_inicio=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&data_fim=<?php echo date('Y-m-d'); ?>&status_filtro=<?php echo $STATUS_PENDENTE; ?>">Gerenciar Pendentes &rarr;</a>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-card-header">
                    <h4>Faturamento do Dia</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-coin" viewBox="0 0 16 16"><path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518z"/><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11m0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12"/></svg></span>
                </div>
                <div class="value"><?php echo formatCurrency($faturamento_hoje); ?></div>
                <div class="card-footer">
                    <a href="relatorios.php?data_inicio=<?php echo date('Y-m-d'); ?>&data_fim=<?php echo date('Y-m-d'); ?>&status_filtro=<?php echo $STATUS_APROVADO; ?>">Ver Vendas de Hoje (<?php echo $pedidos_aprovados_hoje; ?>) &rarr;</a>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-card-header">
                    <h4>Configuração do Produto</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16"><path d="M8.181.814 12.78 3.53l3.054 1.76l-8.736 5.047L.815 6.007l3.054-1.76zm-.648 12.022L.526 8.76l-.16-4.225 7.648 4.417 7.648-4.417-.16 4.225zM8 16c-1.33 0-2.61-.417-3.666-1.163L.5 12.837 8 8.441l7.5 4.396-.46.335C10.61 15.583 9.33 16 8 16z"/></svg></span>
                </div>
                <div class="value">Ajustar ID 1</div>
                <div class="card-footer">
                    <a href="config.php">Gerenciar Preços e Detalhes &rarr;</a>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-card-header">
                    <h4>Credenciais API/Tags</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-google" viewBox="0 0 16 16"><path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.761q0 .265-.027.525c-.246 2.658-2.5 4.857-5.485 4.857-3.666 0-6.643-2.977-6.643-6.643S4.417 1.218 8.083 1.218c1.693 0 3.32.617 4.545 1.776l-1.637 1.637c-.754-.754-1.74-1.157-2.908-1.157-2.458 0-4.468 2.01-4.468 4.468s2.01 4.468 4.468 4.468c2.937 0 4.223-2.309 4.38-4.526h-4.38V5.632h7.354z"/></svg></span>
                </div>
                <div class="value">Google/Gateway</div>
                <div class="card-footer">
                    <a href="config_api.php">Gerenciar Chaves de Acesso &rarr;</a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Avaliação Média</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"/></svg></span>
                </div>
                <div class="value"><?php echo number_format($media_avaliacoes, 1, '.', ''); ?></div>
                <div class="card-footer">
                    <a href="config.php#rating">Ver/Ajustar Rating &rarr;</a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Vídeos de Criadores</h4>
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-camera-video" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M0 5a2 2 0 0 1 2-2h7.5a2 2 0 0 1 1.983 1.738l3.11-1.748A.5.5 0 0 1 16 4.25v7.5a.5.5 0 0 1-.724.453l-3.11-1.748A2 2 0 0 1 9.5 13H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h7.5a1 1 0 0 0 1-.748l1.492.836a.5.5 0 0 1 .508.431V5.713a.5.5 0 0 1-.508.431l-1.492.836A1 1 0 0 0 9.5 4z"/></svg></span>
                </div>
                <div class="value"><?php echo number_format($total_videos, 0, ',', '.'); ?> Ativos</div>
                <div class="card-footer">
                    <a href="config.php#videos">Gerenciar Vídeos &rarr;</a>
                </div>
            </div>

        </div>

    </main>

    <script>
    particlesJS('particles-js', {"particles":{"number":{"value":60,"density":{"enable":true,"value_area":800}},"color":{"value":"#69c9d4"},"shape":{"type":"circle"},"opacity":{"value":0.4,"random":false},"size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},"move":{"enable":true,"speed":1.5,"direction":"none","random":false,"straight":false,"out_mode":"out","bounce":false}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"repulse"},"onclick":{"enable":true,"mode":"push"},"resize":true}},"retina_detect":true});
    </script>
    </body>
</html>