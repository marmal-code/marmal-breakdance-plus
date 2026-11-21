<?php
/**
 * Modul Obrázky – úprava jednoho souboru (společné pro převod při nahrání i hromadný převod).
 *
 * Postup: načíst přes wp_get_image_editor() (Imagick, jinak GD) → srovnat orientaci podle EXIF →
 * zmenšit na maximum (bez ořezu) → odstranit metadata (EXIF, GPS, XMP; profil ICC zůstane) →
 * uložit jako WebP / AVIF s kvalitou z nastavení.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Editor obrázku s vlastními metodami (odstranění metadat, uvolnění paměti).
 * Vlastní třídy se načtou až tady – rodičovské třídy WordPressu jinak ještě nemusí existovat.
 *
 * @return \WP_Image_Editor|\WP_Error
 */
function nacist_editor(string $soubor)
{
    require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
    require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
    require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
    require_once __DIR__ . '/editor-tridy.php';

    $pridat = function ($editory) {
        return array_merge(array(Editor_Imagick::class, Editor_GD::class), (array) $editory);
    };
    add_filter('wp_image_editors', $pridat, 99);
    $ed = wp_get_image_editor($soubor);
    remove_filter('wp_image_editors', $pridat, 99);
    return $ed;
}

/**
 * Orientace JPEG z EXIF bez rozšíření exif (čte jen hlavičku souboru).
 * @return int 1–8, 1 = správně
 */
function exif_orientace(string $soubor): int
{
    $fh = @fopen($soubor, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions,WordPress.PHP.NoSilencedErrors
    if (!$fh) {
        return 1;
    }
    $data = (string) fread($fh, 131072); // phpcs:ignore WordPress.WP.AlternativeFunctions
    fclose($fh); // phpcs:ignore WordPress.WP.AlternativeFunctions
    if (strlen($data) < 4 || substr($data, 0, 2) !== "\xFF\xD8") {
        return 1;
    }
    $pos = 2;
    $len = strlen($data);
    while ($pos + 4 <= $len) {
        if ($data[$pos] !== "\xFF") {
            return 1;
        }
        $znacka = ord($data[$pos + 1]);
        $delka  = unpack('n', substr($data, $pos + 2, 2))[1];
        if ($znacka === 0xE1 && substr($data, $pos + 4, 6) === "Exif\0\0") {
            $tiff  = $pos + 10;
            $intel = substr($data, $tiff, 2) === 'II';
            $u16   = function ($o) use ($data, $intel) {
                $b = substr($data, $o, 2);
                return strlen($b) === 2 ? unpack($intel ? 'v' : 'n', $b)[1] : 0;
            };
            $u32   = function ($o) use ($data, $intel) {
                $b = substr($data, $o, 4);
                return strlen($b) === 4 ? unpack($intel ? 'V' : 'N', $b)[1] : 0;
            };
            $ifd     = $tiff + $u32($tiff + 4);
            $polozek = $u16($ifd);
            for ($i = 0; $i < $polozek && $i < 200; $i++) {
                $e = $ifd + 2 + $i * 12;
                if ($u16($e) === 0x0112) {
                    $o = $u16($e + 8);
                    return ($o >= 1 && $o <= 8) ? $o : 1;
                }
            }
            return 1;
        }
        if ($znacka === 0xDA || $delka < 2) { // začátek obrazových dat – EXIF už nepřijde
            return 1;
        }
        $pos += 2 + $delka;
    }
    return 1;
}

/**
 * Srovná orientaci podle EXIF. S rozšířením exif to udělá WordPress sám (maybe_exif_rotate),
 * bez něj vlastní čtení hlavičky + stejné otočení jako v jádru WordPressu.
 *
 * @param \WP_Image_Editor $ed
 * @return true|\WP_Error
 */
function srovnat_orientaci($ed, string $soubor, string $mime)
{
    if ($mime !== 'image/jpeg') {
        return true;
    }
    if (function_exists('exif_read_data') && method_exists($ed, 'maybe_exif_rotate')) {
        $r = $ed->maybe_exif_rotate();
        return is_wp_error($r) ? $r : true;
    }
    $o = exif_orientace($soubor);
    $r = true;
    switch ($o) {
        case 2:
            $r = $ed->flip(false, true);
            break;
        case 3:
            $r = $ed->flip(true, true);
            break;
        case 4:
            $r = $ed->flip(true, false);
            break;
        case 5:
            $r = $ed->rotate(90);
            if (!is_wp_error($r)) {
                $r = $ed->flip(false, true);
            }
            break;
        case 6:
            $r = $ed->rotate(270);
            break;
        case 7:
            $r = $ed->rotate(90);
            if (!is_wp_error($r)) {
                $r = $ed->flip(true, false);
            }
            break;
        case 8:
            $r = $ed->rotate(90);
            break;
    }
    if (!is_wp_error($r) && $o > 1 && method_exists($ed, 'marmal_orientace_ok')) {
        $ed->marmal_orientace_ok();
    }
    return is_wp_error($r) ? $r : true;
}

/**
 * Převede jeden soubor do WebP / AVIF.
 *
 * @param string $zdroj    absolutní cesta ke zdroji (JPG / PNG)
 * @param string $cil      absolutní cesta k výsledku (včetně přípony .webp / .avif)
 * @param string $mime_cil image/webp | image/avif
 * @param array  $s        nastavení (kvalita, max)
 * @return array{cesta:string,sirka:int,vyska:int,velikost:int,zmenseno:bool}|\WP_Error
 */
function prevest_soubor(string $zdroj, string $cil, string $mime_cil, array $s)
{
    wp_raise_memory_limit('image');

    $mime_zdroj = (string) wp_get_image_mime($zdroj);
    if (!prevadet_mime($mime_zdroj)) {
        return new \WP_Error('marmal_obrazky_typ', sprintf('Soubor %s není JPG ani PNG.', wp_basename($zdroj)));
    }

    $ed = nacist_editor($zdroj);
    if (is_wp_error($ed)) {
        return $ed;
    }

    $r = srovnat_orientaci($ed, $zdroj, $mime_zdroj);
    if (is_wp_error($r)) {
        uvolnit($ed);
        return $r;
    }

    $zmenseno = false;
    $max      = (int) ($s['max'] ?? 0);
    $rozmer   = $ed->get_size();
    if ($max > 0 && max((int) $rozmer['width'], (int) $rozmer['height']) > $max) {
        $r = $ed->resize($max, $max, false);
        if (is_wp_error($r)) {
            uvolnit($ed);
            return $r;
        }
        $zmenseno = true;
    }

    if (method_exists($ed, 'marmal_odstranit_metadata')) {
        $ed->marmal_odstranit_metadata();
    }

    $ed->set_quality(max(1, min(100, (int) ($s['kvalita'] ?? 80))));
    $ulozeno = $ed->save($cil, $mime_cil);
    uvolnit($ed);

    if (is_wp_error($ulozeno)) {
        return $ulozeno;
    }
    $cesta = (string) ($ulozeno['path'] ?? $cil);
    clearstatcache(true, $cesta);
    if (!is_file($cesta) || filesize($cesta) < 1) {
        return new \WP_Error('marmal_obrazky_prazdny', sprintf('Převod %s vytvořil prázdný soubor.', wp_basename($zdroj)));
    }
    return array(
        'cesta'    => $cesta,
        'sirka'    => (int) ($ulozeno['width'] ?? 0),
        'vyska'    => (int) ($ulozeno['height'] ?? 0),
        'velikost' => (int) filesize($cesta),
        'zmenseno' => $zmenseno,
    );
}

/** Uvolní paměť editoru (Imagick drží desítky MB u velkých PNG). */
function uvolnit($ed): void
{
    if (is_object($ed) && method_exists($ed, 'marmal_uvolnit')) {
        $ed->marmal_uvolnit();
    }
}
