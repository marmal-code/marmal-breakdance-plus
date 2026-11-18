<?php
/**
 * Modul Patička – HTML výstup a shortcody.
 *
 *  [marmal_paticka]   celá spodní lišta: copyright · odkazy · autor + údaje o podnikateli
 *  element „Marmal – Patička“ (elements/Paticka_Webu) volá element_html()
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
    $odkazHtml = '';
    if ($url !== '' && $odkaz !== '') {
        $odkazHtml = '<a class="marmal-paticka__odkaz" href="' . esc_url($url, array('http', 'https')) . '" target="_blank" rel="' . esc_attr(autor_rel()) . '">' . esc_html($odkaz) . '</a>';
    } elseif ($odkaz !== '') {
        $odkazHtml = esc_html($odkaz);
    }
    if ($pred === '' || $odkazHtml === '') {
        $html = $pred !== '' ? esc_html($pred) : $odkazHtml;
    } else {
        // Poslední slovo textu (např. „–“) a odkaz se nezalomí – „marmal.cz“ nezůstane sám na dalším řádku.
        $mezera = strrpos($pred, ' ');
        $zacatek = $mezera === false ? '' : substr($pred, 0, $mezera + 1);
        $posledni = $mezera === false ? $pred : substr($pred, $mezera + 1);
        $html = esc_html($zacatek) . '<span class="marmal-paticka__nowrap">' . esc_html($posledni) . '&nbsp;' . $odkazHtml . '</span>';
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
 * [marmal_paticka copyright="ano" drzitel="ano" firma="ano" pod_sebou="ne" odkazy="ano" zasady="ano"
 *                 cookies="ano" autor="ano" rozlozeni="radek|tri|sloupec|stred"
 *                 pozice_copyright|pozice_odkazy|pozice_autor|pozice_udaje="vlevo|stred|vpravo|pod"
 *                 zarovnani_pod="vlevo|stred|vpravo" skladat="mobil|tablet|maly|nikdy" mobil="vlevo|stred"]
 * radek: copyright vlevo, odkazy a podpis vpravo (výchozí). tri: copyright vlevo, odkazy uprostřed, podpis vpravo.
 * Údaje o podnikateli výchozí na vlastním řádku pod. pozice_* přepíše umístění jedné části.
 * Atribut css="ne" = nenačítat paticka.css (element Marmal – Patička má CSS v default.css).
 */
function sc_paticka($atts = array()): string
{
    $a = shortcode_atts(array(
        'copyright'        => 'ano',
        'drzitel'          => 'ano',
        'firma'            => 'ano',
        'pod_sebou'        => 'ne',
        'odkazy'           => 'ano',
        'zasady'           => 'ano',
        'cookies'          => 'ano',
        'autor'            => 'ano',
        'rozlozeni'        => 'radek',
        'pozice_copyright' => '',
        'pozice_odkazy'    => '',
        'pozice_autor'     => '',
        'pozice_udaje'     => '',
        'zarovnani_pod'    => '',
        'skladat'          => 'mobil',
        'mobil'            => 'vlevo',
        'css'              => 'ano',
    ), (array) $atts, 'marmal_paticka');
    if (ano($a['css'])) {
        assets();
    } else {
        wp_enqueue_script('marmal-paticka');
    }

    // Části v pevném pořadí (v jedné zóně jdou za sebou v tomto pořadí).
    $casti = array();
    if (ano($a['copyright'])) {
        $casti['copyright'] = html_copyright(ano($a['drzitel']));
    }
    if (ano($a['odkazy']) && (ano($a['zasady']) || ano($a['cookies']))) {
        $odkazy = html_odkazy(ano($a['zasady']), ano($a['cookies']));
        if (strpos($odkazy, 'marmal-paticka__hlaska') === false) {
            $casti['odkazy'] = $odkazy;
        }
    }
    if (ano($a['autor'])) {
        $casti['autor'] = html_autor();
    }
    if (ano($a['firma']) && udaje()) {
        $casti['udaje'] = html_firma(ano($a['pod_sebou']));
    }
    $casti = array_filter($casti);

    $rozlozeni = in_array($a['rozlozeni'], rozlozeni_volby(), true) ? $a['rozlozeni'] : 'radek';
    $zony      = rozmisteni($rozlozeni, $a);
    $obsah     = array('vlevo' => '', 'stred' => '', 'vpravo' => '', 'pod' => '');
    foreach ($casti as $klic => $html) {
        $obsah[$zony[$klic]] .= $html;
    }

    // Tři sloupce (mřížka) jen když je něco uprostřed nebo je zvolené rozložení „tri“ – jinak původní výstup.
    $sloupce = in_array($rozlozeni, array('radek', 'tri'), true) && ($rozlozeni === 'tri' || $obsah['stred'] !== '');

    $pod = in_array($a['zarovnani_pod'], array('vlevo', 'stred', 'vpravo'), true)
        ? $a['zarovnani_pod']
        : ($rozlozeni === 'tri' ? 'stred' : ($rozlozeni === 'stred' ? 'stred' : 'vlevo'));
    $skladat = in_array($a['skladat'], array('mobil', 'tablet', 'maly', 'nikdy'), true) ? $a['skladat'] : 'mobil';
    $mobil   = $a['mobil'] === 'stred' ? 'stred' : 'vlevo';

    $tridy = array('marmal-paticka', 'marmal-paticka--' . $rozlozeni, 'marmal-paticka--skladat-' . $skladat, 'marmal-paticka--pod-' . $pod);
    if ($sloupce) {
        $tridy[] = 'marmal-paticka--zony';
    }
    if ($mobil === 'stred') {
        $tridy[] = 'marmal-paticka--mobil-stred';
    }

    $html = '<div class="' . esc_attr(implode(' ', $tridy)) . '">';
    foreach (array('vlevo' => 'levo', 'stred' => 'stred', 'vpravo' => 'pravo') as $zona => $trida) {
        // V mřížce zůstávají i prázdné sloupce, aby střed zůstal uprostřed a pravá část vpravo.
        if ($obsah[$zona] !== '' || $sloupce) {
            $html .= '<div class="marmal-paticka__cast marmal-paticka__cast--' . $trida . '">' . $obsah[$zona] . '</div>';
        }
    }
    // Řádek pod (výchozí pro údaje o podnikateli – bývají dlouhé).
    if ($obsah['pod'] !== '') {
        $html .= '<div class="marmal-paticka__cast marmal-paticka__cast--pod marmal-paticka__cast--udaje">' . $obsah['pod'] . '</div>';
    }
    return $html . '</div>';
}

/** Povolená rozložení patičky. */
function rozlozeni_volby(): array
{
    return array('radek', 'tri', 'sloupec', 'stred');
}

/**
 * Umístění částí: výchozí podle rozložení, jednotlivé části jde přesunout atributy pozice_*.
 * Vrací [copyright|odkazy|autor|udaje => vlevo|stred|vpravo|pod].
 */
function rozmisteni(string $rozlozeni, array $a): array
{
    $zony = $rozlozeni === 'tri'
        ? array('copyright' => 'vlevo', 'odkazy' => 'stred', 'autor' => 'vpravo', 'udaje' => 'pod')
        : array('copyright' => 'vlevo', 'odkazy' => 'vpravo', 'autor' => 'vpravo', 'udaje' => 'pod');
    foreach (array_keys($zony) as $klic) {
        $v = isset($a['pozice_' . $klic]) ? (string) $a['pozice_' . $klic] : '';
        if (in_array($v, array('vlevo', 'stred', 'vpravo', 'pod'), true)) {
            $zony[$klic] = $v;
        }
    }
    return $zony;
}

/**
 * Výstup Breakdance elementu „Marmal – Patička“ (elements/Paticka_Webu/ssr.php).
 * $c = hodnoty z Content → Patička a Content → Rozvržení; přepínače „Skrýt …“ se převedou na atributy shortcodu.
 */
function element_html(array $c): string
{
    $skryt = function (string $klic) use ($c): string {
        return empty($c[$klic]) ? 'ano' : 'ne';
    };
    $volba = function (string $klic, string $vychozi = '') use ($c): string {
        return isset($c[$klic]) && is_string($c[$klic]) ? sanitize_key($c[$klic]) : $vychozi;
    };
    $rozlozeni = $volba('rozlozeni', 'radek');
    return sc_paticka(array(
        'copyright'        => $skryt('skryt_copyright'),
        'drzitel'          => $skryt('skryt_drzitele'),
        'firma'            => $skryt('skryt_udaje'),
        'pod_sebou'        => empty($c['udaje_pod_sebou']) ? 'ne' : 'ano',
        'zasady'           => $skryt('skryt_zasady'),
        'cookies'          => $skryt('skryt_cookies'),
        'autor'            => $skryt('skryt_autora'),
        'rozlozeni'        => $rozlozeni !== '' ? $rozlozeni : 'radek',
        'pozice_copyright' => $volba('pozice_copyright'),
        'pozice_odkazy'    => $volba('pozice_odkazy'),
        'pozice_autor'     => $volba('pozice_autor'),
        'pozice_udaje'     => $volba('pozice_udaje'),
        'zarovnani_pod'    => $volba('zarovnani_pod'),
        'skladat'          => $volba('skladat', 'mobil'),
        'mobil'            => $volba('mobil_zarovnani', 'vlevo'),
        'css'              => 'ne',
    ));
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
