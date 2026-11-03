<?php
// Ficheiro: createcontent.php
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
// Objetivo: Exibir o bloco de vídeos de criadores e abrir um modal no estilo TikTok ao clicar,
//           buscando os dados da tabela public.videos_criadores.

// A $pdo deve estar disponível a partir do main_content.php.
$produto_id_videos = 1; // ID do produto que estamos vinculando

$videos = []; // Inicializa o array para evitar erros

try {
    // 1. Busca colunas relevantes para a exibição e o modal
    $sql = "SELECT
                id, caminho_arquivo, nome_criador, descricao_video,
                likes, comentarios, salvos, compartilhamentos
            FROM public.videos_criadores
            WHERE produto_id = :produto_id
            ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':produto_id' => $produto_id_videos]);
    $videos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeamento dos dados do DB para o array $videos usado no HTML/JS
    $videos = array_map(function($v) {
        // Agora 'thumb' usa a própria URL do vídeo (ou um poster real, se disponível).
        return [
            'id' => $v['id'], // Adicionado ID para referência futura
            'thumb' => $v['caminho_arquivo'],
            'video_url' => $v['caminho_arquivo'],
            'avatar' => 'https://placehold.co/60/FFC0CB/000000?text=' . substr($v['nome_criador'], 0, 1),
            'username' => $v['nome_criador'],
            'description' => $v['descricao_video'] ?? '',
            'music' => 'Som Original - ' . $v['nome_criador'],
            'likes' => $v['likes'],
            'comments' => $v['comentarios'],
            'saves' => $v['salvos'],
            'shares' => $v['compartilhamentos'] ?? 0
        ];
    }, $videos_db);

} catch (PDOException $e) {
    // Em caso de erro, usa um array vazio
    $videos = [];
    // error_log("Erro ao buscar vídeos: " . $e->getMessage());
}
?>

<style>
/* ========================================= */
/* --- CSS GERAL --- */
/* ========================================= */
:root {
    --primary-color: #FE2C55; /* Cor principal para o Like */
}

/* ========================================= */
/* --- CSS BLOCO VÍDEOS DE CRIADORES --- */
/* ========================================= */
.creators-videos-section {
    padding: 15px 0 15px 15px;
    background-color: #FFFFFF;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    border-bottom: 10px solid var(--cor-fundo-separador, #F4F4F4);
}

.creators-videos-section h2 {
    font-size: 16px;
    font-weight: 600;
    color: var(--cor-texto-principal, #222222);
    margin: 0 0 15px 0;
    padding-right: 15px;
}

.videos-container {
    display: flex;
    gap: 8px;
    overflow-x: scroll;
    padding-bottom: 10px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    border-bottom: 8px solid #f8f8f8;
}
.videos-container::-webkit-scrollbar {
    display: none;
}

.video-card {
    flex-shrink: 0;
    width: 130px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}

.video-thumbnail-wrapper {
    position: relative;
    width: 100%;
    padding-top: 133%; /* Relação de aspeto 3:4 (133% de altura) */
}

.video-thumbnail-wrapper video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover; /* Importante para o aspeto de thumbnail */
    border-radius: 8px;
}

.play-icon-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 35px;
    height: 35px;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    opacity: 0.8;
    z-index: 10;
}

.creator-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 8px 6px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0));
    display: flex;
    align-items: center;
    z-index: 5;
}

.creator-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1.5px solid white;
    margin-right: 5px;
}

.creator-username {
    font-size: 11px;
    color: white;
    font-weight: 500;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
/* ========================================= */
/* --- MODAL ESTILO TIKTOK (FULL SCREEN) --- */
/* ========================================= */
.tiktok-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: black;
    display: none;
    z-index: 9000;
}
.tiktok-modal.show {
    display: block;
}

.video-player {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Overlay de Play/Pause */
#playPauseOverlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    z-index: 9001;
    opacity: 1;
    transition: opacity 0.3s ease;
}
#playPauseOverlay .icon {
    font-size: 50px;
    color: white;
    opacity: 0.8;
    background: rgba(0, 0, 0, 0.4);
    border-radius: 50%;
    width: 70px;
    height: 70px;
    display: flex;
    justify-content: center;
    align-items: center;
}
#playPauseOverlay.hidden {
    opacity: 0;
    pointer-events: none;
}


/* --- INTERFACE DE CONTROLE (LAYERS) --- */
.video-overlay-interface {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    color: white;
    padding: 10px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Header (Botão Fechar) */
.video-modal-header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 0 10px;
    font-size: 24px;
}
.video-modal-header .close-btn {
    cursor: pointer;
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

/* Ações Laterais (Curtir/Comentar) */
.video-actions-sidebar {
    position: absolute;
    right: 10px;
    bottom: 120px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    text-align: center;
}
.video-action-item {
    cursor: pointer;
    font-size: 12px;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.video-action-item svg {
    width: 28px;
    height: 28px;
    margin-bottom: 2px;
}

/* NOVO: Estilo para o coração curtido */
.video-action-item.liked .heart-icon-wrapper svg {
    fill: var(--primary-color);
}

/* --- ESTILO DO PERFIL COM SÍMBOLO '+' --- */
.profile-action-item {
    display: block;
    width: 55px;
    height: 55px;
    position: relative;
    margin: 0 auto 15px auto;
}
.profile-action-item .avatar-modal {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFC107;
}
.profile-action-item .follow-plus {
    position: absolute;
    bottom: -14px;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--cor-vermelha-principal, #FF3B30);
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    text-align: center;
    font-weight: 500;
    border: 2px solid black;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-action-item .follow-plus span {
    display: block;
    transform: translateY(-2px);
}


/* Informação do Vídeo (Rodapé) */
.video-info-footer {
    padding-left: 5px;
    margin-bottom: 90px;
}
.video-info-footer h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 5px 0;
}
.video-info-footer p {
    font-size: 13px;
    margin: 0 0 5px 0;
}
.video-info-footer .music-info {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
}

/* Botão COMPRE AQUI */
.compra-aqui-btn {
    position: absolute;
    bottom: 165px; /* Ajustado para dar espaço ao novo input de comentário */
    left: 15px;
    z-index: 100;

    display: flex;
    align-items: center;
    justify-content: center;

    background-color: rgba(102, 102, 102, 0.7);
    -webkit-backdrop-filter: blur(5px);
    backdrop-filter: blur(5px);

    color: white;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;

    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    width: fit-content;
}
.compra-aqui-btn .cart-icon-wrapper {
    background-color: #FF7700;
    border-radius: 4px;
    padding: 3px;
    margin-right: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: -8px;
}
.compra-aqui-btn .cart-icon-wrapper svg {
    width: 16px;
    height: 16px;
    fill: white;
    stroke: none;
}

/* SVG ICON COLORS */
.video-actions-sidebar svg {
    stroke: none;
    fill: white;
    stroke-width: 0;
    width: 28px;
    height: 28px;
}

/* ========================================= */
/* --- CAMPO DE COMENTÁRIOS NO OVERLAY DO VÍDEO (NOVO) --- */
/* ========================================= */
.comment-overlay-input {
    position: absolute;
    bottom: 15px; /* Posição fixa no rodapé do modal de vídeo */
    left: 15px;
    right: 15px;
    z-index: 9002; /* Acima do vídeo, mas abaixo do modal de comentários/partilha */
    height: 40px;

    display: flex;
    align-items: center;
    padding: 5px 0;
    cursor: pointer; /* Indica que é clicável */
}

.comment-overlay-input-field {
    flex-grow: 1;
    /* Ajuste para ter o mesmo estilo da imagem de referência (preto opaco) */
    background-color: rgba(20, 20, 20, 0.85);
    color: white;
    padding: 10px 15px;
    border-radius: 30px;
    font-size: 14px;
    border: none;
    pointer-events: none; /* Garante que o clique passe para o contêiner pai para abrir o modal */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-left: 10px;
    height: 100%;
    display: flex;
    align-items: center;
}
.comment-overlay-input-field::before {
    content: "Adicionar comentário...";
    color: rgba(255, 255, 255, 0.6);
}

.comment-overlay-emojis {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 5px;
    pointer-events: none;
    position: absolute;
    right: 15px; /* Alinhar os emojis à direita do input */
}
.comment-overlay-emojis span {
    font-size: 20px;
    line-height: 1;
}

/* ========================================= */
/* --- CAMPO DE COMENTÁRIOS (NO MODAL DE COMENTÁRIOS) --- */
/* ========================================= */
/* Esconde o input bar do modal principal para evitar sobreposição */
.tiktok-modal .comment-input-bar {
    display: none;
}
.comment-input-bar { /* Aplicado ao modal de comentários */
    position: static;
    flex-shrink: 0;
    margin-top: auto;
    background-color: #FFFFFF; /* Fundo branco para o modal de comentários */
    border-top: 1px solid #EAEAEA;
    display: flex;
    align-items: center;
    padding: 8px 15px;
    gap: 8px;
}

.comment-input-bar input {
    flex-grow: 1;
    background-color: #F5F5F5; /* Cinza claro para o input */
    border: none;
    border-radius: 20px;
    padding: 10px 12px;
    color: #333;
    font-size: 14px;
    outline: none;
}
.comment-input-bar input::placeholder {
    color: #999;
}

.comment-input-bar .emojis {
    display: flex;
    gap: 8px;
    order: 1; /* Coloca os emojis à direita do input */
}

/* Emojis Puros (Texto) */
.comment-input-bar .emoji-text {
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    color: #666; /* Cor dos ícones @ e emoji */
}

/* ========================================= */
/* --- MODAL DE COMPARTILHAMENTO (NOVO) --- */
/* ========================================= */
.share-modal {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: rgba(20, 20, 20, 0.95);
    color: white;
    padding: 20px 15px;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.5);
    transform: translateY(100%);
    transition: transform 0.3s ease-out;
    z-index: 9500;
}
.share-modal.show {
    transform: translateY(0);
}
.share-modal h4 {
    margin-top: 0;
    font-weight: 600;
    font-size: 16px;
    text-align: center;
    margin-bottom: 15px;
}
.share-options {
    display: flex;
    justify-content: space-around;
    gap: 15px;
    margin-bottom: 15px;
}
.share-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    font-size: 11px;
    opacity: 0.8;
}
.share-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 5px;
}
.share-icon.whatsapp { background-color: #25D366; font-size: 20px; }
.share-icon.facebook { background-color: #1877F2; font-size: 20px; }
.share-icon.copy { background-color: #666; font-size: 20px; }

.share-copy-link {
    display: flex;
    background-color: #333;
    border-radius: 8px;
    padding: 8px;
    align-items: center;
    font-size: 12px;
}
.share-copy-link input {
    flex-grow: 1;
    background: transparent;
    border: none;
    color: white;
    padding: 0 10px;
    outline: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.share-copy-link button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

/* ========================================= */
/* --- MODAL DE COMENTÁRIOS (ESTRUTURA) --- */
/* ========================================= */
.comments-modal {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    max-height: 80%;
    background-color: #FFFFFF;
    color: #333;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.5);
    transform: translateY(100%);
    transition: transform 0.3s ease-out;
    z-index: 9500;
    display: flex;
    flex-direction: column;
}
.comments-modal.show {
    transform: translateY(0);
}

.comments-header {
    padding: 15px;
    border-bottom: 1px solid #EAEAEA;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    flex-shrink: 0;
}
.comments-header h4 {
    margin: 0;
    font-weight: 600;
    font-size: 16px;
    color: #333;
}
.comments-header .close-comment-btn {
    position: absolute;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.comments-list-wrapper {
    overflow-y: auto;
    padding: 15px;
    flex-grow: 1;
}

.comment-item {
    display: flex;
    margin-bottom: 20px;
    gap: 10px;
    position: relative;
}
.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.comment-content {
    flex-grow: 1;
    padding-right: 50px;
}
.comment-content .username {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-right: 10px;
}
.comment-content .user-info {
     display: flex;
     align-items: center;
     margin-bottom: 5px;
     color: #666;
}
.comment-content .time {
    font-size: 12px;
}
.comment-content p {
    font-size: 14px;
    margin: 0 0 5px 0;
    line-height: 1.4;
}
.comment-content .view-replies {
    font-size: 12px;
    color: #999;
    cursor: pointer;
    margin-top: 5px;
    display: block;
}

/* Ícones de Like/Dislike ao lado direito do comentário */
.comment-actions-right {
    position: absolute;
    top: 5px;
    right: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    color: #999;
}
.comment-actions-right .like-icon,
.comment-actions-right .dislike-icon {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 12px;
}
.comment-actions-right .like-icon svg,
.comment-actions-right .dislike-icon svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
    stroke: none;
}
.comment-actions-right .like-icon.liked {
    color: var(--primary-color);
}
.comment-actions-right .dislike-icon.disliked {
    color: #666;
}

/* Estrutura de Respostas Aninhadas */
.comment-item[style*="margin-left: 30px"] {
    margin-left: 30px !important;
}

/* ========================================= */
/* --- AJUSTE PARA DESLIZAMENTO DE RESPOSTAS (smooth) --- */
/* ========================================= */
.comment-replies-container {
    max-height: 0; /* Começa escondido, definindo a altura para 0 */
    overflow: hidden; /* Esconde o conteúdo que exceder o max-height */
    transition: max-height 0.4s ease-out; /* Define a transição para a altura máxima */
    padding-top: 0;
}

.comment-replies-container.show-replies {
    max-height: 500px; /* Altura máxima suficiente para exibir todas as respostas */
}

/* ========================================= */
/* --- POPUP DE NOTIFICAÇÃO (NOVO) --- */
/* ========================================= */
#notificationPopup {
    position: fixed;
    bottom: 80px; /* Acima do input de comentários ou do rodapé */
    left: 50%;
    transform: translateX(-50%) translateY(100px); /* Começa fora da tela, para baixo */
    background-color: rgba(255, 69, 0, 0.9); /* Laranja/Vermelho para avisos */
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease-in-out;
    max-width: 90%;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

#notificationPopup.show {
    transform: translateX(-50%) translateY(0); /* Desliza para cima */
    opacity: 1;
    visibility: visible;
}

</style>

<div class="creators-videos-section">
    <h2>Vídeos de criadores (<?php echo count($videos); ?>)</h2>

    <div class="videos-container">

        <?php $index = 0; foreach ($videos as $video): ?>
            <div class="video-card" data-video-index="<?php echo $index; ?>">
                <div class="video-thumbnail-wrapper">
                    <video
                        src="<?php echo htmlspecialchars($video['video_url']); ?>"
                        alt="Miniatura do vídeo de <?php echo htmlspecialchars($video['username']); ?>"
                        preload="metadata"
                        muted
                        playsinline
                    >
                        <img src="https://placehold.co/130x173/0000FF/FFFFFF?text=Video+<?php echo $index+1; ?>" alt="Miniatura de vídeo">
                    </video>

                    <div class="play-icon-overlay">
                        &#9654;
                    </div>
                </div>

                <div class="creator-info">
                    <img src="<?php echo htmlspecialchars($video['avatar']); ?>" alt="Avatar" class="creator-avatar">
                    <span class="creator-username"><?php echo htmlspecialchars($video['username']); ?></span>
                </div>
            </div>
        <?php $index++; endforeach; ?>

    </div>
</div>

<div class="tiktok-modal" id="videoModal">
    <video class="video-player" id="modalVideoPlayer" controls loop autoplay playsinline>
        </video>

    <div id="playPauseOverlay">
        <div class="icon" id="playPauseIcon">
            &#9654;
        </div>
    </div>

    <div class="video-overlay-interface">

        <div class="video-modal-header">
            <span class="close-btn" id="closeModalBtn">X</span>
        </div>

        <div class="compra-aqui-btn" id="buyNowBtn">
            <div class="cart-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-fill" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg>
            </div>
            COMPRE AQUI!
        </div>

        <div class="video-actions-sidebar">

            <div class="video-action-item profile-action-item">
                <img id="modalAvatar" src="" alt="Avatar" class="avatar-modal">
                <div class="follow-plus"><span>+</span></div>
            </div>

            <div class="video-action-item heart-icon-wrapper" id="likeBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <g fill-rule="evenodd" clip-rule="evenodd">
                        <path d="M7.5 2.25c3 0 4.5 2 4.5 2s1.5-2 4.5-2c3.5 0 6 2.75 6 6.25 0 4-3.269 7.566-6.25 10.25C14.41 20.407 13 21.5 12 21.5s-2.45-1.101-4.25-2.75C4.82 16.066 1.5 12.5 1.5 8.5c0-3.5 2.5-6.25 6-6.25"></path>
                    </g>
                </svg>
                <span id="modalLikes">0</span>
            </div>

            <div class="video-action-item chat-icon-wrapper" id="chatBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 48 48" fill="currentColor">
                    <path fill-rule="evenodd" d="M2 21.5c0-10.22 9.88-18 22-18s22 7.78 22 18c0 5.63-3.19 10.74-7.32 14.8a43.6 43.6 0 0 1-14.14 9.1A1.5 1.5 0 0 1 22.5 44v-5.04C11.13 38.4 2 31.34 2 21.5M14 25a3 3 0 1 0 0-6 3 3 0 0 0 0 6m10 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6m13-3a3 3 0 1 1-6 0 3 3 0 0 1 6 0" clip-rule="evenodd"></path>
                </svg>
                <span id="modalComments">0</span>
            </div>

            <div class="video-action-item bookmark-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M4 4.5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v15.13a1 1 0 0 1-1.555.831l-6.167-4.12a.5.5 0 0 0-.556 0l-6.167 4.12A1 1 0 0 1 4 19.63z"></path>
                </svg>
                <span id="modalSaves">0</span>
            </div>

            <div class="video-action-item share-icon-wrapper" id="shareBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 48 48" fill="currentColor">
                    <path d="M23.82 3.5A2 2 0 0 0 20.5 5v10.06C8.7 15.96 1 25.32 1 37a2 2 0 0 0 3.41 1.41c4.14-4.13 10.4-5.6 16.09-5.88v9.97a2 2 0 0 0 3.3 1.52l21.5-18.5a2 2 0 0 0 .02-3.02z"/>
                </svg>
                <span id="modalShares">0</span>
            </div>
        </div>

        <div class="video-info-footer">
            <h3 id="modalUsernameFooter">@username</h3>
            <p id="modalDescription">Descrição do Vídeo e Hashtags...</p>
            <div class="music-info">
                 <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-5h2v5zm4 0h-2V7h2v9z"/></svg>
                 <span id="modalMusic">Música Original</span>
            </div>
        </div>

        <div class="comment-overlay-input" id="overlayCommentBtn">
            <div class="comment-overlay-input-field"></div>
            <div class="comment-overlay-emojis">
                <span>😀</span>
                <span>😍</span>
                <span>😂</span>
            </div>
        </div>
        </div>
</div>

<div class="share-modal" id="shareModal">
    <h4>Compartilhar Vídeo</h4>

    <div class="share-options">
        <div class="share-option" data-channel="whatsapp">
            <div class="share-icon whatsapp">W</div> <span>WhatsApp</span>
        </div>
        <div class="share-option" data-channel="facebook">
            <div class="share-icon facebook">f</div> <span>Facebook</span>
        </div>
        <div class="share-option" data-channel="copy">
            <div class="share-icon copy">🔗</div> <span>Copiar Link</span>
        </div>
    </div>

    <div class="share-copy-link">
        <input type="text" id="shareLinkInput" value="<?php echo htmlspecialchars("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" readonly>
        <button id="copyLinkBtn">Copiar</button>
    </div>

    <button class="close-btn" style="background: none; border: none; color: white; margin-top: 10px; display: block; width: 100%; font-size: 14px; font-weight: 600;" onclick="document.getElementById('shareModal').classList.remove('show');">Fechar</button>
</div>

<div class="comments-modal" id="commentsModal">
    <div class="comments-header">
        <h4><span id="totalCommentsCount">0 comentários</span></h4>
        <span class="close-comment-btn" onclick="document.getElementById('commentsModal').classList.remove('show');">&times;</span>
    </div>
    <div class="comments-list-wrapper" id="commentsList">
        <p style="text-align: center; color: #999;" id="commentsLoading">Carregando comentários...</p>
    </div>
    <div class="comment-input-bar">
        <input type="text" id="newCommentInput" placeholder="Adicionar comentário...">
        <div class="emojis">
            <span class="emoji-text">@</span>
            <span class="emoji-text">😀</span>
        </div>
        <button id="postCommentBtn" style="background: none; border: none; color: #FE2C55; font-weight: 600; padding: 0 5px; cursor: pointer; display: none;">Publicar</button>
    </div>
</div>

<div id="notificationPopup"></div>

<script>
    // --- FUNÇÕES GLOBAIS DE UTILIDADE (Mantidas) ---
    const formatNumber = (num) => {
        num = parseInt(num) || 0;
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return num.toString();
    };

    let cachedComments = null;

    // --- LÓGICA DE SPAM, POPUP E BLOQUEIO (Mantida) ---
    const NEGATIVE_WORDS = ['golpe', 'fraude', 'nao entrega', 'não entrega', 'lixo', 'bosta', 'ruim', 'mentira', 'falso', 'nao confiavel', 'não confiável'];
    const SPAM_ATTEMPTS_LIMIT = 2;
    const BLOCK_DURATION_MINUTES = 5;

    const notificationPopup = document.getElementById('notificationPopup');
    let notificationTimeout;

    const showNotification = (message) => {
        clearTimeout(notificationTimeout);
        notificationPopup.textContent = message.replace(/\*\*/g, '');
        notificationPopup.classList.add('show');

        notificationTimeout = setTimeout(() => {
            notificationPopup.classList.remove('show');
        }, 3000);
    };

    const isSpamBlocked = () => {
        const blockTimestamp = parseInt(sessionStorage.getItem('commentBlockTime'));
        if (!blockTimestamp) return false;

        const blockDurationMs = BLOCK_DURATION_MINUTES * 60 * 1000;
        const timeElapsed = Date.now() - blockTimestamp;

        return timeElapsed < blockDurationMs;
    };

    const getTimeRemaining = () => {
        const blockTimestamp = parseInt(sessionStorage.getItem('commentBlockTime'));
        const blockDurationMs = BLOCK_DURATION_MINUTES * 60 * 1000;
        const timeRemainingMs = (blockTimestamp + blockDurationMs) - Date.now();

        if (timeRemainingMs <= 0) return null;

        const minutes = Math.floor(timeRemainingMs / 60000);
        const seconds = Math.floor((timeRemainingMs % 60000) / 1000);

        return `${minutes}m ${seconds}s`;
    };

    const isNegative = (text) => {
        const lowerText = text.toLowerCase();
        return NEGATIVE_WORDS.some(word => lowerText.includes(word));
    };

    const incrementSpamAttempts = () => {
        let attempts = parseInt(sessionStorage.getItem('spamAttempts') || 0);
        attempts += 1;
        sessionStorage.setItem('spamAttempts', attempts);
        return attempts;
    };

    const applyBlock = () => {
        sessionStorage.setItem('commentBlockTime', Date.now().toString());
        sessionStorage.setItem('spamAttempts', '0');
    };

    const USER_NAMES = ["Usuário Anônimo", "Cliente Satisfeito", "Comprador Fiel", "Entusiasta Tech", "Aventureiro"];
    const getOrCreateUserSession = () => {
        let userProfile = sessionStorage.getItem('currentUserProfile');

        if (!userProfile) {
            const randomName = USER_NAMES[Math.floor(Math.random() * USER_NAMES.length)];
            const randomImgId = Math.floor(Math.random() * 51) + 10;

            const profile = {
                user: randomName,
                avatar_url: `https://i.pravatar.cc/150?img=${randomImgId}`
            };
            sessionStorage.setItem('currentUserProfile', JSON.stringify(profile));
            return profile;
        }
        return JSON.parse(userProfile);
    };


    // ===============================================
    // *** FUNÇÕES GLOBAIS DE LÓGICA DO MODAL (Corrigidas) ***
    // ===============================================

    const fetchComments = async () => {
        if (cachedComments) return cachedComments;

        try {
            const response = await fetch('api/get_comments.php');
            if (!response.ok) {
                 return [{
                     "user": "Usuário de Fallback",
                     "handle": "@fallback",
                     "time": "Agora",
                     "likes": 5,
                     "avatar_url": "https://i.pravatar.cc/150?img=1",
                     "text": "Comentário de fallback. Verifique o endpoint api/get_comments.php.",
                     "replies": []
                 }];
            }
            cachedComments = await response.json();
            return cachedComments;
        } catch (error) {
            console.error("Erro ao carregar comentarios:", error);
            return [];
        }
    };

    const loadComments = async () => {
        const commentsList = document.getElementById('commentsList');
        const totalCommentsCount = document.getElementById('totalCommentsCount');

        commentsList.innerHTML = '<p style="text-align: center; color: #999;">Carregando comentários...</p>';

        const comments = await fetchComments();
        commentsList.innerHTML = '';

        if (comments.length === 0) {
            commentsList.innerHTML = '<p style="text-align: center; color: #999;">Seja o primeiro a comentar!</p>';
            totalCommentsCount.textContent = '0 comentários';
            return;
        }

        let htmlContent = '';
        comments.forEach((comment, index) => {
            const formattedLikes = formatNumber(comment.likes);
            const hasReplies = comment.replies && comment.replies.length > 0;

            htmlContent += `
                <div class="comment-item">
                    <img src="${comment.avatar_url}" alt="Avatar de ${comment.user}" class="comment-avatar">
                    <div class="comment-content">
                        <div class="user-info">
                            <span class="username">${comment.user}</span>
                            <span class="time">${comment.time}</span>
                        </div>
                        <p>${comment.text}</p>

                        <div class="reply-action-bar">
                            <span class="reply-btn">Responder</span>
                            ${hasReplies ? `<span id="toggle-replies-btn-${index}" class="view-replies" onclick="window.toggleReplies(${index}, ${comment.replies.length})">Ver mais respostas (${comment.replies.length})</span>` : ''}
                        </div>
                    </div>
                    <div class="comment-actions-right">
                        <span class="like-icon" data-liked="false" data-likes="${comment.likes}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            ${formattedLikes}
                        </span>
                        <span class="dislike-icon" data-disliked="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hand-thumbs-down" viewBox="0 0 16 16">  <path d="M8.864 15.674c-.956.24-1.843-.484-1.908-1.42-.072-1.05-.23-2.015-.428-2.59-.125-.36-.479-1.012-1.04-1.638-.557-.624-1.282-1.179-2.131-1.41C2.685 8.432 2 7.85 2 7V3c0-.845.682-1.464 1.448-1.546 1.07-.113 1.564-.415 2.068-.723l.048-.029c.272-.166.578-.349.97-.484C6.931.08 7.395 0 8 0h3.5c.937 0 1.599.478 1.934 1.064.164.287.254.607.254.913 0 .152-.023.312-.077.464.201.262.38.577.488.9.11.33.172.762.004 1.15.069.13.12.268.159.403.077.27.113.567.113.856s-.036.586-.113.856c-.035.12-.08.244-.138.363.394.571.418 1.2.234 1.733-.206.592-.682 1.1-1.2 1.272-.847.283-1.803.276-2.516.211a10 10 0 0 1-.443-.05 9.36 9.36 0 0 1-.062 4.51c-.138.508-.55.848-1.012.964zM11.5 1H8c-.51 0-.863.068-1.14.163-.281.097-.506.229-.776.393l-.04.025c-.555.338-1.198.73-2.49.868-.333.035-.554.29-.554.55V7c0 .255.226.543.62.65 1.095.3 1.977.997 2.614 1.709.635.71 1.064 1.475 1.238 1.977.243.7.407 1.768.482 2.85.025.362.36.595.667.518l.262-.065c.16-.04.258-.144.288-.255a8.34 8.34 0 0 0-.145-4.726.5.5 0 0 1 .595-.643h.003l.014.004.058.013a9 9 0 0 0 1.036.157c.663.06 1.457.054 2.11-.163.175-.059.45-.301.57-.651.107-.308.087-.67-.266-1.021L12.793 7l.353-.354c.043-.042.105-.14.154-.315.048-.167.075-.37.075-.581s-.027-.414-.075-.581c-.05-.174-.111-.273-.154-.315l-.353-.354.353-.354c.006-.005.041-.05.041-.17a.9.9 0 0 0-.121-.415C12.4 1.272 12.063 1 11.5 1"/></svg>
                        </span>
                    </div>
                </div>
            `;

            if (hasReplies) {
                htmlContent += `<div class="comment-replies-container" id="replies-container-${index}">`;
                comment.replies.forEach(reply => {
                        htmlContent += `
                            <div class="comment-item" style="margin-left: 30px; margin-top: 5px; margin-bottom: 5px;">
                                <img src="${reply.avatar_url}" alt="Avatar" class="comment-avatar" style="width: 30px; height: 30px;">
                                <div class="comment-content" style="padding-right: 0;">
                                    <div class="user-info">
                                        <span class="username" style="font-size: 13px;">${reply.user}</span>
                                        <span class="time">${reply.time}</span>
                                    </div>
                                    <p style="font-size: 13px; margin-bottom: 0;">${reply.text}</p>
                                </div>
                            </div>
                        `;
                });
                htmlContent += `</div>`;
            }
        });

        commentsList.innerHTML = htmlContent;
        totalCommentsCount.textContent = `${comments.length} comentários`;

        document.querySelectorAll('.comment-item').forEach(commentItem => {
            const likeIcon = commentItem.querySelector('.like-icon');
            const dislikeIcon = commentItem.querySelector('.dislike-icon');

            if (likeIcon) {
                // Lógica de stopPropagation para evitar Play/Pause indesejado
                likeIcon.addEventListener('click', (e) => { e.stopPropagation(); /* ... */ });
                dislikeIcon.addEventListener('click', (e) => { e.stopPropagation(); /* ... */ });

                likeIcon.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let currentLikes = parseInt(likeIcon.getAttribute('data-likes'));
                    let isCommentLiked = likeIcon.getAttribute('data-liked') === 'true';

                    if (isCommentLiked) {
                        currentLikes -= 1;
                        likeIcon.setAttribute('data-liked', 'false');
                        likeIcon.classList.remove('liked');
                    } else {
                        currentLikes += 1;
                        likeIcon.setAttribute('data-liked', 'true');
                        likeIcon.classList.add('liked');
                        if (dislikeIcon && dislikeIcon.getAttribute('data-disliked') === 'true') {
                            dislikeIcon.setAttribute('data-disliked', 'false');
                            dislikeIcon.classList.remove('disliked');
                        }
                    }
                    likeIcon.setAttribute('data-likes', currentLikes);
                    likeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> ${formatNumber(currentLikes)}`;
                });

                dislikeIcon.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let isCommentDisliked = dislikeIcon.getAttribute('data-disliked') === 'true';

                    if (isCommentDisliked) {
                        dislikeIcon.setAttribute('data-disliked', 'false');
                        dislikeIcon.classList.remove('disliked');
                    } else {
                        dislikeIcon.setAttribute('data-disliked', 'true');
                        dislikeIcon.classList.add('disliked');
                        if (likeIcon.getAttribute('data-liked') === 'true') {
                            let currentLikes = parseInt(likeIcon.getAttribute('data-likes'));
                            currentLikes -= 1;
                            likeIcon.setAttribute('data-likes', currentLikes);
                            likeIcon.setAttribute('data-liked', 'false');
                            likeIcon.classList.remove('liked');
                            likeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> ${formatNumber(currentLikes)}`;
                        }
                    }
                });
            }
        });
    };

    const checkSpamAndPost = () => {
        const newCommentInput = document.getElementById('newCommentInput');
        const postCommentBtn = document.getElementById('postCommentBtn');
        const commentsList = document.getElementById('commentsList');
        const totalCommentsCount = document.getElementById('totalCommentsCount');

        const commentText = newCommentInput.value.trim();
        const userProfile = getOrCreateUserSession();

        if (isSpamBlocked()) {
            const timeLeft = getTimeRemaining();
            showNotification(`🛑 Bloqueado! Tente novamente em ${timeLeft}.`);
            newCommentInput.value = '';
            postCommentBtn.style.display = 'none';
            return;
        }

        if (commentText === "") {
            showNotification("Escreva um comentário, por favor. 😉");
            return;
        }

        if (isNegative(commentText)) {
            const attempts = incrementSpamAttempts();
            if (attempts >= SPAM_ATTEMPTS_LIMIT) {
                applyBlock();
                showNotification(`❌ Comentário Bloqueado! Conteúdo impróprio. Você foi bloqueado por ${BLOCK_DURATION_MINUTES} minutos.`);
            } else {
                showNotification(`⚠️ Aviso: Comentário bloqueado por palavras-chave. Tentativas restantes: ${SPAM_ATTEMPTS_LIMIT - attempts}`);
            }
            newCommentInput.value = '';
            postCommentBtn.style.display = 'none';
            return;
        }

        // SIMULAÇÃO: Comentário válido
        const newComment = {
            "user": userProfile.user,
            "time": "Agora",
            "likes": 0,
            "avatar_url": userProfile.avatar_url,
            "text": commentText
        };

        const newCommentHtml = `
            <div class="comment-item">
                <img src="${newComment.avatar_url}" alt="Avatar" class="comment-avatar">
                <div class="comment-content">
                    <div class="user-info">
                        <span class="username">${newComment.user}</span>
                        <span class="time">${newComment.time}</span>
                    </div>
                    <p>${newComment.text}</p>
                </div>
                <div class="comment-actions-right">
                    <span class="like-icon liked" data-liked="true" data-likes="1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        1
                    </span>
                    <span class="dislike-icon" data-disliked="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hand-thumbs-down" viewBox="0 0 16 16">  <path d="M8.864 15.674c-.956.24-1.843-.484-1.908-1.42-.072-1.05-.23-2.015-.428-2.59-.125-.36-.479-1.012-1.04-1.638-.557-.624-1.282-1.179-2.131-1.41C2.685 8.432 2 7.85 2 7V3c0-.845.682-1.464 1.448-1.546 1.07-.113 1.564-.415 2.068-.723l.048-.029c.272-.166.578-.349.97-.484C6.931.08 7.395 0 8 0h3.5c.937 0 1.599.478 1.934 1.064.164.287.254.607.254.913 0 .152-.023.312-.077.464.201.262.38.577.488.9.11.33.172.762.004 1.15.069.13.12.268.159.403.077.27.113.567.113.856s-.036.586-.113.856c-.035.12-.08.244-.138.363.394.571.418 1.2.234 1.733-.206.592-.682 1.1-1.2 1.272-.847.283-1.803.276-2.516.211a10 10 0 0 1-.443-.05 9.36 9.36 0 0 1-.062 4.51c-.138.508-.55.848-1.012.964zM11.5 1H8c-.51 0-.863.068-1.14.163-.281.097-.506.229-.776.393l-.04.025c-.555.338-1.198.73-2.49.868-.333.035-.554.29-.554.55V7c0 .255.226.543.62.65 1.095.3 1.977.997 2.614 1.709.635.71 1.064 1.475 1.238 1.977.243.7.407 1.768.482 2.85.025.362.36.595.667.518l.262-.065c.16-.04.258-.144.288-.255a8.34 8.34 0 0 0-.145-4.726.5.5 0 0 1 .595-.643h.003l.014.004.058.013a9 9 0 0 0 1.036.157c.663.06 1.457.054 2.11-.163.175-.059.45-.301.57-.651.107-.308.087-.67-.266-1.021L12.793 7l.353-.354c.043-.042.105-.14.154-.315.048-.167.075-.37.075-.581s-.027-.414-.075-.581c-.05-.174-.111-.273-.154-.315l-.353-.354.353-.354c.006-.005.041-.05.041-.17a.9.9 0 0 0-.121-.415C12.4 1.272 12.063 1 11.5 1"/></svg>
                    </span>
                </div>
            </div>
        `;

        commentsList.insertAdjacentHTML('afterbegin', newCommentHtml);
        showNotification("✅ Comentário publicado com sucesso!");

        totalCommentsCount.textContent = `${parseInt(totalCommentsCount.textContent.split(' ')[0]) + 1} comentários`;

        newCommentInput.value = '';
        postCommentBtn.style.display = 'none';
        sessionStorage.setItem('spamAttempts', '0');
    };

    window.toggleReplies = (commentId, repliesCount) => {
        const repliesContainer = document.getElementById(`replies-container-${commentId}`);
        const toggleButton = document.getElementById(`toggle-replies-btn-${commentId}`);

        if (!repliesContainer || !toggleButton) {
            console.error(`Elemento de resposta ou botão não encontrado para o ID: ${commentId}`);
            return;
        }

        repliesContainer.classList.toggle('show-replies');

        const isVisible = repliesContainer.classList.contains('show-replies');

        if (isVisible) {
            toggleButton.textContent = 'Ocultar respostas';
        } else {
            toggleButton.textContent = `Ver mais respostas (${repliesCount})`;
        }
    };

    // --- FUNÇÃO DE CONTROLE ROBUSTO DO PLAYER (CORRIGIDO PARA O BUG PAUSE/PLAY) ---
    const setupPlayerControl = (modalVideo, playPauseOverlay) => {
        // 1. A LÓGICA DE PAUSE/PLAY É ACIONADA SOMENTE AO CLICAR NO OVERLAY
        playPauseOverlay.addEventListener('click', async (e) => {
            e.stopPropagation();

            if (modalVideo.paused || modalVideo.ended) {
                try {
                    await modalVideo.play();
                } catch (error) {
                    console.warn("Reprodução bloqueada pelo navegador. Usuário precisa interagir novamente.");
                    playPauseOverlay.classList.remove('hidden');
                }
            } else {
                modalVideo.pause();
            }
        });

        // 2. ATUALIZAÇÃO VISUAL BASEADA NO ESTADO REAL DO VÍDEO
        modalVideo.addEventListener('play', () => {
            document.getElementById('playPauseIcon').innerHTML = '❚❚';
            playPauseOverlay.classList.add('hidden');
        });
        modalVideo.addEventListener('pause', () => {
            document.getElementById('playPauseIcon').innerHTML = '&#9654;';
            playPauseOverlay.classList.remove('hidden');
        });
    };
    // -------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', () => {
        // --- Referências de Elementos ---
        const modal = document.getElementById('videoModal');
        const modalVideo = document.getElementById('modalVideoPlayer');
        const playPauseOverlay = document.getElementById('playPauseOverlay');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const commentsModal = document.getElementById('commentsModal');
        const newCommentInput = document.getElementById('newCommentInput');

        // Elementos interativos para stopPropagation
        const chatBtn = document.getElementById('chatBtn');
        const overlayCommentBtn = document.getElementById('overlayCommentBtn');
        const likeBtn = document.getElementById('likeBtn');
        const shareBtn = document.getElementById('shareBtn');
        const buyNowBtn = document.getElementById('buyNowBtn');
        const profileActionItem = document.querySelector('.profile-action-item');
        const videoOverlayInterface = document.querySelector('.video-overlay-interface');
        const videoCards = document.querySelectorAll('.video-card');
        const videoData = <?php echo json_encode($videos); ?>;
        const shareLinkInput = document.getElementById('shareLinkInput');
        const copyLinkBtn = document.getElementById('copyLinkBtn');


        let isLiked = false;

        // --- INICIALIZAÇÃO ROBUSTA DO CONTROLE DO PLAYER ---
        setupPlayerControl(modalVideo, playPauseOverlay);

        // --- FUNÇÃO PARA ABRIR O MODAL DE COMENTÁRIOS ---
        const openCommentsModal = () => {
            modalVideo.pause();
            loadComments();
            commentsModal.classList.add('show');
            newCommentInput.focus();
        };

        // --- FUNÇÃO AUXILIAR PARA PAUSE (USADA NO FECHAMENTO DE MODAIS) ---
        const pauseVideo = () => {
            modalVideo.pause();
        };

        // ===============================================
        // *** VINCULAÇÃO E CONTROLE DE FLUXO ***
        // ===============================================

        // A. CLIQUE NA ÁREA MORTA (Permite Play/Pause ao clicar na área "morta" da interface)
        videoOverlayInterface.addEventListener('click', (e) => {
            if (e.target.classList.contains('video-overlay-interface')) {
                // Aciona o controle de Play/Pause através do overlay
                playPauseOverlay.click();
            }
        });

        // B. FECHAMENTO DO MODAL PRINCIPAL
        closeModalBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            pauseVideo();
            modalVideo.muted = true;
            modal.classList.remove('show');
            document.getElementById('shareModal').classList.remove('show');
            commentsModal.classList.remove('show');
        });


        // C. IMPEDIR CONFLITOS (stopPropagation) em elementos interativos (e abre modais)
        const actionableElements = [
            chatBtn, overlayCommentBtn, likeBtn, shareBtn, buyNowBtn, profileActionItem
        ];

        actionableElements.forEach(el => {
            if (el) {
                el.addEventListener('click', (e) => {
                    e.stopPropagation(); // Impede que o clique suba para a área morta/player

                    if (el === chatBtn || el === overlayCommentBtn) {
                        openCommentsModal();
                    }
                });
            }
        });


        // ===============================================
        // *** LÓGICA DE ABERTURA DO MODAL DE VÍDEO (Mantida) ***
        // ===============================================
        videoCards.forEach(card => {
            card.addEventListener('click', (e) => {
                const index = card.getAttribute('data-video-index');
                const video = videoData[index];

                modalVideo.src = video.video_url;
                modalVideo.load();

                // Injeção de Dados e Formatação
                document.getElementById('modalAvatar').src = video.avatar;
                document.getElementById('modalUsernameFooter').textContent = `@${video.username}`;
                document.getElementById('modalDescription').textContent = video.description;
                document.getElementById('modalMusic').textContent = video.music;
                document.getElementById('modalLikes').textContent = formatNumber(video.likes);
                document.getElementById('modalComments').textContent = formatNumber(video.comments);
                document.getElementById('modalSaves').textContent = formatNumber(video.saves);
                document.getElementById('modalShares').textContent = formatNumber(video.shares);
                document.getElementById('likeBtn').setAttribute('data-likes', video.likes);

                modal.classList.add('show');
                modalVideo.muted = false;

                // Tenta dar Play. Se falhar, o setupPlayerControl garante que o ícone de Play apareça.
                modalVideo.play().catch(error => {
                    console.log("Autoplay bloqueado. Aguardando interação.");
                });
            });
        });

        // VINCULAÇÃO DOS LISTENERS DE INPUT/POSTAGEM (Mantidos)
        newCommentInput.addEventListener('input', () => {
            if (newCommentInput.value.trim().length > 0) {
                postCommentBtn.style.display = 'block';
            } else {
                postCommentBtn.style.display = 'none';
            }
        });
        postCommentBtn.addEventListener('click', checkSpamAndPost);
        newCommentInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') { checkSpamAndPost(); } });

        // OUTRAS AÇÕES (com Lógica de Pausa restaurada)
        document.getElementById('buyNowBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            pauseVideo();
            modal.classList.remove('show');
            commentsModal.classList.remove('show');

            // REDIRECIONA PARA CHECKOUT EXTERNO (se configurado) com UTMs preservadas
            const externalCheckoutUrl = <?php echo json_encode($link_checkout_externo ?? ''); ?>;
            if (externalCheckoutUrl && externalCheckoutUrl.length > 0) {
                const currentUrl = new URL(window.location.href);
                const currentParams = currentUrl.searchParams;
                const targetUrl = new URL(externalCheckoutUrl);
                const utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id', 'src', 'sck'];
                utmParams.forEach(function(param) {
                    if (currentParams.has(param)) {
                        targetUrl.searchParams.set(param, currentParams.get(param));
                    }
                });
                window.location.href = targetUrl.toString();
            } else {
                // Fallback: vai para a pagina do produto
                window.location.href = 'index.php';
            }
        });

        document.getElementById('shareBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            pauseVideo(); // Pausa o vídeo ao abrir o modal de compartilhamento
            if (navigator.share) {
                navigator.share({
                    title: `Assista este vídeo de ${document.getElementById('modalUsernameFooter').textContent}`,
                    text: 'Confira este produto incrível!',
                    url: window.location.href,
                }).catch((error) => console.log('Erro ao compartilhar:', error));
            } else {
                document.getElementById('shareModal').classList.add('show');
            }
        });

        // LÓGICA DE CURTIDA (LIKES) (Mantida)
        document.getElementById('likeBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            let currentLikesRaw = parseInt(document.getElementById('likeBtn').getAttribute('data-likes')) || 0;

            if (isLiked) {
                currentLikesRaw -= 1;
                document.getElementById('likeBtn').classList.remove('liked');
            } else {
                currentLikesRaw += 1;
                document.getElementById('likeBtn').classList.add('liked');
            }

            isLiked = !isLiked;
            document.getElementById('modalLikes').textContent = formatNumber(currentLikesRaw);
            document.getElementById('likeBtn').setAttribute('data-likes', currentLikesRaw);
        });

        // Copiar Link (dentro do Share Modal) (Mantida)
        document.getElementById('copyLinkBtn').addEventListener('click', (e) => {
            e.preventDefault();
            shareLinkInput.select();
            shareLinkInput.setSelectionRange(0, 99999);

            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareLinkInput.value).then(() => {
                    copyLinkBtn.textContent = 'Copiado! ✅';
                    setTimeout(() => { copyLinkBtn.textContent = 'Copiar'; }, 2000);
                });
            } else {
                document.execCommand('copy');
                copyLinkBtn.textContent = 'Copiado! ✅';
                setTimeout(() => { copyLinkBtn.textContent = 'Copiar'; }, 2000);
            }
        });


        // Lógica de compartilhamento social (simples) (IMPLEMENTADA)
        document.querySelectorAll('.share-option').forEach(option => {
            option.addEventListener('click', (e) => {
                e.stopPropagation(); // Importante para não fechar o share modal imediatamente
                const channel = option.getAttribute('data-channel');
                const url = encodeURIComponent(shareLinkInput.value);
                let shareUrl = '';

                if (channel === 'whatsapp') {
                    shareUrl = `whatsapp://send?text=Confira este produto: ${url}`;
                } else if (channel === 'facebook') {
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                } else if (channel === 'copy') {
                    copyLinkBtn.click(); // Reutiliza a lógica de cópia
                    return;
                }

                if (shareUrl) {
                    window.open(shareUrl, '_blank');
                }
                // Fecha o modal após a ação (exceto para o botão Copiar, que já tem timeout)
                if (channel !== 'copy') {
                    document.getElementById('shareModal').classList.remove('show');
                }
            });
        });
    });
</script>