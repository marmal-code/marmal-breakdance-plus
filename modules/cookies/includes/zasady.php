<?php
/**
 * Modul Cookies – vzorová stránka „Zásady cookies“ (tlačítko v MarMal → Cookies → Texty).
 *
 * Stránka se založí jako KONCEPT – text je vzor, správce ho před zveřejněním zkontroluje.
 * Tabulka cookies je shortcode [marmal_cookies_seznam] → aktualizuje se sama.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

/** Odstavec / nadpis / shortcode jako bloky editoru (stránka jde upravit v editoru i otevřít v Breakdance). */
function blok_p(string $html): string
{
    return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function blok_h2(string $text): string
{
    return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html($text) . "</h2>\n<!-- /wp:heading -->\n\n";
}

function blok_sc(string $sc): string
{
    return "<!-- wp:shortcode -->\n" . $sc . "\n<!-- /wp:shortcode -->\n\n";
}

/** Správce pro úvod stránky – z modulu Patička (údaje o podnikateli), jinak název webu. */
function spravce_text(): string
{
    if (function_exists('MarmalElements\Paticka\udaje')) {
        // Jméno / firma, sídlo a IČO (DIČ a zápis v rejstříku sem nepatří).
        $u = array_intersect_key((array) \MarmalElements\Paticka\udaje(), array_flip(array('firma', 'sidlo', 'ico')));
        if (isset($u['firma'])) {
            return esc_html(implode(', ', array_map('strval', $u)));
        }
    }
    /* translators: %s: název webu */
    return esc_html(sprintf(__('%s [doplňte jméno nebo firmu, sídlo a IČO]', 'marmal-breakdance-plus'), get_bloginfo('name')));
}

/** Obsah vzorové stránky Zásady cookies. */
function vzor_zasad(): string
{
    $web      = esc_html((string) get_bloginfo('name'));
    $domena   = esc_html((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $soukromi = function_exists('MarmalElements\Paticka\zasady_url') ? (string) \MarmalElements\Paticka\zasady_url() : (string) get_privacy_policy_url();
    $odkaz    = $soukromi !== ''
        ? '<a href="' . esc_url($soukromi) . '">' . esc_html__('Zásadách ochrany osobních údajů', 'marmal-breakdance-plus') . '</a>'
        : esc_html__('Zásadách ochrany osobních údajů', 'marmal-breakdance-plus');

    $o  = blok_p(sprintf(
        /* translators: 1: název webu, 2: doména */
        esc_html__('Tato stránka vysvětluje, jaké soubory cookies používá web %1$s (%2$s), k čemu slouží a jak můžete svůj souhlas kdykoli změnit nebo odvolat.', 'marmal-breakdance-plus'),
        $web,
        $domena
    ));

    $o .= blok_h2(__('Kdo cookies zpracovává', 'marmal-breakdance-plus'));
    $o .= blok_p(sprintf(
        /* translators: 1: správce, 2: odkaz na zásady ochrany osobních údajů */
        esc_html__('Správcem je %1$s. Kontakty a podrobnosti o zpracování osobních údajů najdete v %2$s.', 'marmal-breakdance-plus'),
        spravce_text(),
        $odkaz
    ));

    $o .= blok_h2(__('Co jsou cookies', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Cookies jsou malé textové soubory, které web ukládá do vašeho prohlížeče. Podobně fungují i jiné technologie pro ukládání a čtení údajů ve vašem zařízení; níže je souhrnně označujeme jako cookies.', 'marmal-breakdance-plus'));

    $o .= blok_h2(__('Na jakém základě cookies používáme', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Nezbytné cookies potřebujeme, aby web fungoval – například aby si zapamatoval vaši volbu cookies. Používáme je bez souhlasu podle § 89 odst. 3 zákona č. 127/2005 Sb., o elektronických komunikacích.', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Preferenční, analytické a marketingové cookies používáme jen s vaším předchozím souhlasem (§ 89 odst. 3 zákona o elektronických komunikacích a čl. 6 odst. 1 písm. a) obecného nařízení o ochraně osobních údajů – GDPR). Bez souhlasu se nenačtou.', 'marmal-breakdance-plus'));

    $o .= blok_h2(__('Jaké cookies používáme', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_seznam]');
    $o .= blok_p(esc_html__('Tabulka se aktualizuje automaticky podle služeb, které web právě používá.', 'marmal-breakdance-plus'));

    $o .= blok_h2(__('Jak změnit nebo odvolat souhlas', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Svou volbu můžete kdykoli změnit tlačítkem níže nebo odkazem „Nastavení cookies“ v patičce webu. Odvolání souhlasu nemá vliv na zákonnost zpracování před jeho odvoláním.', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_nastaveni]');

    $o .= blok_h2(__('Cookies třetích stran', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Některé cookies ukládají poskytovatelé služeb uvedení v tabulce (například Google). Ti mohou údaje zpracovávat i mimo Evropskou unii; podrobnosti najdete v jejich zásadách ochrany soukromí.', 'marmal-breakdance-plus'));

    $o .= blok_h2(__('Jak cookies smazat v prohlížeči', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Uložené cookies můžete kdykoli smazat a jejich ukládání omezit v nastavení prohlížeče (obvykle Nastavení → Soukromí a zabezpečení → Cookies). Když zakážete i nezbytné cookies, některé části webu nemusí fungovat.', 'marmal-breakdance-plus'));

    $o .= blok_h2(__('Platnost těchto zásad', 'marmal-breakdance-plus'));
    /* translators: %s: datum */
    $o .= blok_p(esc_html(sprintf(__('Tyto zásady platí od %s.', 'marmal-breakdance-plus'), wp_date('j. n. Y'))));
    return $o;
}

/** Založí koncept stránky Zásady cookies a nastaví ji v modulu. Vrací ID nebo WP_Error. */
function vytvorit_zasady()
{
    $id = wp_insert_post(array(
        'post_type'    => 'page',
        'post_status'  => 'draft',
        'post_title'   => __('Zásady cookies', 'marmal-breakdance-plus'),
        'post_name'    => 'zasady-cookies',
        'post_content' => vzor_zasad(),
    ), true);
    if (is_wp_error($id)) {
        return $id;
    }
    update_option(OPTION, array_merge(nastaveni(), array('odkaz_stranka' => (int) $id)));
    return (int) $id;
}

add_action('admin_post_marmal_cookies_zasady', function () {
    if (!current_user_can('manage_options') || !current_user_can('publish_pages')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_cookies_zasady');
    $existuje = stranka_zasad();
    if ($existuje > 0) {
        wp_safe_redirect(add_query_arg(array('page' => 'marmal-cookies', 'tab' => 'texty', 'zprava' => 'zasady-existuji'), admin_url('admin.php')));
        exit;
    }
    $id = vytvorit_zasady();
    wp_safe_redirect(add_query_arg(array('page' => 'marmal-cookies', 'tab' => 'texty', 'zprava' => is_wp_error($id) ? 'zasady-chyba' : 'zasady-vytvoreny'), admin_url('admin.php')));
    exit;
});
