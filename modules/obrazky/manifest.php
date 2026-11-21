<?php
/**
 * Manifest modulu Obrázky. Čte ho MarmalElements\Moduly.
 *
 * Výchozí stav VYPNUTO: modul mění soubory v knihovně médií i data stránek – zapíná se vědomě.
 * Konfliktní pluginy (Converter for Media, EWWW, ShortPixel…) nejsou „starý plugin k převodu“,
 * proto 'konflikt' => null a upozornění jde přes 'info' (stejně jako modul Cookies).
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'obrazky',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 27,
    'vychozi'    => false,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Obrázky', 'marmal-breakdance-plus'),
            'popis' => __('JPG a PNG se při nahrání uloží jako WebP (nebo AVIF) v rozumné velikosti a bez metadat. Hromadný převod zmenší už nahrané obrázky, které web používá, a přepíše na ně odkazy v Breakdance – se zálohou a možností vrácení.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-obrazky'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Obrazky\podpora')) {
            return '';
        }
        $zpravy = array();
        if (!\MarmalElements\Obrazky\podpora()['webp']) {
            $zpravy[] = __('Server neumí ukládat WebP (chybí Imagick i GD s WebP) – modul nic nepřevádí.', 'marmal-breakdance-plus');
        }
        $jine = \MarmalElements\Obrazky\konkurence();
        if ($jine) {
            /* translators: %s: názvy pluginů */
            $zpravy[] = sprintf(__('Pozor: běží i %s – dva převaděče obrázků najednou se perou, jeden vypni.', 'marmal-breakdance-plus'), implode(', ', $jine));
        }
        if (function_exists('MarmalElements\Obrazky\fronta') && \MarmalElements\Obrazky\rozpracovano()) {
            $zpravy[] = __('Hromadný převod není dokončený – pokračuj v MarMal → Obrázky.', 'marmal-breakdance-plus');
        }
        return implode(' ', $zpravy);
    },
);
