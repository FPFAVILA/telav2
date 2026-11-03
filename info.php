<?php
// Arquivo: info.php
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
// Objetivo: Exibir a descrição completa (descricao_completa) do produto com a funcionalidade "Ver mais",
//           e OCULTAR a seção de Especificações se a descrição completa estiver preenchida.

// A variável $pdo é presumida estar disponível no escopo global.
global $pdo;
$produto_id = 1; // ID fixo para o produto principal

// Variáveis de inicialização
$descricao_completa = '';
$especificacoes_do_produto = "--- Especificações Técnicas ---
Altura Total: 110-120 cm
Braço até o chão: 64,5 - 74 cm
Assento até o chão: 45 - 53 cm
Encosto: 78 cm
Largura do Braço: 29 cm
"; // Texto de exemplo

if (isset($pdo)) {
    try {
        // 1. Busca APENAS a descrição completa
        $sql = "SELECT descricao_completa
                FROM public.produtos
                WHERE id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':product_id' => $produto_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Remove tags HTML e espaços em branco para determinar se o campo foi preenchido
            $descricao_completa_raw = $data['descricao_completa'] ?? '';
            $descricao_completa = htmlspecialchars($descricao_completa_raw);
        }

    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do produto: " . $e->getMessage());
        $descricao_completa = 'Erro ao carregar os detalhes do produto.';
    }
} else {
     $descricao_completa = 'Erro: Conexão com o banco de dados não estabelecida.';
}

// Variável de controle para esconder a seção de especificações (Se a descrição completa tiver conteúdo)
$mostrar_especificacoes = empty(trim($descricao_completa_raw ?? ''));

// Lógica para limitar a visualização inicial da descrição longa
$limite_caracteres_inicial = 300;
$precisa_ver_mais = strlen($descricao_completa) > $limite_caracteres_inicial;
?>

<style>
/* --- ESTILOS EXCLUSIVOS PARA INFO.PHP --- */
.product-info-block {
    padding: 15px;
    background-color: #FFFFFF;
    border-bottom: 8px solid #f8f8f8;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.info-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #222222;
    margin: 15px 0 10px 0;
}
.info-section p, .info-section pre {
    font-size: 14px;
    color: #000000;
    line-height: 1.5;
    white-space: pre-wrap; /* Mantém quebras de linha e espaços do texto */
    margin-bottom: 5px;
}

/* Container do Texto Truncado */
#descricaoCompletaContainer {
    overflow: hidden;
    max-height: 200px; /* Altura máxima inicial para truncar */
    transition: max-height 0.3s ease-in-out;
}
/* Estado expandido */
#descricaoCompletaContainer.expanded-text {
    max-height: 2000px; /* Altura grande o suficiente para mostrar todo o conteúdo */
}

.ver-mais-btn-container {
    text-align: center;
    margin-top: 10px;
}
.ver-mais-btn {
    background: none;
    border: none;
    color: #FE2C55;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    padding: 5px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}
.ver-mais-btn svg {
    margin-left: 5px;
    width: 16px;
    height: 16px;
    transition: transform 0.3s;
}
.ver-mais-btn.rotated svg {
    transform: rotate(180deg);
}

</style>

<div class="product-info-block" id="descricao">

    <div class="info-section">
        <h3>Descrição</h3>

        <div id="descricaoCompletaContainer">
            <pre><?php echo $descricao_completa; ?></pre>
        </div>

        <?php if ($precisa_ver_mais): ?>
            <div class="ver-mais-btn-container">
                <button id="verMaisBtn" class="ver-mais-btn">
                    Ver mais
                    <svg id="verMaisIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($mostrar_especificacoes): ?>
        <div class="info-section">
            <h3>Especificações</h3>
            <pre><?php echo htmlspecialchars($especificacoes_do_produto); ?></pre>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('descricaoCompletaContainer');
    const button = document.getElementById('verMaisBtn');
    const icon = document.getElementById('verMaisIcon');

    // Só anexa o listener se o botão existir
    if (button && container) {
        button.addEventListener('click', () => {
            const isExpanded = container.classList.contains('expanded-text');

            if (isExpanded) {
                // Fechar (Recolher)
                container.classList.remove('expanded-text');
                button.innerHTML = `Ver mais <svg id="verMaisIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>`;
            } else {
                // Abrir (Expandir)
                container.classList.add('expanded-text');
                button.innerHTML = `Ver menos <svg id="verMaisIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>`;
            }
        });
    }
});
</script>