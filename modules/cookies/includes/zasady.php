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
    return esc_html(sprintf(__('%s [doplňte jméno nebo firmu, sídlo a IČO]', 'marmal-breakdance-plus'), wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES)));
}

/** Odrážkový seznam jako blok editoru; položky jsou hotové (escapované) HTML. */
function blok_ul(array $polozky): string
{
    $o = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
    foreach ($polozky as $p) {
        $o .= "<!-- wp:list-item -->\n<li>" . $p . "</li>\n<!-- /wp:list-item -->";
    }
    return $o . "</ul>\n<!-- /wp:list -->\n\n";
}

/** Odkaz do nového okna (oficiální návody prohlížečů, ÚOOÚ). */
function odkaz_ven(string $url, string $text): string
{
    return '<a href="' . esc_url($url, array('https')) . '" target="_blank" rel="noopener">' . esc_html($text) . '</a>';
}

/**
 * Obsah vzorové stránky Zásady cookies (Breakdance Plus 0.14.0).
 * Struktura: V kostce · Správce · Co jsou cookies · Kategorie a právní základ · Seznam · Poskytovatelé ·
 * Jak dlouho · Změna a odvolání · Prohlížeč · Vaše práva · Změny zásad.
 * Seznam cookies, poskytovatelé a doby se vkládají shortcody – aktualizují se samy.
 */
function vzor_zasad(): string
{
    // Název webu WordPress ukládá s HTML entitami (&amp;) – nejdřív převést zpět, pak escapovat jednou.
    $web      = esc_html(wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));
    $domena   = esc_html((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $soukromi = function_exists('MarmalElements\Paticka\zasady_url') ? (string) \MarmalElements\Paticka\zasady_url() : (string) get_privacy_policy_url();
    $odkaz    = $soukromi !== ''
        ? '<a href="' . esc_url($soukromi) . '">' . esc_html__('Zásadách ochrany osobních údajů', 'marmal-breakdance-plus') . '</a>'
        : esc_html__('Zásadách ochrany osobních údajů', 'marmal-breakdance-plus');
    $ikonka   = nastaveni()['icon'] !== 'off';

    // 1) V kostce
    $o  = blok_p(sprintf(
        /* translators: 1: název webu, 2: doména */
        esc_html__('Tato stránka vysvětluje, jaké soubory cookies používá web %1$s (%2$s), k čemu slouží a jak můžete svůj souhlas kdykoli změnit nebo odvolat.', 'marmal-breakdance-plus'),
        $web,
        $domena
    ));
    $o .= blok_h2(__('V kostce', 'marmal-breakdance-plus'));
    $o .= blok_ul(array(
        esc_html__('Nezbytné cookies potřebujeme, aby web fungoval – ty používáme bez souhlasu.', 'marmal-breakdance-plus'),
        esc_html__('Ostatní cookies (preferenční, analytické, marketingové) použijeme jen tehdy, když s nimi souhlasíte. Bez souhlasu se nenačtou.', 'marmal-breakdance-plus'),
        esc_html__('Svou volbu můžete kdykoli změnit odkazem „Nastavení cookies“ v patičce webu.', 'marmal-breakdance-plus'),
    ));

    // 2) Správce
    $o .= blok_h2(__('Kdo cookies zpracovává', 'marmal-breakdance-plus'));
    $o .= blok_p(sprintf(
        /* translators: 1: správce, 2: odkaz na zásady ochrany osobních údajů */
        esc_html__('Správcem je %1$s. Kontakty a podrobnosti o zpracování osobních údajů najdete v %2$s.', 'marmal-breakdance-plus'),
        spravce_text(),
        $odkaz
    ));

    // 3) Co jsou cookies
    $o .= blok_h2(__('Co jsou cookies', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Cookies jsou malé textové soubory, které web ukládá do vašeho prohlížeče. Podobně fungují i jiné technologie pro ukládání a čtení údajů ve vašem zařízení; níže je souhrnně označujeme jako cookies.', 'marmal-breakdance-plus'));
    $o .= blok_ul(array(
        '<strong>' . esc_html__('Relační cookies', 'marmal-breakdance-plus') . '</strong> ' . esc_html__('se smažou po zavření prohlížeče, trvalé cookies zůstávají po dobu uvedenou v tabulce (sloupec Platnost).', 'marmal-breakdance-plus'),
        '<strong>' . esc_html__('Vlastní cookies', 'marmal-breakdance-plus') . '</strong> ' . esc_html__('ukládá přímo tento web, cookies třetích stran ukládají poskytovatelé služeb, které web používá (sloupec Poskytovatel).', 'marmal-breakdance-plus'),
    ));

    // 4) Kategorie a právní základ
    $o .= blok_h2(__('Na jakém základě cookies používáme', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Nezbytné cookies potřebujeme, aby web fungoval – například aby si zapamatoval vaši volbu cookies. Používáme je bez souhlasu podle § 89 odst. 3 zákona č. 127/2005 Sb., o elektronických komunikacích.', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Preferenční, analytické a marketingové cookies používáme jen s vaším předchozím souhlasem (§ 89 odst. 3 zákona o elektronických komunikacích a čl. 6 odst. 1 písm. a) obecného nařízení o ochraně osobních údajů – GDPR). Bez souhlasu se nenačtou.', 'marmal-breakdance-plus'));

    // 5) Seznam cookies
    $o .= blok_h2(__('Jaké cookies používáme', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_seznam tlacitko="ne"]');
    $o .= blok_p(esc_html__('Tabulka se aktualizuje automaticky podle služeb, které web právě používá.', 'marmal-breakdance-plus'));

    // 6) Poskytovatelé
    $o .= blok_h2(__('Služby třetích stran', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Některé cookies ukládají poskytovatelé služeb, které web používá. Každý z nich zpracovává údaje podle svých zásad:', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_poskytovatele]');

    // 7) Jak dlouho
    $o .= blok_h2(__('Jak dlouho údaje uchováváme', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Platnost jednotlivých cookies je uvedena v tabulce výše.', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_souhlas]');

    // 8) Změna a odvolání
    $o .= blok_h2(__('Jak změnit nebo odvolat souhlas', 'marmal-breakdance-plus'));
    $o .= blok_p($ikonka
        ? esc_html__('Svou volbu můžete kdykoli změnit tlačítkem níže, odkazem „Nastavení cookies“ v patičce webu nebo ikonkou v rohu obrazovky. Odvolání souhlasu nemá vliv na zákonnost zpracování před jeho odvoláním.', 'marmal-breakdance-plus')
        : esc_html__('Svou volbu můžete kdykoli změnit tlačítkem níže nebo odkazem „Nastavení cookies“ v patičce webu. Odvolání souhlasu nemá vliv na zákonnost zpracování před jeho odvoláním.', 'marmal-breakdance-plus'));
    $o .= blok_sc('[marmal_cookies_nastaveni text="' . esc_attr__('Změnit nastavení cookies', 'marmal-breakdance-plus') . '"]');
    $o .= blok_p(esc_html__('Když souhlas odvoláte, web smaže cookies dané kategorie, které uložil pod svou doménou. Cookies, které třetí strany uložily na svých doménách, smažete v nastavení prohlížeče.', 'marmal-breakdance-plus'));

    // 9) Prohlížeč
    $o .= blok_h2(__('Cookies v nastavení prohlížeče', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Uložené cookies můžete kdykoli smazat a jejich ukládání omezit přímo v prohlížeči. Návody výrobců:', 'marmal-breakdance-plus'));
    $o .= blok_ul(array(
        odkaz_ven('https://support.google.com/chrome/answer/95647?hl=cs', 'Google Chrome'),
        odkaz_ven('https://support.mozilla.org/kb/clear-cookies-and-site-data-firefox', 'Mozilla Firefox'),
        odkaz_ven('https://support.apple.com/cs-cz/guide/safari/sfri11471/mac', 'Safari (Mac)'),
        odkaz_ven('https://support.microsoft.com/cs-cz/microsoft-edge/63947406-40ac-c3b8-57b9-2a946a29ae09', 'Microsoft Edge'),
    ));
    $o .= blok_p(esc_html__('Nastavení prohlížeče nenahrazuje volbu v liště cookies – souhlas s jednotlivými kategoriemi dáváte nebo odvoláváte přímo na webu. Když v prohlížeči zakážete i nezbytné cookies, některé části webu nemusí fungovat.', 'marmal-breakdance-plus'));

    // 10) Práva
    $o .= blok_h2(__('Vaše práva', 'marmal-breakdance-plus'));
    $o .= blok_p(sprintf(
        /* translators: %s: odkaz na zásady ochrany osobních údajů */
        esc_html__('Máte právo na přístup ke svým osobním údajům, jejich opravu nebo výmaz, omezení zpracování, právo vznést námitku a kdykoli odvolat souhlas. Podrobnosti najdete v %s.', 'marmal-breakdance-plus'),
        $odkaz
    ));
    $o .= blok_p(sprintf(
        /* translators: %s: odkaz na web ÚOOÚ */
        esc_html__('Pokud se domníváte, že zpracování vašich údajů porušuje právní předpisy, můžete podat stížnost u Úřadu pro ochranu osobních údajů, Pplk. Sochora 27, 170 00 Praha 7 (%s).', 'marmal-breakdance-plus'),
        odkaz_ven('https://uoou.gov.cz/', 'uoou.gov.cz')
    ));

    // 11) Změny
    $o .= blok_h2(__('Změny těchto zásad', 'marmal-breakdance-plus'));
    $o .= blok_p(esc_html__('Zásady upravujeme, když web začne používat jiné služby. Tabulka cookies a seznam poskytovatelů jsou vždy aktuální.', 'marmal-breakdance-plus'));
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
