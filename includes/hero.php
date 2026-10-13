<?php
/**
 * superHERO – vykreslení elementu (volá ho elements/Super_Hero/ssr.php).
 *
 * Všechno se skládá tady v PHP: třídy podle voleb, bezpečně ošetřené CSS proměnné
 * a markup. Styly jsou v elements/Super_Hero/default.css, typografie v css.twig
 * (presety Breakdance), chování v assets/hero/hero.js (načte se jen se superHERO).
 *
 * Věci závislé na čase (otevírací doba, termíny slidů) počítá až JS v prohlížeči,
 * aby je nezamrazila page cache.
 */

namespace MarmalElements;

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    wp_register_script('marmal-hero', MARMAL_BDP_URL . 'assets/hero/hero.js', [], MARMAL_BDP_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
});

// ===================================================================== hodnoty

/** Výchozí hodnoty z includes/hero-vychozi.php (generuje tools/hero_prvky.py). */
function hero_vychozi(): array
{
    static $v = null;
    if (null === $v) {
        $v = require __DIR__ . '/hero-vychozi.php';
        $v['textove'] = array_flip($v['textove']);
    }
    return $v;
}

/**
 * Hodnota prvku. Textová pole: chybějící = prázdné (prázdný text = skrýt).
 * Ostatní: chybějící = výchozí hodnota.
 */
function hero_v(array $p, string $path)
{
    $vych = hero_vychozi();
    $cur  = $p;
    foreach (explode('.', $path) as $key) {
        if (!is_array($cur) || !array_key_exists($key, $cur)) {
            $cur = null;
            break;
        }
        $cur = $cur[$key];
    }
    if (isset($vych['textove'][$path])) {
        return is_scalar($cur) ? trim((string) $cur) : '';
    }
    if ($cur === null || $cur === '' || $cur === []) {
        return $vych['hodnoty'][$path] ?? null;
    }
    return $cur;
}

/** Hodnota z pevného seznamu (dropdown) – nic jiného se do tříd ani CSS nedostane. */
function hero_volba(array $p, string $path, array $povolene): string
{
    $v = (string) hero_v($p, $path);
    if (in_array($v, $povolene, true)) {
        return $v;
    }
    $d = (string) (hero_vychozi()['hodnoty'][$path] ?? '');
    return in_array($d, $povolene, true) ? $d : (string) $povolene[0];
}

function hero_ano(array $p, string $path): bool
{
    return (bool) hero_v($p, $path);
}

/** Bezpečná CSS délka z unit prvku ({number, unit, style} / "64px"). */
function hero_unit($val, string $default): string
{
    $kandidat = '';
    if (is_array($val)) {
        if (isset($val['style']) && is_string($val['style'])) {
            $kandidat = $val['style'];
        } elseif (isset($val['number']) && is_numeric($val['number'])) {
            $kandidat = $val['number'] . (is_string($val['unit'] ?? null) ? $val['unit'] : 'px');
        }
    } elseif (is_scalar($val)) {
        $kandidat = (string) $val;
    }
    $kandidat = trim($kandidat);
    return preg_match('/^\d{1,4}(\.\d{1,3})?(px|rem|em|%|vw|vh|svh|ch)$/', $kandidat) ? $kandidat : $default;
}

/** Bezpečná CSS barva (hex, rgb/hsl, var(--…), název). Jinak prázdné. */
function hero_barva($val): string
{
    if (is_array($val)) {
        $val = $val['value'] ?? $val['color'] ?? '';
    }
    if (!is_string($val)) {
        return '';
    }
    $val = trim($val);
    if (preg_match('/^#[0-9a-f]{3,8}$/i', $val)
        || preg_match('/^(rgb|rgba|hsl|hsla)\([0-9.,%\s\/-]{3,60}\)$/i', $val)
        || preg_match('/^var\(--[a-z0-9_-]{1,80}(,\s*[#a-z0-9(),.%\s-]{1,60})?\)$/i', $val)
        || preg_match('/^[a-z]{3,20}$/i', $val)) {
        return $val;
    }
    return '';
}

// ===================================================================== média

/** První obrázek / soubor z hodnoty wpmedia (nebo ACF): ['id' => …] | ['url' => …] | null */
function hero_media($val): ?array
{
    $vse = hero_media_vse($val);
    return $vse[0] ?? null;
}

/** Všechny položky z hodnoty wpmedia (obrázky i videa). */
function hero_media_vse($val): array
{
    $out = [];
    hero_media_projit($val, $out, true);
    return $out;
}

function hero_media_projit($val, array &$out, bool $holy): void
{
    if (is_int($val) || (is_string($val) && ctype_digit($val))) {
        if ($holy && (int) $val > 0 && get_post_type((int) $val) === 'attachment') {
            $out[] = ['id' => (int) $val];
        }
        return;
    }
    if (is_string($val)) {
        if ($holy && preg_match('~^(https?:)?//\S+$~i', $val)) {
            $out[] = ['url' => $val];
        }
        return;
    }
    if (!is_array($val)) {
        return;
    }
    $id = $val['id'] ?? $val['ID'] ?? null;
    if (is_numeric($id) && (int) $id > 0 && get_post_type((int) $id) === 'attachment') {
        $out[] = ['id' => (int) $id, 'alt' => is_string($val['alt'] ?? null) ? $val['alt'] : ''];
        return;
    }
    if (!empty($val['url']) && is_string($val['url'])) {
        $out[] = ['url' => $val['url'], 'alt' => is_string($val['alt'] ?? null) ? $val['alt'] : ''];
        return;
    }
    $seznam = array_keys($val) === range(0, count($val) - 1);
    foreach ($val as $dite) {
        hero_media_projit($dite, $out, $seznam);
    }
}

function hero_media_url(?array $m): string
{
    if (!$m) {
        return '';
    }
    if (!empty($m['id'])) {
        return (string) wp_get_attachment_url($m['id']);
    }
    return (string) ($m['url'] ?? '');
}

/**
 * <img> s srcset. $priorita = LCP obrázek (fetchpriority high, bez lazy-loadu).
 */
function hero_img(?array $m, string $class, string $velikost, string $sizes, bool $priorita, ?string $alt = null): string
{
    if (!$m) {
        return '';
    }
    $attrs = [
        'class'    => $class,
        'loading'  => $priorita ? 'eager' : 'lazy',
        'decoding' => 'async',
        'sizes'    => $sizes,
    ];
    if ($priorita) {
        $attrs['fetchpriority'] = 'high';
    }
    if (null !== $alt) {
        $attrs['alt'] = $alt;
    }
    if (!empty($m['id'])) {
        return (string) wp_get_attachment_image($m['id'], $velikost, false, $attrs);
    }
    $html = '<img src="' . esc_url($m['url']) . '"';
    foreach ($attrs as $k => $v) {
        if ($k !== 'sizes') {
            $html .= ' ' . $k . '="' . esc_attr($v) . '"';
        }
    }
    if (null === $alt) {
        $html .= ' alt="' . esc_attr($m['alt'] ?? '') . '"';
    }
    return $html . '>';
}

/** Adresa videa souboru (MP4/WebM) – jen http(s) nebo relativní cesta, jinak prázdné. */
function hero_url_mp4(string $url): string
{
    $url = trim($url);
    if ($url === '' || !preg_match('~^(https?://|/)[^\s"<>]+\.(mp4|webm|ogv)(\?[^\s"<>]*)?$~i', $url)) {
        return '';
    }
    return esc_url_raw($url, ['http', 'https']);
}

// ===================================================================== ikony

/** Záložní ikony (když v prvku ikona není vybraná). */
function hero_ikona_vestavena(string $klic): string
{
    $d = [
        'sipka'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check'   => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'hvezda'  => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.8l6.1-.9z"/>',
        'telefon' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'vlevo'   => '<path d="M15 6l-6 6 6 6"/>',
        'vpravo'  => '<path d="M9 6l6 6-6 6"/>',
        'pauza'   => '<path d="M8 5v14M16 5v14" stroke-width="3"/>',
        'play'    => '<path d="M7 5v14l12-7z" fill="currentColor" stroke="none"/>',
    ];
    return isset($d[$klic]) ? '<svg class="marmal-hero-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $d[$klic] . '</svg>' : '';
}

/** Povolené SVG značky a atributy (ikony z knihovny Breakdance i nahrané SVG). */
function hero_svg_povolene(): array
{
    $spolecne = ['class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'fill-rule' => true, 'clip-rule' => true, 'opacity' => true, 'fill-opacity' => true, 'stroke-opacity' => true, 'transform' => true];
    return [
        'svg'      => $spolecne + ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true, 'role' => true, 'preserveaspectratio' => true],
        'g'        => $spolecne,
        'path'     => $spolecne + ['d' => true],
        'circle'   => $spolecne + ['cx' => true, 'cy' => true, 'r' => true],
        'ellipse'  => $spolecne + ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true],
        'rect'     => $spolecne + ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true],
        'line'     => $spolecne + ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true],
        'polyline' => $spolecne + ['points' => true],
        'polygon'  => $spolecne + ['points' => true],
    ];
}

/**
 * Ikona z prvku „icon“ Breakdance. Hodnota obsahuje SVG kód (klíč svgCode) –
 * hledá se tolerantně a SVG se vždy pročistí (žádné skripty, odkazy ani on* atributy).
 */
function hero_ikona($val, string $zalozni = ''): string
{
    $svg = hero_najdi_svg($val);
    if ($svg !== '') {
        $svg = wp_kses($svg, hero_svg_povolene());
        if (stripos($svg, '<svg') !== false) {
            return preg_replace('/<svg\b/i', '<svg aria-hidden="true" focusable="false"', $svg, 1) ?? '';
        }
    }
    return $zalozni !== '' ? hero_ikona_vestavena($zalozni) : '';
}

function hero_najdi_svg($val, int $hloubka = 0): string
{
    if ($hloubka > 4) {
        return '';
    }
    if (is_string($val)) {
        return stripos(ltrim($val), '<svg') === 0 ? $val : '';
    }
    if (!is_array($val)) {
        return '';
    }
    if (isset($val['svgCode']) && is_string($val['svgCode'])) {
        return $val['svgCode'];
    }
    foreach ($val as $dite) {
        $s = hero_najdi_svg($dite, $hloubka + 1);
        if ($s !== '') {
            return $s;
        }
    }
    return '';
}

// ===================================================================== texty

/**
 * Nadpis: *slovo* = zvýraznění, [a|b|c] = rotující slova.
 * Text je vždy escapovaný; HTML vzniká jen z těchto dvou značek.
 */
function hero_nadpis_html(string $str, bool $poSlovech, string $zvyrazneni, string $rotace): string
{
    $casti = preg_split('/(\*[^*]+\*|\[[^\]]+\])/u', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $w     = 0;
    $obal  = function (string $t) use ($poSlovech, &$w): string {
        $kusy = preg_split('/(\s+)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out  = '';
        foreach ($kusy as $k) {
            if (preg_match('/^\s+$/u', $k)) {
                $out .= strpos($k, "\n") !== false ? '<br>' : ' ';
            } else {
                $out .= $poSlovech ? '<span class="marmal-hero-w" style="--w:' . ($w++) . '">' . esc_html($k) . '</span>' : esc_html($k);
            }
        }
        return $out;
    };
    $html = '';
    foreach ((array) $casti as $c) {
        if (preg_match('/^\*([^*]+)\*$/u', $c, $m)) {
            $html .= '<span class="marmal-hero__hl marmal-hero__hl--' . esc_attr($zvyrazneni) . '">' . $obal($m[1]) . '</span>';
        } elseif (preg_match('/^\[([^\]]+)\]$/u', $c, $m)) {
            $slova = array_values(array_filter(array_map('trim', explode('|', $m[1])), 'strlen'));
            if (!$slova) {
                continue;
            }
            $r = '<span class="marmal-hero-rot marmal-hero-rot--' . esc_attr($rotace) . '" data-words="' . esc_attr(wp_json_encode($slova)) . '">'
                . '<span class="marmal-hero-rot__w">' . esc_html($slova[0]) . '</span></span>';
            $html .= $poSlovech ? '<span class="marmal-hero-w" style="--w:' . ($w++) . '">' . $r . '</span>' : $r;
        } else {
            $html .= $obal($c);
        }
    }
    return $html;
}

/** Řádky textového pole bez prázdných. */
function hero_radky(string $s): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $s)), 'strlen'));
}

/** Termín slidu „1.12.–31.12.“ → "1201-1231" (vyhodnocuje JS podle dnešního data). */
function hero_termin(string $s): string
{
    if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.?\s*[–-]\s*(\d{1,2})\.\s*(\d{1,2})\.?/u', $s, $m)) {
        return sprintf('%02d%02d-%02d%02d', (int) $m[2], (int) $m[1], (int) $m[4], (int) $m[3]);
    }
    return '';
}

// ===================================================================== dynamická data

function hero_dynamicke(array $p): array
{
    $out = ['nadpis' => null, 'text' => null, 'obrazek' => null];
    if (hero_volba($p, 'content.zdroj.rezim', ['rucne', 'dynamicky']) !== 'dynamicky') {
        return $out;
    }
    $postId = (int) (get_the_ID() ?: get_queried_object_id());
    if (!$postId) {
        return $out;
    }
    $acf = function (string $pole) use ($postId) {
        $pole = sanitize_key($pole);
        return ($pole !== '' && function_exists('get_field')) ? get_field($pole, $postId) : null;
    };

    switch (hero_volba($p, 'content.zdroj.nadpis_pole', ['post_title', 'acf', 'none'])) {
        case 'post_title':
            $out['nadpis'] = (string) get_post_field('post_title', $postId);
            break;
        case 'acf':
            $v = $acf((string) hero_v($p, 'content.zdroj.nadpis_acf'));
            $out['nadpis'] = is_scalar($v) ? (string) $v : '';
            break;
    }
    switch (hero_volba($p, 'content.zdroj.text_pole', ['post_excerpt', 'acf', 'none'])) {
        case 'post_excerpt':
            // Jen ručně zadaný výňatek – automatický by vykresloval celý obsah stránky.
            $out['text'] = wp_strip_all_tags((string) get_post_field('post_excerpt', $postId));
            break;
        case 'acf':
            $v = $acf((string) hero_v($p, 'content.zdroj.text_acf'));
            $out['text'] = is_scalar($v) ? wp_strip_all_tags((string) $v) : '';
            break;
    }
    switch (hero_volba($p, 'content.zdroj.obrazek_pole', ['featured_image', 'acf', 'none'])) {
        case 'featured_image':
            $id = (int) get_post_thumbnail_id($postId);
            $out['obrazek'] = $id ? ['id' => $id] : false;
            break;
        case 'acf':
            $out['obrazek'] = hero_media($acf((string) hero_v($p, 'content.zdroj.obrazek_acf'))) ?: false;
            break;
    }
    return $out;
}

// ===================================================================== styl efektů

/** Hodnoty, které přebije „Styl efektů“ (Klidný / Moderní / Výrazný). */
function hero_styly(): array
{
    return [
        'klidny'  => ['design.animace.vstup' => 'fade', 'design.animace.postupne' => true, 'design.animace.rychlost' => 'pomalu', 'design.animace.medium_vstup' => 'stejne', 'design.nadpis_efekt.zvyrazneni' => 'barva', 'design.nadpis_efekt.rotace' => 'prolnuti', 'design.tlacitka_efekt.hover' => 'sipka', 'design.tlacitka_efekt.upoutat' => 'zadne', 'design.pozadi_efekt.dekor' => 'zadny', 'design.pozadi_efekt.kenburns' => true, 'design.medium_efekt.animace' => 'zadna', 'design.medium_efekt.stitek_anim' => false, 'design.odjezd.typ' => 'zeslabnuti', 'design.divider.pohyb' => false],
        'moderni' => ['design.animace.vstup' => 'nahoru', 'design.animace.postupne' => true, 'design.animace.rychlost' => 'normal', 'design.animace.medium_vstup' => 'odkryti', 'design.nadpis_efekt.zvyrazneni' => 'podtrzeni', 'design.nadpis_efekt.rotace' => 'vyjeti', 'design.tlacitka_efekt.hover' => 'vypln', 'design.tlacitka_efekt.upoutat' => 'zadne', 'design.pozadi_efekt.dekor' => 'tecky', 'design.pozadi_efekt.dekor_pohyb' => true, 'design.pozadi_efekt.kenburns' => true, 'design.medium_efekt.animace' => 'vznaseni', 'design.medium_efekt.stitek_anim' => true, 'design.odjezd.typ' => 'paralaxa', 'design.divider.pohyb' => true],
        'vyrazny' => ['design.animace.vstup' => 'slova', 'design.animace.postupne' => true, 'design.animace.rychlost' => 'normal', 'design.animace.medium_vstup' => 'zoom', 'design.nadpis_efekt.zvyrazneni' => 'prechod', 'design.nadpis_efekt.rotace' => 'psaci', 'design.tlacitka_efekt.hover' => 'magnet', 'design.tlacitka_efekt.upoutat' => 'pulz', 'design.pozadi_efekt.dekor' => 'castice', 'design.pozadi_efekt.dekor_pohyb' => true, 'design.pozadi_efekt.kenburns' => true, 'design.medium_efekt.animace' => 'naklon', 'design.medium_efekt.stitek_anim' => true, 'design.odjezd.typ' => 'paralaxa', 'design.divider.pohyb' => true],
    ];
}

/** Vloží hodnoty stylu do $p, aby je zbytek kódu četl jako běžné volby. */
function hero_pouzit_styl(array $p): array
{
    $styl  = hero_volba($p, 'design.styl.preset', ['vlastni', 'klidny', 'moderni', 'vyrazny']);
    $styly = hero_styly();
    if (!isset($styly[$styl])) {
        return $p;
    }
    foreach ($styly[$styl] as $path => $hodnota) {
        $k = explode('.', $path);
        $p[$k[0]][$k[1]][$k[2]] = $hodnota;
    }
    return $p;
}

// ===================================================================== divider

function hero_vlna(int $a, int $per, int $zakl, int $sirka): string
{
    $d = 'M0 ' . $zakl;
    for ($x = 0; $x < $sirka; $x += $per) {
        $d .= ' Q' . ($x + $per / 4) . ' ' . ($zakl - $a) . ' ' . ($x + $per / 2) . ' ' . $zakl
            . ' Q' . ($x + $per * 3 / 4) . ' ' . ($zakl + $a) . ' ' . ($x + $per) . ' ' . $zakl;
    }
    return $d . ' V120 H0 Z';
}

function hero_divider(string $tvar, bool $zrcadlit, bool $pohyb): string
{
    $tridaPohyb = $pohyb ? 'marmal-hero-dv-move' : '';
    if ($tvar === 'vlna') {
        $vnitrek = '<g class="' . $tridaPohyb . '" style="--mh-dv-shift:-600px;animation-duration:9s"><path d="' . hero_vlna(20, 600, 62, 2400) . '"/></g>';
    } elseif ($tvar === 'vlny') {
        $vnitrek = '';
        foreach ([[16, 800, 40, '.3', 15], [18, 600, 58, '.55', 11], [12, 400, 78, '1', 8]] as $l) {
            $vnitrek .= '<g class="' . $tridaPohyb . '" style="--mh-dv-shift:-' . $l[1] . 'px;animation-duration:' . $l[4] . 's"><path opacity="' . $l[3] . '" d="' . hero_vlna($l[0], $l[1], $l[2], 2400) . '"/></g>';
        }
    } else {
        $tvary = [
            'oblouk' => 'M0 0 Q600 170 1200 0 V120 H0Z',
            'kopec'  => 'M0 120 Q600 -50 1200 120Z',
            'sikmo'  => 'M0 120 L1200 0 V120Z',
            'krivka' => 'M0 120 C320 -20 760 150 1200 30 V120Z',
            'hory'   => 'M0 120 L0 82 L150 34 L300 90 L470 18 L640 86 L800 40 L980 96 L1100 54 L1200 80 V120Z',
        ];
        if ($tvar === 'zuby') {
            $d = 'M0 120 L0 70';
            for ($x = 0; $x < 1200; $x += 60) {
                $d .= ' L' . ($x + 30) . ' 30 L' . ($x + 60) . ' 70';
            }
            $tvary['zuby'] = $d . ' V120Z';
        }
        if ($tvar === 'papir') {
            $d = 'M0 120 L0 74';
            $y = 74;
            for ($x = 14; $x <= 1200; $x += 14) {
                $y  = max(56, min(92, $y + ((($x * 37) % 23) - 11)));
                $d .= ' L' . $x . ' ' . $y;
            }
            $tvary['papir'] = $d . ' V120Z';
        }
        if (!isset($tvary[$tvar])) {
            return '';
        }
        $vnitrek = '<path d="' . $tvary[$tvar] . '"/>';
    }
    return '<div class="marmal-hero__divider' . ($zrcadlit ? ' marmal-hero__divider--flip' : '') . '" aria-hidden="true">'
        . '<svg viewBox="0 0 1200 120" preserveAspectRatio="none" focusable="false">' . $vnitrek . '</svg></div>';
}

// ===================================================================== vykreslení

function hero_render(array $p): string
{
    $isFrontend = !is_admin() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST);
    $p          = hero_pouzit_styl($p);
    $v          = function (string $path) use ($p) {
        return hero_v($p, $path);
    };
    $volba      = function (string $path, array $povolene) use ($p) {
        return hero_volba($p, $path, $povolene);
    };
    $ano        = function (string $path) use ($p) {
        return hero_ano($p, $path);
    };

    $dyn = hero_dynamicke($p);

    // ---------------------------------------------------------------- volby
    $layout    = $volba('design.rozlozeni.varianta', ['split', 'split-rev', 'center', 'stack', 'text']);
    $sRezim    = $volba('content.slider.rezim', ['vypnuto', 'pozadi', 'cely']);
    $bgTyp     = $sRezim !== 'vypnuto' ? 'slider' : $volba('content.pozadi.typ', ['zadne', 'barva', 'gradient', 'zivy', 'obrazek', 'video']);
    $bgBarva   = $volba('design.pozadi.barva', ['tmava', 'brand', 'accent', 'svetla', 'bila']);
    $mTyp      = $layout === 'text' ? 'zadne' : $volba('content.medium.typ', ['obrazek', 'video', 'formular', 'zadne']);
    $textBarva = $volba('design.pozadi.barva_textu', ['auto', 'svetly', 'tmavy']);
    $svetly    = $textBarva === 'svetly' || ($textBarva === 'auto' && (in_array($bgTyp, ['obrazek', 'video', 'slider', 'zivy'], true)
        || (in_array($bgTyp, ['barva', 'gradient'], true) && in_array($bgBarva, ['tmava', 'brand'], true))));
    $anim      = $volba('design.animace.vstup', ['zadna', 'fade', 'nahoru', 'zleva', 'zprava', 'zoom', 'rozostreni', 'odkryti', 'preklopeni', 'slova']);
    $poSlovech = $anim === 'slova';
    $lcp       = $ano('design.vykon.lcp');
    $vsp       = $volba('content.video.spoustec', ['zadny', 'tlacitko2', 'medium', 'oboje']);

    // obsah: ručně nebo z příspěvku
    $nadpis = null !== $dyn['nadpis'] ? $dyn['nadpis'] : (string) $v('content.obsah.nadpis');
    $text   = null !== $dyn['text'] ? $dyn['text'] : (string) $v('content.obsah.text');
    $mImg   = $dyn['obrazek'] === null ? hero_media($v('content.medium.obrazek')) : ($dyn['obrazek'] ?: null);

    // médium ve frontendu bez obrázku/videa se nevykreslí
    $mVideo = $mTyp === 'video' ? hero_url_mp4((string) $v('content.medium.video')) : '';
    $formHtml = '';
    if ($mTyp === 'formular') {
        // Jen skutečný shortcode ([nazev …]) – žádné volné HTML ani text.
        $sc = trim((string) $v('content.medium.form_shortcode'));
        $formHtml = preg_match('/^\[[a-z0-9_-]{1,64}(\s[^\[\]<>]{0,300})?\]$/i', $sc) ? do_shortcode($sc) : '';
    }
    $maMedium = ($mTyp === 'obrazek' && ($mImg || !$isFrontend))
        || ($mTyp === 'video' && ($mVideo !== '' || !$isFrontend))
        || ($mTyp === 'formular' && ($formHtml !== '' || !$isFrontend || $v('content.medium.form_nadpis') !== ''));
    if (!$maMedium) {
        $mTyp = 'zadne';
    }

    // ---------------------------------------------------------------- slidy
    $slidy = [['h' => $nadpis, 't' => $text, 'term' => '']];
    if ($sRezim === 'cely') {
        for ($n = 2; $n <= 5; $n++) {
            $h = (string) $v("content.slider.slide{$n}_nadpis");
            if ($h === '') {
                continue;
            }
            $slidy[] = ['h' => $h, 't' => (string) $v("content.slider.slide{$n}_text"), 'term' => hero_termin((string) $v("content.slider.slide{$n}_termin"))];
        }
    }
    $sliderObr = $sRezim !== 'vypnuto' ? hero_media_vse($v('content.slider.obrazky')) : [];
    $pocetSlidu = $sRezim === 'pozadi' ? count($sliderObr) : ($sRezim === 'cely' ? count($slidy) : 1);
    if ($bgTyp === 'slider' && !$sliderObr) {
        $bgTyp = 'barva';
    }

    // ---------------------------------------------------------------- třídy a CSS proměnné
    $mask  = $volba('design.medium.maska', ['zadna', 'oblouk', 'blob', 'kruh', 'kapsle', 'list', 'sestiuhelnik', 'sikmo', 'roh', 'vlna']);
    $poly  = in_array($mask, ['sestiuhelnik', 'sikmo', 'roh', 'vlna'], true);
    $dekor = $volba('design.medium.dekor', ['zadny', 'plocha', 'ramecek', 'tecky', 'kruh']);
    $mEff  = $volba('design.medium_efekt.animace', ['zadna', 'vznaseni', 'morph', 'zoom', 'naklon']);
    $deco  = $volba('design.pozadi_efekt.dekor', ['zadny', 'tecky', 'mrizka', 'sum', 'bloby', 'castice', 'paprsky', 'reflektor']);
    $dvTvar = $volba('design.divider.tvar', ['zadny', 'vlna', 'vlny', 'oblouk', 'kopec', 'sikmo', 'krivka', 'hory', 'zuby', 'papir']);
    $odjezd = $volba('design.odjezd.typ', ['zadny', 'zeslabnuti', 'paralaxa']);
    $hover  = $volba('design.tlacitka_efekt.hover', ['zadny', 'zvednuti', 'sipka', 'vypln', 'lesk', 'zare', 'magnet']);
    $minH   = $volba('design.rozlozeni.min_vyska', ['auto', '60', '75', '85', '100']);
    $minHm  = $volba('design.mobil.min_vyska_mobil', ['auto', '60', '75', '100']);
    $tc     = $layout === 'center';
    $tcMob  = $tc || $volba('design.mobil.zarovnani_mobil', ['vlevo', 'stred']) === 'stred';
    $medMob = $volba('design.mobil.medium_mobil', ['pod', 'nad', 'skryt']);

    $cls = ['marmal-hero', 'marmal-hero--' . $layout, 'marmal-hero--h' . $minH, 'marmal-hero--hm' . $minHm,
        'marmal-hero--stack-' . $volba('design.mobil.stack_od', ['tablet', 'mobil']),
        'marmal-hero--valign-' . $volba('design.rozlozeni.zarovnani', ['start', 'center', 'end']),
        'marmal-hero--hover-' . $hover, 'marmal-hero--bg-' . $bgTyp, 'marmal-hero--mob-media-' . $medMob,
        'marmal-hero--size-' . $volba('design.typografie.velikost', ['S', 'M', 'L', 'XL'])];
    if ($mTyp === 'zadne' || in_array($layout, ['center', 'stack', 'text'], true)) {
        $cls[] = 'marmal-hero--1col';
    }
    if ($tc) {
        $cls[] = 'marmal-hero--tc';
    }
    if ($tcMob) {
        $cls[] = 'marmal-hero--tc-mob';
    }
    if ($svetly) {
        $cls[] = 'marmal-hero--light';
    }
    if ($ano('design.medium.stin')) {
        $cls[] = 'marmal-hero--shadow';
    }
    if ($ano('design.mobil.tlacitka_full')) {
        $cls[] = 'marmal-hero--btnfull';
    }
    if ($ano('content.doplnky.header')) {
        $cls[] = 'marmal-hero--head';
    }
    if ($poSlovech) {
        $cls[] = 'marmal-hero--words';
    }
    if ($mEff !== 'zadna') {
        $cls[] = 'marmal-hero--m-' . $mEff;
    }
    if (!$ano('design.pozadi_efekt.dekor_pohyb')) {
        $cls[] = 'marmal-hero--deco-static';
    }
    if ($volba('design.mobil.animace_mobil', ['vypnout', 'ponechat']) === 'vypnout') {
        $cls[] = 'marmal-hero--calm-mob';
    }
    if ($odjezd !== 'zadny') {
        $cls[] = 'marmal-hero--odjezd-' . $odjezd;
    }
    if (!$isFrontend) {
        $cls[] = 'marmal-hero--builder';
    }

    $zWebu = $ano('design.rozlozeni.z_webu');
    if (!$zWebu) {
        $cls[] = 'marmal-hero--own';
    }
    $rych  = ['rychle' => '.5s', 'normal' => '.8s', 'pomalu' => '1.2s'][$volba('design.animace.rychlost', ['normal', 'rychle', 'pomalu'])];
    $AN    = ['zadna' => 'none', 'fade' => 'marmal-hero-fade', 'nahoru' => 'marmal-hero-up', 'zleva' => 'marmal-hero-left', 'zprava' => 'marmal-hero-right', 'zoom' => 'marmal-hero-zoom', 'rozostreni' => 'marmal-hero-blur', 'odkryti' => 'marmal-hero-reveal', 'preklopeni' => 'marmal-hero-flip', 'slova' => 'marmal-hero-up'];
    $MAN   = ['stejne' => $AN[$anim], 'zoom' => 'marmal-hero-zoom', 'zprava' => 'marmal-hero-right', 'odkryti' => 'marmal-hero-reveal', 'rotace' => 'marmal-hero-rotin'];
    $mAnim = $anim === 'zadna' ? 'none' : $MAN[$volba('design.animace.medium_vstup', ['stejne', 'zoom', 'zprava', 'odkryti', 'rotace'])];
    $pom   = explode(' ', $volba('design.rozlozeni.pomer', ['1fr 1fr', '1.2fr 1fr', '1.5fr 1fr', '1fr 1.2fr']));
    $cols  = $layout === 'split-rev' ? "minmax(0,{$pom[1]}) minmax(0,{$pom[0]})" : "minmax(0,{$pom[0]}) minmax(0,{$pom[1]})";
    $barvaVar = ['tmava' => 'var(--mh-dark)', 'brand' => 'var(--mh-brand)', 'accent' => 'var(--mh-accent)', 'svetla' => 'var(--mh-light)', 'bila' => '#fff'];

    $vars = [
        '--mh-cols'    => $cols,
        '--mh-anim'    => $AN[$anim],
        '--mh-dur'     => $rych,
        '--mh-step'    => $ano('design.animace.postupne') ? '110ms' : '0ms',
        '--mh-hmax'    => max(10, min(40, (int) $v('design.typografie.nadpis_max'))) . 'ch',
        '--mh-ar'      => $volba('design.medium.pomer', ['4/3', 'auto', '1/1', '3/4', '16/9']),
        '--mh-ov-a'    => max(0, min(90, (int) $v('design.pozadi.prekryv_sila'))),
        '--mh-ov-c'    => $volba('design.pozadi.prekryv', ['tmava', 'brand']) === 'brand' ? 'var(--mh-brand)' : 'var(--mh-dark)',
        '--mh-int'     => max(3, min(12, (int) $v('design.slider.interval'))) . 's',
    ];
    if ($bgTyp === 'barva') {
        $vars['--mh-bgc'] = $barvaVar[$bgBarva];
    } elseif ($bgTyp === 'gradient') {
        $vars['--mh-bgc'] = 'linear-gradient(135deg,' . $barvaVar[$bgBarva] . ' 0%,' . ($bgBarva === 'tmava' ? 'var(--mh-brand)' : 'var(--mh-dark)') . ' 100%)';
    } elseif ($bgTyp === 'zadne') {
        $vars['--mh-bgc'] = 'transparent';
    }
    if (!$zWebu) {
        $vars['--mh-w']   = hero_unit($v('design.rozlozeni.sirka'), '1200px');
        $vars['--mh-pad-own'] = hero_unit($v('design.rozlozeni.odsazeni'), '120px');
        $vars['--mh-gap-own'] = hero_unit($v('design.rozlozeni.mezera'), '64px');
        $vars['--mh-r']   = hero_unit($v('design.medium.zaobleni'), '16px');
    }
    if (!$ano('design.tlacitka.z_presetu')) {
        $vars['--mh-b-r'] = ['hranaty' => '0px', 'zaobleny' => '8px', 'pilulka' => '999px'][$volba('design.tlacitka.tvar', ['zaobleny', 'hranaty', 'pilulka'])];
        $vel = $volba('design.tlacitka.velikost', ['M', 'S', 'L']);
        $vars['--mh-b-py'] = ['S' => '10px', 'M' => '14px', 'L' => '18px'][$vel];
        $vars['--mh-b-px'] = ['S' => '18px', 'M' => '24px', 'L' => '32px'][$vel];
        $vars['--mh-b-fs'] = ['S' => '14px', 'M' => '16px', 'L' => '18px'][$vel];
    }
    foreach (['brand' => '--mh-brand', 'accent' => '--mh-accent', 'tmava' => '--mh-dark', 'svetla' => '--mh-light'] as $k => $var) {
        $b = hero_barva($v('design.barvy.' . $k));
        if ($b !== '') {
            $vars[$var] = $b;
        }
    }
    $hl = hero_barva($v('design.pismo.barva_hl'));
    if ($hl !== '') {
        $vars['--mh-hl'] = $hl;
    }
    if ($dvTvar !== 'zadny') {
        $vars['--mh-dv-h'] = hero_unit($v('design.divider.vyska'), '90px');
        $dvB = hero_barva($v('design.divider.barva'));
        $vars['--mh-dv-c'] = $dvB !== '' ? $dvB : '#fff';
    }
    $style = '';
    foreach ($vars as $k => $val) {
        $style .= $k . ':' . $val . ';';
    }

    // ---------------------------------------------------------------- pozadí
    $pohyb = false; // je v sekci něco v pohybu? (tlačítko „Pozastavit“)
    $kb    = $ano('design.pozadi_efekt.kenburns');
    $bg    = '';
    $lcpHotovo = false;
    if ($bgTyp === 'slider') {
        $bg = '<div class="marmal-hero__bg marmal-hero-tr--' . esc_attr($volba('design.slider.prechod', ['prolnuti', 'posun', 'zoom'])) . ($kb ? ' marmal-hero__bg--kb' : '') . '">';
        foreach ($sliderObr as $i => $m) {
            $bg .= '<div class="marmal-hero__slide' . ($i === 0 ? ' is-on' : '') . '" data-s="' . (int) $i . '">'
                . hero_img($m, 'marmal-hero__bg-img', 'full', '100vw', $lcp && $i === 0, '') . '</div>';
        }
        $bg .= '</div>';
        $lcpHotovo = true;
        $pohyb     = true;
    } elseif ($bgTyp === 'obrazek' || $bgTyp === 'video') {
        $poster = hero_media($v('content.pozadi.obrazek'));
        $video  = $bgTyp === 'video' ? hero_url_mp4((string) $v('content.pozadi.video')) : '';
        $kbOn   = $bgTyp === 'obrazek' && $kb;
        $bg     = '<div class="marmal-hero__bg' . ($kbOn ? ' marmal-hero__bg--kb' : '') . '">';
        $bg    .= hero_img($poster, 'marmal-hero__bg-img', 'full', '100vw', $lcp, '');
        if ($video !== '') {
            // src doplní JS – jen na desktopu/tabletu a bez šetření dat
            $bg   .= '<video class="marmal-hero__bg-video" data-src="' . esc_url($video) . '" muted loop playsinline preload="none" aria-hidden="true"'
                . ($poster ? ' poster="' . esc_url(hero_media_url($poster)) . '"' : '') . '></video>';
            $pohyb = true;
        }
        $bg .= '</div>';
        $lcpHotovo = (bool) $poster;
        if ($kbOn) {
            $pohyb = true;
        }
    } elseif ($bgTyp === 'zivy') {
        $bg    = '<div class="marmal-hero__bg"><div class="marmal-hero__mesh"><i></i><i></i><i></i></div></div>';
        $pohyb = true;
    }
    if (in_array($bgTyp, ['obrazek', 'video', 'slider'], true)) {
        $ovTvar = $volba('design.pozadi.prekryv_styl', ['prechod', 'plny']);
        $bg    .= '<div class="marmal-hero__overlay marmal-hero__overlay--' . esc_attr($ovTvar) . '" aria-hidden="true"></div>';
    }
    if ($deco !== 'zadny') {
        $di = '';
        if ($deco === 'bloby') {
            $di = '<i></i><i></i>';
        }
        if ($deco === 'castice') {
            for ($i = 0; $i < 26; $i++) {
                $s   = 2 + $i * 7 % 4;
                $di .= '<i style="left:' . (($i * 53 + 17) % 100) . '%;width:' . $s . 'px;height:' . $s . 'px;animation-duration:' . (9 + $i * 13 % 10) . 's;animation-delay:-' . number_format(fmod($i * 1.7, 12), 1, '.', '') . 's"></i>';
            }
        }
        $bg .= '<div class="marmal-hero__deco marmal-hero__deco--' . esc_attr($deco) . '" aria-hidden="true">' . $di . '</div>';
        if ($deco !== 'reflektor' && $ano('design.pozadi_efekt.dekor_pohyb')) {
            $pohyb = true;
        }
    }

    // ---------------------------------------------------------------- obsah
    $i = 0;
    $a = function () use (&$i) {
        return ' marmal-hero-a" style="--i:' . ($i++);
    };
    $c = '';

    $oteviraci = '';
    if ($ano('content.oteviraci.zapnout')) {
        $dny = ['po-pa' => [1, 2, 3, 4, 5], 'po-so' => [1, 2, 3, 4, 5, 6], 'denne' => [0, 1, 2, 3, 4, 5, 6]][$volba('content.oteviraci.dny', ['po-pa', 'po-so', 'denne'])];
        $od  = preg_match('/^\d{1,2}(:\d{2})?$/', (string) $v('content.oteviraci.od')) ? (string) $v('content.oteviraci.od') : '8:00';
        $do  = preg_match('/^\d{1,2}(:\d{2})?$/', (string) $v('content.oteviraci.do')) ? (string) $v('content.oteviraci.do') : '16:00';
        $tel = (string) $v('content.oteviraci.telefon');
        $txtDny = ['po-pa' => __('Po–Pá', 'marmal-breakdance-plus'), 'po-so' => __('Po–So', 'marmal-breakdance-plus'), 'denne' => __('Denně', 'marmal-breakdance-plus')][$volba('content.oteviraci.dny', ['po-pa', 'po-so', 'denne'])];
        $oteviraci = '<span class="marmal-hero__open' . $a() . '" data-open="' . esc_attr(wp_json_encode(['dny' => $dny, 'od' => $od, 'do' => $do])) . '">'
            . '<i class="marmal-hero-off" aria-hidden="true"></i><span class="marmal-hero__open-t">' . esc_html($txtDny . ' ' . $od . '–' . $do) . '</span>'
            . ($tel !== '' ? '<span aria-hidden="true">·</span><a href="' . esc_url('tel:' . preg_replace('/[^\d+]/', '', $tel)) . '">' . esc_html($tel) . '</a>' : '')
            . '</span>';
    }
    $otevNahore = $volba('content.oteviraci.pozice', ['pod', 'nahore']) === 'nahore';
    if ($oteviraci && $otevNahore) {
        $c .= $oteviraci;
    }

    $odznak = (string) $v('content.obsah.odznak');
    if ($odznak !== '') {
        $st  = $volba('content.obsah.odznak_styl', ['tecka', 'obrys', 'sklo']);
        $c  .= '<span class="marmal-hero__badge' . ($st === 'sklo' ? ' marmal-hero__badge--sklo' : '') . $a() . '">'
            . ($st === 'tecka' ? '<i class="marmal-hero-live" aria-hidden="true"></i>' : hero_ikona($v('content.obsah.odznak_ikona')))
            . '<span>' . esc_html($odznak) . '</span></span>';
    }
    $eyebrow = (string) $v('content.obsah.eyebrow');
    if ($eyebrow !== '') {
        $c .= '<span class="marmal-hero__eyebrow' . ($volba('design.typografie.eyebrow_styl', ['kapitalky', 'pilulka']) === 'pilulka' ? ' marmal-hero__eyebrow--pill' : '') . $a() . '">' . esc_html($eyebrow) . '</span>';
    }

    $tag  = $volba('content.obsah.nadpis_tag', ['h1', 'h2', 'p']);
    $zv   = $volba('design.nadpis_efekt.zvyrazneni', ['barva', 'podtrzeni', 'zvyraznovac', 'prechod']);
    $rot  = $volba('design.nadpis_efekt.rotace', ['vyjeti', 'prolnuti', 'psaci']);
    $iT   = $i;
    $i   += 2;
    $stack = '';
    foreach ($slidy as $n => $s) {
        $x = '';
        if ($s['h'] !== '') {
            if (preg_match('/\[[^\]]+\|/', $s['h'])) {
                $pohyb = true;
            }
            $t   = $n === 0 ? $tag : 'p';
            $x  .= '<' . $t . ' class="marmal-hero__title' . ($poSlovech ? '' : ' marmal-hero-a" style="--i:' . $iT) . '"' . ($n > 0 ? ' role="heading" aria-level="2"' : '') . '>'
                . hero_nadpis_html($s['h'], $poSlovech, $zv, $rot) . '</' . $t . '>';
        }
        if ($s['t'] !== '') {
            $x .= '<p class="marmal-hero__text marmal-hero-a" style="--i:' . ($iT + 1) . '">' . esc_html($s['t']) . '</p>';
        }
        $stack .= '<div class="marmal-hero__sc' . ($n === 0 ? ' is-on' : '') . '" data-s="' . (int) $n . '"' . ($s['term'] ? ' data-term="' . esc_attr($s['term']) . '"' : '') . '>' . $x . '</div>';
    }
    $c .= '<div class="marmal-hero__stack">' . $stack . '</div>';

    // tlačítka
    $upoutat = $volba('design.tlacitka_efekt.upoutat', ['zadne', 'pulz', 'lesk', 'houpnuti']);
    if ($upoutat !== 'zadne') {
        $pohyb = true;
    }
    $btns = '';
    foreach ([1, 2] as $n) {
        $t = (string) $v("content.tlacitka.btn{$n}_text");
        if ($t === '') {
            continue;
        }
        $st    = $volba("design.tlacitka.btn{$n}_styl", ['primary', 'secondary', 'outline', 'sklo', 'prechod', 'link']);
        $ic    = hero_ikona($v("content.tlacitka.btn{$n}_ikona"));
        $video = $n === 2 && in_array($vsp, ['tlacitko2', 'oboje'], true);
        $url   = (string) $v("content.tlacitka.btn{$n}_url");
        $nove  = $ano("content.tlacitka.btn{$n}_nove_okno") && !$video;
        $btns .= '<a class="marmal-hero-btn marmal-hero-btn--' . esc_attr($st) . ($n === 1 && $upoutat !== 'zadne' ? ' marmal-hero-btn--att-' . esc_attr($upoutat) : '') . '"'
            . ' href="' . esc_url($url !== '' ? $url : '#') . '"' . ($nove ? ' target="_blank" rel="noopener"' : '') . ($video ? ' data-mh-video role="button"' : '') . '>'
            . '<span>' . esc_html($t) . '</span>'
            . ($ic ? '<span class="marmal-hero-btn__ic">' . $ic . '</span>' : '')
            . ($n === 1 && $upoutat === 'lesk' ? '<span class="marmal-hero-btn__glint" aria-hidden="true"></span>' : '')
            . '</a>';
    }
    if ($btns !== '') {
        $c .= '<div class="marmal-hero__btns' . $a() . '">' . $btns . '</div>';
    }

    // důvěra
    $dt    = $volba('content.duvera.typ', ['zadna', 'hodnoceni', 'avatary', 'loga']);
    $pruh  = '';
    if ($dt === 'hodnoceni') {
        $cislo = (string) $v('content.duvera.hodnoceni_cislo');
        $val   = (float) str_replace(',', '.', $cislo);
        $c    .= '<div class="marmal-hero__trust marmal-hero__rating' . $a() . '">'
            . ($cislo !== '' ? '<b>' . esc_html($cislo) . '</b>' : '')
            /* translators: %s: hodnocení, např. 4,9 */
            . '<span class="marmal-hero-stars" role="img" aria-label="' . esc_attr(sprintf(__('Hodnocení %s z 5', 'marmal-breakdance-plus'), $cislo)) . '"><i style="width:' . max(0, min(100, round($val / 5 * 100))) . '%"></i></span>'
            . '<span>' . esc_html((string) $v('content.duvera.hodnoceni_text')) . '</span></div>';
    } elseif ($dt === 'avatary') {
        $fotky = hero_media_vse($v('content.duvera.avatary_foto'));
        $pocet = $fotky ? min(6, count($fotky)) : max(3, min(6, (int) $v('content.duvera.avatary_pocet')));
        $kruhy = '';
        for ($k = 0; $k < $pocet; $k++) {
            $kruhy .= '<span class="marmal-hero__av marmal-hero__av--' . ($k % 4) . '">' . (isset($fotky[$k]) ? hero_img($fotky[$k], '', 'thumbnail', '40px', false, '') : '') . '</span>';
        }
        $c .= '<div class="marmal-hero__trust' . $a() . '"><span class="marmal-hero__avatars" aria-hidden="true">' . $kruhy . '</span><span>' . esc_html((string) $v('content.duvera.avatary_text')) . '</span></div>';
    } elseif ($dt === 'loga') {
        $loga = '';
        foreach (hero_media_vse($v('content.duvera.loga_soubory')) as $m) {
            $loga .= hero_img($m, 'marmal-hero__logo', 'medium', '160px', false);
        }
        if ($loga === '') {
            foreach (array_filter(array_map('trim', explode(',', (string) $v('content.duvera.loga'))), 'strlen') as $nazev) {
                $loga .= '<span class="marmal-hero__lg">' . esc_html($nazev) . '</span>';
            }
        }
        $nad = (string) $v('content.duvera.loga_nadpis');
        if ($loga !== '') {
            if ($volba('content.duvera.loga_pozice', ['dole', 'pod']) === 'pod') {
                $c .= '<div class="marmal-hero__strip marmal-hero__strip--pod' . $a() . '">' . ($nad !== '' ? '<small>' . esc_html($nad) . '</small>' : '') . '<div class="marmal-hero__logos">' . $loga . '</div></div>';
            } else {
                $jede = $ano('content.duvera.loga_pohyb');
                if ($jede) {
                    $pohyb = true;
                }
                $kopie = $jede ? preg_replace('/<(span|img) /', '<$1 aria-hidden="true" ', $loga) : '';
                $pruh  = '<div class="marmal-hero__strip' . ($jede ? ' marmal-hero__strip--move' : '') . '">' . ($nad !== '' ? '<small>' . esc_html($nad) . '</small>' : '')
                    . '<div class="marmal-hero__logos">' . $loga . $kopie . '</div></div>';
            }
        }
    }
    if ($oteviraci && !$otevNahore) {
        $c .= $oteviraci;
    }

    // výhody / čísla
    $polozky = [];
    for ($n = 1; $n <= 6; $n++) {
        $x = (string) $v("content.vyhody.polozka{$n}");
        if ($x !== '') {
            $polozky[] = $x;
        }
    }
    if ($polozky) {
        $vst = $volba('design.vyhody.styl', ['seznam', 'karty', 'cisla']);
        if ($vst === 'cisla') {
            $li = '';
            foreach ($polozky as $x) {
                $kusy = array_map('trim', explode('|', $x, 2));
                $cis  = $kusy[0];
                if (preg_match('/^(\D*?)(\d+(?:[ \x{00a0}]\d{3})*(?:[.,]\d+)?)(.*)$/u', $cis, $m)) {
                    $do    = str_replace([' ', "\xc2\xa0", ','], ['', '', '.'], $m[2]);
                    $des   = strpos($m[2], ',') !== false || strpos($m[2], '.') !== false ? strlen((string) preg_replace('/^.*[.,]/', '', $m[2])) : 0;
                    $cisH  = esc_html($m[1]) . '<span class="marmal-hero-cnt" data-to="' . esc_attr($do) . '" data-dec="' . (int) $des . '">' . esc_html($m[2]) . '</span><small>' . esc_html($m[3]) . '</small>';
                } else {
                    $cisH = esc_html($cis);
                }
                $li .= '<li><span class="marmal-hero-num">' . $cisH . '</span>' . (isset($kusy[1]) && $kusy[1] !== '' ? '<span class="marmal-hero__lab">' . esc_html($kusy[1]) . '</span>' : '') . '</li>';
            }
            $c .= '<ul class="marmal-hero__list marmal-hero__list--cisla' . $a() . '">' . $li . '</ul>';
        } else {
            $ist  = $volba('design.vyhody.ikona_styl', ['kruh', 'ikona', 'tecka', 'blikajici']);
            $ikon = hero_ikona($v('content.vyhody.ikona'), 'check');
            if ($ist === 'blikajici') {
                $znak = '<i class="marmal-hero-live" aria-hidden="true"></i>';
            } elseif ($ist === 'tecka') {
                $znak = '<i class="marmal-hero-dot" aria-hidden="true"></i>';
            } else {
                $znak = '<span class="marmal-hero__li-ic' . ($ist === 'kruh' ? ' marmal-hero__li-ic--kruh' : '') . '">' . $ikon . '</span>';
            }
            $li = '';
            foreach ($polozky as $x) {
                $li .= '<li>' . $znak . '<span>' . esc_html($x) . '</span></li>';
            }
            $c .= '<ul class="marmal-hero__list marmal-hero__list--' . esc_attr($vst) . $a() . '">' . $li . '</ul>';
        }
    }

    // ---------------------------------------------------------------- médium
    $media   = '';
    $mStyle  = '" style="--i:' . ($i++) . ';animation-name:' . $mAnim;
    $maskCls = $mask === 'zadna' ? '' : ' marmal-hero-mask--' . $mask . ($poly ? ' marmal-hero-mask--poly' : '');
    $auto    = $vars['--mh-ar'] === 'auto' && $mask !== 'kruh';
    if ($mTyp === 'obrazek' || $mTyp === 'video') {
        $pics = '';
        if ($mTyp === 'video') {
            $pics = $mVideo !== ''
                ? '<div class="marmal-hero__pic is-on' . $maskCls . '" data-s="0"><video src="' . esc_url($mVideo) . '" autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video></div>'
                : '<div class="marmal-hero__pic marmal-hero__pic--empty is-on' . $maskCls . '" data-s="0"><span>' . esc_html__('superHERO: vyberte video v Content → Médium.', 'marmal-breakdance-plus') . '</span></div>';
            if ($mVideo !== '') {
                $pohyb = true;
            }
        } else {
            $alt   = (string) $v('content.medium.alt');
            $seznam = [$mImg];
            if ($sRezim === 'cely') {
                // další slidy střídají obrázky slideru (pokud jsou)
                for ($n = 1; $n < count($slidy); $n++) {
                    $seznam[] = $sliderObr ? $sliderObr[$n % count($sliderObr)] : $mImg;
                }
            }
            foreach ($seznam as $n => $m) {
                $img = $m ? hero_img($m, '', 'large', '(max-width: 767px) 100vw, 50vw', $lcp && !$lcpHotovo && $n === 0, $alt !== '' && $n === 0 ? $alt : null)
                    : '<span>' . esc_html__('superHERO: vyberte obrázek v Content → Médium.', 'marmal-breakdance-plus') . '</span>';
                $pics .= '<div class="marmal-hero__pic' . ($auto && $m ? ' marmal-hero__pic--auto' : '') . ($m ? '' : ' marmal-hero__pic--empty') . ($n === 0 ? ' is-on' : '') . $maskCls . '" data-s="' . (int) $n . '">' . $img . '</div>';
            }
        }
        $ghost = $dekor === 'zadny' ? '' : '<span class="marmal-hero__ghost marmal-hero__ghost--' . esc_attr($dekor) . (in_array($dekor, ['plocha', 'ramecek'], true) ? $maskCls : '') . '" aria-hidden="true"></span>';
        $stitek = (string) $v('content.medium.stitek');
        $fl = '';
        if ($stitek !== '') {
            $stAnim = $ano('design.medium_efekt.stitek_anim');
            if ($stAnim) {
                $pohyb = true;
            }
            $fl = '<div class="marmal-hero__float' . ($stAnim ? ' marmal-hero__float--anim' : '') . '">' . hero_ikona($v('content.medium.stitek_ikona'), 'hvezda') . '<span>' . esc_html($stitek) . '</span></div>';
        }
        if (in_array($mEff, ['vznaseni', 'morph', 'zoom'], true)) {
            $pohyb = true;
        }
        $play = in_array($vsp, ['medium', 'oboje'], true)
            ? '<button type="button" class="marmal-hero__play" data-mh-video aria-label="' . esc_attr__('Přehrát video', 'marmal-breakdance-plus') . '">' . hero_ikona_vestavena('play') . '</button>' : '';
        $media = '<figure class="marmal-hero__media marmal-hero-a' . $mStyle . '"><div class="marmal-hero__mwrap">' . $ghost . '<div class="marmal-hero__pics">' . $pics . '</div>' . $play . $fl . '</div></figure>';
    } elseif ($mTyp === 'formular') {
        $fn = (string) $v('content.medium.form_nadpis');
        $media = '<div class="marmal-hero__media marmal-hero-a' . $mStyle . '"><div class="marmal-hero-form">'
            . ($fn !== '' ? '<h3 class="marmal-hero-form__title">' . esc_html($fn) . '</h3>' : '')
            . ($formHtml !== '' ? $formHtml : '<p class="marmal-hero-form__hint">' . esc_html__('superHERO: vložte shortcode formuláře (např. Global Block s formulářem Breakdance) v Content → Médium.', 'marmal-breakdance-plus') . '</p>')
            . '</div></div>';
    }

    // ---------------------------------------------------------------- navigace, pauza, šipka, divider
    $nav    = '';
    $navTyp = $volba('design.slider.navigace', ['tecky', 'sipky', 'cisla', 'progres', 'zadna']);
    if ($pocetSlidu > 1 && $navTyp !== 'zadna') {
        $tecky = '<div class="marmal-hero__dots">';
        for ($n = 0; $n < $pocetSlidu; $n++) {
            /* translators: %d: číslo slidu */
            $tecky .= '<button type="button" data-go="' . $n . '" aria-label="' . esc_attr(sprintf(__('Slide %d', 'marmal-breakdance-plus'), $n + 1)) . '" aria-current="' . ($n === 0 ? 'true' : 'false') . '"></button>';
        }
        $tecky .= '</div>';
        $sipky = '<button type="button" class="marmal-hero__arr" data-step="-1" aria-label="' . esc_attr__('Předchozí', 'marmal-breakdance-plus') . '">' . hero_ikona_vestavena('vlevo') . '</button>'
            . '<button type="button" class="marmal-hero__arr" data-step="1" aria-label="' . esc_attr__('Další', 'marmal-breakdance-plus') . '">' . hero_ikona_vestavena('vpravo') . '</button>';
        $cit   = '<span class="marmal-hero__count"><b data-cur>01</b> / <span data-total>' . sprintf('%02d', $pocetSlidu) . '</span></span>';
        $vnitr = ['tecky' => $tecky, 'sipky' => $sipky, 'cisla' => $cit . $sipky, 'progres' => $cit . '<span class="marmal-hero__prog"><i></i></span>'][$navTyp];
        $nav   = '<div class="marmal-hero__nav"><div class="marmal-hero__navin">' . $vnitr . '</div></div>';
    }
    if ($pocetSlidu > 1) {
        $pohyb = true;
    }
    if ($dvTvar !== 'zadny' && in_array($dvTvar, ['vlna', 'vlny'], true) && $ano('design.divider.pohyb')) {
        $pohyb = true;
    }

    $sipka = $volba('content.doplnky.sipka', ['zadna', 'kruh', 'mys', 'cara']);
    $sipkaHtml = '';
    if ($sipka !== 'zadna' && $minH !== 'auto' && $nav === '' && $pruh === '') {
        $sipkaHtml = $sipka === 'cara'
            ? '<span class="marmal-hero__scroll marmal-hero__scroll--cara" aria-hidden="true">' . esc_html__('Posunout', 'marmal-breakdance-plus') . '<i></i></span>'
            : '<span class="marmal-hero__scroll marmal-hero__scroll--' . esc_attr($sipka) . '" aria-hidden="true"></span>';
    }
    $pauza = ($ano('content.doplnky.pauza') && $pohyb)
        ? '<button type="button" class="marmal-hero__pause" aria-pressed="false" data-l-pause="' . esc_attr__('Pozastavit pohyb', 'marmal-breakdance-plus') . '" data-l-play="' . esc_attr__('Spustit pohyb', 'marmal-breakdance-plus') . '" aria-label="' . esc_attr__('Pozastavit pohyb', 'marmal-breakdance-plus') . '" title="' . esc_attr__('Pozastavit pohyb', 'marmal-breakdance-plus') . '">'
          . hero_ikona_vestavena('pauza') . hero_ikona_vestavena('play') . '</button>'
        : '';
    $divider = $dvTvar !== 'zadny' ? hero_divider($dvTvar, $ano('design.divider.otocit'), $ano('design.divider.pohyb')) : '';

    // přichycené CTA (jen mobil, ukáže ho JS po odscrollování)
    $sticky = '';
    if ($ano('content.doplnky.sticky') && (string) $v('content.doplnky.sticky_text') !== '') {
        $st1    = $volba('design.tlacitka.btn1_styl', ['primary', 'secondary', 'outline', 'sklo', 'prechod', 'link']);
        $sticky = '<div class="marmal-hero__sticky" aria-hidden="true">'
            . '<a class="marmal-hero-btn marmal-hero-btn--' . esc_attr(in_array($st1, ['primary', 'secondary', 'prechod'], true) ? $st1 : 'primary') . '" href="' . esc_url((string) $v('content.doplnky.sticky_url') ?: '#') . '" tabindex="-1">'
            . hero_ikona($v('content.doplnky.sticky_ikona'), 'telefon') . '<span>' . esc_html((string) $v('content.doplnky.sticky_text')) . '</span></a></div>';
    }

    // video do lightboxu (sdílený lightbox Galerie Plus)
    $lb = '';
    if ($vsp !== 'zadny') {
        $soubor = hero_url_mp4((string) $v('content.video.soubor'));
        $url    = (string) $v('content.video.url');
        $embed  = $soubor !== '' ? ['type' => 'file', 'src' => $soubor] : (function_exists(__NAMESPACE__ . '\\gallery_video_embed') ? gallery_video_embed($url) : null);
        if ($embed) {
            $lb = '<div class="marmal-hero__lb" data-lb-items hidden><a data-lb-item href="' . esc_url($soubor !== '' ? $soubor : $url) . '" data-video="' . esc_url($embed['src']) . '" data-video-type="' . esc_attr($embed['type']) . '"></a></div>';
            if ($isFrontend) {
                wp_enqueue_style('marmal-lightbox');
                wp_enqueue_script('marmal-lightbox');
                if (function_exists(__NAMESPACE__ . '\\gallery_lightbox_i18n')) {
                    gallery_lightbox_i18n();
                }
            }
        }
    }

    // nastavení pro hero.js
    $cfg = [
        'slider'   => $pocetSlidu > 1 ? ['n' => $pocetSlidu, 'int' => max(3, min(12, (int) $v('design.slider.interval'))), 'pauza' => $ano('design.slider.pauza')] : null,
        'odjezd'   => $odjezd,
        'magnet'   => $hover === 'magnet',
        'naklon'   => $mEff === 'naklon',
        'svetlo'   => $deco === 'reflektor',
        'tz'       => function_exists('wp_timezone_string') ? wp_timezone_string() : '',
        'video'    => ['mobil' => $volba('design.mobil.video_mobil', ['obrazek', 'video']) === 'video', 'setrit' => $ano('design.vykon.setrit_data')],
        'otevreno' => [
            /* translators: %s: čas zavření, např. 17:00 */
            __('Teď máme otevřeno · do %s', 'marmal-breakdance-plus'),
            /* translators: %s: čas otevření */
            __('Zavřeno · otevíráme dnes v %s', 'marmal-breakdance-plus'),
            /* translators: %s: čas otevření */
            __('Zavřeno · otevíráme zítra v %s', 'marmal-breakdance-plus'),
            /* translators: 1: den („v pondělí“), 2: čas otevření */
            __('Zavřeno · otevíráme %1$s v %2$s', 'marmal-breakdance-plus'),
            [__('v neděli', 'marmal-breakdance-plus'), __('v pondělí', 'marmal-breakdance-plus'), __('v úterý', 'marmal-breakdance-plus'), __('ve středu', 'marmal-breakdance-plus'), __('ve čtvrtek', 'marmal-breakdance-plus'), __('v pátek', 'marmal-breakdance-plus'), __('v sobotu', 'marmal-breakdance-plus')]],
    ];

    if ($isFrontend) {
        wp_enqueue_script('marmal-hero');
    }

    if ($pruh !== '') {
        $cls[] = 'marmal-hero--has-strip';
    }

    return '<div class="' . esc_attr(implode(' ', $cls)) . '" style="' . esc_attr($style) . '" data-mh="' . esc_attr(wp_json_encode($cfg)) . '">'
        . $bg
        . '<div class="marmal-hero__inner"><div class="marmal-hero__content">' . $c . '</div>' . $media . '</div>'
        . $nav . $pruh . $sipkaHtml . $pauza . $divider . $sticky . $lb
        . '</div>';
}
