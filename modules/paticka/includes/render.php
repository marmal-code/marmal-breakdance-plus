<?php
/**
 * Modul Patička – HTML výstup a shortcody.
 *
 *  [marmal_paticka]   celá spodní lišta: copyright · údaje o podnikateli · odkazy · autor
 *  [marmal_copyright] © 2019–2026 Název          (drzitel="ne" = jen roky)
 *  [marmal_autor]     Web vytvořil Martin Malý – marmal.cz (odkaz)
 *  [marmal_firma]     Firma · Sídlo: … · IČO: … · DIČ: … · zápis   (pod_sebou="ano")
 *  [marmal_odkazy]    Ochrana osobních údajů · Nastavení cookies   (zasady="ne", cookies="ne")
 *
 * Všechny texty se escapují tady (esc_html / esc_url / esc_attr). CSS a JS se načtou
 * jen na stránce, kde je shortcode použitý.
 */

namespace MarmalElements\Paticka;

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    $url = plugin_dir_url(__DIR__) . 'assets/';
    $dir = dirname(__DIR__) . '/assets/';
    $ver = function (string $soubor) use ($dir): string {
        $cas = @filemtime($dir . $soubor); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        return MARMAL_BDP_VERSION . ($cas ? '.' . $cas : '');
    };
    wp_register_style('marmal-paticka', $url . 'paticka.css', array(), $ver('paticka.css'));
    wp_register_script('marmal-paticka', $url . 'paticka.js', array(), $ver('paticka.js'), array('in_footer' => true, 'strategy' => 'defer'));

    add_shortcode('marmal_paticka', __NAMESPACE__ . '\sc_paticka');
    add_shortcode('marmal_copyright', __NAMESPACE__ . '\sc_copyright');
    add_shortcode('marmal_autor', __NAMESPACE__ . '\sc_autor');
    add_shortcode('marmal_firma', __NAMESPACE__ . '\sc_firma');
    add_shortcode('marmal_odkazy', __NAMESPACE__ . '\sc_odkazy');
});

/** Načte CSS a JS modulu (jen jednou, jen na stránce se shortcodem). */
function assets(): void
{
    wp_enqueue_style('marmal-paticka');
    wp_enqueue_script('marmal-paticka');
}

/** „ano“ / „ne“ / true / 1 z atributu shortcodu. */
function ano($hodnota): bool
{
    return in_array(strtolower(trim((string) $hodnota)), array('1', 'ano', 'yes', 'true', 'on'), true);
}

/**
 * Upozornění jen pro správce (návštěvník nic nevidí), když není co zobrazit.
 */
function hlaska(string $text): string
{
    if (!current_user_can('manage_options')) {
        return '';
    }
    return '<span class="marmal-paticka__hlaska">' . esc_html($text) . '</span>';
}

// ============================================================= části

/**
 * © 2019–2026 Název. Roky jsou ve <span> s rokem spuštění – skript je po Novém roce
 * opraví, i když stránka přijde ze staré page cache.
 */
function html_copyright(bool $sDrzitelem = true): string
{
    $html = '© <span class="marmal-paticka__roky" data-marmal-rok-od="' . esc_attr((string) rok_od()) . '">' . esc_html(roky()) . '</span>';
    if ($sDrzitelem && drzitel() !== '') {
        $html .= ' ' . esc_html(drzitel());
    }
    return '<span class="marmal-paticka__copyright">' . $html . '</span>';
}

function html_autor(): string
{
    if (!autor_zobrazit()) {
        return '';
    }
    $url   = autor_url();
    $pred  = text('autor_pred');
    $odkaz = text('autor_odkaz');
    $html  = $pred !== '' ? esc_html($pred) . ' ' : '';
    if ($url !== '' && $odkaz !== '') {
        $html .= '<a class="marmal-paticka__odkaz" href="' . esc_url($url, array('http', 'https')) . '" target="_blank" rel="' . esc_attr(autor_rel()) . '">' . esc_html($odkaz) . '</a>';
    } else {
        $html .= esc_html($odkaz);
    }
    return '<span class="marmal-paticka__autor">' . trim($html) . '</span>';
}

function html_firma(bool $podSebou = false): string
{
    $udaje = udaje();
    if (!$udaje) {
        return hlaska(__('Údaje o podnikateli nejsou vyplněné (MarMal → Patička).', 'marmal-breakdance-plus'));
    }
    $casti = array();
    foreach ($udaje as $klic => $text) {
        // IČO a DIČ se nezalomí uprostřed („IČO: | 12345678“).
        $nowrap  = in_array($klic, array('ico', 'dic'), true) ? ' marmal-paticka__udaj--nowrap' : '';
        $casti[] = '<span class="marmal-paticka__udaj marmal-paticka__udaj--' . esc_attr($klic) . $nowrap . '">' . esc_html($text) . '</span>';
    }
    $mod = $podSebou ? ' marmal-paticka__firma--pod' : '';
    return '<span class="marmal-paticka__firma' . $mod . '">' . implode('<span class="marmal-paticka__tecka" aria-hidden="true">&nbsp;· </span>', $casti) . '</span>';
}

function html_odkazy(bool $zasady = true, bool $cookies = true): string
{
    $casti = array();
    if ($zasady) {
        $url = zasady_url();
        if ($url !== '') {
            $casti[] = '<a class="marmal-paticka__odkaz marmal-paticka__zasady" href="' . esc_url($url) . '">' . esc_html(text('zasady_text')) . '</a>';
        }
    }
    if ($cookies) {
        $rezim = cookies_rezim();
        if ($rezim !== 'ne') {
            $trida   = cookies_trida($rezim);
            $casti[] = '<button type="button" class="marmal-paticka__odkaz marmal-paticka__cookies' . ($trida !== '' ? ' ' . esc_attr($trida) : '') . '" data-marmal-cookies="' . esc_attr($rezim) . '">' . esc_html(text('cookies_text')) . '</button>';
        }
    }
    if (!$casti) {
        return hlaska(__('Žádný odkaz: chybí stránka se zásadami a cookie plugin (MarMal → Patička).', 'marmal-breakdance-plus'));
    }
    return '<span class="marmal-paticka__odkazy">' . implode('<span class="marmal-paticka__tecka" aria-hidden="true">&nbsp;· </span>', $casti) . '</span>';
}

// ============================================================= shortcody

/**
 * [marmal_paticka copyright="ano" firma="ano" odkazy="ano" autor="ano" rozlozeni="radek|sloupec|stred"]
 * Řádek 1: copyright vlevo, odkazy a autor vpravo. Řádek 2: údaje o podnikateli.
 */
function sc_paticka($atts = array()): string
{
    $a = shortcode_atts(array(
        'copyright' => 'ano',
        'firma'     => 'ano',
        'odkazy'    => 'ano',
        'autor'     => 'ano',
        'rozlozeni' => 'radek',
    ), (array) $atts, 'marmal_paticka');
    assets();

    $levo = array();
    if (ano($a['copyright'])) {
        $levo[] = html_copyright();
    }
    $pravo = array();
    if (ano($a['odkazy'])) {
        $odkazy = html_odkazy();
        if (strpos($odkazy, 'marmal-paticka__hlaska') === false) {
            $pravo[] = $odkazy;
        }
    }
    if (ano($a['autor'])) {
        $pravo[] = html_autor();
    }
    $pravo = array_filter($pravo);

    $rozlozeni = in_array($a['rozlozeni'], array('radek', 'sloupec', 'stred'), true) ? $a['rozlozeni'] : 'radek';
    $html      = '<div class="marmal-paticka marmal-paticka--' . esc_attr($rozlozeni) . '">';
    if ($levo) {
        $html .= '<div class="marmal-paticka__cast marmal-paticka__cast--levo">' . implode('', $levo) . '</div>';
    }
    if ($pravo) {
        $html .= '<div class="marmal-paticka__cast marmal-paticka__cast--pravo">' . implode('', $pravo) . '</div>';
    }
    // Údaje o podnikateli bývají dlouhé – vlastní řádek pod copyrightem a odkazy.
    if (ano($a['firma']) && udaje()) {
        $html .= '<div class="marmal-paticka__cast marmal-paticka__cast--udaje">' . html_firma() . '</div>';
    }
    return $html . '</div>';
}

/** [marmal_copyright drzitel="ne"] */
function sc_copyright($atts = array()): string
{
    $a = shortcode_atts(array('drzitel' => 'ano'), (array) $atts, 'marmal_copyright');
    assets();
    return html_copyright(ano($a['drzitel']));
}

/** [marmal_autor] */
function sc_autor($atts = array()): string
{
    assets();
    return html_autor();
}

/** [marmal_firma pod_sebou="ano"] */
function sc_firma($atts = array()): string
{
    $a = shortcode_atts(array('pod_sebou' => 'ne'), (array) $atts, 'marmal_firma');
    assets();
    return html_firma(ano($a['pod_sebou']));
}

/** [marmal_odkazy zasady="ne" cookies="ne"] */
function sc_odkazy($atts = array()): string
{
    $a = shortcode_atts(array('zasady' => 'ano', 'cookies' => 'ano'), (array) $atts, 'marmal_odkazy');
    assets();
    return html_odkazy(ano($a['zasady']), ano($a['cookies']));
}
