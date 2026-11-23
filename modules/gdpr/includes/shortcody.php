<?php
/**
 * Modul Ochrana osobních údajů – shortcody. Části, které se mění (údaje o správci, kontakt,
 * odkaz na Zásady cookies, datum účinnosti), se na stránku vkládají shortcodem → změna v nastavení
 * nebo v Patičce se na stránce projeví sama.
 */

namespace MarmalElements\Gdpr;

if (!defined('ABSPATH')) {
    exit;
}

/** E-mail jako odkaz, nebo zvýrazněné „DOPLŇTE“. */
function email_html(): string
{
    $e = (string) nastaveni()['email'];
    return $e !== '' ? '<a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>' : doplnit(__('kontaktní e-mail pro ochranu údajů', 'marmal-breakdance-plus'));
}

/** [marmal_gdpr_spravce] – kdo je správce a jak ho kontaktovat. */
function sc_spravce($atts = array()): string
{
    $sp = spravce();
    $r  = array();
    $r[] = '<strong>' . nebo_doplnit($sp['firma'], __('jméno nebo firma správce', 'marmal-breakdance-plus')) . '</strong>';
    /* translators: %s: adresa sídla */
    $r[] = sprintf(esc_html__('sídlo: %s', 'marmal-breakdance-plus'), nebo_doplnit($sp['sidlo'], __('sídlo správce', 'marmal-breakdance-plus')));
    /* translators: %s: IČO */
    $r[] = sprintf(esc_html__('IČO: %s', 'marmal-breakdance-plus'), nebo_doplnit($sp['ico'], __('IČO', 'marmal-breakdance-plus')));
    if ($sp['dic'] !== '') {
        /* translators: %s: DIČ */
        $r[] = sprintf(esc_html__('DIČ: %s', 'marmal-breakdance-plus'), esc_html($sp['dic']));
    }
    if ($sp['rejstrik'] !== '') {
        $r[] = esc_html($sp['rejstrik']);
    }
    $k = array();
    /* translators: %s: e-mail */
    $k[] = sprintf(esc_html__('e-mail: %s', 'marmal-breakdance-plus'), email_html());
    if ($sp['telefon'] !== '') {
        /* translators: %s: telefon */
        $k[] = sprintf(esc_html__('telefon: %s', 'marmal-breakdance-plus'), '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $sp['telefon'])) . '">' . esc_html($sp['telefon']) . '</a>');
    }
    if ($sp['adresa'] !== '' && $sp['adresa'] !== $sp['sidlo']) {
        /* translators: %s: adresa */
        $k[] = sprintf(esc_html__('korespondenční adresa: %s', 'marmal-breakdance-plus'), esc_html($sp['adresa']));
    }
    if ($sp['ds'] !== '') {
        /* translators: %s: ID datové schránky */
        $k[] = sprintf(esc_html__('datová schránka: %s', 'marmal-breakdance-plus'), esc_html($sp['ds']));
    }
    $o  = '<p class="marmal-gdpr-spravce">' . implode('<br>', $r) . '</p>';
    $o .= '<p class="marmal-gdpr-kontakt">' . implode('<br>', $k) . '</p>';
    $pov = (string) nastaveni()['poverenec'];
    $o  .= '<p>' . ($pov !== ''
        /* translators: %s: kontakt na pověřence */
        ? sprintf(esc_html__('Pověřenec pro ochranu osobních údajů: %s', 'marmal-breakdance-plus'), esc_html($pov))
        : esc_html__('Pověřence pro ochranu osobních údajů jsme nejmenovali – podle čl. 37 GDPR tuto povinnost nemáme. S jakýmkoli dotazem se obracejte na kontakty výše.', 'marmal-breakdance-plus')) . '</p>';
    return $o;
}

/** [marmal_gdpr_kontakt] – e-mail pro uplatnění práv. */
function sc_kontakt($atts = array()): string
{
    return email_html();
}

/** [marmal_gdpr_cookies] – věta s odkazem na Zásady cookies (modul Cookies / Patička), jinak na odkaz v patičce. */
function sc_cookies($atts = array()): string
{
    $url = '';
    if (function_exists('MarmalElements\Cookies\zasady_url')) {
        $url = (string) \MarmalElements\Cookies\zasady_url();
    }
    if ($url === '' && function_exists('MarmalElements\Paticka\cookies_zasady_url')) {
        $url = (string) \MarmalElements\Paticka\cookies_zasady_url();
    }
    if ($url !== '') {
        return '<p>' . sprintf(
            /* translators: %s: odkaz na Zásady cookies */
            esc_html__('Jaké cookies web používá, k čemu slouží, kteří poskytovatelé (např. Google) při tom zpracovávají údaje a jak můžete souhlas změnit nebo odvolat, popisujeme samostatně v %s.', 'marmal-breakdance-plus'),
            '<a href="' . esc_url($url) . '">' . esc_html__('Zásadách cookies', 'marmal-breakdance-plus') . '</a>'
        ) . '</p>';
    }
    return '<p>' . esc_html__('Nezbytné cookies používáme bez souhlasu, ostatní (analytické, marketingové) jen s vaším souhlasem z cookie lišty. Svou volbu můžete kdykoli změnit odkazem „Nastavení cookies“ v patičce webu.', 'marmal-breakdance-plus') . '</p>';
}

/** [marmal_gdpr_ucinnost] – datum účinnosti z nastavení. */
function sc_ucinnost($atts = array()): string
{
    $d = (string) nastaveni()['ucinnost'];
    $t = $d !== '' ? strtotime($d . ' 12:00:00') : false;
    if ($t) {
        return esc_html(wp_date('j. n. Y', $t));
    }
    // Bez data v nastavení: datum zveřejnění stránky, na které shortcode je.
    $p = get_post();
    if ($p && $p->post_status === 'publish') {
        return esc_html(get_the_date('j. n. Y', $p));
    }
    return doplnit(__('datum účinnosti – doplní se samo při zveřejnění', 'marmal-breakdance-plus'));
}

/**
 * [marmal_gdpr_formular text=""] – krátká informace pod formulář (Breakdance Form → pod tlačítko,
 * nebo Text element). Souhlasové zaškrtávátko u kontaktního formuláře není potřeba – zpracování
 * stojí na vyřízení dotazu, stačí informovat.
 */
function sc_formular($atts = array()): string
{
    $a   = shortcode_atts(array('text' => ''), (array) $atts, 'marmal_gdpr_formular');
    $url = function_exists('MarmalElements\Paticka\zasady_url') ? (string) \MarmalElements\Paticka\zasady_url() : (string) get_privacy_policy_url();
    $txt = $a['text'] !== '' ? sanitize_text_field($a['text']) : __('Údaje z formuláře použijeme jen k vyřízení vašeho dotazu.', 'marmal-breakdance-plus');
    $o   = '<p class="marmal-gdpr-formular">' . esc_html($txt);
    if ($url !== '') {
        $o .= ' <a href="' . esc_url($url) . '">' . esc_html__('Jak chráníme osobní údaje', 'marmal-breakdance-plus') . '</a>';
    }
    return $o . '</p>';
}

/** [marmal_gdpr_prijemci] – komu údaje předáváme (podle nastavení modulu; chybějící jsou zvýrazněné). */
function sc_prijemci($atts = array()): string
{
    $s  = nastaveni();
    $ch = chybejici();
    $r  = array();
    $hodnota = function (string $k) use ($s, $ch): string {
        return isset($ch[$k]) ? doplnit($ch[$k][0]) : esc_html((string) $s[$k]);
    };
    $radek = function (string $popisek, string $html): string {
        return '<strong>' . esc_html($popisek) . ':</strong> ' . $html;
    };
    $r[] = $radek(__('Poskytovatel webhostingu', 'marmal-breakdance-plus'), $hodnota('hosting'));
    $r[] = $radek(__('Poskytovatel e-mailových služeb', 'marmal-breakdance-plus'), $hodnota('emailsluzba'));
    if (je_ucel('ucetnictvi') && !$s['ucetni_sami']) {
        $r[] = $radek(__('Účetní nebo daňový poradce', 'marmal-breakdance-plus'), $hodnota('ucetni'));
    }
    if (je_ucel('newsletter')) {
        $r[] = $radek(__('Služba pro rozesílání newsletteru', 'marmal-breakdance-plus'), $hodnota('newsletter_sluzba'));
    }
    if (je_woo()) {
        $r[] = $radek(__('Dopravci', 'marmal-breakdance-plus'), $hodnota('doprava'));
        $r[] = $radek(__('Platební brána', 'marmal-breakdance-plus'), $hodnota('platby'));
    }
    if (je_ucel('socialni')) {
        $r[] = $radek(__('Sociální sítě', 'marmal-breakdance-plus'), esc_html__('Meta Platforms Ireland Limited (Facebook, Instagram)', 'marmal-breakdance-plus'));
    }
    if (function_exists('MarmalElements\Cookies\aktivni_poskytovatele') && \MarmalElements\Cookies\aktivni_poskytovatele()) {
        $r[] = $radek(__('Analytické a marketingové služby', 'marmal-breakdance-plus'), esc_html__('jen s vaším souhlasem z cookie lišty – seznam je v Zásadách cookies', 'marmal-breakdance-plus'));
    }
    foreach (preg_split('/\r\n|\r|\n/', (string) $s['dalsi']) as $x) {
        if (trim($x) !== '') {
            $r[] = esc_html(trim($x));
        }
    }
    $r[] = esc_html__('Orgány veřejné moci (např. finanční úřad, soud), pokud nám to ukládá zákon.', 'marmal-breakdance-plus');
    return '<ul class="marmal-gdpr-prijemci"><li>' . implode('</li><li>', $r) . '</li></ul>';
}

add_action('init', function () {
    add_shortcode('marmal_gdpr_prijemci', __NAMESPACE__ . '\sc_prijemci');
    add_shortcode('marmal_gdpr_spravce', __NAMESPACE__ . '\sc_spravce');
    add_shortcode('marmal_gdpr_kontakt', __NAMESPACE__ . '\sc_kontakt');
    add_shortcode('marmal_gdpr_cookies', __NAMESPACE__ . '\sc_cookies');
    add_shortcode('marmal_gdpr_ucinnost', __NAMESPACE__ . '\sc_ucinnost');
    add_shortcode('marmal_gdpr_formular', __NAMESPACE__ . '\sc_formular');
});
