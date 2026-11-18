<?php
/**
 * Modul Fonty – výstup stránky. Odkazy na fonts.googleapis.com nahradí lokálním CSS,
 * odstraní preconnect / dns-prefetch na servery Google Fonts a přidá preload běžného řezu.
 *
 * Když stažení selže (hosting nepustí spojení ven…), odkaz na Google zůstane – web nepřijde o písmo.
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

const MAX_STAZENI = 3; // nových sad na jeden požadavek (první návštěva po změně fontů)

/** Běží modul na tomto požadavku? (ne administrace, REST, AJAX, builder Breakdance…) */
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

/**
 * Lokální adresa CSS pro odkaz z Google Fonts; když sada ještě není, stáhne ji (nejvýš MAX_STAZENI za požadavek).
 * Vrací [adresa CSS, údaje sady] nebo null = nechat odkaz na Google.
 */
function lokalni(string $google_url, int &$stazeno): ?array
{
    $klic = klic($google_url);
    $sady = sady();
    $s    = $sady[$klic] ?? null;
    list($dir, $url) = adresar();

    $ok = is_array($s) && ($s['stav'] ?? '') === 'ok' && !empty($s['css']) && file_exists($dir . $s['css']);
    if (!$ok) {
        $nedavna_chyba = is_array($s) && ($s['stav'] ?? '') !== 'ok' && time() - (int) ($s['cas'] ?? 0) < ZNOVU_PO;
        if ($nedavna_chyba || $stazeno >= MAX_STAZENI || get_transient('marmal_fonty_zamek_' . $klic)) {
            return null;
        }
        set_transient('marmal_fonty_zamek_' . $klic, 1, 60); // dva souběžné požadavky nestahují totéž
        $stazeno++;
        $s = stahnout_sadu($google_url);
        delete_transient('marmal_fonty_zamek_' . $klic);
        if (($s['stav'] ?? '') !== 'ok') {
            return null;
        }
    } elseif (time() - (int) ($s['pouzito'] ?? 0) > DAY_IN_SECONDS) {
        // Poslední použití se zapisuje nejvýš jednou denně (přehled „naposledy na webu“).
        $sady[$klic]['pouzito'] = time();
        ulozit_sady($sady);
    }
    return array($url . $s['css'] . '?ver=' . (int) $s['cas'], $s);
}

/**
 * Přepíše HTML stránky. Vrací [html, počet nahrazených odkazů, počet ponechaných na Google].
 */
function prepsat_html(string $html): array
{
    if (stripos($html, 'fonts.googleapis.com') === false && stripos($html, 'fonts.gstatic.com') === false) {
        return array($html, 0, 0);
    }
    $stazeno   = 0;
    $nahrazeno = 0;
    $ponechano = 0;
    $preload   = array();
    list(, $url_dir) = adresar();

    // 1) Odkazy na CSS z Google Fonts (link href, @import, přednačtení) – v jakékoli podobě zápisu.
    $html = (string) preg_replace_callback(
        '#(?:https?:)?//fonts\.googleapis\.com/css2?\?[^"\'\s<>)]+#i',
        function ($m) use (&$stazeno, &$nahrazeno, &$ponechano, &$preload, $url_dir) {
            $g = google_url($m[0]);
            if ($g === '') {
                return $m[0];
            }
            $l = lokalni($g, $stazeno);
            if ($l === null) {
                $ponechano++;
                return $m[0];
            }
            $nahrazeno++;
            foreach ((array) ($l[1]['preload'] ?? array()) as $soubor) {
                $preload[$soubor] = $url_dir . 'soubory/' . $soubor;
            }
            return esc_url($l[0]);
        },
        $html
    );

    // 2) preconnect / dns-prefetch na Google Fonts pryč (spojení by se navázalo i bez fontu).
    if ($ponechano === 0) {
        $html = (string) preg_replace('#<link\b[^>]*\brel=["\']?(?:preconnect|dns-prefetch)["\']?[^>]*fonts\.(?:googleapis|gstatic)\.com[^>]*>\s*#i', '', $html);
        $html = (string) preg_replace('#<link\b[^>]*fonts\.(?:googleapis|gstatic)\.com[^>]*\brel=["\']?(?:preconnect|dns-prefetch)["\']?[^>]*>\s*#i', '', $html);
    }

    // 3) Preload běžného řezu hned za <head> (nejvýš 2 soubory na stránku).
    if ($preload && !empty(nastaveni()['preload'])) {
        $tagy = '';
        foreach (array_slice($preload, 0, 2) as $u) {
            $tagy .= '<link rel="preload" href="' . esc_url($u) . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }
        $html = (string) preg_replace('#<head\b[^>]*>#i', '$0' . "\n" . str_replace('$', '\$', $tagy), $html, 1);
    }
    return array($html, $nahrazeno, $ponechano);
}

// Bufferu se spustí po modulu Cookies (priorita 1) → je vnořený a HTML upraví dřív, než ho Cookies zkontroluje.
add_action('template_redirect', function () {
    if (!bezi() || is_feed() || is_robots() || is_trackback()) {
        return;
    }
    ob_start(function ($html) {
        if (!is_string($html) || stripos($html, '<html') === false) {
            return $html;
        }
        return prepsat_html($html)[0];
    });
}, 1);
