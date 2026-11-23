<?php
/**
 * Modul Přesměrování – na webu.
 *
 * template_redirect 0 (před output buffery Cookies / Fonty / Obrázky): pravidlo pro cestu → přesměrování
 * (wp_redirect s kódem 301 / 302 / 307), nebo 410 (stránka zrušena – stavový kód 410 a šablona 404).
 * Pravidlo platí i pro existující stránku (vědomé přepsání). Pak záznam chyby 404 (template_redirect 99).
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

/** Požadavek, na který se přesměrování vztahuje (ne administrace, builder, REST, AJAX, cron, náhled). */
function bezi(): bool
{
    // phpcs:disable WordPress.Security.NonceVerification -- jen čtení příznaků builderu a náhledu
    $builder = isset($_GET['breakdance']) || isset($_GET['breakdance_iframe']) || isset($_GET['preview']);
    // phpcs:enable
    return !is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$builder
        && !(defined('REST_REQUEST') && REST_REQUEST) && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        && !is_robots() && db_pripravena();
}

/** Cesta a parametry aktuálního požadavku. @return array{0: string, 1: string} */
function pozadavek(): array
{
    $uri   = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- normalizuje cesta()
    $dotaz = (string) wp_parse_url($uri, PHP_URL_QUERY);
    return array(cesta($uri), $dotaz);
}

/** Najde a provede přesměrování. Vrací true, když bylo použito (v testech místo exit). */
function presmerovat(bool $ukoncit = true): bool
{
    if (!bezi()) {
        return false;
    }
    list($cesta, $dotaz) = pozadavek();
    if ($cesta === '' || $cesta === '/') {
        return false;
    }
    $p = najit($cesta);
    if (!$p) {
        return false;
    }
    $kod = (int) $p['kod'];
    if ($kod === 410) {
        zasah((int) $p['id']);
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->set_404();
        }
        status_header(410);
        nocache_headers();
        $GLOBALS['marmal_presmerovani_410'] = true;
        return true;
    }
    $url = cil_pro($p, $cesta, $dotaz);
    if ($url === '' || cesta($url) === $cesta && je_interni((string) $p['cil'])) {
        return false; // smazaná cílová stránka nebo smyčka – radši nic
    }
    zasah((int) $p['id']);
    if (!$ukoncit) {
        $GLOBALS['marmal_presmerovani_test'] = array($url, $kod);
        return true;
    }
    // Cíl zadal správce (může vést i na jiný web), proto wp_redirect, ne wp_safe_redirect.
    wp_redirect($url, $kod, 'Marmal'); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
    exit;
}

add_action('template_redirect', function () {
    presmerovat();
}, 0);

/** 410 si drží stavový kód i přes šablonu 404 (WordPress by jinak poslal 404). */
add_filter('status_header', function ($hlavicka, $kod) {
    if (!empty($GLOBALS['marmal_presmerovani_410']) && (int) $kod === 404) {
        return str_replace('404', '410', (string) $hlavicka);
    }
    return $hlavicka;
}, 10, 2);

/** Záznam chyby 404 (bez IP adresy a prohlížeče). */
add_action('template_redirect', function () {
    if (!is_404() || !empty($GLOBALS['marmal_presmerovani_410']) || !nastaveni()['log404'] || !bezi()) {
        return;
    }
    list($cesta) = pozadavek();
    $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    $ua      = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
    zapsat_404($cesta, $referer, je_robot($ua));
}, 99);
