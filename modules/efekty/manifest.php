<?php
/**
 * Manifest modulu Efekty. Čte ho MarmalElements\Moduly.
 * Texty jsou ve funkci, aby se překládaly až při zobrazení (ne před načtením překladů).
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'efekty',
    'soubor'     => 'modul.php',
    'breakdance' => false, // funguje i bez Breakdance
    'zamceny'    => false,
    'poradi'     => 20,

    // Výchozí stav na webu, kde se modul objeví poprvé: zapnuto tam, kde se efekty už používaly.
    'vychozi'    => function () {
        $aktivni = array_merge(
            (array) get_option('active_plugins', array()),
            array_keys((array) get_site_option('active_sitewide_plugins', array()))
        );
        return null !== get_option('mm_effects_settings', null)
            || in_array('marmal-effects/marmal-effects.php', $aktivni, true);
    },

    // Starý samostatný plugin – když běží, modul se nenačte (duplicitní funkce = pád webu).
    'konflikt'   => array(
        'konstanta' => 'MM_EFFECTS_FILE',
        'plugin'    => 'marmal-effects/marmal-effects.php',
        'nazev'     => 'MarMal Effects',
    ),

    'texty'      => function () {
        return array(
            'nazev' => __('Efekty', 'marmal-breakdance-plus'),
            'popis' => __('Knihovna CSS efektů (rámečky, tlačítka, karty, obrázky, hero, animovaná pozadí, animace při scrollu). Efekt se zapne třídou mm-… v poli Classes. Nahrazuje plugin MarMal Effects.', 'marmal-breakdance-plus'),
        );
    },

    // Odkazy a doplňující informace na kartě (jen když je modul načtený).
    'odkazy'     => function () {
        return array(
            __('Galerie efektů', 'marmal-breakdance-plus') => admin_url('admin.php?page=mm-effects'),
            __('Nastavení', 'marmal-breakdance-plus')      => admin_url('admin.php?page=mm-effects-settings'),
        );
    },
    'info'       => function () {
        if (!function_exists('mm_effects_zapnute_kategorie')) {
            return '';
        }
        $vse     = count(mm_effects_kategorie());
        $zapnute = mm_effects_zapnute_kategorie();
        /* translators: 1: zapnuté kategorie, 2: všechny kategorie */
        return sprintf(__('Načítá se %1$d z %2$d kategorií efektů.', 'marmal-breakdance-plus'), null === $zapnute ? $vse : count($zapnute), $vse);
    },
);
