<?php
/**
 * Modul Fonty – stažení sady z Google Fonts.
 *
 * 1. CSS z fonts.googleapis.com (s hlavičkou moderního prohlížeče → woff2 s rozdělením na znakové sady)
 * 2. Rozbor @font-face, ponechají se jen zvolené znakové sady
 * 3. Každý soubor jen z https://fonts.gstatic.com/, jen woff2/woff, kontrola podpisu souboru a velikosti
 * 4. Uloží se uploads/marmal-fonty/{klic}.css a soubory do uploads/marmal-fonty/soubory/
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

const UA         = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
const MAX_CSS    = 512000;   // 500 kB
const MAX_FONT   = 3145728;  // 3 MB na soubor
const MAX_FONTU  = 120;      // souborů na sadu
const ZNOVU_PO   = 3600;     // po chybě zkusit znovu nejdřív za hodinu

/**
 * Rozbor CSS z Google Fonts na bloky @font-face.
 *
 * @return array<int,array{sada:string,rodina:string,vaha:string,styl:string,urls:string[],css:string}>
 */
function rozebrat_css(string $css): array
{
    $bloky = array();
    // Google před každý blok píše komentář se znakovou sadou: /* latin-ext */ @font-face { … }
    if (!preg_match_all('#(?:/\*\s*([a-z0-9\-\[\]]+)\s*\*/\s*)?@font-face\s*\{([^}]*)\}#i', $css, $m, PREG_SET_ORDER)) {
        return $bloky;
    }
    foreach ($m as $b) {
        $telo = $b[2];
        preg_match('/font-family\s*:\s*[\'"]?([^;\'"]+)[\'"]?\s*;/i', $telo, $f);
        preg_match('/font-weight\s*:\s*([0-9 ]+|normal|bold)\s*;/i', $telo, $w);
        preg_match('/font-style\s*:\s*(normal|italic|oblique)\s*;/i', $telo, $s);
        preg_match_all('/url\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/i', $telo, $u);
        $bloky[] = array(
            'sada'   => isset($b[1]) && $b[1] !== '' ? strtolower($b[1]) : 'latin',
            'rodina' => isset($f[1]) ? trim($f[1]) : '',
            'vaha'   => isset($w[1]) ? trim($w[1]) : '400',
            'styl'   => isset($s[1]) ? strtolower($s[1]) : 'normal',
            'urls'   => $u[1] ?? array(),
            'css'    => '@font-face {' . $telo . '}',
        );
    }
    return $bloky;
}

/** Adresa souboru fontu je z Google (jen https://fonts.gstatic.com/s/…woff2|woff). */
function font_url_ok(string $url): bool
{
    return (bool) preg_match('#^https://fonts\.gstatic\.com/s/[A-Za-z0-9_\-/.]+\.(woff2|woff)$#', $url);
}

/** Bezpečný název souboru z adresy: /s/opensans/v40/abc.woff2 → opensans-v40-abc.woff2 */
function nazev_souboru(string $url): string
{
    $cesta = (string) wp_parse_url($url, PHP_URL_PATH);
    $cesta = preg_replace('#^/s/#', '', $cesta);
    $nazev = strtolower(str_replace('/', '-', $cesta));
    return (string) preg_replace('/[^a-z0-9._\-]/', '', $nazev);
}

/** Je obsah opravdu font (podpis woff2 / woff)? */
function je_font(string $data, string $pripona): bool
{
    $hlava = substr($data, 0, 4);
    return $pripona === 'woff2' ? $hlava === 'wOF2' : $hlava === 'wOFF';
}

/** HTTP GET s limitem velikosti; vrací tělo nebo WP_Error. */
function stahnout_url(string $url, int $max)
{
    $r = wp_safe_remote_get($url, array(
        'timeout'             => 10,
        'redirection'         => 0,
        'user-agent'          => UA,
        'limit_response_size' => $max + 1,
    ));
    if (is_wp_error($r)) {
        return $r;
    }
    $kod = (int) wp_remote_retrieve_response_code($r);
    if ($kod !== 200) {
        /* translators: %d: HTTP kód */
        return new \WP_Error('http', sprintf(__('Google odpověděl kódem %d.', 'marmal-breakdance-plus'), $kod));
    }
    $telo = (string) wp_remote_retrieve_body($r);
    if (strlen($telo) > $max) {
        return new \WP_Error('velikost', __('Soubor je příliš velký.', 'marmal-breakdance-plus'));
    }
    return $telo;
}

/**
 * Stáhne sadu (CSS + fonty) a zapíše ji do evidence. Vrací údaje sady (stav ok / chyba).
 * Volá se z frontend.php při prvním výskytu odkazu, nebo z administrace.
 */
function stahnout_sadu(string $google_url): array
{
    $klic  = klic($google_url);
    $sady  = sady();
    $zaznam = array(
        'url'     => $google_url,
        'css'     => '',
        'rodiny'  => rodiny_z_url($google_url),
        'soubory' => 0,
        'velikost' => 0,
        'cas'     => time(),
        'stav'    => 'chyba',
        'chyba'   => '',
        'pouzito' => time(),
        'preload' => array(),
    );

    $vysledek = (function () use ($google_url, $klic, &$zaznam) {
        list($dir, $url) = adresar();
        if (!wp_mkdir_p($dir . 'soubory')) {
            return __('Nejde vytvořit složku uploads/marmal-fonty.', 'marmal-breakdance-plus');
        }
        $css = stahnout_url($google_url, MAX_CSS);
        if (is_wp_error($css)) {
            return $css->get_error_message();
        }
        $bloky = array_values(array_filter(rozebrat_css($css), function ($b) {
            return in_array($b['sada'], stahovane_sady(), true);
        }));
        if (!$bloky) {
            return __('V odpovědi Googlu nejsou žádné fonty.', 'marmal-breakdance-plus');
        }

        $vystup  = '/* Marmal – Breakdance Plus, modul Fonty: ' . esc_html(implode(', ', $zaznam['rodiny'])) . ' – staženo z Google Fonts ' . gmdate('Y-m-d') . " */\n";
        $soubory = array();
        $preload = array();
        foreach ($bloky as $b) {
            $telo = $b['css'];
            foreach ($b['urls'] as $fu) {
                if (!font_url_ok($fu)) {
                    return __('Neočekávaná adresa fontu v odpovědi Googlu.', 'marmal-breakdance-plus');
                }
                $nazev = nazev_souboru($fu);
                if (!isset($soubory[$nazev])) {
                    if (count($soubory) >= MAX_FONTU) {
                        return __('Sada má příliš mnoho souborů.', 'marmal-breakdance-plus');
                    }
                    $cil = $dir . 'soubory/' . $nazev;
                    if (!file_exists($cil)) {
                        $data = stahnout_url($fu, MAX_FONT);
                        if (is_wp_error($data)) {
                            return $data->get_error_message();
                        }
                        if (!je_font($data, pathinfo($nazev, PATHINFO_EXTENSION))) {
                            return __('Stažený soubor není font.', 'marmal-breakdance-plus');
                        }
                        if (file_put_contents($cil, $data) === false) { // phpcs:ignore WordPress.WP.AlternativeFunctions
                            return __('Nejde zapsat soubor fontu.', 'marmal-breakdance-plus');
                        }
                    }
                    $soubory[$nazev] = (int) filesize($cil);
                }
                $telo = str_replace($fu, 'soubory/' . $nazev, $telo);
                // Preload: běžný řez (400, normal) latinky každé rodiny – nejvýš 2 soubory na sadu.
                if ($b['sada'] === 'latin' && in_array($b['vaha'], array('400', 'normal'), true) && $b['styl'] === 'normal'
                    && count($preload) < 2 && pathinfo($nazev, PATHINFO_EXTENSION) === 'woff2') {
                    $preload[] = $nazev;
                }
            }
            if (!empty(nastaveni()['swap']) && stripos($telo, 'font-display') === false) {
                $telo = preg_replace('/\}\s*$/', "  font-display: swap;\n}", $telo);
            }
            $vystup .= '/* ' . $b['sada'] . " */\n" . $telo . "\n";
        }

        $css_soubor = $klic . '.css';
        if (file_put_contents($dir . $css_soubor, $vystup) === false) { // phpcs:ignore WordPress.WP.AlternativeFunctions
            return __('Nejde zapsat CSS fontů.', 'marmal-breakdance-plus');
        }
        $zaznam['css']      = $css_soubor;
        $zaznam['soubory']  = count($soubory);
        $zaznam['velikost'] = array_sum($soubory);
        $zaznam['preload']  = array_values(array_unique($preload));
        $zaznam['stav']     = 'ok';
        return '';
    })();

    if ($vysledek !== '') {
        $zaznam['chyba'] = $vysledek;
    }
    $sady        = sady(); // mezitím mohl zapsat jiný požadavek
    $sady[$klic] = $zaznam;
    ulozit_sady($sady);
    return $zaznam;
}

/** Smaže všechny stažené fonty a evidenci (administrace → Smazat a stáhnout znovu). */
function smazat_vse(): void
{
    list($dir) = adresar();
    foreach (array_merge((array) glob($dir . '*.css'), (array) glob($dir . 'soubory/*')) as $f) {
        if (is_string($f) && is_file($f)) {
            wp_delete_file($f);
        }
    }
    delete_option(OPTION_SADY);
}
