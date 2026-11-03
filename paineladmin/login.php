<?php
// Ficheiro: admin/login.php - Página de Login do Administrador

// Inicia a sessão para armazenar o status de login
session_start();

// Verifica se o usuário já está logado e, se sim, redireciona para o dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Inclui a configuração do banco de dados
include '../db_config.php';

$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (empty($username_input) || empty($password_input)) {
        $mensagem_erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // 1. Busca o usuário no banco de dados pela username
            $stmt = $pdo->prepare("SELECT id, username, password_hash, user_level FROM admin_users WHERE username = :username OR email = :username");
            $stmt->execute([':username' => $username_input]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password_input, $admin['password_hash'])) {

                // 2. Login bem-sucedido: Define as variáveis de sessão
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_level'] = $admin['user_level'];

                // 3. Redireciona para o dashboard
                header('Location: index.php');
                exit;

            } else {
                $mensagem_erro = 'Credenciais inválidas. Verifique seu usuário e senha.';
            }

        } catch (PDOException $e) {
            $mensagem_erro = 'Erro interno do sistema. Tente novamente mais tarde.';
            error_log("Erro de Login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <style>
        /* CSS Básico e Variáveis (Mantidas do painel) */
        :root {
            --primary-color: #FE2C55; /* Rosa/Vermelho TikTok */
            --secondary-color: #69c9d4; /* Ciano TikTok */
            --text-color: #f1f1f1;
            --light-text-color: #a1a1a1;
            --background-color: #121212;
            --glass-background: rgba(255, 255, 255, 0.08);
            --border-color: rgba(255, 255, 255, 0.1);
            --border-radius: 8px;
            --box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            --error-color: #dc3545;
        }
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            overflow: hidden; /* Para garantir que as partículas fiquem bem */
        }
        #particles-js { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }

        /* Estilos do Card de Login */
        .login-container {
            width: 100%;
            max-width: 400px;
            background: var(--glass-background);
            padding: 30px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(5px);
            text-align: center;
            z-index: 10; /* Garante que o card fique por cima das partículas */
        }

        .login-container h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .login-container p {
            color: var(--light-text-color);
            margin-bottom: 25px;
        }

        /* Formulário */
        label { display: block; text-align: left; margin-bottom: 5px; font-weight: 500; color: var(--light-text-color); font-size: 0.9rem; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }
        button[type="submit"] {
            width: 100%;
            background-color: var(--primary-color);
            color: var(--background-color);
            font-weight: 700;
            font-size: 1rem;
            padding: 15px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        button[type="submit"]:hover { background-color: var(--secondary-color); color: var(--text-color); box-shadow: 0 0 20px rgba(105, 201, 212, 0.5); }

        /* Mensagens de Erro */
        .mensagem.error {
            background-color: rgba(220, 53, 69, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div id="particles-js"></div>

    <div class="login-container">
        <h1>Login Admin</h1>
        <p>Acesso restrito ao painel de gerenciamento.</p>

        <?php if ($mensagem_erro): ?>
            <div class="mensagem error"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Usuário ou Email:</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Entrar no Painel</button>
        </form>
    </div>

    <script>
    // Inicializa o fundo de partículas (Mesmas configurações do dashboard)
    particlesJS('particles-js', {"particles":{"number":{"value":60,"density":{"enable":true,"value_area":800}},"color":{"value":"#69c9d4"},"shape":{"type":"circle"},"opacity":{"value":0.4,"random":false},"size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},"move":{"enable":true,"speed":1.5,"direction":"none","random":false,"straight":false,"out_mode":"out","bounce":false}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"repulse"},"onclick":{"enable":true,"mode":"push"},"resize":true}},"retina_detect":true});
    </script>

</body>
</html>