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
    $cfg = wp_json_encode(array('cookie' => COOKIE, 'rev' => (int) nastaveni()['rev']), JSON_HEX_TAG | JSON_HEX_AMP);
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
  var vychozi = mapa([]); vychozi.security_storage = 'granted'; vychozi.wait_for_update = 500;
  w.gtag('consent', 'default', vychozi);
  w.gtag('set', 'ads_data_redaction', true);
  var m = d.cookie.match(new RegExp('(?:^|; )' + c.cookie + '=([^;]*)')), s = null;
  if (m) { try { s = JSON.parse(decodeURIComponent(m[1])); } catch (e) { s = null; } }
  if (s && s.r === c.rev && Array.isArray(s.k)) { w.gtag('consent', 'update', mapa(s.k)); w.marmalCookiesUlozeny = s; }
})(window, document, <?php echo $cfg; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode s JSON_HEX_TAG ?>);
</script>
    <?php
    $ga = ga_id();
    if ($ga === '') {
        return;
    }
    $cfg_ga = array();
    if (ga_expirace()) {
        $cfg_ga['cookie_expires'] = ga_expirace();
    }
    $config = 'gtag("js", new Date()); gtag("config", ' . wp_json_encode($ga) . ($cfg_ga ? ', ' . wp_json_encode($cfg_ga) : '') . ');';
    $src    = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ga);
    if (nastaveni()['gcm'] === 'advanced') {
        // Rozšířený Consent Mode: tag se načte vždy, bez souhlasu posílá jen pingy bez cookies.
        printf("<script async src=\"%s\" data-marmal-ignore></script>\n<script data-marmal-ignore>%s</script>\n", esc_url($src), $config); // phpcs:ignore WordPress.Security.EscapeOutput -- $config z wp_json_encode
    } else {
        // Základní: tag čeká na souhlas s analytickými cookies.
        printf("<script type=\"text/plain\" data-marmal-kat=\"analyticke\" data-marmal-src=\"%s\" async></script>\n<script type=\"text/plain\" data-marmal-kat=\"analyticke\">%s</script>\n", esc_url($src), $config); // phpcs:ignore WordPress.Security.EscapeOutput
    }
}, 1);

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
    wp_add_inline_style('marmal-cookies', css_promenne());
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
        'vzhled'    => array('layout' => $s['layout'], 'corner' => $s['corner'], 'prefs' => $s['prefs'], 'icon' => $s['icon'], 'close' => (bool) $s['close'], 'autoInk' => $s['barvy'] !== 'vlastni' && $s['btn'] === 'fill'),
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
    $a  = shortcode_atts(array('nadpis' => 'h3'), (array) $atts, 'marmal_cookies_seznam');
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
    $o .= '<p><button type="button" class="marmal-ck-seznam__btn marmal-cookies-nastaveni" data-marmal-cookies="marmal">' . esc_html__('Změnit nastavení cookies', 'marmal-breakdance-plus') . '</button></p>';
    return $o . '</div>';
}

/** [marmal_cookies_nastaveni text="…"] – tlačítko, které otevře okno nastavení cookies. */
function sc_nastaveni($atts = array()): string
{
    $a = shortcode_atts(array('text' => __('Nastavení cookies', 'marmal-breakdance-plus')), (array) $atts, 'marmal_cookies_nastaveni');
    return '<button type="button" class="marmal-cookies-nastaveni marmal-ck-odkaz" data-marmal-cookies="marmal">' . esc_html($a['text']) . '</button>';
}
