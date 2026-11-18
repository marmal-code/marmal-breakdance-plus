<?php
/**
 * Modul Cookies – stránka MarMal → Cookies.
 *
 * Nastavení jde přes options.php (register_setting: nonce + manage_options hlídá WordPress),
 * akce (revize, export, zapomenutí nalezených služeb) přes admin-post s nonce a manage_options.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA = 'marmal-cookies';

add_action('admin_init', function () {
    register_setting('marmal_cookies', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    add_submenu_page('marmal', __('Cookies', 'marmal-breakdance-plus'), __('Cookies', 'marmal-breakdance-plus'), 'manage_options', STRANKA, __NAMESPACE__ . '\stranka');
}, 20);

// Upozornění na druhý cookie plugin (dvě lišty) – jen správcům.
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    $jine = konkurence();
    if (!$jine) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>MarMal – Cookies:</strong> ';
    /* translators: %s: názvy pluginů */
    echo esc_html(sprintf(__('Na webu běží i %s. Vypni ho v Pluginech, jinak budou návštěvníci vidět dvě cookie lišty.', 'marmal-breakdance-plus'), implode(', ', $jine)));
    echo '</p></div>';
});

function radio(string $klic, array $volby): void
{
    $s = nastaveni();
    echo '<div class="mmck-seg" role="radiogroup">';
    foreach ($volby as $v => $text) {
        printf(
            '<label><input type="radio" name="%1$s[%2$s]" value="%3$s"%4$s> <span>%5$s</span></label>',
            esc_attr(OPTION),
            esc_attr($klic),
            esc_attr((string) $v),
            checked((string) $s[$klic], (string) $v, false),
            esc_html($text)
        );
    }
    echo '</div>';
}

function pole_text(string $klic, string $placeholder, bool $area = false): void
{
    $s = nastaveni();
    if ($area) {
        printf('<textarea id="mmck-%1$s" name="%2$s[%1$s]" rows="3" placeholder="%3$s">%4$s</textarea>', esc_attr($klic), esc_attr(OPTION), esc_attr($placeholder), esc_textarea((string) $s[$klic]));
    } else {
        printf('<input type="text" class="regular-text" id="mmck-%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s">', esc_attr($klic), esc_attr(OPTION), esc_attr((string) $s[$klic]), esc_attr($placeholder));
    }
}

function admin_css(): string
{
    return '.mmck{max-width:1000px}.mmck-tabs{display:flex;flex-wrap:wrap;gap:4px;border-bottom:1px solid #c3c4c7;margin:16px 0 0}'
        . '.mmck-tabs button{font:600 14px/1 inherit;padding:12px 14px;border:0;border-bottom:3px solid transparent;background:none;cursor:pointer;color:#50575e}'
        . '.mmck-tabs button[aria-selected=true]{color:#1d2327;border-bottom-color:#00a862}'
        . '.mmck-tp{display:none;background:#fff;border:1px solid #dcdcde;border-top:0;padding:8px 20px 16px}.mmck-tp.on{display:block}'
        . '.mmck .form-table th{width:220px}.mmck textarea{width:100%;max-width:640px}.mmck .regular-text{width:100%;max-width:520px}'
        . '.mmck-seg{display:flex;flex-wrap:wrap;gap:6px}.mmck-seg label{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #c3c4c7;border-radius:6px;background:#f6f7f7}'
        . '.mmck-seg label:has(input:checked){border-color:#1d2327;background:#fff;box-shadow:0 0 0 1px #1d2327}'
        . '.mmck-barvy{display:flex;flex-wrap:wrap;gap:16px;align-items:end}.mmck-barvy label{display:grid;gap:4px}'
        . '.mmck-sl{margin:10px 0;padding:10px 14px;border:1px solid #dcdcde;border-radius:6px}.mmck-sl h3{margin:4px 0 8px}'
        . '.mmck-sl li{margin:4px 0}.mmck-tag{font-size:12px;padding:1px 8px;border-radius:99px;background:#f0f0f1;margin-left:6px}'
        . '.mmck-tag.nove{background:#fcf0c3}.mmck-stat{display:flex;gap:24px;flex-wrap:wrap;margin:10px 0}.mmck-stat b{font-size:22px;display:block}'
        . '.mmck-kroky li{margin-bottom:6px}.mmck code{user-select:all}';
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s        = nastaveni();
    $tx       = texty_vychozi();
    $vse      = sluzby();
    $aktivni  = aktivni_sluzby();
    $nalez    = (array) get_option(OPTION_NALEZ, array());
    $tab      = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'vzhled'; // phpcs:ignore WordPress.Security.NonceVerification
    $zprava   = isset($_GET['zprava']) ? sanitize_key(wp_unslash($_GET['zprava'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $zalozky  = array(
        'vzhled'    => __('Vzhled', 'marmal-breakdance-plus'),
        'texty'     => __('Texty', 'marmal-breakdance-plus'),
        'kategorie' => __('Kategorie a cookies', 'marmal-breakdance-plus'),
        'mereni'    => __('Měření', 'marmal-breakdance-plus'),
        'souhlasy'  => __('Záznam souhlasů', 'marmal-breakdance-plus'),
        'navod'     => __('Jak zapojit', 'marmal-breakdance-plus'),
    );
    $tab      = array_key_exists($tab, $zalozky) ? $tab : 'vzhled';
    $nahled   = add_query_arg('marmal_cookies_nahled', '1', home_url('/'));
    ?>
    <style><?php echo admin_css(); // phpcs:ignore WordPress.Security.EscapeOutput -- statické CSS ?></style>
    <div class="wrap mmck">
        <h1><?php esc_html_e('MarMal – Cookies', 'marmal-breakdance-plus'); ?></h1>
        <p><?php esc_html_e('Cookie lišta podle pravidel ÚOOÚ. Odmítnutí je vždy stejně snadné jako souhlas, nic není předem zaškrtnuté a skripty se spustí až po souhlasu.', 'marmal-breakdance-plus'); ?>
            <a class="button" href="<?php echo esc_url($nahled); ?>" target="_blank" rel="noopener"><?php esc_html_e('Náhled lišty na webu', 'marmal-breakdance-plus'); ?></a></p>

        <?php settings_errors(); ?>
        <?php if ($zprava === 'revize') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Revize zvýšena – všem návštěvníkům se lišta zobrazí znovu.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'zapomenuto') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Nalezené služby zapomenuty – při dalších návštěvách webu se najdou znovu, pokud tam jsou.', 'marmal-breakdance-plus'); ?></p></div>
        <?php endif; ?>

        <div class="mmck-tabs" role="tablist">
            <?php foreach ($zalozky as $id => $nazev) : ?>
                <button type="button" role="tab" data-tab="<?php echo esc_attr($id); ?>" aria-selected="<?php echo $id === $tab ? 'true' : 'false'; ?>"><?php echo esc_html($nazev); ?></button>
            <?php endforeach; ?>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('marmal_cookies'); ?>
            <?php // Revize se mění jen tlačítkem; skryté pole jen kvůli úplnosti formuláře. ?>

            <section class="mmck-tp<?php echo $tab === 'vzhled' ? ' on' : ''; ?>" data-tp="vzhled">
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><?php esc_html_e('Rozložení lišty', 'marmal-breakdance-plus'); ?></th><td><?php radio('layout', array('box' => __('Box v rohu', 'marmal-breakdance-plus'), 'bar-b' => __('Lišta dole', 'marmal-breakdance-plus'), 'bar-t' => __('Lišta nahoře', 'marmal-breakdance-plus'), 'mid' => __('Okno uprostřed', 'marmal-breakdance-plus'))); ?></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Roh boxu', 'marmal-breakdance-plus'); ?></th><td><?php radio('corner', array('br' => __('Vpravo dole', 'marmal-breakdance-plus'), 'bl' => __('Vlevo dole', 'marmal-breakdance-plus'))); ?></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Okno „Nastavení cookies“', 'marmal-breakdance-plus'); ?></th><td><?php radio('prefs', array('mid' => __('Uprostřed', 'marmal-breakdance-plus'), 'side' => __('Panel z boku', 'marmal-breakdance-plus'))); ?></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Barvy', 'marmal-breakdance-plus'); ?></th><td>
                        <?php radio('barvy', array('web' => __('Podle webu (Global Settings Breakdance)', 'marmal-breakdance-plus'), 'vlastni' => __('Vlastní', 'marmal-breakdance-plus'))); ?>
                        <div class="mmck-barvy" id="mmck-barvy" style="margin-top:12px">
                            <label><?php esc_html_e('Hlavní barva', 'marmal-breakdance-plus'); ?><input type="color" name="<?php echo esc_attr(OPTION); ?>[brand]" value="<?php echo esc_attr($s['brand']); ?>"></label>
                            <label><?php esc_html_e('Pozadí lišty', 'marmal-breakdance-plus'); ?><input type="color" name="<?php echo esc_attr(OPTION); ?>[bg]" value="<?php echo esc_attr($s['bg']); ?>"></label>
                            <label><?php esc_html_e('Text lišty', 'marmal-breakdance-plus'); ?><input type="color" name="<?php echo esc_attr(OPTION); ?>[ink]" value="<?php echo esc_attr($s['ink']); ?>"></label>
                            <span><button type="button" class="button" data-preset="#1f3a5f,#ffffff,#1c2430"><?php esc_html_e('Světlá', 'marmal-breakdance-plus'); ?></button>
                                <button type="button" class="button" data-preset="#c8a96a,#14202e,#eef2f6"><?php esc_html_e('Tmavá', 'marmal-breakdance-plus'); ?></button>
                                <button type="button" class="button" data-preset="#111111,#ffffff,#111111"><?php esc_html_e('Neutrální', 'marmal-breakdance-plus'); ?></button></span>
                        </div>
                        <?php if ($s['barvy'] === 'vlastni') :
                            $k1 = kontrast($s['ink'], $s['bg']);
                            $k2 = $s['btn'] === 'fill' ? kontrast(na_brand($s['brand']), $s['brand']) : kontrast($s['ink'], $s['bg']); ?>
                            <p class="description"><?php
                                /* translators: 1: kontrast textu, 2: kontrast tlačítek */
                                echo esc_html(sprintf(__('Kontrast textu %1$s : 1, tlačítek %2$s : 1 (WCAG AA = 4,5 : 1).', 'marmal-breakdance-plus'), number_format_i18n($k1, 1), number_format_i18n($k2, 1)));
                                if ($k1 < 4.5 || $k2 < 4.5) {
                                    echo ' <strong>' . esc_html__('Zvol kontrastnější barvy.', 'marmal-breakdance-plus') . '</strong>';
                                } ?></p>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th scope="row"><?php esc_html_e('Styl tlačítek', 'marmal-breakdance-plus'); ?></th><td><?php radio('btn', array('fill' => __('Plná', 'marmal-breakdance-plus'), 'line' => __('Obrys', 'marmal-breakdance-plus'))); ?>
                        <p class="description"><?php esc_html_e('Přijmout vše a Odmítnout vše mají vždy stejný vzhled – plugin jinou volbu nedovolí (požadavek ÚOOÚ).', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-radius"><?php esc_html_e('Zaoblení (px)', 'marmal-breakdance-plus'); ?></label></th><td><input type="number" id="mmck-radius" class="small-text" min="0" max="24" name="<?php echo esc_attr(OPTION); ?>[radius]" value="<?php echo esc_attr((string) $s['radius']); ?>"></td></tr>
                    <tr><th scope="row"><label for="mmck-width"><?php esc_html_e('Šířka boxu (px)', 'marmal-breakdance-plus'); ?></label></th><td><input type="number" id="mmck-width" class="small-text" min="320" max="480" step="10" name="<?php echo esc_attr(OPTION); ?>[width]" value="<?php echo esc_attr((string) $s['width']); ?>"></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Stín', 'marmal-breakdance-plus'); ?></th><td><?php radio('shadow', array('none' => __('Žádný', 'marmal-breakdance-plus'), 'soft' => __('Jemný', 'marmal-breakdance-plus'), 'strong' => __('Výrazný', 'marmal-breakdance-plus'))); ?></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Ikonka po volbě', 'marmal-breakdance-plus'); ?></th><td><?php radio('icon', array('l' => __('Vlevo dole', 'marmal-breakdance-plus'), 'r' => __('Vpravo dole', 'marmal-breakdance-plus'), 'off' => __('Skrýt – stačí odkaz v patičce', 'marmal-breakdance-plus'))); ?></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Křížek', 'marmal-breakdance-plus'); ?></th><td><label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[close]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[close]" value="1" <?php checked($s['close']); ?>> <?php esc_html_e('Zobrazit křížek – zavře lištu bez souhlasu, na další stránce se ukáže znovu', 'marmal-breakdance-plus'); ?></label></td></tr>
                </table>
            </section>

            <section class="mmck-tp<?php echo $tab === 'texty' ? ' on' : ''; ?>" data-tp="texty">
                <p class="description"><?php esc_html_e('Prázdné pole = výchozí text (šedý). Na CZ+EN webu se vlastní texty překládají v Polylangu (Překlady řetězců) nebo WPML.', 'marmal-breakdance-plus'); ?></p>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="mmck-title"><?php esc_html_e('Nadpis lišty', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('title', $tx['title']); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-text"><?php esc_html_e('Text lišty', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('text', $tx['text'], true); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-lAccept"><?php esc_html_e('Tlačítko Přijmout', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('lAccept', $tx['lAccept']); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-lReject"><?php esc_html_e('Tlačítko Odmítnout', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('lReject', $tx['lReject']); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-lPrefs"><?php esc_html_e('Odkaz Nastavení', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('lPrefs', $tx['lPrefs']); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-lMore"><?php esc_html_e('Odkaz Více o cookies', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('lMore', $tx['lMore']); ?></td></tr>
                    <tr><th scope="row"><label for="mmck-odkaz"><?php esc_html_e('Stránka „Více o cookies“', 'marmal-breakdance-plus'); ?></label></th><td>
                        <?php wp_dropdown_pages(array('name' => OPTION . '[odkaz_stranka]', 'id' => 'mmck-odkaz', 'selected' => (int) $s['odkaz_stranka'], 'show_option_none' => __('— stránka zásad z Patičky / Nastavení → Soukromí —', 'marmal-breakdance-plus'), 'option_none_value' => '0')); ?>
                        <p class="description"><?php echo esc_html(odkaz_vice() !== '' ? sprintf(/* translators: %s: adresa */ __('Teď vede na: %s', 'marmal-breakdance-plus'), odkaz_vice()) : __('Žádná stránka – odkaz se v liště nezobrazí.', 'marmal-breakdance-plus')); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-pIntro"><?php esc_html_e('Úvod okna nastavení', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('pIntro', $tx['pIntro'], true); ?></td></tr>
                </table>
            </section>

            <section class="mmck-tp<?php echo $tab === 'kategorie' ? ' on' : ''; ?>" data-tp="kategorie">
                <p class="description"><?php esc_html_e('Kategorie se v liště zobrazí jen tehdy, když v ní je aspoň jedna cookie. Služby označené „automaticky“ plugin pozná sám, „nalezeno“ našlo blokování přímo na stránkách webu.', 'marmal-breakdance-plus'); ?></p>
                <?php foreach (kategorie_zaklad() as $kid => $k) : ?>
                    <div class="mmck-sl">
                        <h3><?php echo esc_html($k[0]); ?></h3>
                        <p><label for="mmck-popis_<?php echo esc_attr($kid); ?>"><?php esc_html_e('Popis kategorie', 'marmal-breakdance-plus'); ?></label><br><?php pole_text('popis_' . $kid, popis_vychozi($kid), true); ?></p>
                        <ul>
                        <?php foreach ($vse as $sid => $sl) :
                            if ($sl['kat'] !== $kid) {
                                continue;
                            }
                            $auto = isset($sl['auto']) && is_callable($sl['auto']) && call_user_func($sl['auto']);
                            $zap  = in_array($sid, $aktivni, true); ?>
                            <li><label><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[sluzby][]" value="<?php echo esc_attr($sid); ?>" <?php checked($zap); ?> <?php disabled($auto || isset($nalez[$sid])); ?>>
                                <strong><?php echo esc_html($sl['nazev']); ?></strong></label>
                                <?php if ($auto) : ?><span class="mmck-tag"><?php esc_html_e('automaticky', 'marmal-breakdance-plus'); ?></span><?php endif; ?>
                                <?php if (isset($nalez[$sid])) : ?><span class="mmck-tag nove"><?php
                                    /* translators: %s: datum */
                                    echo esc_html(sprintf(__('nalezeno na webu %s', 'marmal-breakdance-plus'), wp_date('j. n. Y', (int) $nalez[$sid]))); ?></span><?php endif; ?>
                                <br><small><?php echo esc_html(implode(', ', array_map(function ($c) {
                                    return $c[0];
                                }, $sl['cookies']))); ?></small>
                                <?php if ($auto || isset($nalez[$sid])) : ?><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[sluzby][]" value="<?php echo esc_attr($sid); ?>"><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
                <p><label for="mmck-vlastni"><strong><?php esc_html_e('Vlastní cookies', 'marmal-breakdance-plus'); ?></strong></label><br>
                    <?php pole_text('vlastni', "lang | tento web | Zapamatuje jazyk | 1 rok | preferencni", true); ?><br>
                    <span class="description"><?php esc_html_e('Řádek = jedna cookie: název | poskytovatel | účel | platnost | kategorie (nezbytne, preferencni, analyticke, marketingove).', 'marmal-breakdance-plus'); ?></span></p>
                <?php if ($nalez) : ?>
                    <p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_cookies_zapomenout'), 'marmal_cookies_zapomenout')); ?>"><?php esc_html_e('Zapomenout nalezené služby', 'marmal-breakdance-plus'); ?></a>
                        <span class="description"><?php esc_html_e('Např. po odstranění videa z webu.', 'marmal-breakdance-plus'); ?></span></p>
                <?php endif; ?>
            </section>

            <section class="mmck-tp<?php echo $tab === 'mereni' ? ' on' : ''; ?>" data-tp="mereni">
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="mmck-ga"><?php esc_html_e('Google Analytics 4 – ID měření', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('ga', 'G-XXXXXXXXXX'); ?>
                        <p class="description"><?php esc_html_e('Kód vloží modul sám, včetně Consent Mode v2. GA4 odjinud (Site Kit, GTM, kód v hlavičce, Breakdance → Custom Code) odstraň – jinak by se měřilo dvakrát.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Consent Mode v2', 'marmal-breakdance-plus'); ?></th><td><?php radio('gcm', array('basic' => __('Základní (doporučeno)', 'marmal-breakdance-plus'), 'advanced' => __('Rozšířený', 'marmal-breakdance-plus'))); ?>
                        <p class="description"><?php esc_html_e('Základní: bez souhlasu se Google tagy (GA4, Google Ads) vůbec nenačtou. Rozšířený: GA4 a Google Ads se načtou hned a bez souhlasu posílají Googlu pingy bez cookies pro modelování dat – výklad úřadů není jednotný, jen na přání klienta. Ostatní služby čekají na souhlas vždy.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Platnost GA cookies', 'marmal-breakdance-plus'); ?></th><td><label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[gaShort]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[gaShort]" value="1" <?php checked($s['gaShort']); ?>> <?php esc_html_e('Zkrátit na 13 měsíců (výchozí Google jsou 2 roky – ÚOOÚ vytýká neúměrně dlouhou platnost)', 'marmal-breakdance-plus'); ?></label></td></tr>
                    <tr><th scope="row"><label for="mmck-gads"><?php esc_html_e('Google Ads – ID konverzí', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('gads', 'AW-123456789'); ?>
                        <p class="description"><?php esc_html_e('Remarketing a propojení konverzí (cookie _gcl_au). Až po souhlasu s marketingovými cookies; sdílí Google tag s GA4.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-gtm"><?php esc_html_e('Google Tag Manager', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('gtm', 'GTM-XXXXXXX'); ?>
                        <?php radio('gtm_rezim', array('souhlas' => __('Načíst až po souhlasu (doporučeno)', 'marmal-breakdance-plus'), 'vzdy' => __('Načíst vždy – značky v GTM hlídají souhlas samy', 'marmal-breakdance-plus'))); ?>
                        <p class="description"><?php esc_html_e('„Až po souhlasu“: kontejner se načte po souhlasu s analytickými nebo marketingovými cookies. Značky v GTM pak spouštěj podle Consent Mode nebo události marmal_consent (proměnné dataLayer marmal_analyticke, marmal_marketingove, marmal_preferencni). „Vždy“ jen když máš u každé značky v GTM nastavenou kontrolu souhlasu. GA4 vlož buď sem do pole GA4, nebo do GTM – ne obojí.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-sem"><?php esc_html_e('Sklik – SEM ID', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('sem', __('SEM ID ze Skliku', 'marmal-breakdance-plus')); ?>
                        <p class="description"><?php esc_html_e('Seznam Event Measurement (sul.js) – nový měřicí kód Skliku pro retargeting a konverze. SEM ID najdeš ve Skliku → Nastavení účtu → Zobrazit kód SEM. Až po souhlasu s marketingovými cookies, souhlas se Seznamu předá automaticky. Starý retargetingový kód (rc.js) na webu modul jen zablokuje do souhlasu.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-meta"><?php esc_html_e('Meta Pixel – ID', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('meta', '123456789012345'); ?>
                        <p class="description"><?php esc_html_e('Facebook / Instagram reklamy. Až po souhlasu s marketingovými cookies.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-linkedin"><?php esc_html_e('LinkedIn Insight Tag – Partner ID', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('linkedin', '1234567'); ?>
                        <p class="description"><?php esc_html_e('Až po souhlasu s marketingovými cookies.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-clarity"><?php esc_html_e('Microsoft Clarity – Project ID', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('clarity', 'abcd1234ef'); ?>
                        <p class="description"><?php esc_html_e('Heatmapy a záznamy návštěv. Až po souhlasu s analytickými cookies; volbu modul předá Clarity přes její consent API. V Clarity zapni Settings → Setup → Cookie consent.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-hotjar"><?php esc_html_e('Hotjar – Site ID', 'marmal-breakdance-plus'); ?></label></th><td><?php pole_text('hotjar', '1234567'); ?>
                        <p class="description"><?php esc_html_e('Hotjar (Contentsquare). Až po souhlasu s analytickými cookies.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Google Search Console', 'marmal-breakdance-plus'); ?></th><td><p class="description"><?php esc_html_e('Ověření webu metaznačkou nebo DNS. Na webu nenastavuje žádné cookies, souhlas není potřeba.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Kód vložený jinde', 'marmal-breakdance-plus'); ?></th><td><p class="description"><?php esc_html_e('Měřicí kódy vlož jen sem – ne do Breakdance → Custom Code ani jiného pluginu. Když na webu nějaký zůstane, modul ho pozná a zablokuje do souhlasu (objeví se jako „nalezeno“), ale měřilo by se dvakrát.', 'marmal-breakdance-plus'); ?></p></td></tr>
                </table>
            </section>

            <section class="mmck-tp<?php echo $tab === 'souhlasy' ? ' on' : ''; ?>" data-tp="souhlasy">
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="mmck-valid"><?php esc_html_e('Platnost souhlasu', 'marmal-breakdance-plus'); ?></label></th><td>
                        <select id="mmck-valid" name="<?php echo esc_attr(OPTION); ?>[valid]"><?php foreach (array(3, 6, 12) as $m) : ?><option value="<?php echo (int) $m; ?>" <?php selected((int) $s['valid'], $m); ?>><?php echo esc_html(sprintf(_n('%d měsíc', '%d měsíců', $m, 'marmal-breakdance-plus'), $m)); ?></option><?php endforeach; ?></select>
                        <p class="description"><?php esc_html_e('Po této době se návštěvníkovi lišta zobrazí znovu.', 'marmal-breakdance-plus'); ?></p></td></tr>
                    <tr><th scope="row"><label for="mmck-uchovat"><?php esc_html_e('Uchovávat záznamy', 'marmal-breakdance-plus'); ?></label></th><td>
                        <select id="mmck-uchovat" name="<?php echo esc_attr(OPTION); ?>[uchovat]"><?php foreach (array(1, 2, 3, 5) as $r) : ?><option value="<?php echo (int) $r; ?>" <?php selected((int) $s['uchovat'], $r); ?>><?php echo esc_html(sprintf(_n('%d rok', '%d roky', $r, 'marmal-breakdance-plus'), $r)); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Revize textů', 'marmal-breakdance-plus'); ?></th><td><strong><?php echo (int) $s['rev']; ?></strong>
                        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_cookies_revize'), 'marmal_cookies_revize')); ?>"><?php esc_html_e('Zvýšit revizi', 'marmal-breakdance-plus'); ?></a>
                        <p class="description"><?php esc_html_e('Po změně cookies nebo textů: všem návštěvníkům se lišta ukáže znovu a souhlas dají k nové verzi.', 'marmal-breakdance-plus'); ?></p></td></tr>
                </table>
                <?php $st = statistika(); $celkem = array_sum($st); ?>
                <h3><?php esc_html_e('Posledních 30 dní', 'marmal-breakdance-plus'); ?></h3>
                <div class="mmck-stat">
                    <?php foreach (array('vse' => __('Přijmout vše', 'marmal-breakdance-plus'), 'nic' => __('Odmítnout vše', 'marmal-breakdance-plus'), 'vyber' => __('Vlastní výběr', 'marmal-breakdance-plus')) as $k => $n) : ?>
                        <div><b><?php echo esc_html(number_format_i18n($st[$k])); ?></b><?php echo esc_html($n); ?><?php echo $celkem ? ' (' . esc_html(number_format_i18n(100 * $st[$k] / $celkem)) . ' %)' : ''; ?></div>
                    <?php endforeach; ?>
                </div>
                <p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_cookies_export'), 'marmal_cookies_export')); ?>"><?php esc_html_e('Stáhnout záznamy (CSV)', 'marmal-breakdance-plus'); ?></a></p>
                <table class="widefat striped"><thead><tr><th><?php esc_html_e('Čas', 'marmal-breakdance-plus'); ?></th><th>ID</th><th><?php esc_html_e('Volba', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Kategorie', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Revize', 'marmal-breakdance-plus'); ?></th></tr></thead><tbody>
                <?php $rows = posledni(30);
                if (!$rows) : ?>
                    <tr><td colspan="5"><?php esc_html_e('Zatím žádný záznam.', 'marmal-breakdance-plus'); ?></td></tr>
                <?php endif;
                foreach ($rows as $r) : ?>
                    <tr><td><?php echo esc_html(wp_date('j. n. Y H:i:s', strtotime($r['cas'] . ' UTC'))); ?></td><td><code><?php echo esc_html($r['souhlas_id']); ?></code></td><td><?php echo esc_html($r['volba']); ?></td><td><?php echo esc_html($r['kategorie'] !== '' ? $r['kategorie'] : '—'); ?></td><td><?php echo (int) $r['revize']; ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </section>

            <section class="mmck-tp<?php echo $tab === 'navod' ? ' on' : ''; ?>" data-tp="navod">
                <ol class="mmck-kroky">
                    <li><?php esc_html_e('Vypni starý cookie plugin (CookieYes apod.) – dvě lišty najednou nesmí běžet.', 'marmal-breakdance-plus'); ?></li>
                    <li><?php esc_html_e('Záložka Měření: vlož ID služeb (GA4, Google Ads, GTM, Sklik, Meta Pixel, LinkedIn, Clarity, Hotjar) a stejné kódy odstraň odjinud (Site Kit, Breakdance → Settings → Custom Code, kód v hlavičce).', 'marmal-breakdance-plus'); ?></li>
                    <li><?php esc_html_e('Patička: odkaz „Nastavení cookies“ v elementu Marmal – Patička otevře okno tohoto modulu automaticky.', 'marmal-breakdance-plus'); ?></li>
                    <li><?php echo wp_kses(__('Na stránku zásad cookies vlož shortcode <code>[marmal_cookies_seznam]</code> – tabulka cookies podle kategorií se bude aktualizovat sama.', 'marmal-breakdance-plus'), array('code' => array())); ?></li>
                    <li><?php esc_html_e('Projdi web: videa a mapy mají místo sebe náhled, dokud návštěvník nesouhlasí. Nové služby se objeví v záložce Kategorie a cookies jako „nalezeno“.', 'marmal-breakdance-plus'); ?></li>
                    <li><?php esc_html_e('Tlačítkem Náhled lišty na webu zkontroluj vzhled (náhled nic neukládá).', 'marmal-breakdance-plus'); ?></li>
                </ol>
                <p class="description"><?php echo wp_kses(__('Vlastní odkaz na otevření nastavení: třída <code>marmal-cookies-nastaveni</code>, odkaz na <code>#nastaveni-cookies</code> nebo shortcode <code>[marmal_cookies_nastaveni]</code>. Ve skriptech: <code>MarmalCookies.souhlas(\'analyticke\')</code>, událost <code>marmal:cookies</code>. V Google Tag Manageru: událost <code>marmal_consent</code> a proměnné dataLayer <code>marmal_analyticke</code>, <code>marmal_marketingove</code>, <code>marmal_preferencni</code>.', 'marmal-breakdance-plus'), array('code' => array())); ?></p>
            </section>

            <?php submit_button(__('Uložit nastavení', 'marmal-breakdance-plus')); ?>
        </form>
    </div>
    <script>
    (function () {
      var tabs = document.querySelectorAll('.mmck-tabs [data-tab]');
      Array.prototype.forEach.call(tabs, function (t) {
        t.addEventListener('click', function () {
          Array.prototype.forEach.call(tabs, function (x) { x.setAttribute('aria-selected', x === t ? 'true' : 'false'); });
          Array.prototype.forEach.call(document.querySelectorAll('.mmck-tp'), function (p) { p.classList.toggle('on', p.getAttribute('data-tp') === t.getAttribute('data-tab')); });
        });
      });
      var barvy = document.getElementById('mmck-barvy');
      function prepnout() {
        var v = document.querySelector('input[name$="[barvy]"]:checked');
        barvy.style.display = v && v.value === 'vlastni' ? 'flex' : 'none';
      }
      Array.prototype.forEach.call(document.querySelectorAll('input[name$="[barvy]"]'), function (r) { r.addEventListener('change', prepnout); });
      prepnout();
      Array.prototype.forEach.call(document.querySelectorAll('[data-preset]'), function (b) {
        b.addEventListener('click', function () {
          var p = b.getAttribute('data-preset').split(',');
          ['brand', 'bg', 'ink'].forEach(function (k, i) { barvy.querySelector('input[name$="[' + k + ']"]').value = p[i]; });
        });
      });
    })();
    </script>
    <?php
}
