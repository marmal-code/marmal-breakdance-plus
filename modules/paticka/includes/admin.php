<?php
/**
 * Modul Patička – stránka nastavení MarMal → Patička.
 *
 * Ukládá se přes options.php (register_setting): WordPress sám ověří nonce
 * a oprávnění manage_options, hodnoty čistí sanitizovat() v data.php.
 */

namespace MarmalElements\Paticka;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA = 'marmal-paticka';

add_action('admin_init', function () {
    register_setting('marmal_paticka', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    $hook = add_submenu_page(
        'marmal',
        __('Patička', 'marmal-breakdance-plus'),
        __('Patička', 'marmal-breakdance-plus'),
        'manage_options',
        STRANKA,
        __NAMESPACE__ . '\stranka'
    );
    add_action('admin_print_styles-' . $hook, function () {
        wp_enqueue_style('marmal-paticka');
        wp_add_inline_style('marmal-paticka', admin_css());
    });
}, 20);

function admin_css(): string
{
    return '.marmal-pat{max-width:960px}'
        . '.marmal-pat__uvod{font-size:14px;max-width:760px}'
        . '.marmal-pat__box{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px 20px;margin:16px 0}'
        . '.marmal-pat__box h2{margin-top:0;font-size:15px}'
        . '.marmal-pat__nahled{background:#1d2327;color:#f0f0f1;border-radius:6px;padding:20px 24px;font-size:14px}'
        . '.marmal-pat .form-table th{width:220px}'
        . '.marmal-pat .regular-text{width:100%;max-width:520px}'
        . '.marmal-pat code{user-select:all}'
        . '.marmal-pat__kroky li{margin-bottom:6px}'
        . '.marmal-pat__tab td{padding:4px 12px 4px 0;vertical-align:top}';
}

/** Textové pole s výchozí hodnotou jako placeholderem. */
function pole(string $klic, string $placeholder = '', string $popis = '', string $typ = 'text', array $attr = array()): void
{
    $s   = nastaveni();
    $val = $s[$klic] ?? '';
    if ($klic === 'rok' && !$val) {
        $val = '';
    }
    $extra = '';
    foreach ($attr as $k => $v) {
        $extra .= ' ' . esc_attr($k) . '="' . esc_attr((string) $v) . '"';
    }
    printf(
        '<input type="%1$s" id="mp-%2$s" name="%3$s[%2$s]" value="%4$s" placeholder="%5$s" class="%6$s"%7$s>',
        esc_attr($typ),
        esc_attr($klic),
        esc_attr(OPTION),
        esc_attr((string) $val),
        esc_attr($placeholder),
        $typ === 'number' ? 'small-text' : 'regular-text',
        $extra // phpcs:ignore WordPress.Security.EscapeOutput -- escapované výše
    );
    if ($popis !== '') {
        echo '<p class="description">' . wp_kses($popis, array('code' => array(), 'strong' => array(), 'br' => array())) . '</p>';
    }
}

function zaskrtavatko(string $klic, string $text): void
{
    $s = nastaveni();
    printf(
        '<label><input type="hidden" name="%1$s[%2$s]" value="0"><input type="checkbox" id="mp-%2$s" name="%1$s[%2$s]" value="1"%3$s> %4$s</label>',
        esc_attr(OPTION),
        esc_attr($klic),
        checked(!empty($s[$klic]), true, false),
        esc_html($text)
    );
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s       = nastaveni();
    $vychozi = vychozi_texty();
    $rezim   = cookies_rezim();
    $rezimy  = cookies_rezimy();
    ?>
    <div class="wrap marmal-pat">
        <h1><?php esc_html_e('MarMal – Patička', 'marmal-breakdance-plus'); ?></h1>
        <p class="marmal-pat__uvod"><?php esc_html_e('Copyright, podpis autora webu, údaje o podnikateli a odkazy do patičky. Prázdná pole použijí výchozí text (šedý v poli).', 'marmal-breakdance-plus'); ?></p>

        <?php settings_errors(); ?>

        <div class="marmal-pat__box">
            <h2><?php esc_html_e('Náhled', 'marmal-breakdance-plus'); ?></h2>
            <div class="marmal-pat__nahled"><?php echo sc_paticka(); // phpcs:ignore WordPress.Security.EscapeOutput -- výstup je escapovaný v render.php ?></div>
            <p class="description"><?php esc_html_e('Písmo a barvy se na webu převezmou z patičky. Na úvodní stránce je odkaz autora běžný, na ostatních stránkách s nofollow.', 'marmal-breakdance-plus'); ?></p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('marmal_paticka'); ?>

            <div class="marmal-pat__box">
                <h2><?php esc_html_e('Copyright', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mp-rok"><?php esc_html_e('Rok spuštění webu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('rok', (string) rok_navrh(), __('Předvyplněno podle nejstarší stránky webu. U převzatého nebo předělaného webu sem dej rok původního webu – v patičce bude vidět historie (© 2019–2026). Prázdné = znovu navrhnout.', 'marmal-breakdance-plus'), 'number', array('min' => 1990, 'max' => letos(), 'step' => 1)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-drzitel"><?php esc_html_e('Držitel práv', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('drzitel', wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES), __('Prázdné = název webu (Nastavení → Obecné).', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                </table>
            </div>

            <div class="marmal-pat__box">
                <h2><?php esc_html_e('Podpis autora webu', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Zobrazit', 'marmal-breakdance-plus'); ?></th>
                        <td><?php zaskrtavatko('autor_zobrazit', __('Zobrazit podpis autora v patičce', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-autor_pred"><?php esc_html_e('Text před odkazem', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('autor_pred', $vychozi['autor_pred']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-autor_odkaz"><?php esc_html_e('Text odkazu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('autor_odkaz', $vychozi['autor_odkaz']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-autor_url"><?php esc_html_e('Adresa odkazu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('autor_url', $vychozi['autor_url'], '', 'url'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Měření návštěv', 'marmal-breakdance-plus'); ?></th>
                        <td>
                            <?php zaskrtavatko('utm', __('Přidat k odkazu UTM parametry', 'marmal-breakdance-plus')); ?>
                            <p class="description">
                                <?php
                                /* translators: %s: doména webu */
                                echo esc_html(sprintf(__('V Google Analytics na marmal.cz pak uvidíš návštěvy z tohoto webu jako zdroj „%s“ (médium referral, kampaň podpis-webu).', 'marmal-breakdance-plus'), domena()));
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="marmal-pat__box">
                <h2><?php esc_html_e('Údaje o podnikateli (§ 435 občanského zákoníku)', 'marmal-breakdance-plus'); ?></h2>
                <p class="description"><?php esc_html_e('Podnikatel má na webu uvést jméno nebo firmu, sídlo, IČO a údaj o zápisu v rejstříku (u OSVČ v živnostenském rejstříku). Vyplněné údaje se zobrazí v patičce, prázdné se vynechají.', 'marmal-breakdance-plus'); ?></p>
                <?php if (udaje_z_gdpr()) :
                    $u = udaje(); ?>
                    <p><?php esc_html_e('Údaje se vyplňují na jednom místě – v modulu Ochrana osobních údajů (jsou zároveň údaji o správci). Patička je odtud přebírá.', 'marmal-breakdance-plus'); ?>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=marmal-gdpr')); ?>"><?php esc_html_e('Upravit v MarMal → Ochrana os. údajů', 'marmal-breakdance-plus'); ?></a></p>
                    <?php if ($u) : ?>
                        <p><?php echo implode('<br>', array_map('esc_html', $u)); // phpcs:ignore WordPress.Security.EscapeOutput -- escapováno po položkách ?></p>
                    <?php else : ?>
                        <p><em><?php esc_html_e('Zatím nevyplněno.', 'marmal-breakdance-plus'); ?></em></p>
                    <?php endif; ?>
                <?php else : ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mp-firma"><?php esc_html_e('Firma / jméno a příjmení', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('firma', __('např. Noitami akustika s.r.o. nebo Jan Novák', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-sidlo"><?php esc_html_e('Sídlo', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('sidlo', __('Ulice 123, 252 28 Černošice', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-ico"><?php esc_html_e('IČO', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('ico', '12345678', __('Kontroluje se kontrolní číslice.', 'marmal-breakdance-plus'), 'text', array('inputmode' => 'numeric', 'maxlength' => 10)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-dic"><?php esc_html_e('DIČ', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('dic', 'CZ12345678', __('Jen u plátců DPH (nepovinné).', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-rejstrik"><?php esc_html_e('Zápis v rejstříku', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('rejstrik', '', __('s.r.o.: <code>Zapsáno v obchodním rejstříku vedeném Městským soudem v Praze, oddíl C, vložka 123456</code><br>OSVČ: <code>Zapsán v živnostenském rejstříku vedeném Městským úřadem Černošice</code>', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                </table>
                <?php endif; ?>
            </div>

            <div class="marmal-pat__box">
                <h2><?php esc_html_e('Odkazy', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mp-zasady_stranka"><?php esc_html_e('Stránka ochrany osobních údajů', 'marmal-breakdance-plus'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name'              => OPTION . '[zasady_stranka]',
                                'id'                => 'mp-zasady_stranka',
                                'selected'          => (int) $s['zasady_stranka'],
                                'show_option_none'  => __('— podle Nastavení → Soukromí —', 'marmal-breakdance-plus'),
                                'option_none_value' => '0',
                                'post_status'       => array('publish'),
                            ));
                            ?>
                            <p class="description">
                                <?php
                                $url = zasady_url();
                                echo $url !== ''
                                    ? esc_html(sprintf(/* translators: %s: adresa */ __('Teď vede na: %s', 'marmal-breakdance-plus'), $url))
                                    : esc_html__('Stránka není nastavená – odkaz se nezobrazí.', 'marmal-breakdance-plus');
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-zasady_text"><?php esc_html_e('Text odkazu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('zasady_text', $vychozi['zasady_text']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-cz_stranka"><?php esc_html_e('Stránka Zásady cookies', 'marmal-breakdance-plus'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name'              => OPTION . '[cz_stranka]',
                                'id'                => 'mp-cz_stranka',
                                'selected'          => (int) $s['cz_stranka'],
                                'show_option_none'  => __('— automaticky (modul Cookies / stránka zasady-cookies) —', 'marmal-breakdance-plus'),
                                'option_none_value' => '0',
                                'post_status'       => array('publish'),
                            ));
                            ?>
                            <p class="description">
                                <?php
                                $url = cookies_zasady_url();
                                echo $url !== ''
                                    ? esc_html(sprintf(/* translators: %s: adresa */ __('Teď vede na: %s', 'marmal-breakdance-plus'), $url))
                                    : esc_html__('Web nemá samostatnou stránku Zásady cookies – odkaz se nezobrazí. Stránku založíš jedním tlačítkem v MarMal → Cookies → Texty.', 'marmal-breakdance-plus');
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-cz_text"><?php esc_html_e('Text odkazu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('cz_text', $vychozi['cz_text']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-cookies"><?php esc_html_e('Nastavení cookies', 'marmal-breakdance-plus'); ?></label></th>
                        <td>
                            <select id="mp-cookies" name="<?php echo esc_attr(OPTION); ?>[cookies]">
                                <?php foreach ($rezimy as $klic => $nazev) : ?>
                                    <option value="<?php echo esc_attr($klic); ?>" <?php selected($s['cookies'], $klic); ?>><?php echo esc_html($nazev); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php
                                echo esc_html($rezim === 'ne'
                                    ? __('Teď se odkaz nezobrazí (cookie plugin nenalezen nebo vypnuto).', 'marmal-breakdance-plus')
                                    : sprintf(/* translators: %s: název pluginu */ __('Teď: %s – kliknutí znovu otevře cookie lištu.', 'marmal-breakdance-plus'), $rezimy[$rezim] ?? $rezim));
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-cookies_trida"><?php esc_html_e('Vlastní CSS třída', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('cookies_trida', '', __('Jen pro „Jiný plugin“ – třída, na kterou cookie plugin otevře lištu (najdeš v jeho dokumentaci).', 'marmal-breakdance-plus')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mp-cookies_text"><?php esc_html_e('Text odkazu', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole('cookies_text', $vychozi['cookies_text']); ?></td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Uložit nastavení', 'marmal-breakdance-plus')); ?>
        </form>

        <div class="marmal-pat__box">
            <h2><?php esc_html_e('Jak to dát do patičky', 'marmal-breakdance-plus'); ?></h2>
            <ol class="marmal-pat__kroky">
                <li><?php esc_html_e('Breakdance → Footers → otevři patičku webu.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Do spodní lišty přidej element „Marmal – Patička“ (Add → do hledání napiš „Patička“).', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Content → Patička: rozložení a co zobrazit. Design: barvy, písmo a podtržení odkazů (i zvlášť pro odkaz autora), copyright, údaje o podnikateli, mezery.', 'marmal-breakdance-plus'); ?></li>
            </ol>
            <p><?php echo wp_kses(__('Bez elementu to jde i shortcodem <code>[marmal_paticka]</code> v elementu Shortcode – vzhled se pak jen převezme z patičky.', 'marmal-breakdance-plus'), array('code' => array())); ?></p>
            <p><strong><?php esc_html_e('Jednotlivé části (každou do vlastního elementu Shortcode):', 'marmal-breakdance-plus'); ?></strong></p>
            <table class="marmal-pat__tab">
                <tr><td><code>[marmal_copyright]</code></td><td><?php esc_html_e('© 2019–2026 Název; drzitel="ne" = jen roky', 'marmal-breakdance-plus'); ?></td></tr>
                <tr><td><code>[marmal_autor]</code></td><td><?php esc_html_e('podpis autora s odkazem', 'marmal-breakdance-plus'); ?></td></tr>
                <tr><td><code>[marmal_firma]</code></td><td><?php esc_html_e('údaje o podnikateli; pod_sebou="ano" = každý údaj na řádek', 'marmal-breakdance-plus'); ?></td></tr>
                <tr><td><code>[marmal_odkazy]</code></td><td><?php esc_html_e('ochrana osobních údajů, zásady cookies a nastavení cookies; zasady="ne" / zasady_cookies="ne" / cookies="ne"', 'marmal-breakdance-plus'); ?></td></tr>
                <tr><td><code>[marmal_paticka rozlozeni="stred"]</code></td><td><?php esc_html_e('celá lišta na střed (radek = vlevo a vpravo, sloupec = pod sebou); části vypneš copyright="ne", firma="ne", odkazy="ne", autor="ne"', 'marmal-breakdance-plus'); ?></td></tr>
                <tr><td><code>[marmal_paticka rozlozeni="tri"]</code></td><td><?php esc_html_e('copyright vlevo, zásady a cookies uprostřed, podpis vpravo; přesun části pozice_copyright / pozice_odkazy / pozice_autor / pozice_udaje="vlevo|stred|vpravo|pod", zarovnání řádku pod zarovnani_pod="vlevo|stred|vpravo", pod sebe od skladat="mobil|tablet|maly|nikdy", mobil="stred"', 'marmal-breakdance-plus'); ?></td></tr>
            </table>
            <p><?php esc_html_e('Dynamická data: u textu klikni na ikonu dynamických dat → MarMal → Patička (copyright, roky, údaje o podnikateli, odkazy).', 'marmal-breakdance-plus'); ?></p>
        </div>
    </div>
    <?php
}
