<?php
/**
 * Manifest modulu Ochrana osobních údajů (GDPR). Čte ho MarmalElements\Moduly.
 * Výchozí stav VYPNUTO – modul sám nic na webu nemění, jen připraví stránku na tlačítko.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'gdpr',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 27,
    'vychozi'    => false,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Ochrana osobních údajů', 'marmal-breakdance-plus'),
            'popis' => __('Připraví stránku Zásady ochrany osobních údajů podle čl. 13 GDPR a zákona č. 110/2019 Sb.: správce z Patičky, účely podle toho, co web dělá, práva a stížnost u ÚOOÚ. Místa k doplnění jsou barevně zvýrazněná. Propojí se s Patičkou a Zásadami cookies.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-gdpr'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Gdpr\pocet_doplnit')) {
            return '';
        }
        $n = \MarmalElements\Gdpr\pocet_doplnit();
        /* translators: %d: počet míst */
        return $n > 0 ? sprintf(_n('Na stránce zásad zbývá doplnit %d místo.', 'Na stránce zásad zbývá doplnit %d míst.', $n, 'marmal-breakdance-plus'), $n) : '';
    },
);
