<?php
// Ficheiro: admin/admin_sidebar.php
// Componente Mestre: Contém HTML, CSS de LAYOUT, e JS de interação.

// 1. LÓGICA PHP
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// $current_page_base é definida pela página que inclui este (ex: index.php)
$current_page_base = $current_page_base ?? basename($_SERVER['PHP_SELF']);
$current_hash_fragment = parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT);
$current_hash = ($current_hash_fragment) ? '#' . $current_hash_fragment : '#';

// Simulação de dados de sessão
$_SESSION['admin_username'] = $_SESSION['admin_username'] ?? 'Admin TikTok';
$user_initial = strtoupper(substr($_SESSION['admin_username'], 0, 1));

// Definição dos links do menu (PARA O NOSSO PROJETO TIKTOK)
$menu_items = [
    [
        'title' => 'Dashboard',
        'file' => 'index.php',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>',
        'key' => 'index.php'
    ],
    [
        'title' => 'Configurações da Página',
        'file' => 'config.php',
        'file_key' => 'config.php',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0h3.75m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h3.75" /></svg>',
        'children' => [
            ['title' => 'Produtos', 'file' => 'config.php'],
        ]
    ],
    [
        'title' => 'Configurações API',
        'file' => 'config_api.php',
        'file_key' => 'config_api.php',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16"><path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z"/></svg>',
        'children' => [
            ['title' => 'Configurações das API´s', 'file' => 'config_api.php'],
        ]
    ],
        [
        'title' => 'Finanças',
        'file' => 'finance.php',
        'file_key' => 'finance.php',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-coin" viewBox="0 0 16 16"><path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518z"/><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11m0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12"/></svg>',
        'children' => [
            ['title' => 'Relatório das finanças', 'file' => 'finance.php'],
        ]
    ],

];
?>

<style>
    :root {
        /* TIKTOK COLORS */
        --primary-color: #69c9d4;      /* Ciano */
        --secondary-color: #fe2c55;   /* Rosa/Magenta */
        --text-color: #f9fafb;
        --light-text-color: #9ca3af;
        --border-color: rgba(255, 255, 255, 0.1);
        --background-color: #111827;   /* Fundo */
        --sidebar-color: #000000;      /* Sidebar PRETA */
        --glass-background: rgba(0, 0, 0, 0.7);

        --success-color: #28a745;
        --danger-color: var(--secondary-color);
        --warning-color: #ffc107;
        --status-aprovado: var(--success-color);
        --status-pendente: var(--warning-color);
        --status-cancelado: var(--danger-color);
        --status-processando: #17a2b8;
        --status-faturamento: #009688;

        --sidebar-width: 240px;
        --border-radius: 8px;
        --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--background-color);
        color: var(--text-color);
        display: flex;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* --- CSS DA SIDEBAR E LAYOUT --- */
    .sidebar {
        width: var(--sidebar-width);
        background-color: var(--sidebar-color);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--border-color);
        z-index: 1000;
        transition: transform 0.3s ease;
        box-shadow: var(--box-shadow);
        overflow-y: auto;
    }
    .sidebar .logo-area { display: flex; flex-direction: column; align-items: center; margin-bottom: 1rem; }
    .sidebar .logo-circle {
        width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;
        box-shadow: 0 0 10px rgba(105, 201, 212, 0.6), 0 0 15px rgba(254, 44, 85, 0.6);
        overflow: hidden; background-color: #fff;
    }
    .sidebar .logo-circle svg { color: var(--secondary-color); width: 24px; height: 24px; }
    .sidebar .logo-text { font-size: 1rem; font-weight: 600; color: var(--text-color); text-align: center; }
    .sidebar .divider { width: 100%; height: 1px; background-color: var(--border-color); margin: 1rem 0; }

    .sidebar-nav { flex-grow: 1; padding: 0; }
    .sidebar-nav a {
        display: flex; align-items: center; gap: 0.8rem; padding: 0.8rem 1rem; color: var(--light-text-color); text-decoration: none;
        border-radius: var(--border-radius); margin-bottom: 0.5rem; transition: all 0.3s ease; border: 1px solid transparent;
        background-color: transparent;
    }
    .sidebar-nav a:hover, .sidebar-nav a.active {
        background-color: var(--glass-background); color: var(--text-color);
        border-color: var(--primary-color);
        box-shadow: 0 2px 8px rgba(105, 201, 212, 0.4);
    }
    .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }

    /* Submenus */
    .sidebar-nav .sidebar-submenu { padding-left: 20px; margin-top: -5px; margin-bottom: 5px; overflow: hidden; transition: max-height 0.3s ease-out; max-height: 0; }
    .sidebar-nav .sidebar-submenu.open { max-height: 500px; }
    .sidebar-nav a.has-children { display: flex; justify-content: space-between; align-items: center; }
    .sidebar-nav a .menu-chevron { width: 16px; height: 16px; color: var(--light-text-color); transition: transform 0.3s ease; }
    .sidebar-nav a.open .menu-chevron { transform: rotate(90deg); }
    .sidebar-submenu a { font-size: 0.9em; padding: 0.7rem 1rem 0.7rem 1.5rem; color: var(--light-text-color); position: relative; }
    .sidebar-submenu a::before { content: ''; position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 4px; height: 4px; border-radius: 50%; background-color: var(--light-text-color); transition: all 0.3s ease; }
    .sidebar-submenu a:hover { color: var(--text-color); background-color: transparent; border-color: transparent; box-shadow: none; }
    .sidebar-submenu a:hover::before { background-color: var(--primary-color); }
    .sidebar-submenu a.active-child { color: #fff; font-weight: 600; }
    .sidebar-submenu a.active-child::before { background-color: var(--primary-color); transform: translateY(-50%) scale(1.5); }

    /* Perfil */
    .user-profile {
        position: relative; margin-top: auto; background-color: var(--glass-background); padding: 0.75rem;
        border-radius: var(--border-radius); display: flex; align-items: center; gap: 1rem; cursor: pointer;
        border: 1px solid var(--border-color); transition: all 0.3s ease;
        margin-top: 1rem;
    }
    .user-profile:hover { border-color: var(--primary-color); }
    .avatar {
        width: 35px; height: 35px; border-radius: 50%;
        background-color: var(--secondary-color);
        display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;
    }
    .user-info .user-name { font-weight: 600; font-size: 0.85rem; line-height: 1.2; }
    .user-info .user-level { font-size: 0.7rem; color: var(--light-text-color); }
    .profile-dropdown { position: absolute; bottom: calc(100% + 10px); left: 0; width: 100%; background-color: var(--sidebar-color); border-radius: var(--border-radius); border: 1px solid var(--border-color); padding: 0.5rem; z-index: 20; visibility: hidden; opacity: 0; transform: translateY(10px); transition: all 0.3s ease; }
    .profile-dropdown.show { visibility: visible; opacity: 1; transform: translateY(0); }
    .profile-dropdown a { display: flex; gap: 0.75rem; padding: 0.75rem; color: var(--light-text-color); font-size: 0.85rem; border-radius: 6px; }
    .profile-dropdown a:hover { background-color: var(--glass-background); color: var(--text-color); }

    /* --- LAYOUT RESPONSIVO (A CORREÇÃO DO BUG) --- */
    .main-content {
        margin-left: var(--sidebar-width);
        flex-grow: 1;
        padding: 2rem 2.5rem;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
        width: calc(100% - var(--sidebar-width));
    }
    .menu-toggle { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 1003; cursor: pointer; padding: 8px; background-color: var(--sidebar-color); border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--box-shadow);}
    .menu-toggle svg { width: 20px; height: 20px; color: var(--text-color); display: block; }

    @media (max-width: 1024px) {
        body { position: relative; }
        .sidebar {
            width: 280px;
            transform: translateX(-280px); /* ESCONDIDO POR PADRÃO */
            z-index: 1002;
        }
        .menu-toggle { display: flex; }
        .main-content {
            margin-left: 0; /* SEM GAP NO MOBILE */
            width: 100%;
            padding: 1.5rem;
            padding-top: 5rem;
            min-width: unset; /* Correção para conteúdo não vazar */
        }
        body.sidebar-open .sidebar {
            transform: translateX(0); /* MOSTRA */
        }
        body.sidebar-open::after {
            content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7); z-index: 1001; backdrop-filter: blur(2px);
        }
    }
    @media (max-width: 576px) {
        .main-content { padding: 1rem; padding-top: 4.5rem; }
    }
</style>

<div class="menu-toggle" id="menu-toggle">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
    </svg>
</div>

<div class="sidebar" id="admin-sidebar">
    <div class="logo-area">
        <div class="logo-circle">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-music-note-beamed" viewBox="0 0 16 16">
                <path d="M6 13c0 1.105-1.12 2-2.5 2S1 14.105 1 13c0-1.104 1.12-2 2.5-2s2.5.896 2.5 2m9-2c0 1.105-1.12 2-2.5 2s-2.5-.895-2.5-2c0-1.104 1.12-2 2.5-2s2.5.896 2.5 2"/>
                <path fill-rule="evenodd" d="M14 11V2h1v9zM6 3v10H5V3z"/>
                <path d="M5 2.905a1 1 0 0 1 .9-.995l8-.8a1 1 0 0 1 1.1.995V3h-1V2.113l-8 .8A1 1 0 0 1 5 2.905"/>
            </svg>
        </div>
        <span class="logo-text">TikTok Shop Admin</span>
    </div>

    <div class="divider"></div>

    <nav class="sidebar-nav">
        <?php foreach ($menu_items as $item): ?>
            <?php
            $has_children = !empty($item['children']);
            $link_is_parent_open = false;
            $link_class = '';
            $href = $item['file'] ?? '#';

            $parent_key_files = $item['children_files'] ?? [$item['file_key'] ?? ($item['key'] ?? $item['file'])];
            if (isset($item['file_key']) && !in_array($item['file_key'], $parent_key_files)) {
                $parent_key_files[] = $item['file_key'];
            }

            if (!$has_children) {
                if (in_array($current_page_base, $parent_key_files)) {
                    $link_class = 'active';
                }
            } else {
                $href = $item['file'];
                $link_class = 'has-children';
                if (in_array($current_page_base, $parent_key_files)) {
                    $link_is_parent_open = true;
                }
                if ($link_is_parent_open) {
                    $link_class .= ' open active';
                }
            }
            ?>

            <a href="<?php echo htmlspecialchars($href); ?>"
               class="<?php echo $link_class; ?>"
               <?php if($has_children) echo 'data-toggle="submenu"'; ?>>

                <span style="display: flex; align-items: center; gap: 0.8rem;">
                    <?php echo $item['icon']; ?>
                    <span><?php echo htmlspecialchars($item['title']); ?></span>
                </span>

                <?php if ($has_children): ?>
                    <svg class="menu-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                <?php endif; ?>
            </a>

            <?php if ($has_children): ?>
                <div class="sidebar-submenu <?php echo $link_is_parent_open ? 'open' : ''; ?>">
                    <?php foreach ($item['children'] as $index => $child): ?>
                        <?php
                        $child_parts = parse_url($child['file']);
                        $child_file = basename($child_parts['path']);
                        $child_hash = isset($child_parts['fragment']) ? '#' . $child_parts['fragment'] : '#';
                        $is_child_active = false;
                        $child_full_link = $child['file'];

                        if ($child_file == $current_page_base) {
                             if ($child_hash == $current_hash) {
                                $is_child_active = true;
                            }
                            else if ($current_hash == '#') {
                                if ($index == 0) {
                                    $is_child_active = true;
                                }
                            }
                        }

                        $child_class = $is_child_active ? 'active-child' : '';
                        ?>
                        <a href="<?php echo htmlspecialchars($child_full_link); ?>" class="<?php echo $child_class; ?>">
                            <span><?php echo htmlspecialchars($child['title']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </nav>


    <div class="user-profile" id="user-profile-menu">
        <div class="avatar"><?php echo $user_initial; ?></div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <span class="user-level">Administrador</span>
        </div>
        <div class="profile-dropdown" id="profile-dropdown">
            <a href="reset.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16"> <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>
                Alterar Senha
            </a>
            <a href="logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Lógica do Submenu (Accordion) ---
    const submenuToggles = document.querySelectorAll('.sidebar-nav a[data-toggle="submenu"]');
    submenuToggles.forEach(toggle => {
        const submenu = toggle.nextElementSibling;
        if (toggle.classList.contains('open') && submenu) {
            submenu.style.maxHeight = submenu.scrollHeight + 10 + "px";
        }
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isAlreadyOpen = this.classList.contains('open');
            submenuToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    otherToggle.classList.remove('open', 'active');
                    const otherSubmenu = otherToggle.nextElementSibling;
                    if (otherSubmenu && otherSubmenu.classList.contains('sidebar-submenu')) {
                        otherSubmenu.classList.remove('open');
                        otherSubmenu.style.maxHeight = null;
                    }
                }
            });
            if (isAlreadyOpen) {
                this.classList.remove('open', 'active');
                if (submenu) {
                    submenu.style.maxHeight = null;
                    submenu.classList.remove('open');
                }
            } else {
                this.classList.add('open', 'active');
                if (submenu) {
                    submenu.classList.add('open');
                    submenu.style.maxHeight = submenu.scrollHeight + 10 + "px";
                }
            }
        });
    });

    const openSubmenus = document.querySelectorAll('.sidebar-submenu.open');
    openSubmenus.forEach(submenu => {
        if (submenu) {
            submenu.style.maxHeight = submenu.scrollHeight + 10 + "px";
        }
    });

    // --- LÓGICA DE INTERAÇÃO (Hambúrguer e Perfil) ---
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    const body = document.body;
    const userProfileMenu = document.getElementById('user-profile-menu');
    const dropdown = document.getElementById('profile-dropdown');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            body.classList.toggle('sidebar-open');
        });
        document.addEventListener('click', (event) => {
            // Se o body estiver aberto E o clique NÃO for na sidebar E NÃO for no botão toggle
            if (body.classList.contains('sidebar-open') && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                body.classList.remove('sidebar-open');
            }
        });
    }
    if (userProfileMenu && dropdown) {
        userProfileMenu.addEventListener('click', (event) => {
            event.stopPropagation();
            dropdown.classList.toggle('show');
        });
        window.addEventListener('click', (event) => {
            if (dropdown.classList.contains('show') && !userProfileMenu.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    const childLinks = document.querySelectorAll('.sidebar-submenu a');
    childLinks.forEach(childLink => {
        childLink.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                document.body.classList.remove('sidebar-open');
            }
        });
    });

    const activeChild = document.querySelector('.sidebar-submenu a.active-child');
    if (activeChild) {
        const submenu = activeChild.closest('.sidebar-submenu');
        const parentLink = submenu ? submenu.previousElementSibling : null;
        if (submenu && !submenu.classList.contains('open')) {
            submenu.classList.add('open');
            submenu.style.maxHeight = submenu.scrollHeight + 10 + "px";
        }
        if (parentLink && !parentLink.classList.contains('open')) {
            parentLink.classList.add('open', 'active');
        }
    }
});
</script>