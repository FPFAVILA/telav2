<?php
// Ficheiro: admin/finance.php - Relatórios de Faturamento Aprimorado (V2)

// --- 1. LÓGICA PHP E AUTENTICAÇÃO ---
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

$STATUS_APROVADO = 'APROVADO';
$STATUS_PENDENTE = 'PENDENTE';
$mensagem_status = null; // Mensagem de sucesso/erro
$mensagem_erro_db = null;


// --- 2. LÓGICA DE EXCLUSÃO (DELETE) ---

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['excluir_pedido_id']) || isset($_POST['excluir_todos_pendentes']))) {

    $pdo->beginTransaction();
    try {
        if (isset($_POST['excluir_pedido_id'])) {
            // EXCLUSÃO INDIVIDUAL
            $pedido_id = filter_input(INPUT_POST, 'excluir_pedido_id', FILTER_VALIDATE_INT);
            if ($pedido_id) {
                $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
                $stmt->execute([$pedido_id]);
                $mensagem_status = "Pedido #{$pedido_id} excluído com sucesso. 🗑️";
            } else {
                $mensagem_status = "Erro: ID de pedido inválido.";
            }

        } elseif (isset($_POST['excluir_todos_pendentes'])) {
            // EXCLUSÃO EM MASSA (Apenas Pendentes, por segurança)
            $stmt = $pdo->prepare("DELETE FROM pedidos WHERE status = ?");
            $stmt->execute([$STATUS_PENDENTE]);
            $count = $stmt->rowCount();
            $mensagem_status = "{$count} pedidos PENDENTES excluídos com sucesso. 🧹";
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem_status = "Erro ao excluir: " . $e->getMessage();
    }
}


// --- 3. TRATAMENTO DOS FILTROS E VARIÁVEIS ---

$data_hoje = date('Y-m-d');
// Captura e define filtros de data e status
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? $data_hoje;
$status_filtro = $_GET['status_filtro'] ?? 'TODOS'; // 'TODOS', 'APROVADO', 'PENDENTE'

// Garante que as datas são válidas
if ($data_inicio > $data_fim) {
    list($data_inicio, $data_fim) = [$data_fim, $data_inicio];
}

// Converte para o formato de timestamp do PostgreSQL
$data_inicio_ts = $data_inicio . ' 00:00:00';
$data_fim_ts = $data_fim . ' 23:59:59';


// --- 4. CONSULTA DE SUMÁRIO GERAL (Para cartões de resumo) ---
$pedidos_pendentes_total = 0;
$total_faturado_periodo = 0;
$total_pedidos_aprovados_periodo = 0;

try {
    // A. SUMÁRIO GERAL DE PENDENTES (Todos os Tempos)
    $sql_pendentes_total = "SELECT COUNT(id) FROM pedidos WHERE status = ?";
    $stmt_pendentes_total = $pdo->prepare($sql_pendentes_total);
    $stmt_pendentes_total->execute([$STATUS_PENDENTE]);
    $pedidos_pendentes_total = $stmt_pendentes_total->fetchColumn();


    // B. LISTA DETALHADA DE PEDIDOS (Filtrados por Data e Status)
    $sql_params = [':data_inicio' => $data_inicio_ts, ':data_fim' => $data_fim_ts];
    $sql_where = "created_at >= :data_inicio AND created_at <= :data_fim";
    $status_display = ['APROVADO', 'PENDENTE']; // Default: Mostrar ambos

    // Adiciona o filtro de status, se não for 'TODOS'
    if ($status_filtro !== 'TODOS') {
        $sql_where .= " AND status = :status_filtro";
        $sql_params[':status_filtro'] = $status_filtro;
        $status_display = [$status_filtro]; // Apenas o status selecionado
    }

    $sql_detalhe = "
        SELECT
            id, status, customer_name, customer_email, product_name, total_amount_centavos, created_at
        FROM
            pedidos
        WHERE
            {$sql_where}
        ORDER BY
            created_at DESC
    ";

    $stmt_detalhe = $pdo->prepare($sql_detalhe);
    $stmt_detalhe->execute($sql_params);
    $pedidos_detalhe = $stmt_detalhe->fetchAll(PDO::FETCH_ASSOC);


    // C. CÁLCULO DE SUMÁRIO DO PERÍODO (para cartões de Faturamento)
    foreach ($pedidos_detalhe as $pedido) {
        if ($pedido['status'] === $STATUS_APROVADO) {
            $total_faturado_periodo += $pedido['total_amount_centavos'];
            $total_pedidos_aprovados_periodo++;
        }
    }


} catch (PDOException $e) {
    error_log("Erro ao buscar relatórios: " . $e->getMessage());
    $mensagem_erro_db = "Erro ao carregar dados do banco de dados.";
    $pedidos_detalhe = [];
}


// --- 5. FORMATAÇÃO E CÁLCULO MÉDIO ---
$faturamento_formatado = 'R$ ' . number_format($total_faturado_periodo / 100, 2, ',', '.');
$receita_media = ($total_pedidos_aprovados_periodo > 0) ? ($total_faturado_periodo / $total_pedidos_aprovados_periodo) / 100 : 0;
$receita_media_formatada = 'R$ ' . number_format($receita_media, 2, ',', '.');


// Formata os detalhes para a tabela
foreach ($pedidos_detalhe as &$pedido) {
    $pedido['total_formatado'] = 'R$ ' . number_format($pedido['total_amount_centavos'] / 100, 2, ',', '.');
    $pedido['created_at_formatado'] = date('d/m/Y H:i', strtotime($pedido['created_at']));
    $pedido['status_class'] = ($pedido['status'] === $STATUS_APROVADO) ? 'aprovado' : 'pendente';
    $pedido['status_display'] = htmlspecialchars($pedido['status']);
    unset($pedido);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Relatórios de Faturamento</title>
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
            --warning-color: #ffc107; /* Amarelo para Pendente */
        }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 250px; padding: 2rem; }
        #particles-js { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .content-header { margin-bottom: 2rem; background: var(--glass-background); padding: 1.5rem 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); backdrop-filter: blur(5px); }
        .content-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--primary-color); margin: 0 0 0.25rem 0; }
        .content-header p { font-size: 1rem; color: var(--light-text-color); margin: 0; }
        .mensagem { padding: 1rem; margin-bottom: 2rem; border-radius: var(--border-radius); font-weight: 500; }
        .mensagem.error { background-color: rgba(220, 53, 69, 0.2); color: var(--text-color); border: 1px solid var(--error-color); }
        .mensagem.success { background-color: rgba(40, 167, 69, 0.2); color: var(--text-color); border: 1px solid var(--success-color); }

        /* --- Estilos de Relatório --- */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 1rem;
            margin-bottom: 3rem;
        }
        .report-card { background: var(--glass-background); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        .report-card h3 { font-size: 1.1rem; color: var(--light-text-color); margin-top: 0; margin-bottom: 10px; font-weight: 400; }
        .report-value { font-size: 2.2rem; font-weight: 700; color: var(--secondary-color); margin-bottom: 5px; white-space: nowrap; }
        .report-details { font-size: 0.9rem; color: var(--primary-color); font-weight: 600; }

        .report-card.pending .report-value { color: var(--warning-color); }
        .report-card.pending .report-details { color: var(--warning-color); }

        /* --- Filtros e Ações --- */
        .filter-actions-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filter-form {
            background: var(--glass-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            display: flex;
            gap: 15px;
            align-items: flex-end;
            border: 1px solid var(--border-color);
            flex-grow: 1;
        }
        .filter-group { min-width: 120px; }
        .filter-group label { color: var(--light-text-color); font-size: 0.9rem; display: block; margin-bottom: 5px; }
        .filter-form input, .filter-form select {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            width: 100%;
        }
        .filter-form button {
            background-color: var(--primary-color);
            color: var(--sidebar-color);
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        .delete-actions { display: flex; gap: 10px; }
        .delete-actions button {
            background-color: var(--error-color);
            color: white;
            font-weight: 600;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 0.9rem;
        }
        .delete-actions button:hover { background-color: #a01f2f; }


        /* --- Tabela de Detalhes --- */
        .table-container { overflow-x: auto; background: var(--glass-background); padding: 1rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); }
        .details-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; white-space: nowrap; }
        .details-table th, .details-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .details-table th { color: var(--primary-color); font-weight: 600; text-transform: uppercase; }
        .details-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.05); }
        .status-chip { padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; text-align: center; }
        .status-chip.aprovado { background-color: rgba(40, 167, 69, 0.2); color: var(--success-color); }
        .status-chip.pendente { background-color: rgba(255, 193, 7, 0.2); color: var(--warning-color); }

        .btn-excluir-individual {
            background: var(--error-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: opacity 0.2s;
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .filter-form { flex-direction: column; gap: 10px; }
            .filter-form button { width: 100%; }
            .filter-actions-container { flex-direction: column-reverse; align-items: stretch; }
            .delete-actions { flex-grow: 1; }
        }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>
    <div id="particles-js"></div>
    <main class="main-content">
        <div class="content-header">
            <h1>Relatórios e Gestão de Pedidos 📈</h1>
            <p>Filtre pedidos por período e status, e gerencie exclusões.</p>
        </div>

        <?php if ($mensagem_status): ?>
            <div class="mensagem <?php echo strpos($mensagem_status, 'Erro') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensagem_status); ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagem_erro_db): ?>
            <div class="mensagem error">Erro: <?php echo htmlspecialchars($mensagem_erro_db); ?></div>
        <?php endif; ?>

        <div class="filter-actions-container">
            <form action="finance.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="data_inicio">Data Inicial:</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="data_fim">Data Final:</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>" required>
                </div>
                <div class="filter-group" style="min-width: 150px;">
                    <label for="status_filtro">Status:</label>
                    <select id="status_filtro" name="status_filtro">
                        <option value="TODOS" <?php echo ($status_filtro == 'TODOS') ? 'selected' : ''; ?>>TODOS</option>
                        <option value="<?php echo $STATUS_APROVADO; ?>" <?php echo ($status_filtro == $STATUS_APROVADO) ? 'selected' : ''; ?>>APROVADO</option>
                        <option value="<?php echo $STATUS_PENDENTE; ?>" <?php echo ($status_filtro == $STATUS_PENDENTE) ? 'selected' : ''; ?>>PENDENTE</option>
                    </select>
                </div>
                <button type="submit">Filtrar</button>
            </form>

            <div class="delete-actions">
                <form action="finance.php" method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja excluir TODOS os pedidos PENDENTES? Esta ação é irreversível.');">
                    <button type="submit" name="excluir_todos_pendentes" title="Exclui todos os pedidos com status PENDENTE no banco de dados.">
                        Excluir Pendentes (<?php echo $pedidos_pendentes_total; ?>)
                    </button>
                </form>
            </div>
        </div>

        <h2>Sumário do Período (<?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>)</h2>
        <div class="report-grid">

            <div class="report-card">
                <h3>Faturamento Aprovado</h3>
                <div class="report-value"><?php echo $faturamento_formatado; ?></div>
                <div class="report-details">Total de Pedidos Aprovados: <?php echo number_format($total_pedidos_aprovados_periodo, 0, ',', '.'); ?></div>
            </div>

            <div class="report-card">
                <h3>Ticket Médio</h3>
                <div class="report-value"><?php echo $receita_media_formatada; ?></div>
                <div class="report-details">Receita média por pedido aprovado.</div>
            </div>

            <div class="report-card pending">
                <h3>Pendentes (Total Geral)</h3>
                <div class="report-value"><?php echo number_format($pedidos_pendentes_total, 0, ',', '.'); ?></div>
                <div class="report-details" style="color: var(--warning-color);">Total em espera de pagamento.</div>
            </div>

        </div>

        <h2>Transações Detalhadas (<?php echo count($pedidos_detalhe); ?> Resultados)</h2>
        <div class="table-container">
            <table class="details-table">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos_detalhe)): ?>
                        <?php foreach ($pedidos_detalhe as $pedido): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($pedido['id']); ?></td>
                                <td><?php echo $pedido['created_at_formatado']; ?></td>
                                <td><span class="status-chip <?php echo $pedido['status_class']; ?>"><?php echo $pedido['status_display']; ?></span></td>
                                <td><?php echo htmlspecialchars($pedido['customer_name']); ?><br><small style="color:var(--light-text-color);"><?php echo htmlspecialchars($pedido['customer_email']); ?></small></td>
                                <td><?php echo htmlspecialchars($pedido['product_name']); ?></td>
                                <td><strong><?php echo $pedido['total_formatado']; ?></strong></td>
                                <td>
                                    <form action="finance.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir o Pedido #<?php echo $pedido['id']; ?>? Esta ação é irreversível.');">
                                        <input type="hidden" name="excluir_pedido_id" value="<?php echo $pedido['id']; ?>">
                                        <button type="submit" class="btn-excluir-individual">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--light-text-color); padding: 20px;">Nenhuma transação encontrada com os filtros e datas selecionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // JS Específico da Página (Particles)
        particlesJS('particles-js', {"particles":{"number":{"value":60,"density":{"enable":true,"value_area":800}},"color":{"value":"#69c9d4"},"shape":{"type":"circle"},"opacity":{"value":0.4,"random":false},"size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},"move":{"enable":true,"speed":1.5,"direction":"none","random":false,"straight":false,"out_mode":"out","bounce":false}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"repulse"},"onclick":{"enable":true,"mode":"push"},"resize":true}},"retina_detect":true});
    </script>
</body>
</html>