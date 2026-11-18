<?php
/**
 * Modul Ochrana osobních údajů – nastavení, údaje o správci, zvýraznění míst k doplnění, stránky.
 */

namespace MarmalElements\Gdpr;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION     = 'marmal_bdp_gdpr';
const META_NAVRH = '_marmal_gdpr_navrh';   // stránka vytvořená modulem (koncept / zveřejněná)
const TRIDA      = 'marmal-gdpr-doplnit';  // třída zvýrazněných míst

/** Účely zpracování, ze kterých se skládá stránka: klíč => [název, popis pro administraci, výchozí]. */
function ucely(): array
{
    return array(
        'dotazy'     => array(__('Dotazy a poptávky', 'marmal-breakdance-plus'), __('kontaktní formulář, e-mail, telefon', 'marmal-breakdance-plus'), true),
        'smlouvy'    => array(__('Objednávky a smlouvy', 'marmal-breakdance-plus'), __('objednávky služeb nebo zboží, plnění smlouvy, reklamace', 'marmal-breakdance-plus'), true),
        'ucetnictvi' => array(__('Fakturace a účetnictví', 'marmal-breakdance-plus'), __('faktury a účetní doklady – povinnost ze zákona', 'marmal-breakdance-plus'), true),
        'akce'       => array(__('Přihlášky na akce, kurzy a workshopy', 'marmal-breakdance-plus'), __('rezervace termínu, seznam účastníků', 'marmal-breakdance-plus'), false),
        'foto'       => array(__('Fotografie a videa z akcí', 'marmal-breakdance-plus'), __('zveřejnění na webu a sociálních sítích – jen se souhlasem', 'marmal-breakdance-plus'), false),
        'newsletter' => array(__('Newsletter a obchodní sdělení', 'marmal-breakdance-plus'), __('zasílání novinek e-mailem', 'marmal-breakdance-plus'), false),
        'socialni'   => array(__('Profily na sociálních sítích', 'marmal-breakdance-plus'), __('Facebook, Instagram – komunikace a statistiky stránky', 'marmal-breakdance-plus'), false),
        'uchazeci'   => array(__('Uchazeči o zaměstnání', 'marmal-breakdance-plus'), __('životopisy a výběrová řízení', 'marmal-breakdance-plus'), false),
    );
}

function vychozi(): array
{
    $ucely = array();
    foreach (ucely() as $k => $u) {
        if ($u[2]) {
            $ucely[] = $k;
        }
    }
    return array(
        'email'      => '',
        'telefon'    => '',
        'adresa'     => '',   // korespondenční adresa, prázdné = sídlo z Patičky
        'ds'         => '',   // datová schránka
        'poverenec'  => '',   // kontakt na pověřence, prázdné = není jmenován
        'ucely'      => $ucely,
        'hosting'    => '',
        'emailsluzba' => '',
        'ucetni'     => '',
        'ucetni_sami' => false,
        'newsletter_sluzba' => '',
        'doprava'    => '',   // jen WooCommerce
        'platby'     => '',   // jen WooCommerce
        'dalsi'      => '',
        'ucinnost'   => '',   // Y-m-d
    );
}

function nastaveni(): array
{
    $s = get_option(OPTION, array());
    $s = is_array($s) ? $s : array();
    return array_merge(vychozi(), array_intersect_key($s, vychozi()));
}

/** @param mixed $vstup */
function sanitizovat($vstup): array
{
    $v = is_array($vstup) ? $vstup : array();
    $t = function (string $k, int $max) use ($v): string {
        $x = isset($v[$k]) ? sanitize_text_field((string) $v[$k]) : '';
        return function_exists('mb_substr') ? mb_substr($x, 0, $max) : substr($x, 0, $max);
    };
    $email = isset($v['email']) ? sanitize_email((string) $v['email']) : '';
    if (isset($v['email']) && trim((string) $v['email']) !== '' && $email === '') {
        add_settings_error(OPTION, 'email', __('E-mail nemá platný tvar – neuložen.', 'marmal-breakdance-plus'), 'error');
    }
    $datum = isset($v['ucinnost']) ? (string) $v['ucinnost'] : '';
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datum, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        $datum = '';
    }
    $dalsi = isset($v['dalsi']) ? sanitize_textarea_field((string) $v['dalsi']) : '';
    return array(
        'email'       => $email,
        'telefon'     => preg_replace('/[^0-9+ ()\/-]/', '', $t('telefon', 40)),
        'adresa'      => $t('adresa', 200),
        'ds'          => preg_replace('/[^a-z0-9]/', '', strtolower($t('ds', 10))),
        'poverenec'   => $t('poverenec', 200),
        'ucely'       => array_values(array_intersect(array_keys(ucely()), array_map('strval', (array) ($v['ucely'] ?? array())))),
        'hosting'     => $t('hosting', 120),
        'emailsluzba' => $t('emailsluzba', 120),
        'ucetni'      => $t('ucetni', 120),
        'ucetni_sami' => !empty($v['ucetni_sami']),
        'newsletter_sluzba' => $t('newsletter_sluzba', 120),
        'doprava'     => $t('doprava', 200),
        'platby'      => $t('platby', 200),
        'dalsi'       => function_exists('mb_substr') ? mb_substr($dalsi, 0, 1000) : substr($dalsi, 0, 1000),
        'ucinnost'    => $datum,
    );
}

function je_ucel(string $k): bool
{
    return in_array($k, (array) nastaveni()['ucely'], true);
}

/**
 * Zvýrazněné místo k doplnění. Styl je přímo v HTML, aby bylo vidět i v editoru a v Breakdance.
 * Na webu ho uvidí i návštěvník – nevyplněná místa tak nejde přehlédnout.
 */
function doplnit(string $co): string
{
    return '<mark class="' . TRIDA . '" style="background:#ffe066;color:#5c3c00;padding:0 .3em;border-radius:3px;font-weight:600">'
        /* translators: %s: co doplnit */
        . esc_html(sprintf(__('[DOPLŇTE: %s]', 'marmal-breakdance-plus'), $co)) . '</mark>';
}

/** Hodnota, nebo zvýrazněné „DOPLŇTE“, když chybí. Hodnota se escapuje. */
function nebo_doplnit(string $hodnota, string $co): string
{
    return trim($hodnota) !== '' ? esc_html($hodnota) : doplnit($co);
}

/** Údaje o správci: Patička (firma, sídlo, IČO, DIČ, rejstřík) + kontakty z tohoto modulu. */
function spravce(): array
{
    $p = function_exists('MarmalElements\Paticka\nastaveni') ? (array) \MarmalElements\Paticka\nastaveni() : array();
    $s = nastaveni();
    return array(
        'firma'    => (string) ($p['firma'] ?? ''),
        'sidlo'    => (string) ($p['sidlo'] ?? ''),
        'ico'      => (string) ($p['ico'] ?? ''),
        'dic'      => (string) ($p['dic'] ?? ''),
        'rejstrik' => function_exists('MarmalElements\Paticka\text') ? (string) \MarmalElements\Paticka\text('rejstrik') : '',
        'email'    => (string) $s['email'],
        'telefon'  => (string) $s['telefon'],
        'adresa'   => (string) $s['adresa'] !== '' ? (string) $s['adresa'] : (string) ($p['sidlo'] ?? ''),
        'ds'       => (string) $s['ds'],
    );
}

/**
 * Co je potřeba vyplnit: klíč => [popis, kde se to vyplňuje ('paticka' | 'gdpr')].
 * Stejný seznam zvýrazní shortcody na stránce i kontrolní seznam v administraci.
 */
function chybejici(): array
{
    $sp = spravce();
    $s  = nastaveni();
    $ch = array();
    $pridat = function (string $k, string $hodnota, string $popis, string $kde) use (&$ch) {
        if (trim($hodnota) === '') {
            $ch[$k] = array($popis, $kde);
        }
    };
    $pridat('firma', $sp['firma'], __('jméno nebo firma správce', 'marmal-breakdance-plus'), 'paticka');
    $pridat('sidlo', $sp['sidlo'], __('sídlo správce', 'marmal-breakdance-plus'), 'paticka');
    $pridat('ico', $sp['ico'], __('IČO', 'marmal-breakdance-plus'), 'paticka');
    $pridat('email', $s['email'], __('kontaktní e-mail pro ochranu údajů', 'marmal-breakdance-plus'), 'gdpr');
    $pridat('hosting', $s['hosting'], __('poskytovatel webhostingu', 'marmal-breakdance-plus'), 'gdpr');
    $pridat('emailsluzba', $s['emailsluzba'], __('poskytovatel e-mailu', 'marmal-breakdance-plus'), 'gdpr');
    if (je_ucel('ucetnictvi') && !$s['ucetni_sami']) {
        $pridat('ucetni', $s['ucetni'], __('účetní nebo daňový poradce', 'marmal-breakdance-plus'), 'gdpr');
    }
    if (je_ucel('newsletter')) {
        $pridat('newsletter_sluzba', $s['newsletter_sluzba'], __('služba pro rozesílání newsletteru', 'marmal-breakdance-plus'), 'gdpr');
    }
    if (je_woo()) {
        $pridat('doprava', $s['doprava'], __('dopravci', 'marmal-breakdance-plus'), 'gdpr');
        $pridat('platby', $s['platby'], __('platební brána', 'marmal-breakdance-plus'), 'gdpr');
    }
    return $ch;
}

function je_woo(): bool
{
    return class_exists('WooCommerce');
}

/** Dosavadní stránka zásad: vybraná v Patičce, jinak Nastavení → Soukromí. 0 = žádná. */
function stranka_zasad(): int
{
    if (function_exists('MarmalElements\Paticka\nastaveni')) {
        $id = (int) (\MarmalElements\Paticka\nastaveni()['zasady_stranka'] ?? 0);
        if ($id > 0 && get_post_type($id) === 'page' && get_post_status($id) !== 'trash') {
            return $id;
        }
    }
    $id = (int) get_option('wp_page_for_privacy_policy', 0);
    return ($id > 0 && get_post_type($id) === 'page' && get_post_status($id) !== 'trash') ? $id : 0;
}

/** Koncept vytvořený modulem (čeká na kontrolu a zveřejnění). 0 = není. */
function navrh(): int
{
    $q = get_posts(array(
        'post_type'      => 'page',
        'post_status'    => array('draft', 'pending'),
        'meta_key'       => META_NAVRH,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    return $q ? (int) $q[0] : 0;
}

/** Stránka zásad, kterou spravuje modul (koncept, jinak zveřejněná). 0 = žádná. */
function stranka_modulu(): int
{
    $id = navrh();
    if ($id > 0) {
        return $id;
    }
    $z = stranka_zasad();
    return ($z > 0 && get_post_meta($z, META_NAVRH, true)) ? $z : 0;
}

/** Počet míst k doplnění na stránce modulu: chybějící údaje (shortcody) + ručně ponechaná zvýraznění v textu. */
function pocet_doplnit(): int
{
    $id = stranka_modulu();
    if ($id <= 0) {
        return 0;
    }
    return count(chybejici()) + substr_count((string) get_post_field('post_content', $id), TRIDA);
}
