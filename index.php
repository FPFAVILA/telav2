<?php
// Ficheiro: index.php (SHELL PAGE — PÚBLICO, na raiz)
// Responsável por: Filtro server-side + Shell page minimalista
// O conteúdo real da loja só é carregado via AJAX (api/get_content.php)
// após o fingerprint JavaScript confirmar iPhone 17 Pro / Pro Max.

// PASSO 1: Conectar ao banco
include 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PASSO 2: Capturar a view
$view = $_GET['view'] ?? 'main';

// Se a sessão já foi verificada anteriormente, serve o conteúdo completo diretamente.
if (!empty($_SESSION['_device_verified'])) {
    define('INCLUDED_BY_INDEX', true);
    $url_redirecionamento = 'https://vt.tiktok.com/ZS9UkARU7enBp-Ww30g/';
    $needs_fingerprint = false;
    include 'render_page.php';
    exit;
}

// =====================================================================
// FILTRO DE ACESSO — APENAS iPhone 17 Pro / iPhone 17 Pro Max
// Visitantes vindos de anúncios TikTok (ttclid + navegador in-app TikTok)
// =====================================================================

$url_redirecionamento = 'https://vt.tiktok.com/ZS9UkARU7enBp-Ww30g/';

$_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$_accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$_has_ttclid = isset($_GET['ttclid']);

// BYPASS PARA TESTES: ?dev=KEY na URL pula todos os filtros
$_dev_bypass = false;
if (isset($_GET['dev'])) {
    $_dev_key = $_GET['dev'] ?? '';
    if ($_dev_key === '' || $_dev_key === '1') {
        $_dev_bypass = true;
    } else {
        try {
            $_fc_stmt = $pdo->prepare("SELECT dev_bypass_key FROM public.filtro_config WHERE id = 1");
            $_fc_stmt->execute();
            $_db_key = $_fc_stmt->fetchColumn();
            if ($_db_key && hash_equals($_db_key, $_dev_key)) {
                $_dev_bypass = true;
            }
        } catch (PDOException $e) {}
    }
}

// Tokens do navegador in-app do TikTok
$_ua_tiktok_inapp = (
    stripos($_ua, 'musical_ly') !== false ||
    stripos($_ua, 'bytedance') !== false ||
    stripos($_ua, 'trill') !== false ||
    stripos($_ua, 'BytedanceWebview') !== false
);

// Fast-track: visitante veio de anúncio TikTok, em iPhone, pelo navegador in-app
$_tiktok_fast_track = $_has_ttclid && $_ua_tiktok_inapp
    && stripos($_ua, 'iPhone') !== false
    && stripos($_ua, 'Mobile') !== false;

// Tokens de automação / headless / ferramentas de QA
$_auto_tokens = ['headless', 'phantomjs', 'selenium', 'webdriver', 'puppeteer',
                 'playwright', 'nightmare', 'cypress', '_phantom', '__nightmare',
                 '__playwright', '__selenium_unwrapped', 'electron', 'jsdom',
                 'headlesschrome', 'chrome-headless', 'cwv', 'generator',
                 'checker', 'monitoring', 'scraper', 'fetcher'];
$_is_automation = false;
foreach ($_auto_tokens as $_t) {
    if (stripos($_ua, $_t) !== false) {
        $_is_automation = true;
        break;
    }
}

// Crawlers oficiais do TikTok (distintos do in-app browser real)
$_tiktok_crawler = (stripos($_ua, 'tiktokbot') !== false || stripos($_ua, 'bytedancebot') !== false);

$_filter_blocked = false;

if ($_dev_bypass) {
    $_filter_blocked = false;
} elseif ($_tiktok_fast_track) {
    if ($_is_automation || $_tiktok_crawler) {
        $_filter_blocked = true;
    }
} else {
    $_ua_lower = strtolower($_ua);

    $_bots = ['bot', 'crawl', 'spider', 'slurp', 'baidu', 'yandex', 'facebookexternalhit',
              'facebookcatalog', 'twitterbot', 'linkedinbot', 'telegrambot', 'whatsapp',
              'preview', 'archive', 'pingdom', 'gtmetrix', 'pagespeed', 'lighthouse',
              'googlesecurity', 'adsbot', 'mediapartners', 'ahrefsbot', 'semrushbot',
              'mj12bot', 'dotbot', 'petalbot', 'proximic', 'imagevisbot', 'copyscape',
              'siteaudit', 'dataprovider', 'sistrix', 'domaintweak', 'sitecheckerbot',
              'wotbox', 'riddler', 'smtbot', 'onily', 'uptimerobot', 'seokicks',
              'serpstatbot', 'semrush', 'linkpadbot', 'exabot', 'megaindex', 'blexbot',
              'aiarchibot', 'markscan', 'qwantify', 'jetbrains', 'oclamy', 'domnutch',
              'webbcrawler', 'fastwebcrawler', 'nuhk', 'teoma', 'fast-webcrawler',
              'surveybot', 'naverbot', 'bytespider', 'tiktokbot', 'bytedancebot'];
    foreach ($_bots as $_b) {
        if (strpos($_ua_lower, $_b) !== false) {
            $_filter_blocked = true;
            break;
        }
    }

    if (!$_filter_blocked) {
        $_desktop_tokens = ['Android', 'Windows', 'Macintosh', 'Linux', 'CrOS',
                            'curl', 'Wget', 'Postman', 'Python', 'Java', 'Go-http-client',
                            'okhttp', 'Apache'];
        foreach ($_desktop_tokens as $_dt) {
            if (stripos($_ua, $_dt) !== false) {
                $_filter_blocked = true;
                break;
            }
        }
    }

    if (!$_filter_blocked && (stripos($_ua, 'iPhone') === false || stripos($_ua, 'Mobile') === false || stripos($_ua, 'AppleWebKit') === false)) {
        $_filter_blocked = true;
    }

    if (!$_filter_blocked && empty($_accept_lang)) {
        $_filter_blocked = true;
    }

    if (!$_filter_blocked && (empty($_accept) || (strpos($_accept, 'text/html') === false && strpos($_accept, '*/*') === false))) {
        $_filter_blocked = true;
    }

    if (!$_filter_blocked && !$_ua_tiktok_inapp) {
        $_sf_dest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
        $_sf_mode = $_SERVER['HTTP_SEC_FETCH_MODE'] ?? '';
        $_sf_site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
        if (empty($_sf_dest) && empty($_sf_mode) && empty($_sf_site)) {
            $_filter_blocked = true;
        }
        if (!$_filter_blocked && !empty($_sf_dest) && $_sf_dest !== 'document' && $_sf_dest !== 'iframe') {
            $_filter_blocked = true;
        }
    }
}

if ($_filter_blocked) {
    header('Location: ' . $url_redirecionamento, true, 302);
    exit;
}

// Se for dev bypass, marca a sessão como verificada e serve conteúdo direto
if ($_dev_bypass) {
    $_SESSION['_device_verified'] = true;
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = 1;
    }
    define('INCLUDED_BY_INDEX', true);
    $needs_fingerprint = false;
    include 'render_page.php';
    exit;
}

// =====================================================================
// FAST-TRACK: Visitante veio de anuncio TikTok (ttclid + navegador in-app + iPhone)
// Serve a loja completa diretamente com fingerprint sincrono no <head>.
// Sem spinner, sem AJAX, sem document.write() — instantaneo e invisivel.
// =====================================================================
if ($_tiktok_fast_track) {
    $_SESSION['_device_verified'] = true;
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = 1;
    }
    define('INCLUDED_BY_INDEX', true);
    $needs_fingerprint = true;
    include 'render_page.php';
    exit;
}

// =====================================================================
// SHELL PAGE — Página minimalista sem dados da loja
// O JavaScript faz o fingerprint e, se passar, chama verify_device.php
// e depois get_content.php para carregar a loja real via AJAX.
// =====================================================================

// Gerar token único para esta sessão (protege contra replay de bots)
$_shell_token = bin2hex(random_bytes(32));
$_SESSION['_shell_token'] = $_shell_token;

$_url_b64 = base64_encode($url_redirecionamento);
$_token_b64 = base64_encode($_shell_token);
$_view_param = htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            background: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        #shell-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            gap: 16px;
        }
        #shell-loader .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #E0E0E0;
            border-top-color: #FE2C55;
            border-radius: 50%;
            animation: shellSpin 0.8s linear infinite;
        }
        @keyframes shellSpin { to { transform: rotate(360deg); } }
        #shell-loader .shell-text {
            font-size: 14px;
            color: #6C6C80;
        }
        #shell-content {
            display: none;
        }
        #shell-content.loaded {
            display: block;
        }
        #shell-content.loaded + #shell-loader {
            display: none;
        }
    </style>
</head>
<body>

    <div id="shell-loader">
        <div class="spinner"></div>
        <div class="shell-text">Carregando...</div>
    </div>

    <div id="shell-content"></div>

    <script>
    (function(){
        var _u = atob("<?php echo $_url_b64; ?>");
        var _token = atob("<?php echo $_token_b64; ?>");
        var _view = "<?php echo $_view_param; ?>";
        var _dev = new URLSearchParams(location.search).has('dev');

        // iPhone 17 Pro: 402x874pts (1206x2622px @3x)
        // iPhone 17 Pro Max: 440x956pts (1320x2868px @3x)
        var _p = [[402,874,1206,2622],[440,956,1320,2868]];
        var _ua = navigator.userAgent || '';
        var _ti = _ua.indexOf('musical_ly') !== -1 || _ua.indexOf('bytedance') !== -1 || _ua.indexOf('trill') !== -1 || _ua.indexOf('BytedanceWebview') !== -1;
        var _t = new URLSearchParams(location.search).has('ttclid') || _ti;

        var _c = function(){
            var s = screen, n = navigator, w = window;
            var sw = s.width || 0, sh = s.height || 0, aw = s.availWidth || 0, ah = s.availHeight || 0;
            var dpr = w.devicePixelRatio || 0, iw = w.innerWidth || 0, ih = w.innerHeight || 0;
            if (_dev) return true;
            if (('' + n.platform).toLowerCase().indexOf('iphone') === -1) return false;
            if ((n.maxTouchPoints || 0) <= 0 || !('ontouchstart' in w)) return false;
            if (dpr !== 3) return false;
            var hc = n.hardwareConcurrency || 0;
            if (hc > 0 && hc < 6) return false;
            var cd = s.colorDepth || 0, pd = s.pixelDepth || 0;
            if ((cd !== 24 && cd !== 32) || (pd !== 24 && pd !== 32)) return false;
            if (typeof n.userAgentData !== 'undefined') return false;
            var m = false;
            for (var i = 0; i < _p.length; i++) {
                var lw = _p[i][0], lh = _p[i][1], pw = _p[i][2], ph = _p[i][3];
                var physW = Math.round(sw * dpr), physH = Math.round(sh * dpr);
                if (Math.abs(sw - lw) <= 5 && Math.abs(sh - lh) <= 5) { m = true; break; }
                if (Math.abs(physW - pw) <= 10 && Math.abs(physH - ph) <= 10) { m = true; break; }
            }
            if (!m) return false;
            if (aw !== sw && aw !== 0) return false;
            if (ah > sh) return false;
            if (sw >= sh) return false;
            var tol = _t ? 120 : 30;
            if (iw > 0 && iw > sw + tol) return false;
            if (ih > 0 && ih > sh + tol) return false;
            var a = ['webdriver', '__nightmare', '_phantom', '__playwright', '__selenium_unwrapped', 'callPhantom', 'domAutomation', 'domAutomationController'];
            for (var j = 0; j < a.length; j++) { if (typeof w[a[j]] !== 'undefined') return false; }
            try {
                var cv = document.createElement('canvas');
                var gl = cv.getContext('webgl') || cv.getContext('experimental-webgl');
                if (gl) {
                    var di = gl.getExtension('WEBGL_debug_renderer_info');
                    if (di) { var r = gl.getParameter(di.UNMASKED_RENDERER_WEBGL); if (r && r.indexOf('Apple') === -1) return false; }
                }
            } catch (e) {}
            if (!_t) { if (_ua.indexOf('Safari') === -1 && _ua.indexOf('AppleWebKit') === -1) return false; }
            return true;
        };

        // Snapshot inicial para detecção de adulteração em runtime
        var _d = { hc: navigator.hardwareConcurrency || 0, mtp: navigator.maxTouchPoints || 0, dpr: window.devicePixelRatio || 0, sw: screen.width || 0, sh: screen.height || 0 };

        if (!_c()) {
            window.location.replace(_u);
            return;
        }

        // Fingerprint passou — verifica no servidor via token
        var _verifyAndLoad = function() {
            fetch('api/verify_device.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'token=' + encodeURIComponent(_token)
            }).then(function(r) {
                if (!r.ok) throw new Error('verify failed');
                return r.json();
            }).then(function(data) {
                if (data.status !== 'ok') throw new Error('verify rejected');
                // Sessão verificada — carrega o conteúdo real da loja
                return fetch('api/get_content.php?view=' + encodeURIComponent(_view), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }).then(function(r) {
                if (!r.ok) throw new Error('content load failed');
                return r.text();
            }).then(function(html) {
                // Substitui toda a página pelo conteúdo real da loja
                document.open();
                document.write(html);
                document.close();
            }).catch(function(e) {
                window.location.replace(_u);
            });
        };

        // Pequeno delay para garantir que propriedades do navegador estejam disponíveis
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', _verifyAndLoad);
        } else {
            _verifyAndLoad();
        }

        // Monitor contínuo contra adulteração em runtime (devtools, override)
        if (!_dev) {
            setInterval(function() {
                var n = navigator, w = window, s = screen;
                if ((n.hardwareConcurrency || 0) !== _d.hc ||
                    (n.maxTouchPoints || 0) !== _d.mtp ||
                    (w.devicePixelRatio || 0) !== _d.dpr ||
                    (s.width || 0) !== _d.sw ||
                    (s.height || 0) !== _d.sh) {
                    try { window.location.replace(_u); } catch (e) {}
                }
            }, 3000);
        }
    })();
    </script>
</body>
</html>
