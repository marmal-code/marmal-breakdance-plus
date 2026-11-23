<?php
/**
 * Modul Přesměrování – import / export CSV.
 *
 * Sloupce: stara_adresa;nova_adresa;kod;poznamka (export navíc zasahy, puvod).
 * Import: oddělovač ; nebo , (zjistí se z prvního řádku), kódování UTF-8 i Windows-1250 (Excel),
 * první řádek s názvy sloupců se přeskočí, nejvýš 2 MB / 5000 řádků. Existující stará adresa se přepíše.
 * Export: buňky začínající = + - @ dostanou apostrof (ochrana proti vzorcům v Excelu).
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

const CSV_MAX_BAJTU  = 2097152;
const CSV_MAX_RADKU  = 5000;

/** Hodnota bezpečná pro tabulkový procesor (CSV injection). */
function csv_bunka(string $v): string
{
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
}

/** Odešle CSV se všemi pravidly (volá admin.php po kontrole oprávnění a nonce). */
function export_csv(): void
{
    global $wpdb;
    $nazev = 'presmerovani-' . sanitize_file_name((string) wp_parse_url(home_url(), PHP_URL_HOST)) . '-' . gmdate('Y-m-d') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nazev . '"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM – Excel pak pozná UTF-8
    fputcsv($out, array('stara_adresa', 'nova_adresa', 'kod', 'poznamka', 'zasahy', 'puvod', 'aktivni'), ';');
    $posun = 0;
    do {
        $rows = (array) $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . t_pravidla() . ' ORDER BY id ASC LIMIT 1000 OFFSET %d', $posun), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        foreach ($rows as $r) {
            $cil = (string) $r['cil'];
            if (preg_match('/^post:(\d+)$/', $cil, $m)) {
                $url = get_post_status((int) $m[1]) ? (string) get_permalink((int) $m[1]) : '';
                $cil = $url !== '' ? cesta_pro_export($url) : $cil;
            }
            fputcsv($out, array_map(__NAMESPACE__ . '\csv_bunka', array(
                (string) $r['zdroj'],
                $cil,
                (string) $r['kod'],
                (string) $r['poznamka'],
                (string) $r['zasahy'],
                (string) $r['puvod'],
                (string) $r['aktivni'],
            )), ';');
        }
        $posun += 1000;
    } while (count($rows) === 1000);
    fclose($out);
    exit;
}

/** Adresa stránky do exportu jako cesta (čitelná a přenositelná mezi weby). */
function cesta_pro_export(string $url): string
{
    $c = (string) wp_parse_url($url, PHP_URL_PATH);
    $z = zaklad();
    if ($z !== '' && stripos($c, $z . '/') === 0) {
        $c = substr($c, strlen($z));
    }
    return $c === '' ? '/' : $c;
}

/**
 * Import ze souboru nahraného formulářem ($_FILES položka po kontrole v admin.php).
 * @return array{ok: int, chyby: string[]}|\WP_Error
 */
function import_csv(array $soubor)
{
    if (!empty($soubor['error']) || empty($soubor['tmp_name']) || !is_uploaded_file($soubor['tmp_name'])) {
        return new \WP_Error('soubor', __('Soubor se nepodařilo nahrát.', 'marmal-breakdance-plus'));
    }
    if ((int) $soubor['size'] > CSV_MAX_BAJTU) {
        return new \WP_Error('velikost', __('Soubor je větší než 2 MB.', 'marmal-breakdance-plus'));
    }
    $pripona = strtolower(pathinfo((string) $soubor['name'], PATHINFO_EXTENSION));
    if (!in_array($pripona, array('csv', 'txt'), true)) {
        return new \WP_Error('typ', __('Nahraj soubor .csv.', 'marmal-breakdance-plus'));
    }
    $obsah = (string) file_get_contents($soubor['tmp_name']); // phpcs:ignore WordPress.WP.AlternativeFunctions
    return import_text($obsah);
}

/** @return array{ok: int, chyby: string[]}|\WP_Error */
function import_text(string $obsah)
{
    $obsah = preg_replace('/^\xEF\xBB\xBF/', '', $obsah);
    if (!mb_check_encoding($obsah, 'UTF-8')) {
        $prevod = function_exists('iconv') ? @iconv('Windows-1250', 'UTF-8//IGNORE', $obsah) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $obsah  = $prevod !== false ? $prevod : mb_convert_encoding($obsah, 'UTF-8', 'ISO-8859-2');
    }
    $radky = preg_split('/\r\n|\r|\n/', (string) $obsah);
    $radky = array_values(array_filter($radky, function ($r) {
        return trim((string) $r) !== '';
    }));
    if (!$radky) {
        return new \WP_Error('prazdny', __('Soubor je prázdný.', 'marmal-breakdance-plus'));
    }
    if (count($radky) > CSV_MAX_RADKU + 1) {
        return new \WP_Error('radky', sprintf(/* translators: %d: počet */ __('Soubor má víc než %d řádků – rozděl ho.', 'marmal-breakdance-plus'), CSV_MAX_RADKU));
    }
    $oddelovac = substr_count($radky[0], ';') >= substr_count($radky[0], ',') ? ';' : ',';
    $ok    = 0;
    $chyby = array();
    foreach ($radky as $i => $radek) {
        $b = array_map('trim', str_getcsv((string) $radek, $oddelovac, '"', '\\'));
        $b = array_map(function ($v) {
            return strpos($v, "'") === 0 && preg_match('/^\'[=+\-@]/', $v) ? substr($v, 1) : $v;
        }, $b);
        $zdroj = (string) ($b[0] ?? '');
        if ($i === 0 && !preg_match('#^(/|https?://)#i', $zdroj)) {
            continue; // hlavička
        }
        if ($zdroj === '') {
            continue;
        }
        $kod = (int) ($b[2] ?? 301);
        $res = ulozit(array(
            'zdroj'    => $zdroj,
            'cil'      => (string) ($b[1] ?? ''),
            'kod'      => $kod ?: 301,
            'poznamka' => (string) ($b[3] ?? ''),
            'aktivni'  => isset($b[6]) && $b[6] !== '' ? (int) $b[6] : 1,
            'puvod'    => 'import',
            'na_stranku' => true,
        ), 0, true);
        if (is_wp_error($res)) {
            $chyby[] = sprintf(/* translators: 1: řádek, 2: adresa, 3: chyba */ __('Řádek %1$d (%2$s): %3$s', 'marmal-breakdance-plus'), $i + 1, mb_substr($zdroj, 0, 80, 'UTF-8'), $res->get_error_message());
        } else {
            $ok++;
        }
    }
    return array('ok' => $ok, 'chyby' => array_slice($chyby, 0, 30));
}
