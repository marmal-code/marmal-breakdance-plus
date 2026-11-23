<?php
/**
 * Modul Cookies – web: Consent Mode v hlavičce, GA4, blokování skriptů a iframů do souhlasu,
 * načtení lišty (CSS + JS), shortcody.
 *
 * Vše, co závisí na souhlasu, se rozhoduje v prohlížeči (cookie marmal_consent) – HTML je pro
 * všechny návštěvníky stejné, takže modul funguje s page cache.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

/** Běží modul na tomto požadavku? (ne administrace, REST, AJAX, builder Breakdance, feed…) */
function bezi(): bool
{
    static $r = null;
    if (null !== $r) {
        return $r;
    }
    // phpcs:disable WordPress.Security.NonceVerification -- jen čtení příznaků builderu, nic se neukládá
    $builder = isset($_GET['breakdance']) || isset($_GET['breakdance_iframe']) || isset($_GET['bricks'])
        || (function_exists('\Breakdance\isRequestFromBuilderIframe') && \Breakdance\isRequestFromBuilderIframe());
    // phpcs:enable
    $r = !is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$builder
        && !(defined('REST_REQUEST') && REST_REQUEST) && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        && !is_customize_preview();
    return $r;
}

/** Správce si otevřel náhled lišty (?marmal_cookies_nahled=1) – lišta se ukáže vždy, nic se neukládá. */
function nahled(): bool
{
    return isset($_GET['marmal_cookies_nahled']) && current_user_can('manage_options'); // phpcs:ignore WordPress.Security.NonceVerification
}

// ============================================================= hlavička: Consent Mode v2 + GA4

add_action('wp_head', function () {
    if (!bezi()) {
        return;
    }
    $cfg = wp_json_encode(array('cookie' => COOKIE, 'rev' => (int) nastaveni()['rev'], 'n' => nahled()), JSON_HEX_TAG | JSON_HEX_AMP);
    // Výchozí stav Consent Mode = vše zamítnuto (kromě bezpečnosti); uložená volba se použije hned,
    // ještě před načtením jakéhokoli Google tagu. data-marmal-ignore = blokování tento skript nechá být.
    ?>
<script data-marmal-ignore>
(function (w, d, c) {
  w.dataLayer = w.dataLayer || [];
  w.gtag = w.gtag || function () { w.dataLayer.push(arguments); };
  function mapa(k) {
    var g = function (x) { return k.indexOf(x) > -1 ? 'granted' : 'denied'; };
    return { analytics_storage: g('analyticke'), ad_storage: g('marketingove'), ad_user_data: g('marketingove'),
      ad_personalization: g('marketingove'), functionality_storage: g('preferencni'), personalization_storage: g('preferencni') };
  }
  w.marmalCookiesMapa = mapa;
  // Událost pro Google Tag Manager (spouštěče „marmal_consent“, proměnné marmal_analyticke…).
  function udalost(k) {
    return { event: 'marmal_consent', marmal_kategorie: k.join(','), marmal_analyticke: k.indexOf('analyticke') > -1,
      marmal_marketingove: k.indexOf('marketingove') > -1, marmal_preferencni: k.indexOf('preferencni') > -1 };
  }
  w.marmalCookiesUdalost = udalost;
  var vychozi = mapa([]); vychozi.security_storage = 'granted'; vychozi.wait_for_update = 500;
  w.gtag('consent', 'default', vychozi);
  w.gtag('set', 'ads_data_redaction', true);
  var m = d.cookie.match(new RegExp('(?:^|; )' + c.cookie + '=([^;]*)')), s = null;
  if (m) { try { s = JSON.parse(decodeURIComponent(m[1])); } catch (e) { s = null; } }
  if (s && s.r === c.rev && Array.isArray(s.k)) {
    w.gtag('consent', 'update', mapa(s.k)); w.marmalCookiesUlozeny = s; w.marmalCookiesStav = s.k.slice();
    w.dataLayer.push(udalost(s.k));
    // Lišta je v HTML (kvůli LCP) – návštěvník s platnou volbou ji neuvidí ani na okamžik.
    if (!c.n) { d.documentElement.classList.add('marmal-ck-ma'); }
  }
})(window, document, <?php echo $cfg; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode s JSON_HEX_TAG ?>);
</script>
    <?php
    echo kody_mereni(); // phpcs:ignore WordPress.Security.EscapeOutput -- ID ověřená regexem, do JS přes wp_json_encode
}, 1);

/**
 * Je na stránce odkaz pro otevření nastavení (Patička, shortcode, vlastní odkaz)? Zapíše čas, nejvýš jednou za den.
 * Administrace podle toho upozorní, když je skrytá ikonka a odkaz na webu chybí (ÚOOÚ: odkaz v patičce = minimum).
 */
function zapsat_odkaz_nastaveni(string $html): void
{
    // Tlačítka ze shortcodů [marmal_cookies_nastaveni] a [marmal_cookies_seznam] jsou jen na jedné stránce – nepočítají se.
    $najdeno = (bool) preg_match('/href=["\'][^"\']*#nastaveni-cookies["\']/i', $html);
    if (!$najdeno && preg_match_all('/class=["\']([^"\']*\bmarmal-cookies-nastaveni\b[^"\']*)["\']/i', $html, $m)) {
        foreach ($m[1] as $tridy) {
            if (strpos($tridy, 'marmal-ck-odkaz') === false && strpos($tridy, 'marmal-ck-seznam__btn') === false) {
                $najdeno = true;
                break;
            }
        }
    }
    if (!$najdeno) {
        return;
    }
    if (time() - (int) get_option(OPTION_ODKAZ, 0) > DAY_IN_SECONDS) {
        update_option(OPTION_ODKAZ, time(), false);
    }
}

/**
 * Načítá stránka fonty z Googlu (přenos IP adresy návštěvníka)? Zapíše čas, nejvýš jednou za den.
 * Modul Fonty běží dřív (vnořený buffer) – když je zapnutý a fonty stáhl, tady už nic nenajde.
 */
function zapsat_google_fonty(string $html): void
{
    if (stripos($html, 'fonts.googleapis.com') === false && stripos($html, 'fonts.gstatic.com/s/') === false) {
        return;
    }
    $posledne = (int) get_option(OPTION_GFONTY, 0);
    if (time() - $posledne > DAY_IN_SECONDS) {
        update_option(OPTION_GFONTY, time(), false);
    }
}

// ============================================================= měřicí kódy

/** <script> s kódem: bez kategorie se spustí hned, jinak čeká na souhlas (kategorie oddělené mezerou = stačí kterákoli). */
function skript(string $js, string $kat = ''): string
{
    if ($kat === '') {
        return '<script data-marmal-ignore>' . $js . "</script>\n";
    }
    return '<script type="text/plain" data-marmal-kat="' . esc_attr($kat) . '">' . $js . "</script>\n";
}

function skript_src(string $src, string $kat = ''): string
{
    if ($kat === '') {
        return '<script async src="' . esc_url($src) . "\" data-marmal-ignore></script>\n";
    }
    return '<script type="text/plain" data-marmal-kat="' . esc_attr($kat) . '" data-marmal-src="' . esc_url($src) . "\" async></script>\n";
}

function js($v): string
{
    return (string) wp_json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
}

/**
 * Kódy GA4, Google Ads, GTM, Sklik (SEM), Meta Pixel, Clarity, Hotjar a LinkedIn podle nastavení.
 * Základní Consent Mode: Google tagy čekají na souhlas; rozšířený: Google tagy hned (bez souhlasu jen pingy bez cookies).
 * Ostatní služby vždy až po souhlasu. Všechna ID prošla regexem z merici_vzory() / ga_id().
 */
function kody_mereni(): string
{
    $s     = nastaveni();
    $basic = $s['gcm'] !== 'advanced';
    $out   = '';

    // Google tag (gtag.js) – sdílený pro GA4 a Google Ads.
    $ga = ga_id();
    $aw = merici_id('gads');
    if ($ga !== '' || $aw !== '') {
        $kat = $basic ? trim(($ga !== '' ? 'analyticke ' : '') . ($aw !== '' ? 'marketingove' : '')) : '';
        $out .= skript_src('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ga !== '' ? $ga : $aw), $kat);
        $out .= skript('gtag("js", new Date());', $kat);
        if ($ga !== '') {
            $cfg  = ga_expirace() ? ', ' . js(array('cookie_expires' => ga_expirace())) : '';
            $out .= skript('gtag("config", ' . js($ga) . $cfg . ');', $basic ? 'analyticke' : '');
        }
        if ($aw !== '') {
            $out .= skript('gtag("config", ' . js($aw) . ');', $basic ? 'marketingove' : '');
        }
    }

    // Google Tag Manager – výchozí až po souhlasu (analytické nebo marketingové), nebo vždy s Consent Mode.
    $gtm = merici_id('gtm');
    if ($gtm !== '') {
        $out .= skript(
            "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s);j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer'," . js($gtm) . ');',
            $s['gtm_rezim'] === 'vzdy' ? '' : 'analyticke marketingove'
        );
    }

    // Sklik – Seznam Event Measurement: souhlas se předá před PageView (sid vznikne jen s ad_storage).
    $sem = merici_id('sem');
    if ($sem !== '') {
        $out .= skript(
            "(function(w,d,id){var s=d.createElement('script');s.async=true;s.src='https://l.seznam.cz/sul.js?id='+encodeURIComponent(id);"
            . "s.onload=function(){if(typeof w.SEM!=='function'){return;}var m=w.marmalCookiesMapa?w.marmalCookiesMapa(w.marmalCookiesStav||[]):{};"
            . "w.SEM('updateConsent',{consent_mode:{ad_storage:m.ad_storage,ad_user_data:m.ad_user_data,ad_personalization:m.ad_personalization,functionality_storage:m.functionality_storage,analytics_storage:m.analytics_storage}});"
            . "w.SEM('track','PageView');};d.head.appendChild(s);})(window,document," . js($sem) . ');',
            'marketingove'
        );
    }

    // Meta Pixel.
    $meta = merici_id('meta');
    if ($meta !== '') {
        $out .= skript(
            "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');"
            . 'fbq("init", ' . js($meta) . ');fbq("track", "PageView");',
            'marketingove'
        );
    }

    // LinkedIn Insight Tag.
    $li = merici_id('linkedin');
    if ($li !== '') {
        $out .= skript(
            'window._linkedin_partner_id=' . js($li) . ';window._linkedin_data_partner_ids=window._linkedin_data_partner_ids||[];window._linkedin_data_partner_ids.push(window._linkedin_partner_id);'
            . "(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}var s=document.getElementsByTagName('script')[0];var b=document.createElement('script');b.type='text/javascript';b.async=true;b.src='https://snap.licdn.com/li.lms-analytics/insight.min.js';s.parentNode.insertBefore(b,s);})(window.lintrk);",
            'marketingove'
        );
    }

    // Microsoft Clarity – po souhlasu s analytickými; consentv2 hned s aktuální volbou (reklamní část = marketingové).
    $cl = merici_id('clarity');
    if ($cl !== '') {
        $out .= skript(
            "(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,'clarity','script'," . js($cl) . ');'
            . "window.clarity('consentv2',{ad_Storage:(window.marmalCookiesStav||[]).indexOf('marketingove')>-1?'granted':'denied',analytics_Storage:'granted'});",
            'analyticke'
        );
    }

    // Hotjar (Contentsquare).
    $hj = merici_id('hotjar');
    if ($hj !== '') {
        $out .= skript(
            "(function(c,s,q,u,a,r,e){c.hj=c.hj||function(){(c.hj.q=c.hj.q||[]).push(arguments)};c._hjSettings={hjid:a};r=s.getElementsByTagName('head')[0];e=s.createElement('script');e.async=true;e.src=q+c._hjSettings.hjid+u;r.appendChild(e);})(window,document,'https://static.hj.contentsquare.net/c/csq-','.js'," . (int) $hj . ');',
            'analyticke'
        );
    }
    return $out;
}

// ============================================================= CSS + JS lišty

add_action('wp_enqueue_scripts', function () {
    if (!bezi()) {
        return;
    }
    $dir = dirname(__DIR__) . '/assets/';
    $url = plugin_dir_url(__DIR__) . 'assets/';
    $ver = function (string $f) use ($dir): string {
        $t = @filemtime($dir . $f); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        return MARMAL_BDP_VERSION . ($t ? '.' . $t : '');
    };
    wp_enqueue_style('marmal-cookies', $url . 'cookies.css', array(), $ver('cookies.css'));
    wp_add_inline_style('marmal-cookies', css_promenne() . css_mobil());
    wp_enqueue_script('marmal-cookies', $url . 'cookies.js', array(), $ver('cookies.js'), array('in_footer' => true, 'strategy' => 'defer'));
    wp_add_inline_script('marmal-cookies', 'window.marmalCookies = ' . wp_json_encode(konfigurace(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

/** CSS proměnné vzhledu (výchozí barvy z Global Settings Breakdance, nebo vlastní). */
function css_promenne(): string
{
    $s = nastaveni();
    if ($s['barvy'] === 'vlastni') {
        // Znovu přes hex() – i kdyby v databázi bylo něco jiného, do <style> jde jen barva.
        $brand = hex((string) $s['brand'], vychozi()['brand']);
        $bg    = hex((string) $s['bg'], vychozi()['bg']);
        $ink   = hex((string) $s['ink'], vychozi()['ink']);
        $na    = na_brand($brand);
    } else {
        $brand = 'var(--bde-brand-primary-color, ' . vychozi()['brand'] . ')';
        $bg    = '#ffffff';
        $ink   = 'var(--bde-body-text-color, ' . vychozi()['ink'] . ')';
        $na    = ''; // dopočítá JS podle skutečné barvy
    }
    $stin = array('none' => '0 0 0 1px rgba(0,0,0,.12)', 'soft' => '0 10px 30px -10px rgba(0,0,0,.35)', 'strong' => '0 18px 50px -12px rgba(0,0,0,.6)');
    $v    = array(
        '--mck-brand' => $brand,
        '--mck-bg'    => $bg,
        '--mck-ink'   => $ink,
        '--mck-r'     => max(0, min(24, (int) $s['radius'])) . 'px',
        '--mck-w'     => max(320, min(480, (int) $s['width'])) . 'px',
        '--mck-sh'    => $stin[(string) $s['shadow']] ?? $stin['soft'],
    );
    if ($s['btn'] === 'line') {
        $v['--mck-btnbg'] = 'transparent';
        $v['--mck-btnfg'] = 'var(--mck-ink)';
    } elseif ($na !== '') {
        $v['--mck-btnfg'] = $na;
    }
    $css = ':root{';
    foreach ($v as $k => $h) {
        // Hodnoty jsou z pevných seznamů, čísla, nebo ověřené hex barvy (sanitize_hex_color).
        $css .= $k . ':' . $h . ';';
    }
    return $css . '}';
}

/**
 * CSS rozložení na mobilu (do bodu zlomu z nastavení, výchozí 767 px). Media query neumí CSS proměnné,
 * proto se generuje tady; hodnoty jsou jen celé číslo a pevné řetězce.
 *
 * Kompaktní lišta: plná šířka, přilepená dole, text 14 px (nejvýš 3 řádky; křížek ubírá místo jen prvnímu řádku),
 * Přijmout / Odmítnout vedle sebe (2 × 50 %, výška 44 px, nezalomí se ani na 320 px), Nastavení jako odkaz v textu, okno nastavení přes celou
 * obrazovku. position: fixed bez posunu obsahu (žádný CLS), vjezd jen transformací 200 ms.
 */
function css_mobil(): string
{
    $s = nastaveni();
    if ($s['mobil'] === 'stejne') {
        return '';
    }
    $bod = max(320, min(1200, (int) $s['mBod']));
    $css = '@media (max-width:' . $bod . 'px){';
    if ($s['mobil'] === 'lista') {
        $css .= '.marmal-ck.marmal-ck--m-lista{left:0;right:0;bottom:0;top:auto;width:auto;max-height:none;overflow:visible;transform:none;'
            . 'grid-template-columns:minmax(0,1fr);gap:10px;padding:12px 16px calc(12px + env(safe-area-inset-bottom,0px));'
            . 'border-radius:var(--mck-r,14px) var(--mck-r,14px) 0 0;font-size:14px;line-height:1.45;animation:marmal-ck-up .2s ease-out}'
            . '.marmal-ck--m-lista .marmal-ck__title,.marmal-ck--m-lista .marmal-ck__text,.marmal-ck--m-lista .marmal-ck__btns .marmal-ck__link{display:none}'
            . '.marmal-ck--m-lista .marmal-ck__mtext{display:block;margin:0}'
            . '.marmal-ck--m-lista.marmal-ck--x .marmal-ck__mtext::before{content:"";float:right;width:30px;height:1em}'
            . '.marmal-ck--m-lista .marmal-ck__btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;grid-column:1;grid-row:auto}'
            . '.marmal-ck--m-lista .marmal-ck__btns .marmal-ck__btn{min-width:0;min-height:44px;padding:0 4px;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
            . '.marmal-ck--m-lista .marmal-ck__x{top:0;right:0;width:44px;height:44px}';
    } else {
        $css .= '.marmal-ck.marmal-ck--m-okno{left:50%;right:auto;top:50%;bottom:auto;transform:translate(-50%,-50%);width:calc(100vw - 32px);'
            . 'max-height:calc(100vh - 32px);overflow:auto;grid-template-columns:minmax(0,1fr);border-radius:var(--mck-r,14px);animation:none}'
            . '.marmal-ck--m-okno .marmal-ck__btns{grid-column:1;grid-row:auto}'
            . '.marmal-ck--m-okno .marmal-ck__btns .marmal-ck__btn{flex:1 1 100%;min-height:44px}'
            . '.marmal-ck--m-okno .marmal-ck__x{top:2px;right:2px;width:44px;height:44px}';
    }
    // Okno „Nastavení cookies“ na telefonu přes celou obrazovku.
    $css .= '.marmal-ck-pf{padding:0}.marmal-ck-pf .marmal-ck-pf__box{width:100%;height:100%;max-height:none;border-radius:0}';
    $css .= '}';
    return $css . '@media (prefers-reduced-motion:reduce){.marmal-ck.marmal-ck--m-lista{animation:none}}';
}

/**
 * HTML lišty – vypisuje se přímo do stránky (wp_footer), ne až JavaScriptem: pozdě vložený blok textu
 * by na mobilu mohl být největším prvkem obrazovky a zhoršit LCP. Pro všechny návštěvníky stejné
 * (page cache) – kdo už volbu má, tomu ji skryje třída marmal-ck-ma z hlavičky; cookies.js obsluhuje tlačítka.
 * Všechny texty přes esc_html, adresa přes esc_url.
 */
function lista_html(): string
{
    $s     = nastaveni();
    $tridy = 'marmal-ck marmal-ck--' . ($s['layout'] === 'box' ? 'box marmal-ck--' . $s['corner'] : $s['layout']) . ' marmal-ck--m-' . $s['mobil'];
    if ($s['close']) {
        $tridy .= ' marmal-ck--x';
    }
    $odkaz = odkaz_vice();
    $vice  = $odkaz !== '' ? '<a class="marmal-ck__more" href="' . esc_url($odkaz) . '">' . esc_html(text('lMore')) . '</a>' : '';

    $h  = '<div class="' . esc_attr($tridy) . '" role="region" aria-label="' . esc_attr__('Souhlas s cookies', 'marmal-breakdance-plus') . '">';
    if ($s['close']) {
        $h .= '<button type="button" class="marmal-ck__x" data-ck="odlozit" aria-label="' . esc_attr__('Zavřít bez souhlasu', 'marmal-breakdance-plus') . '">&times;</button>';
    }
    $h .= '<div class="marmal-ck__title">' . esc_html(text('title')) . '</div>';
    $h .= '<p class="marmal-ck__text">' . esc_html(text('text')) . ($vice !== '' ? ' ' . $vice : '') . '</p>';
    if ($s['mobil'] === 'lista') {
        // Krátký text kompaktní lišty (viditelný jen do bodu zlomu) s odkazy Více o cookies · Nastavení.
        $h .= '<p class="marmal-ck__mtext">' . esc_html(text('mText')) . ' ' . $vice . ($vice !== '' ? ' <span aria-hidden="true">·</span> ' : '')
            . '<button type="button" class="marmal-ck__link marmal-ck__link--in" data-ck="nastaveni">' . esc_html(text('lPrefs')) . '</button></p>';
    }
    $h .= '<div class="marmal-ck__btns">'
        . '<button type="button" class="marmal-ck__btn" data-ck="vse">' . esc_html(text('lAccept')) . '</button>'
        . '<button type="button" class="marmal-ck__btn" data-ck="nic">' . esc_html(text('lReject')) . '</button>'
        . '<button type="button" class="marmal-ck__link" data-ck="nastaveni">' . esc_html(text('lPrefs')) . '</button>'
        . '</div></div>';
    return $h;
}

add_action('wp_footer', function () {
    if (!bezi()) {
        return;
    }
    echo lista_html() . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- escapováno v lista_html()
}, 5);

/** Konfigurace pro cookies.js (texty jsou čistý text – JS je vkládá přes textContent). */
function konfigurace(): array
{
    $s = nastaveni();
    $t = array();
    foreach (array_keys(texty_vychozi()) as $k) {
        $t[$k] = text($k);
    }
    $t['pTitle']   = __('Nastavení cookies', 'marmal-breakdance-plus');
    $t['zavrit']   = __('Zavřít', 'marmal-breakdance-plus');
    $t['zavritX']  = __('Zavřít bez souhlasu', 'marmal-breakdance-plus');
    $t['ulozit']   = __('Uložit výběr', 'marmal-breakdance-plus');
    $t['seznam']   = __('Seznam cookies', 'marmal-breakdance-plus');
    $t['vzdy']     = __('vždy zapnuté', 'marmal-breakdance-plus');
    $t['sloupce']  = array(__('Název', 'marmal-breakdance-plus'), __('Poskytovatel', 'marmal-breakdance-plus'), __('Účel', 'marmal-breakdance-plus'), __('Platnost', 'marmal-breakdance-plus'));
    $t['ikona']    = __('Nastavení cookies', 'marmal-breakdance-plus');
    return array(
        'cookie'    => COOKIE,
        'rev'       => (int) $s['rev'],
        'platnost'  => platnost_souhlasu_dny(),
        'nahled'    => nahled(),
        'rest'      => esc_url_raw(rest_url('marmal/v1/souhlas')),
        'odkaz'     => esc_url_raw(odkaz_vice()),
        'vzhled'    => array('layout' => $s['layout'], 'corner' => $s['corner'], 'prefs' => $s['prefs'], 'icon' => $s['icon'], 'close' => (bool) $s['close'], 'autoInk' => $s['barvy'] !== 'vlastni' && $s['btn'] === 'fill', 'mobil' => $s['mobil']),
        'texty'     => $t,
        'kategorie' => kategorie(),
    );
}

// ============================================================= blokování skriptů a iframů

/** Přídavné jméno kategorie pro text náhledu („…po souhlasu s marketingovými cookies“). */
function kat_7p(string $kat): string
{
    $m = array(
        'preferencni'  => __('preferenčními', 'marmal-breakdance-plus'),
        'analyticke'   => __('analytickými', 'marmal-breakdance-plus'),
        'marketingove' => __('marketingovými', 'marmal-breakdance-plus'),
    );
    return $m[$kat] ?? $kat;
}

/** Hodnota atributu z řetězce atributů tagu. */
function atribut(string $attrs, string $nazev): ?string
{
    if (preg_match('/(?:^|\s)' . preg_quote($nazev, '/') . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $m)) {
        return html_entity_decode($m[1] !== '' ? $m[1] : ($m[2] ?? '') . ($m[3] ?? ''), ENT_QUOTES);
    }
    return null;
}

function bez_atributu(string $attrs, string $nazev): string
{
    $r = trim((string) preg_replace('/\s' . preg_quote($nazev, '/') . '\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', ' ' . $attrs));
    return $r === '' ? '' : ' ' . $r;
}

/**
 * Zablokuje v HTML stránky známé skripty a vložené rámce do souhlasu.
 * Vrací [upravené HTML, id nalezených služeb].
 *
 * @return array{0:string,1:string[]}
 */
function blokovat_html(string $html): array
{
    $sluzby = sluzby();
    $nalez  = array();

    $html = (string) preg_replace_callback('#<script\b([^>]*)>(.*?)</script>#is', function ($m) use ($sluzby, &$nalez) {
        $attrs = $m[1];
        if (stripos($attrs, 'data-marmal-') !== false) {
            return $m[0];
        }
        $typ = atribut($attrs, 'type');
        if ($typ !== null && !in_array(strtolower(trim($typ)), array('', 'text/javascript', 'application/javascript', 'module'), true)) {
            return $m[0]; // JSON-LD, šablony apod.
        }
        $src = atribut($attrs, 'src');
        $kat = null;
        foreach ($sluzby as $id => $sl) {
            foreach ($src !== null ? ($sl['skript'] ?? array()) : array() as $vzor) {
                if (stripos($src, $vzor) !== false) {
                    $kat = $sl['kat'];
                    $nalez[$id] = true;
                    break 2;
                }
            }
            foreach ($src === null ? ($sl['inline'] ?? array()) : array() as $re) {
                if (preg_match($re, $m[2])) {
                    $kat = $sl['kat'];
                    $nalez[$id] = true;
                    break 2;
                }
            }
        }
        if (!$kat || $kat === 'nezbytne') {
            return $m[0];
        }
        $nove = bez_atributu($attrs, 'type');
        if ($src !== null) {
            $nove = bez_atributu($nove, 'src') . ' data-marmal-src="' . esc_attr($src) . '"';
        }
        if ($typ === 'module') {
            $nove .= ' data-marmal-type="module"';
        }
        return '<script type="text/plain" data-marmal-kat="' . esc_attr($kat) . '"' . $nove . '>' . $m[2] . '</script>';
    }, $html);

    // Už vytvořené náhledy (původní iframe v <template>) se znovu nezpracují.
    $schovane = array();
    $html     = (string) preg_replace_callback('#<template class="marmal-ck-ph__orig">.*?</template>#is', function ($m) use (&$schovane) {
        $schovane[] = $m[0];
        return '<!--marmal-ck-' . (count($schovane) - 1) . '-->';
    }, $html);

    $html = (string) preg_replace_callback('#<iframe\b([^>]*)>(.*?)</iframe>#is', function ($m) use ($sluzby, &$nalez) {
        $attrs = $m[1];
        if (stripos($attrs, 'data-marmal-') !== false) {
            return $m[0];
        }
        $src = (string) (atribut($attrs, 'src') ?? '');
        if ($src === '' || stripos($src, 'about:blank') === 0) {
            $src = (string) (atribut($attrs, 'data-src') ?? atribut($attrs, 'data-lazy-src') ?? '');
        }
        foreach ($sluzby as $id => $sl) {
            foreach ($sl['iframe'] ?? array() as $vzor) {
                if ($src !== '' && stripos($src, $vzor) !== false) {
                    $nalez[$id] = true;
                    return nahled_html($m[0], $sl, $id, $attrs);
                }
            }
        }
        return $m[0];
    }, $html);

    $html = (string) preg_replace_callback('#<!--marmal-ck-(\d+)-->#', function ($m) use ($schovane) {
        return $schovane[(int) $m[1]] ?? '';
    }, $html);

    return array($html, array_keys($nalez));
}

/** Náhled místo vloženého rámce. Původní iframe je v <template> (prohlížeč ho nenačte). */
function nahled_html(string $iframe, array $sl, string $id, string $attrs): string
{
    $w     = (int) atribut($attrs, 'width');
    $h     = (int) atribut($attrs, 'height');
    $pomer = ($w > 50 && $h > 50) ? $w . ' / ' . $h : ($id === 'gmaps' ? '4 / 3' : '16 / 9');
    $kat   = $sl['kat'];
    /* translators: 1: např. Video z YouTube, 2: např. marketingovými */
    $text = sprintf(__('%1$s se načte až po souhlasu s %2$s cookies.', 'marmal-breakdance-plus'), $sl['vlozeny'] ?? $sl['nazev'], kat_7p($kat));
    return '<div class="marmal-ck-ph" data-marmal-kat="' . esc_attr($kat) . '" data-sluzba="' . esc_attr($id) . '" style="aspect-ratio:' . esc_attr($pomer) . '">'
        . '<template class="marmal-ck-ph__orig">' . $iframe . '</template>'
        . '<div class="marmal-ck-ph__in"><p class="marmal-ck-ph__text">' . esc_html($text) . '</p>'
        . '<button type="button" class="marmal-ck-ph__btn" data-marmal-povolit="' . esc_attr($kat) . '">' . esc_html__('Povolit a zobrazit', 'marmal-breakdance-plus') . '</button>'
        . '<button type="button" class="marmal-ck-ph__link" data-marmal-cookies="marmal">' . esc_html__('Nastavení cookies', 'marmal-breakdance-plus') . '</button></div></div>';
}

add_action('template_redirect', function () {
    if (!bezi() || is_feed() || is_robots() || is_trackback()) {
        return;
    }
    ob_start(function ($html) {
        if (!is_string($html) || stripos($html, '<html') === false) {
            return $html;
        }
        list($html, $nalez) = blokovat_html($html);
        zapsat_nalezene($nalez);
        zapsat_google_fonty($html);
        zapsat_odkaz_nastaveni($html);
        return $html;
    });
}, 0);

/** Nově nalezené služby si zapamatuje (zápis jen při změně – ne při každém zobrazení stránky). */
function zapsat_nalezene(array $ids): void
{
    if (!$ids) {
        return;
    }
    $ulozene = (array) get_option(OPTION_NALEZ, array());
    $nove    = array_diff($ids, array_keys($ulozene));
    if (!$nove) {
        return;
    }
    foreach ($nove as $id) {
        $ulozene[$id] = time();
    }
    update_option(OPTION_NALEZ, $ulozene, false);
}

// ============================================================= shortcody

add_action('init', function () {
    add_shortcode('marmal_cookies_seznam', __NAMESPACE__ . '\sc_seznam');
    add_shortcode('marmal_cookies_nastaveni', __NAMESPACE__ . '\sc_nastaveni');
    add_shortcode('marmal_cookies_poskytovatele', __NAMESPACE__ . '\sc_poskytovatele');
    add_shortcode('marmal_cookies_souhlas', __NAMESPACE__ . '\sc_souhlas');

    if (function_exists('pll_register_string')) {
        $s = nastaveni();
        foreach (prekladane_klice() as $k) {
            if (!empty($s[$k])) {
                pll_register_string('marmal_cookies_' . $k, $s[$k], 'Marmal – Cookies', strlen($s[$k]) > 80);
            }
        }
    }
});

/** [marmal_cookies_seznam nadpis="h3"] – tabulka cookies podle kategorií pro stránku zásad. */
function sc_seznam($atts = array()): string
{
    $a  = shortcode_atts(array('nadpis' => 'h3', 'tlacitko' => 'ano'), (array) $atts, 'marmal_cookies_seznam');
    $hn = in_array($a['nadpis'], array('h2', 'h3', 'h4'), true) ? $a['nadpis'] : 'h3';
    $hl = array(__('Název', 'marmal-breakdance-plus'), __('Poskytovatel', 'marmal-breakdance-plus'), __('Účel', 'marmal-breakdance-plus'), __('Platnost', 'marmal-breakdance-plus'));
    $o  = '<div class="marmal-ck-seznam">';
    foreach (kategorie() as $k) {
        $o .= '<' . $hn . ' class="marmal-ck-seznam__kat">' . esc_html($k['nazev']) . '</' . $hn . '>';
        $o .= '<p class="marmal-ck-seznam__popis">' . esc_html($k['popis']) . '</p>';
        $o .= '<div class="marmal-ck-seznam__tab"><table><thead><tr>';
        foreach ($hl as $h) {
            $o .= '<th scope="col">' . esc_html($h) . '</th>';
        }
        $o .= '</tr></thead><tbody>';
        foreach ($k['cookies'] as $c) {
            $o .= '<tr><td>' . esc_html($c[0]) . '</td><td>' . esc_html($c[1]) . '</td><td>' . esc_html($c[2]) . '</td><td>' . esc_html($c[3]) . '</td></tr>';
        }
        $o .= '</tbody></table></div>';
    }
    if ($a['tlacitko'] !== 'ne') {
        $o .= '<p><button type="button" class="marmal-ck-seznam__btn marmal-cookies-nastaveni" data-marmal-cookies="marmal">' . esc_html__('Změnit nastavení cookies', 'marmal-breakdance-plus') . '</button></p>';
    }
    return $o . '</div>';
}

/** [marmal_cookies_poskytovatele] – třetí strany, jejichž služby web používá, s odkazy na jejich zásady (aktualizuje se samo). */
function sc_poskytovatele($atts = array()): string
{
    $p = aktivni_poskytovatele();
    if (!$p) {
        return '<p class="marmal-ck-posk">' . esc_html__('Web nepoužívá služby třetích stran, které by ukládaly cookies.', 'marmal-breakdance-plus') . '</p>';
    }
    $o = '<ul class="marmal-ck-posk">';
    foreach ($p as $x) {
        $o .= '<li><strong>' . esc_html($x[0]) . '</strong> – ' . esc_html(implode(', ', array_unique($x[3])))
            . ' (<a href="' . esc_url($x[1], array('https')) . '" target="_blank" rel="noopener">' . esc_html__('zásady ochrany soukromí', 'marmal-breakdance-plus') . '</a>)</li>';
    }
    $o .= '</ul>';
    if (array_filter(array_column($p, 2))) {
        $o .= '<p class="marmal-ck-posk__eu">' . esc_html__('Tito poskytovatelé mohou údaje zpracovávat i mimo Evropskou unii, zejména v USA. Jak údaje chrání a jaké záruky pro předávání používají, popisují ve svých zásadách ochrany soukromí.', 'marmal-breakdance-plus') . '</p>';
    }
    return $o;
}

/** [marmal_cookies_souhlas] – jak dlouho platí volba a jak dlouho se uchovává záznam o souhlasu (čísla z nastavení). */
function sc_souhlas($atts = array()): string
{
    $roky = (int) nastaveni()['uchovat'];
    /* translators: %d: počet let */
    $uchovat = sprintf(_n('%d rok', '%d roky', $roky, 'marmal-breakdance-plus'), $roky);
    if ($roky >= 5) {
        /* translators: %d: počet let (5 a více) */
        $uchovat = sprintf(__('%d let', 'marmal-breakdance-plus'), $roky);
    }
    return '<p class="marmal-ck-souhlas">' . esc_html(sprintf(
        /* translators: 1: platnost volby (např. 6 měsíců), 2: doba uchování záznamu (např. 3 roky) */
        __('Vaši volbu cookies si pamatujeme %1$s, potom se vás zeptáme znovu. Záznam o udělení nebo odmítnutí souhlasu (náhodný identifikátor, zvolené kategorie, datum a čas a verze textů – bez IP adresy) uchováváme %2$s, abychom mohli doložit, že jste souhlas udělili (čl. 7 odst. 1 GDPR).', 'marmal-breakdance-plus'),
        platnost_text('platnost_souhlasu'),
        $uchovat
    )) . '</p>';
}

/** [marmal_cookies_nastaveni text="…"] – tlačítko, které otevře okno nastavení cookies. */
function sc_nastaveni($atts = array()): string
{
    $a = shortcode_atts(array('text' => __('Nastavení cookies', 'marmal-breakdance-plus')), (array) $atts, 'marmal_cookies_nastaveni');
    return '<button type="button" class="marmal-cookies-nastaveni marmal-ck-odkaz" data-marmal-cookies="marmal">' . esc_html($a['text']) . '</button>';
}
