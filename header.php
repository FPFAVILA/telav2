<?php
// Ficheiro: header.php (Com Abas de Navegação)
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}
?>

<style>
    /* ----------------------------------
     * 1. VARIÁVEIS E ESTILOS GERAIS
     * ---------------------------------- */
    :root {
        /* A altura total do header é a soma da barra principal (50px) + barra de abas (40px) */
        --header-main-height: 50px;
        --tabs-height: 40px;
        --header-total-height: 90px; /* 50px + 40px */

        --header-bg: #FFFFFF;
        --header-icon-color: #161823;
        --tab-active-color: #161823;
        --tab-inactive-color: #6C6C80;
        --header-max-width: 768px;
    }

    /* Padding Global: Garante que o conteúdo comece abaixo do header fixo */
    body {
        margin: 0;
        /* O padding-top AGORA usa a altura total (90px) */
        padding-top: var(--header-total-height);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    html {
        scroll-behavior: smooth;
    }

    /* ----------------------------------
     * 2. ESTILO DO HEADER FIXO (Contêiner Principal)
     * ---------------------------------- */
    .top-header {
        height: var(--header-total-height);
        width: 100%;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 999;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        background-color: var(--header-bg);
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }

    /* 2a. BARRA PRINCIPAL (Ícones) */
    .header-main-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        /* Altura fixa da barra de ícones */
        height: var(--header-main-height);
        margin: 0 auto;
        max-width: var(--header-max-width);
        padding: 0 15px;
        box-sizing: border-box;
    }

    /* 2b. BARRA DE NAVEGAÇÃO DE ABAS (Tabs) */
    .header-tabs-bar {
        display: flex;
        width: 100%;
        height: var(--tabs-height); /* 40px de altura */
        overflow-x: auto; /* Permite scroll lateral se houver muitas abas */
        -webkit-overflow-scrolling: touch;
        white-space: nowrap; /* Mantém as abas na mesma linha */
    }

    .tabs-content-wrapper {
        display: flex;
        align-items: center;
        margin: 0 auto;
        max-width: var(--header-max-width);
        padding: 0 15px;
        box-sizing: border-box;
        height: 100%;
        min-width: 100%; /* Garante que o wrapper ocupa a largura total para o scroll */
    }

    /* Estilo das Abas */
    .tab-item {
        display: block;
        height: 100%;
        padding: 0 10px;
        margin-right: 15px;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        color: var(--tab-inactive-color);
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        position: relative;
        -webkit-tap-highlight-color: transparent;
    }

    /* Estilo da Aba Ativa (Visível na imagem) */
    .tab-item.active {
        color: var(--tab-active-color);
        font-weight: 600;
    }
    .tab-item.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(135deg, #FE2C55 0%, #E0234D 100%);
        border-radius: 2px 2px 0 0;
    }

    /* ----------------------------------
     * 3. ESTILO DOS ÍCONES (Mantido)
     * ---------------------------------- */
    .top-header .right-icons {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .top-header .icon-button {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: var(--header-icon-color);
        padding: 5px;
    }

    .top-header .icon-button svg {
        height: 22px;
        width: 22px;
        flex-shrink: 0;
        transition: opacity 0.15s ease;
    }
    .top-header .icon-button:active svg {
        opacity: 0.6;
    }

    /* ----------------------------------
     * 4. MEDIA QUERIES (Responsividade)
     * ---------------------------------- */
    @media (max-width: 360px) {
        .header-main-bar {
            padding: 0 10px;
        }

        .top-header .right-icons {
            gap: 15px;
        }
    }

</style>

<header class="top-header">

    <div class="header-main-bar">

        <a href="#" class="icon-button close-button" aria-label="Fechar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
            </svg>
        </a>

        <div class="right-icons">
            <a href="#" class="icon-button share-button" id="headerShareButton" aria-label="Compartilhar">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 48 48" fill="currentColor">
                    <path d="M23.82 3.5A2 2 0 0 0 20.5 5v10.06C8.7 15.96 1 25.32 1 37a2 2 0 0 0 3.41 1.41c4.14-4.13 10.4-5.6 16.09-5.88v9.97a2 2 0 0 0 3.3 1.52l21.5-18.5a2 2 0 0 0 .02-3.02z"></path>
                </svg>
            </a>

            <a href="#" class="icon-button cart-button" aria-label="Carrinho de Compras">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart2" viewBox="0 0 16 16">
                    <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l1.25 5h8.22l1.25-5zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/>
                </svg>
            </a>

            <a href="#" class="icon-button more-button" aria-label="Mais opções">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3"/>
                </svg>
            </a>
        </div>
    </div>

    <nav class="header-tabs-bar">
        <div class="tabs-content-wrapper">
            <a href="#visao-geral" class="tab-item active">Visão geral</a>
            <a href="#avaliacoes" class="tab-item">Avaliações</a>
            <a href="#descricao" class="tab-item">Descrição</a>
            </div>
    </nav>

</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const headerShareButton = document.getElementById('headerShareButton');
    const tabItems = document.querySelectorAll('.tab-item');
    const HEADER_OFFSET = 90; // Altura do header fixo (para ajuste de scroll)

    // Armazena as seções que a Intersection Observer deve observar
    const sections = {};

    // ==========================================================
    // 1. LÓGICA DE DETECÇÃO DE VISIBILIDADE (INTERSECTION OBSERVER)
    // ==========================================================

    // Opções do Observer: ajusta o ponto onde a detecção deve ocorrer
    const observerOptions = {
        root: null, // viewport
        rootMargin: `-${HEADER_OFFSET}px 0px -50% 0px`, // Detecta quando a seção atinge o topo do conteúdo (abaixo do header)
        threshold: 0 // Não é estritamente necessário um threshold alto
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const id = entry.target.id;
            const targetTab = document.querySelector(`.tab-item[href="#${id}"]`);

            if (entry.isIntersecting) {
                // Se a seção entrar na viewport (na margem definida), ativa a aba

                // 1. Remove a classe 'active' de todos os itens
                tabItems.forEach(t => t.classList.remove('active'));

                // 2. Adiciona a classe 'active' ao item correspondente
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            } else {
                // Opcional: Se a seção sair da viewport, remove a classe 'active'
                // NOTA: Descomente se quiser um controle mais rigoroso, mas o 'isIntersecting'
                // da próxima seção geralmente já lida com isso.
                // if (targetTab) {
                //     targetTab.classList.remove('active');
                // }
            }
        });
    }, observerOptions);

    // ==========================================================
    // 2. LÓGICA DE NAVEGAÇÃO ENTRE ABAS (Scroll Suave)
    // ==========================================================

    tabItems.forEach(tab => {
        const targetId = tab.getAttribute('href');
        const targetElement = document.querySelector(targetId);

        // Mapeia e começa a observar as seções de conteúdo
        if (targetElement) {
            sections[targetId] = targetElement;
            observer.observe(targetElement); // Começa a observar a seção
        }

        tab.addEventListener('click', (e) => {
            e.preventDefault(); // Impede o salto instantâneo padrão do navegador

            // Ativa o link (visual) imediatamente
            tabItems.forEach(t => t.classList.remove('active'));
            e.currentTarget.classList.add('active');

            // Encontra o elemento de destino
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                // Calcula a posição de destino: posição do elemento - altura do header
                const targetPosition = targetElement.offsetTop - HEADER_OFFSET;

                // Executa o scroll suave
                window.scrollTo({
                    top: targetPosition,
                    behavior: "smooth"
                });
            }
        });
    });

    // ==========================================================
    // 3. LÓGICA DE COMPARTILHAMENTO (Mantida)
    // ==========================================================

    const copyCurrentUrl = (e) => {
        e.preventDefault();
        const currentUrl = window.location.href;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(currentUrl)
                .then(() => {
                    const originalContent = headerShareButton.innerHTML;
                    headerShareButton.innerHTML = '✅';
                    setTimeout(() => {
                        headerShareButton.innerHTML = originalContent;
                    }, 2000);
                })
                .catch(err => {
                    alert("Copie o link manualmente: " + currentUrl);
                });
        } else {
            alert("Copie o link manualmente: " + currentUrl);
        }
    };

    if (headerShareButton) {
        headerShareButton.addEventListener('click', (e) => {
            e.preventDefault();
            const shareData = {
                title: document.title || 'Confira esta página!',
                url: window.location.href,
            };

            if (navigator.share) {
                navigator.share(shareData)
                    .catch((error) => {
                        console.log('Erro ao compartilhar ou cancelado:', error);
                        copyCurrentUrl(e);
                    });
            } else {
                copyCurrentUrl(e);
            }
        });
    }

});
</script>