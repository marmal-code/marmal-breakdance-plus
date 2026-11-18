<?php
/**
 * Manifest modulu Patička. Čte ho MarmalElements\Moduly.
 *
 * Modul NENÍ zamčený: element „Marmal – Patička“ (elements/Paticka_Webu) zůstane v builderu
 * i při vypnutém modulu, jen nic nevykreslí (správce vidí upozornění) – vypnutí = patička zmizí. Funguje i bez Breakdance (shortcody); dynamická data
 * se zaregistrují jen tehdy, když Breakdance běží.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'paticka',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 30,
    // Na webu, kde se modul objeví poprvé, je zapnutý – sám nic nezobrazí, dokud se nevloží do patičky.
    'vychozi'    => true,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Patička', 'marmal-breakdance-plus'),
            'popis' => __('Copyright s rokem spuštění webu (© 2019–2026), podpis autora webu s odkazem, údaje o podnikateli podle § 435 občanského zákoníku a odkazy na ochranu osobních údajů a nastavení cookies. Vkládá se elementem Marmal – Patička (vzhled odkazů, písma a barev v Design tabu), shortcodem nebo dynamickými daty.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-paticka'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Paticka\copyright')) {
            return '';
        }
        return \MarmalElements\Paticka\copyright();
    },
);
