<?php
/**
 * Modul Fonty – přednačtení (od 0.17.0) a přehled, odkud Breakdance písma bere.
 *
 * Režimy:
 *  auto    – 1 soubor písma textu + 1 soubor písma nadpisů podle Global Settings Breakdance
 *            (typography.body_font / heading_font, např. „gfont-dmsans“); latin, normal;
 *            variabilní písmo = soubor bez ohledu na váhu, statické = váha z Global Settings, jinak 400
 *  rucne   – nejvýš 3 soubory zaškrtnuté v administraci (vazba přes rodinu, styl, váhu a sadu)
 *  vypnuto – nic
 *
 * Přednačte se jen soubor ze sady, kterou stránka opravdu načítá (frontend.php to hlídá),
 * takže se nikdy nepřednačítá font, který stránka nevykresluje.
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

// ================================================================= Global Settings Breakdance

/**
 * Global Settings Breakdance jako pole (prázdné, když Breakdance není nebo data nejdou přečíst).
 * Breakdance je ukládá jako JSON do volby breakdance_global_settings_json_string (obal „settings“).
 */
function bd_globalni(): array
{
    static $g = null;
    if (null !== $g) {
        return $g;
    }
    $raw = null;
    if (function_exists('\Breakdance\Data\get_global_option')) {
        try {
            $raw = \Breakdance\Data\get_global_option('global_settings_json_string');
        } catch (\Throwable $e) {
            $raw = null;
        }
    }
    if (!is_string($raw) || $raw === '') {
        $raw = get_option('breakdance_global_settings_json_string', '');
    }
    $a = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? json_decode($raw, true) : null);
    if (is_array($a) && isset($a['settings']) && is_array($a['settings'])) {
        $a = $a['settings'];
    }
    $g = is_array($a) ? $a : array();
    return $g;
}

/** Hodnota z vnořeného pole podle cesty „a.b.c“. */
function cesta(array $a, string $cesta)
{
    foreach (explode('.', $cesta) as $k) {
        if (!is_array($a) || !array_key_exists($k, $a)) {
            return null;
        }
        $a = $a[$k];
    }
    return $a;
}

/** „gfont-dmsans“ / „DM Sans“ → „dmsans“ (jednotný klíč pro porovnání). */
function klic_rodiny(string $s): string
{
    $s = strtolower(trim($s));
    if (strpos($s, 'gfont-') === 0) {
        $s = substr($s, 6);
    }
    return (string) preg_replace('/[^a-z0-9]/', '', $s);
}

/** Váha z Global Settings („500“, 500, „bold“…) jako trojciferné číslo, nebo '' když není. */
function vaha_z(?string $v): string
{
    $v = strtolower(trim((string) $v));
    if ($v === 'normal') {
        return '400';
    }
    if ($v === 'bold') {
        return '700';
    }
    return preg_match('/^[1-9]00$/', $v) ? $v : '';
}

/**
 * Písmo textu a nadpisů podle Global Settings.
 * @return array<string,array{font:string,vaha:string,popis:string}> role (text|nadpisy) => údaje
 */
function pisma_webu(): array
{
    $g = bd_globalni();
    $r = array();
    $text = (string) (cesta($g, 'typography.body_font') ?? '');
    if ($text !== '') {
        $r['text'] = array(
            'font'  => $text,
            'vaha'  => vaha_z((string) cesta($g, 'typography.advanced.body.typography.custom.customTypography.fontWeight.breakpoint_base')),
            'popis' => __('Písmo textu', 'marmal-breakdance-plus'),
        );
    }
    $nadpisy = (string) (cesta($g, 'typography.heading_font') ?? '');
    if ($nadpisy !== '') {
        $r['nadpisy'] = array(
            'font'  => $nadpisy,
            'vaha'  => vaha_z((string) cesta($g, 'typography.advanced.headings.all_headings.typography.custom.customTypography.fontWeight.breakpoint_base')),
            'popis' => __('Písmo nadpisů', 'marmal-breakdance-plus'),
        );
    }
    return $r;
}

// ================================================================= katalog stažených souborů

/**
 * Všechny stažené woff2 soubory (ze sad ve stavu ok).
 * @return array<string,array> název souboru => [rodina, styl, sada, vahy[], var, velikost, klic_sady]
 */
function katalog(): array
{
    static $k = null;
    if (null !== $k) {
        return $k;
    }
    $k = array();
    foreach (sady() as $klic => $s) {
        if (($s['stav'] ?? '') !== 'ok') {
            continue;
        }
        foreach (info_sady((string) $klic, $s) as $nazev => $i) {
            if (!isset($k[$nazev])) {
                $k[$nazev] = $i + array('klic_sady' => array());
            }
            $k[$nazev]['klic_sady'][] = (string) $klic;
        }
    }
    return $k;
}

/** Identita souboru z katalogu (pro ruční výběr). */
function identita_souboru(array $i): string
{
    return identita((string) $i['rodina'], (string) $i['styl'], !empty($i['var']) ? 'var' : (string) ($i['vahy'][0] ?? '400'), (string) $i['sada']);
}

/**
 * Soubor pro jednu roli v automatickém režimu, nebo '' když písmo mezi staženými není.
 */
function soubor_pro(string $font, string $vaha): string
{
    $kl    = klic_rodiny($font);
    $kandidati = array_filter(katalog(), function ($i) use ($kl) {
        return klic_rodiny((string) $i['rodina']) === $kl && $i['sada'] === 'latin' && $i['styl'] === 'normal';
    });
    if (!$kandidati) {
        return '';
    }
    // Variabilní písmo: jeden soubor pokrývá všechny váhy.
    foreach ($kandidati as $nazev => $i) {
        if (!empty($i['var'])) {
            return (string) $nazev;
        }
    }
    // Statické: váha z Global Settings, jinak 400; když přesně není, nejbližší.
    $chci  = (int) ($vaha !== '' ? $vaha : '400');
    $nej   = '';
    $rozdil = PHP_INT_MAX;
    foreach ($kandidati as $nazev => $i) {
        foreach ((array) $i['vahy'] as $v) {
            $d = abs((int) $v - $chci);
            if ($d < $rozdil) {
                $rozdil = $d;
                $nej    = (string) $nazev;
            }
        }
    }
    return $nej;
}

/**
 * Soubory k přednačtení podle režimu (pořadí = priorita), bez ohledu na stránku.
 * @return string[] názvy souborů v uploads/marmal-fonty/soubory/
 */
function soubory_k_prednacteni(): array
{
    $s = nastaveni();
    if ($s['preload_rezim'] === 'vypnuto') {
        return array();
    }
    if ($s['preload_rezim'] === 'rucne') {
        $out = array();
        foreach ((array) $s['preload_soubory'] as $id) {
            foreach (katalog() as $nazev => $i) {
                if (identita_souboru($i) === $id) {
                    $out[] = (string) $nazev;
                    break;
                }
            }
        }
        return array_slice(array_values(array_unique($out)), 0, MAX_RUCNE);
    }
    $out = array();
    foreach (pisma_webu() as $p) {
        $f = soubor_pro($p['font'], $p['vaha']);
        if ($f !== '') {
            $out[] = $f;
        }
    }
    return array_values(array_unique($out)); // stejné písmo textu i nadpisů = 1 soubor
}

/**
 * Upozornění pro administraci (automatický režim): písmo z Global Settings, které není mezi staženými.
 * @return string[]
 */
function upozorneni_preload(): array
{
    if (nastaveni()['preload_rezim'] !== 'auto') {
        return array();
    }
    $out = array();
    foreach (pisma_webu() as $p) {
        if (strpos(strtolower($p['font']), 'gfont-') !== 0) {
            continue; // vlastní nebo systémové písmo – Google Fonts se ho netýká
        }
        if (soubor_pro($p['font'], $p['vaha']) === '') {
            /* translators: 1: písmo textu / nadpisů, 2: např. gfont-jost */
            $out[] = sprintf(__('%1$s (%2$s) není mezi staženými soubory – nepřednačítá se nic. Klikni na „Projít web a stáhnout“; když to nepomůže, web písmo z Googlu nenačítá.', 'marmal-breakdance-plus'), $p['popis'], $p['font']);
        }
    }
    return $out;
}

// ================================================================= odkud Breakdance písma bere

/**
 * Přehled použití písem: klíč rodiny => [font => „gfont-…“, mista => [[popis, odkaz]]].
 * Prochází Global Settings (vč. typografických presetů) a data Breakdance všech stránek,
 * šablon, hlaviček, patiček, globálních bloků a popupů (jen v administraci, na vyžádání).
 */
function pouziti_pisem(): array
{
    $out = array();
    $pridat = function (string $font, string $popis, string $odkaz = '') use (&$out) {
        $k = klic_rodiny($font);
        if ($k === '') {
            return;
        }
        if (!isset($out[$k])) {
            $out[$k] = array('font' => $font, 'mista' => array());
        }
        $out[$k]['mista'][md5($popis . $odkaz)] = array($popis, $odkaz);
    };

    // 1) Global Settings
    $g = bd_globalni();
    foreach (pisma_webu() as $p) {
        $pridat($p['font'], sprintf(/* translators: %s: Písmo textu / Písmo nadpisů */ __('Global Settings → %s', 'marmal-breakdance-plus'), $p['popis']));
    }
    $presety = (array) (cesta($g, 'typography.global_typography.typography_presets') ?? array());
    foreach ($presety as $pr) {
        $json = (string) wp_json_encode($pr);
        if (preg_match_all('/gfont-[a-z0-9\-]+/i', $json, $m)) {
            $nazev = (string) ($pr['preset']['label'] ?? '?');
            foreach (array_unique($m[0]) as $f) {
                /* translators: %s: název presetu */
                $pridat($f, sprintf(__('Typografický preset „%s“', 'marmal-breakdance-plus'), sanitize_text_field($nazev)));
            }
        }
    }
    // Ostatní místa v Global Settings (nadpisy H1–H6, tlačítka, formuláře…) – mimo už zapsané.
    $zbytek = $g;
    unset($zbytek['typography']['body_font'], $zbytek['typography']['heading_font'], $zbytek['typography']['global_typography']);
    if (preg_match_all('/gfont-[a-z0-9\-]+/i', (string) wp_json_encode($zbytek), $m)) {
        foreach (array_unique($m[0]) as $f) {
            $pridat($f, __('Global Settings – jiné nastavení (např. H1–H6, tlačítka, formuláře)', 'marmal-breakdance-plus'));
        }
    }

    // 2) Data Breakdance – jen příspěvky, jejichž data písmo obsahují.
    global $wpdb;
    // Nejdřív jen ID (data stránek můžou mít stovky kB), pak data po dávkách 20 – šetří paměť.
    $ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_breakdance_data'
         WHERE p.post_type <> 'revision' AND p.post_status NOT IN ('trash','auto-draft','inherit') AND m.meta_value LIKE %s LIMIT 1000",
        '%' . $wpdb->esc_like('gfont-') . '%'
    )));
    foreach (array_chunk($ids, 20) as $davka) {
        // ID jsou celá čísla (intval výše)
        $radky = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT p.ID, p.post_title, p.post_type, m.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_breakdance_data'
             WHERE p.ID IN (" . implode(',', array_map('intval', $davka)) . ')'
        );
        foreach ($radky as $r) {
            $vnejsi = json_decode((string) $r->meta_value, true);
            $strom  = is_array($vnejsi) && isset($vnejsi['tree_json_string']) && is_string($vnejsi['tree_json_string']) ? json_decode($vnejsi['tree_json_string'], true) : null;
            $typ    = get_post_type_object((string) $r->post_type);
            $nazev  = ($r->post_title !== '' ? $r->post_title : '#' . $r->ID) . ' (' . ($typ ? $typ->labels->singular_name : $r->post_type) . ')';
            $odkaz  = add_query_arg(array('breakdance' => 'builder', 'id' => (int) $r->ID), home_url('/')); // otevře builder
            if (!is_array($strom) || !isset($strom['root'])) {
                if (preg_match_all('/gfont-[a-z0-9\-]+/i', (string) $r->meta_value, $m)) {
                    foreach (array_unique($m[0]) as $f) {
                        $pridat($f, $nazev, $odkaz);
                    }
                }
                continue;
            }
            $projit = function ($uzel) use (&$projit, $pridat, $nazev, $odkaz) {
                if (!is_array($uzel)) {
                    return;
                }
                $vlastnosti = $uzel['data']['properties'] ?? null;
                if ($vlastnosti && preg_match_all('/gfont-[a-z0-9\-]+/i', (string) wp_json_encode($vlastnosti), $m)) {
                    $typ = (string) ($uzel['data']['type'] ?? '');
                    $pos = strrpos($typ, '\\');
                    $typ = $pos !== false ? substr($typ, $pos + 1) : $typ; // EssentialElements\\Heading → Heading
                    foreach (array_unique($m[0]) as $f) {
                        /* translators: 1: stránka, 2: typ elementu, 3: ID elementu */
                        $pridat($f, sprintf(__('%1$s → %2$s, ID elementu %3$s', 'marmal-breakdance-plus'), $nazev, $typ, (string) ($uzel['id'] ?? '?')), $odkaz);
                    }
                }
                foreach ((array) ($uzel['children'] ?? array()) as $c) {
                    $projit($c);
                }
            };
            $projit($strom['root']);
        }
    }
    ksort($out);
    return $out;
}
