<?php
// Ficheiro: admin/logout.php - Processo de Encerramento de Sessão

// 1. Inicia a sessão (necessário para acessar as variáveis de sessão)
session_start();

// 2. Limpa todas as variáveis de sessão
// (Esta linha é recomendada para garantir que todos os dados sejam excluídos)
$_SESSION = array();

// 3. Destrói a sessão no servidor
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redireciona o usuário para a página de login
header('Location: login.php');
exit;
?>