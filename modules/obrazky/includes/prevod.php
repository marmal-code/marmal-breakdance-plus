<?php
/**
 * Modul Obrázky – A) převod při nahrání.
 *
 * Filtr wp_handle_upload běží, když soubor už leží v uploads a příloha ještě nevznikla – platí pro
 * knihovnu médií, builder Breakdance, REST API i media_sideload_image(). JPG / PNG se tu uloží
 * rovnou jako WebP (AVIF) v nastavené velikosti, WordPress pak z něj vytvoří zmenšeniny ve stejném
 * formátu. Když cokoli selže, nahrání pokračuje s původním souborem a chyba jde do logu modulu.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_handle_upload', __NAMESPACE__ . '\pri_nahrani', 20, 2);
add_filter('wp_handle_sideload', __NAMESPACE__ . '\pri_nahrani', 20, 2); // starší / jiné cesty; podruhé se WebP přeskočí

/**
 * @param mixed $nahrani ['file' => cesta, 'url' => adresa, 'type' => MIME]
 * @param mixed $kontext 'upload' | 'sideload'
 * @return mixed
 */
function pri_nahrani($nahrani, $kontext = 'upload')
{
    if (!is_array($nahrani) || !empty($nahrani['error']) || empty($nahrani['file']) || empty($nahrani['url'])) {
        return $nahrani;
    }
    // Jen nahrávání přihlášených uživatelů s právem nahrávat (ne soubory z veřejných formulářů).
    if (!current_user_can('upload_files')) {
        return $nahrani;
    }
    $mime_cil = cilovy_mime();
    if ($mime_cil === '') {
        return $nahrani;
    }
    $s      = nastaveni();
    $soubor = (string) $nahrani['file'];
    if (!is_file($soubor)) {
        return $nahrani;
    }
    // Skutečný typ podle obsahu, ne podle přípony (GIF, SVG, ICO, WebP, AVIF se nepřevádějí).
    $mime = (string) wp_get_image_mime($soubor);
    if (!prevadet_mime($mime, (bool) $s['png'])) {
        return $nahrani;
    }
    $velikost = (int) filesize($soubor);
    if ($s['min_kb'] > 0 && $velikost < $s['min_kb'] * 1024) {
        return $nahrani;
    }

    $slozka = dirname($soubor);
    $zaklad = pathinfo(wp_basename($soubor), PATHINFO_FILENAME);
    $nazev  = wp_unique_filename($slozka, $zaklad . '.' . pripona($mime_cil));
    $cil    = trailingslashit($slozka) . $nazev;

    $r = prevest_soubor($soubor, $cil, $mime_cil, $s);
    if (is_wp_error($r)) {
        if (is_file($cil)) {
            wp_delete_file($cil);
        }
        /* translators: 1: název souboru, 2: chyba */
        zapsat_log(sprintf(__('Nahrání %1$s: převod se nepovedl, zůstal původní soubor (%2$s).', 'marmal-breakdance-plus'), wp_basename($soubor), $r->get_error_message()));
        return $nahrani;
    }

    // Malé PNG s málo barvami bývají ve WebP větší – pak nechat originál.
    if ($r['velikost'] >= $velikost && !$r['zmenseno']) {
        wp_delete_file($r['cesta']);
        return $nahrani;
    }

    if (empty($s['ponechat'])) {
        wp_delete_file($soubor);
    }

    $url = (string) $nahrani['url'];
    $url = substr($url, 0, (int) strrpos($url, '/') + 1) . wp_basename($r['cesta']);

    return array_merge($nahrani, array(
        'file' => $r['cesta'],
        'url'  => $url,
        'type' => $mime_cil,
    ));
}

/**
 * Doplňkové filtry: stejné maximum pro „velké obrázky“, kvalita WebP / AVIF a zmenšeniny JPG / PNG
 * vzniklé jinou cestou (regenerace náhledů, jiné pluginy) taky ve WebP.
 */
add_filter('big_image_size_threshold', function ($prah) {
    $s = nastaveni();
    return (cilovy_mime() !== '' && $s['max'] > 0) ? (int) $s['max'] : $prah;
}, 20);

add_filter('wp_editor_set_quality', function ($kvalita, $mime = '') {
    if (in_array($mime, array('image/webp', 'image/avif'), true) && cilovy_mime() !== '') {
        return (int) nastaveni()['kvalita'];
    }
    return $kvalita;
}, 20, 2);

add_filter('image_editor_output_format', function ($formaty) {
    $mime_cil = cilovy_mime();
    if ($mime_cil === '') {
        return $formaty;
    }
    $formaty = is_array($formaty) ? $formaty : array();
    $formaty['image/jpeg'] = $mime_cil;
    if (nastaveni()['png']) {
        $formaty['image/png'] = $mime_cil;
    }
    return $formaty;
}, 20);
