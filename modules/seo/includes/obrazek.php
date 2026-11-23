<?php
/**
 * Modul SEO – obrázek pro sdílení 1200 × 630 px.
 *
 * Velikost se NEvytváří u každého nahraného obrázku (zbytečné soubory navíc) – jen u obrázků,
 * které se opravdu použijí pro sdílení: při uložení stránky / nastavení v administraci,
 * nebo na pozadí (jednorázová událost cronu) při prvním zobrazení stránky. Do té doby se použije
 * velikost „large“.
 *
 * Výřez se ukládá jako JPEG (Facebook, WhatsApp i LinkedIn ho zobrazí vždy, WebP ne všude)
 * vedle ostatních velikostí a zapíše se do metadat přílohy – WordPress ho tak při smazání
 * přílohy smaže s ostatními velikostmi.
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

/** Velikost marmal-og v metadatech přílohy (nebo null). */
function og_velikost(int $id): ?array
{
    $meta = wp_get_attachment_metadata($id);
    if (!is_array($meta) || empty($meta['sizes'][OG_SIZE]['file'])) {
        return null;
    }
    return $meta['sizes'][OG_SIZE];
}

/**
 * Vytvoří výřez 1200 × 630 (JPEG) z originálu přílohy. Vrátí true, když velikost existuje.
 * Menší obrázek se nezvětšuje – výřez bude menší (Facebook přijme od 600 × 315).
 */
function og_vytvorit(int $id): bool
{
    if (!$id || !wp_attachment_is_image($id)) {
        return false;
    }
    $soubor = get_attached_file($id);
    if (!$soubor || !file_exists($soubor)) {
        return false;
    }
    $slozka = dirname($soubor);
    $hotova = og_velikost($id);
    if ($hotova && file_exists($slozka . '/' . $hotova['file'])) {
        return true;
    }
    $zdroj = function_exists('wp_get_original_image_path') ? wp_get_original_image_path($id) : '';
    $zdroj = ($zdroj && file_exists($zdroj)) ? $zdroj : $soubor;

    $editor = wp_get_image_editor($zdroj);
    if (is_wp_error($editor)) {
        return false;
    }
    $rozmer = $editor->get_size();
    if (empty($rozmer['width']) || empty($rozmer['height']) || $rozmer['width'] < 200 || $rozmer['height'] < 105) {
        return false;
    }
    // Poměr 1,91 : 1 – menší obrázek se ořízne na stejný poměr bez zvětšení.
    $w = (int) min(OG_W, $rozmer['width'], floor($rozmer['height'] * OG_W / OG_H));
    $h = (int) round($w * OG_H / OG_W);
    $r = $editor->resize($w, $h, true);
    if (is_wp_error($r)) {
        return false;
    }
    $editor->set_quality(82);
    $nazev  = pathinfo($soubor, PATHINFO_FILENAME);
    $cil    = $slozka . '/' . sanitize_file_name($nazev . '-og-' . $w . 'x' . $h . '.jpg');
    $ulozen = $editor->save($cil, 'image/jpeg');
    if (is_wp_error($ulozen) || empty($ulozen['file'])) {
        return false;
    }
    $meta = wp_get_attachment_metadata($id);
    $meta = is_array($meta) ? $meta : array();
    $meta['sizes']          = isset($meta['sizes']) && is_array($meta['sizes']) ? $meta['sizes'] : array();
    $meta['sizes'][OG_SIZE] = array(
        'file'      => wp_basename($ulozen['path'] ?? $ulozen['file']),
        'width'     => (int) $ulozen['width'],
        'height'    => (int) $ulozen['height'],
        'mime-type' => 'image/jpeg',
        'filesize'  => isset($ulozen['path']) && file_exists($ulozen['path']) ? (int) filesize($ulozen['path']) : 0,
    );
    wp_update_attachment_metadata($id, $meta);
    return true;
}

add_action('marmal_seo_og', function ($id) {
    og_vytvorit((int) $id);
});

/**
 * Údaje obrázku pro og:image. Bez hotového výřezu použije „large“ a výřez naplánuje na pozadí
 * (nejvýš jednou za požadavek, a jen když už není naplánovaný).
 *
 * @return array{url:string,width:int,height:int,alt:string,type:string}|null
 */
function og_obrazek(int $id, bool $naplanovat = true): ?array
{
    if (!$id || !wp_attachment_is_image($id)) {
        return null;
    }
    $alt = cisty_text(get_post_meta($id, '_wp_attachment_image_alt', true), 200);
    $v   = og_velikost($id);
    if ($v) {
        $src = wp_get_attachment_image_src($id, OG_SIZE);
        if ($src) {
            return array('url' => absolutni((string) $src[0]), 'width' => (int) $src[1], 'height' => (int) $src[2], 'alt' => $alt, 'type' => 'image/jpeg');
        }
    }
    static $naplanovano = false;
    if ($naplanovat && !$naplanovano && !wp_next_scheduled('marmal_seo_og', array($id))) {
        wp_schedule_single_event(time() + 5, 'marmal_seo_og', array($id));
        $naplanovano = true;
    }
    $src = wp_get_attachment_image_src($id, 'large');
    if (!$src) {
        return null;
    }
    $typ = (string) get_post_mime_type($id);
    $ext = strtolower(pathinfo(wp_parse_url((string) $src[0], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    $typy = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif');
    return array('url' => absolutni((string) $src[0]), 'width' => (int) $src[1], 'height' => (int) $src[2], 'alt' => $alt, 'type' => $typy[$ext] ?? $typ);
}
