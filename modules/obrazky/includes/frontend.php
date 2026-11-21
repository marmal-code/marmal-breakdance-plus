<?php
/**
 * Modul Obrázky – výstup stránky (od 0.17.0): sizes="auto" u lazy obrázků.
 *
 * Breakdance vypisuje sizes podle šířky originálu („(max-width: 1920px) 100vw, 1920px“), takže prohlížeč
 * stahuje mnohem větší soubor, než jaký se zobrazí. sizes="auto, …" mu dovolí vybrat soubor podle skutečné
 * šířky obrázku na stránce. WordPress to od 6.7 dělá u obrázků v obsahu příspěvků, Breakdance má vlastní HTML.
 *
 *  - jen <img> s loading="lazy", srcset a sizes bez „auto“ (u ostatních auto prohlížeč nesmí použít; často LCP)
 *  - auto potřebuje rozměry: chybí-li width i height, doplní se z metadat přílohy (pomůže i proti CLS);
 *    když rozměry zjistit nejdou, obrázek zůstane beze změny (auto bez rozměrů by mohl obrázek roztáhnout)
 *  - doplněné rozměry nesmí změnit vzhled: :where(img[data-marmal-rozmery]){height:auto} má nulovou specifičnost,
 *    přebije jen výšku z atributu, ne CSS webu
 *  - prohlížeče, které auto neumí, ho ignorují a použijí zbytek atributu
 *  - output buffer od template_redirect, ne v administraci, builderu, REST, AJAXu ani cronu; běží před uložením
 *    do page cache (WP Super Cache má vlastní vnější buffer), takže návštěvníkům se posílá už hotové HTML
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

const MAX_HLEDANI = 30; // nejvýš 30 dotazů na přílohu podle adresy na jednu stránku

/** Běží úprava HTML na tomto požadavku? */
function bezi_frontend(): bool
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
        && !is_customize_preview() && !empty(nastaveni()['sizes_auto']);
    return $r;
}

/** Hodnota atributu z řetězce atributů tagu, nebo null. */
function img_atribut(string $tag, string $nazev): ?string
{
    if (preg_match('/\s' . preg_quote($nazev, '/') . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>"\']+))/i', $tag, $m)) {
        $v = isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[2]) && $m[2] !== '' ? $m[2] : $m[1]);
        return html_entity_decode($v, ENT_QUOTES, 'UTF-8');
    }
    return null;
}

/** Nahradí (nebo připíše) atribut v tagu <img …>. Hodnota se escapuje. */
function img_nastavit(string $tag, string $nazev, string $hodnota): string
{
    $novy = ' ' . $nazev . '="' . esc_attr($hodnota) . '"';
    $re   = '/\s' . preg_quote($nazev, '/') . '\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>"\']+)/i';
    if (preg_match($re, $tag)) {
        return (string) preg_replace_callback($re, function () use ($novy) {
            return $novy;
        }, $tag, 1);
    }
    return (string) preg_replace_callback('#\s*/?>$#', function ($k) use ($novy) {
        return $novy . $k[0];
    }, $tag, 1);
}

/** Obsahuje sizes slovo auto? */
function ma_auto(string $sizes): bool
{
    foreach (explode(',', $sizes) as $c) {
        if (strtolower(trim($c)) === 'auto') {
            return true;
        }
    }
    return false;
}

/**
 * Rozměry souboru v src podle metadat přílohy: [šířka, výška] nebo null.
 * Příloha podle třídy wp-image-123, jinak podle adresy (attachment_url_to_postid, s limitem na stránku).
 */
function rozmery_obrazku(string $tag, string $src, int &$hledano): ?array
{
    static $cache = array();
    $src = (string) strtok($src, '?#');
    if ($src === '') {
        return null;
    }
    if (array_key_exists($src, $cache)) {
        return $cache[$src];
    }
    $id = 0;
    if (preg_match('/\bwp-image-(\d+)\b/', (string) img_atribut($tag, 'class'), $m)) {
        $id = (int) $m[1];
    }
    if ($id < 1) {
        $uploads = wp_get_upload_dir();
        $baseurl = set_url_scheme((string) $uploads['baseurl']);
        if (stripos(set_url_scheme($src), $baseurl) !== 0 || $hledano >= MAX_HLEDANI) {
            return $cache[$src] = null; // jen soubory z knihovny médií
        }
        $hledano++;
        // Velikost „-300x200“ v názvu → originál (attachment_url_to_postid hledá _wp_attached_file).
        $original = (string) preg_replace('/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $src);
        $id       = (int) attachment_url_to_postid($original);
        if ($id < 1 && $original !== $src && $hledano < MAX_HLEDANI) {
            $hledano++;
            $id = (int) attachment_url_to_postid($src); // soubor, který jen vypadá jako velikost
        }
    }
    $meta = $id > 0 ? wp_get_attachment_metadata($id) : false;
    if (!is_array($meta) || empty($meta['width']) || empty($meta['height'])) {
        return $cache[$src] = null; // např. SVG bez rozměrů
    }
    $soubor = wp_basename($src);
    if ($soubor === wp_basename((string) ($meta['file'] ?? ''))) {
        return $cache[$src] = array((int) $meta['width'], (int) $meta['height']);
    }
    foreach ((array) ($meta['sizes'] ?? array()) as $v) {
        if (is_array($v) && ($v['file'] ?? '') === $soubor && !empty($v['width']) && !empty($v['height'])) {
            return $cache[$src] = array((int) $v['width'], (int) $v['height']);
        }
    }
    return $cache[$src] = null; // soubor není mezi velikostmi (např. původní velký „original_image“)
}

/**
 * Upraví HTML stránky. Vrací [html, počet obrázků se sizes=auto, počet doplněných rozměrů].
 */
function sizes_auto_html(string $html): array
{
    if (stripos($html, 'srcset') === false || stripos($html, 'lazy') === false) {
        return array($html, 0, 0);
    }
    $upraveno = 0;
    $rozmery  = 0;
    $hledano  = 0;
    $html = (string) preg_replace_callback('#<img\b[^>]*>#i', function ($m) use (&$upraveno, &$rozmery, &$hledano) {
        $tag = $m[0];
        if (strtolower(trim((string) img_atribut($tag, 'loading'))) !== 'lazy') {
            return $tag; // bez lazy loadingu nic (auto nesmí, a často jde o LCP obrázek)
        }
        $sizes  = img_atribut($tag, 'sizes');
        $srcset = img_atribut($tag, 'srcset');
        if ($sizes === null || trim($sizes) === '' || $srcset === null || trim($srcset) === '' || ma_auto($sizes)) {
            return $tag;
        }
        $w = img_atribut($tag, 'width');
        $h = img_atribut($tag, 'height');
        $ma_w = $w !== null && preg_match('/^\d+$/', trim($w));
        $ma_h = $h !== null && preg_match('/^\d+$/', trim($h));
        if (!$ma_w && !$ma_h) {
            $r = rozmery_obrazku($tag, (string) img_atribut($tag, 'src'), $hledano);
            if ($r === null) {
                return $tag;
            }
            $tag = img_nastavit($tag, 'width', (string) $r[0]);
            $tag = img_nastavit($tag, 'height', (string) $r[1]);
            $tag = img_nastavit($tag, 'data-marmal-rozmery', '1');
            $rozmery++;
        } elseif (!$ma_w || !$ma_h) {
            return $tag; // jen jeden rozměr – nesahat
        }
        $upraveno++;
        return img_nastavit($tag, 'sizes', 'auto, ' . trim($sizes));
    }, $html);

    if ($rozmery > 0) {
        // Nulová specifičnost: přebije jen výšku z atributu height, CSS webu má vždy přednost.
        $html = (string) preg_replace('#</head>#i', '<style id="marmal-obrazky-rozmery">:where(img[data-marmal-rozmery]){height:auto}</style>$0', $html, 1);
    }
    return array($html, $upraveno, $rozmery);
}

// Priorita 2: buffer uvnitř bufferů modulů Cookies (0) a Fonty (1) – HTML upraví jako první.
add_action('template_redirect', function () {
    if (!bezi_frontend() || is_feed() || is_robots() || is_trackback() || is_embed()) {
        return;
    }
    ob_start(function ($html) {
        if (!is_string($html) || stripos($html, '<html') === false) {
            return $html;
        }
        return sizes_auto_html($html)[0];
    });
}, 2);
