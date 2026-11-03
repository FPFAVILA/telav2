<?php
// Ficheiro: admin/reset.php - Gerenciamento e Redefinição de Credenciais

// --- 1. LÓGICA PHP E AUTENTICAÇÃO ---
session_start();

// Proteção de Acesso
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Certifique-se de que o caminho para db_config.php está correto
include '../db_config.php';

$mensagem_status = null;
$admin_id = $_SESSION['admin_id'] ?? 1;

// ... (Lógica de ATUALIZAÇÃO BEM-SUCEDIDA AQUI) ...

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_credenciais'])) {

    $senha_antiga_input = $_POST['senha_antiga'] ?? '';
    $novo_username = trim($_POST['novo_username'] ?? '');
    $novo_email = trim($_POST['novo_email'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    try {
        // A. Busca os dados atuais do administrador logado
        $stmt_fetch = $pdo->prepare("SELECT username, email, password_hash FROM admin_users WHERE id = :id");
        $stmt_fetch->execute([':id' => $admin_id]);
        $admin_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$admin_data) {
            $mensagem_status = "Erro: Usuário administrador não encontrado.";
            goto end_process;
        }

        // B. VERIFICAÇÃO DE AUTENTICAÇÃO (Senha Antiga)
        if (!password_verify($senha_antiga_input, $admin_data['password_hash'])) {
            $mensagem_status = "Erro: A senha antiga fornecida está incorreta.";
            goto end_process;
        }

        // C. Prepara a atualização
        $pdo->beginTransaction();
        $updates = [];
        $params = [':id' => $admin_id];
        $nova_senha_hash = $admin_data['password_hash'];

        // 1. Atualizar Username (se for alterado e não estiver vazio)
        if (!empty($novo_username) && $novo_username !== $admin_data['username']) {
            $updates[] = "username = :username";
            $params[':username'] = $novo_username;
            $_SESSION['admin_username'] = $novo_username;
        }

        // 2. Atualizar Email (se for alterado e não estiver vazio)
        if (!empty($novo_email) && $novo_email !== $admin_data['email']) {
            if (filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
                $updates[] = "email = :email";
                $params[':email'] = $novo_email;
            } else {
                $mensagem_status = "Erro: Formato de e-mail inválido.";
                goto end_process;
            }
        }

        // 3. Atualizar Senha (se fornecida)
        if (!empty($nova_senha)) {
            if ($nova_senha !== $confirmar_senha) {
                $mensagem_status = "Erro: A nova senha e a confirmação não coincidem.";
                goto end_process;
            }
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $updates[] = "password_hash = :password_hash";
            $params[':password_hash'] = $nova_senha_hash;
        }

        // D. Executa a Atualização se houver mudanças
        if (!empty($updates)) {
            $sql_update = "UPDATE admin_users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute($params);

            $pdo->commit();
            $mensagem_status = "Credenciais atualizadas com sucesso! 🎉";

        } else {
            $pdo->rollBack();
            $mensagem_status = "Nenhuma alteração detectada para ser salva.";
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23505) {
             $mensagem_status = "Erro: O nome de usuário ou e-mail já está em uso.";
        } else {
             $mensagem_status = "Erro interno ao atualizar: " . $e->getMessage();
        }
    }

    end_process:
}

// 3. Busca dados atuais do DB para preencher o formulário
try {
    $stmt_current = $pdo->prepare("SELECT username, email FROM admin_users WHERE id = :id");
    $stmt_current->execute([':id' => $admin_id]);
    $current_admin_data = $stmt_current->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_admin_data = ['username' => 'Erro DB', 'email' => 'erro@db.com'];
    $mensagem_status = $mensagem_status ?? "Não foi possível carregar dados atuais.";
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Redefinir Credenciais</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <style>
        /* CSS Básico (Consistência com as outras páginas) */
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
        /* Ajuste: O BODY será estilizado pela sidebar, mas a main-content precisa de margin */
        .main-content { margin-left: 250px; padding: 2rem; }
        #particles-js { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .content-header { margin-bottom: 2rem; background: var(--glass-background); padding: 1.5rem 2rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); backdrop-filter: blur(5px); }
        .content-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--primary-color); margin: 0 0 0.25rem 0; }
        .content-header p { font-size: 1rem; color: var(--light-text-color); margin: 0; }

        /* Formulário e Mensagens */
        form { background: var(--glass-background); border: 1px solid var(--border-color); padding: 2rem; margin-bottom: 2rem; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        h2 { color: var(--secondary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; font-size: 1.5rem; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--light-text-color); font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: var(--border-radius);
            background-color: rgba(0, 0, 0, 0.2); color: var(--text-color); margin-bottom: 1rem; font-family: 'Poppins', sans-serif;
        }
        button[type="submit"] {
            background-color: var(--primary-color); color: var(--sidebar-color); font-weight: 600; font-size: 0.9rem;
            padding: 12px 20px; border: none; border-radius: var(--border-radius); cursor: pointer; transition: all 0.3s ease;
            text-transform: uppercase; margin-top: 1rem;
        }
        button[type="submit"]:hover { background-color: var(--secondary-color); color: var(--text-color); box-shadow: 0 4px 10px rgba(254, 44, 85, 0.3); }

        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .grid-3-col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }

        .mensagem { padding: 1rem; margin-bottom: 2rem; border-radius: var(--border-radius); font-weight: 500; }
        .mensagem.success { background-color: rgba(40, 167, 69, 0.2); color: var(--success-color); border: 1px solid var(--success-color); }
        .mensagem.error { background-color: rgba(220, 53, 69, 0.2); color: var(--error-color); border: 1px solid var(--error-color); }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .grid-2-col, .grid-3-col { grid-template-columns: 1fr; gap: 1rem; }
        }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; // INCLUSÃO DA BARRA LATERAL AQUI ?>

    <div id="particles-js"></div>
    <main class="main-content">
        <div class="content-header">
            <h1>Redefinir Credenciais</h1>
            <p>Altere o usuário, e-mail e/ou senha da sua conta de administrador.</p>
        </div>

        <?php if ($mensagem_status): ?>
            <div class="mensagem <?php echo strpos($mensagem_status, 'Erro') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensagem_status); ?>
            </div>
        <?php endif; ?>

        <form action="reset.php" method="POST">

            <h2>Informações de Login (Atuais)</h2>
            <div class="grid-2-col">
                <div>
                    <label for="novo_username">Novo Nome de Usuário:</label>
                    <input type="text" id="novo_username" name="novo_username"
                           value="<?php echo htmlspecialchars($current_admin_data['username'] ?? ''); ?>"
                           placeholder="Mantenha vazio para não alterar" autocomplete="off">
                    <small style="color: var(--light-text-color);">Atual: **<?php echo htmlspecialchars($current_admin_data['username'] ?? ''); ?>**</small>
                </div>
                <div>
                    <label for="novo_email">Novo E-mail:</label>
                    <input type="email" id="novo_email" name="novo_email"
                           value="<?php echo htmlspecialchars($current_admin_data['email'] ?? ''); ?>"
                           placeholder="Mantenha vazio para não alterar" autocomplete="off">
                    <small style="color: var(--light-text-color);">Atual: **<?php echo htmlspecialchars($current_admin_data['email'] ?? ''); ?>**</small>
                </div>
            </div>

            <h2 style="margin-top: 2rem;">Redefinir Senha</h2>
            <div class="grid-3-col">
                <div>
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha (opcional)" autocomplete="new-password">
                </div>
                <div>
                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme a nova senha" autocomplete="new-password">
                </div>
                <div style="border-left: 1px solid var(--border-color); padding-left: 2rem;">
                    <label for="senha_antiga" style="color: var(--error-color); font-weight: 700;">Senha Antiga (Obrigatório para Salvar):</label>
                    <input type="password" id="senha_antiga" name="senha_antiga" required placeholder="Sua senha atual para confirmar" autocomplete="current-password">
                </div>
            </div>

            <button type="submit" name="salvar_credenciais">Salvar Credenciais e Senha</button>
        </form>

    </main>

    <script>
    particlesJS('particles-js', {"particles":{"number":{"value":60,"density":{"enable":true,"value_area":800}},"color":{"value":"#69c9d4"},"shape":{"type":"circle"},"opacity":{"value":0.4,"random":false},"size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},"move":{"enable":true,"speed":1.5,"direction":"none","random":false,"straight":false,"out_mode":"out","bounce":false}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"repulse"},"onclick":{"enable":true,"mode":"push"},"resize":true}},"retina_detect":true});
    </script>
</body>
</html>