<?php
/**
 * Modul SEO – stránka MarMal → SEO.
 *
 * Nastavení se ukládá přes options.php (register_setting): WordPress sám ověří nonce
 * a oprávnění manage_options, hodnoty čistí sanitizovat() v data.php.
 * Převod z Yoastu / Rank Mathu má vlastní formulář (admin-post, import.php).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA = 'marmal-seo';

add_action('admin_init', function () {
    register_setting('marmal_seo', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    $hook = add_submenu_page('marmal', __('SEO a sdílení', 'marmal-breakdance-plus'), __('SEO', 'marmal-breakdance-plus'), 'manage_options', STRANKA, __NAMESPACE__ . '\stranka');
    add_action('admin_print_scripts-' . $hook, function () {
        wp_enqueue_media();
        wp_enqueue_style('marmal-seo-admin', MARMAL_BDP_URL . 'modules/seo/assets/seo-admin.css', array(), MARMAL_BDP_VERSION);
        wp_enqueue_script('marmal-seo-nastaveni', MARMAL_BDP_URL . 'modules/seo/assets/seo-nastaveni.js', array('media-editor'), MARMAL_BDP_VERSION, true);
        wp_add_inline_script('marmal-seo-nastaveni', 'window.marmalSeoNast = ' . wp_json_encode(array(
            'vybrat' => __('Výchozí obrázek pro sdílení', 'marmal-breakdance-plus'),
            'pouzit' => __('Použít obrázek', 'marmal-breakdance-plus'),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
    });
}, 20);

/** Upozornění modulu v administraci (jen na stránkách MarMal a u seznamu pluginů). */
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen || !current_user_can('manage_options')) {
        return;
    }
    $naStrance = strpos((string) $screen->id, 'marmal') !== false;
    if (!$naStrance && $screen->id !== 'plugins') {
        return;
    }
    if ($screen->id === STRANKA || strpos((string) $screen->id, STRANKA) !== false) {
        return; // stránka SEO má vlastní výpis
    }
    if ($k = konkurence()) {
        echo '<div class="notice notice-warning"><p><strong>MarMal – SEO:</strong> ' . esc_html(sprintf(
            /* translators: %s: názvy SEO pluginů */
            __('Běží %s – modul SEO nic nevypisuje. Převod dat najdeš v MarMal → SEO.', 'marmal-breakdance-plus'),
            implode(', ', $k)
        )) . '</p></div>';
    }
});

function pole_text(string $klic, string $placeholder = '', int $max = 120): void
{
    printf(
        '<input type="text" id="ms-%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" class="regular-text" maxlength="%5$d">',
        esc_attr($klic),
        esc_attr(OPTION),
        esc_attr((string) nastaveni()[$klic]),
        esc_attr($placeholder),
        (int) $max
    );
}

function zaskrtavatko(string $klic, string $text): void
{
    printf(
        '<label><input type="hidden" name="%1$s[%2$s]" value="0"><input type="checkbox" name="%1$s[%2$s]" value="1"%3$s> %4$s</label><br>',
        esc_attr(OPTION),
        esc_attr($klic),
        checked(!empty(nastaveni()[$klic]), true, false),
        esc_html($text)
    );
}

function vyber(string $nazev, array $moznosti, string $vybrano, string $id = ''): void
{
    echo '<select name="' . esc_attr($nazev) . '"' . ($id ? ' id="' . esc_attr($id) . '"' : '') . '>';
    foreach ($moznosti as $hodnota => $popis) {
        echo '<option value="' . esc_attr((string) $hodnota) . '"' . selected($vybrano, (string) $hodnota, false) . '>' . esc_html($popis) . '</option>';
    }
    echo '</select>';
}

/** Výběr obrázku z knihovny médií (skryté pole s ID + náhled). */
function vyber_obrazku(string $nazev, int $img, string $id): void
{
    $src = $img ? wp_get_attachment_image_src($img, 'medium_large') : null;
    ?>
    <div class="marmal-seo-nast__obr" data-marmal-obr>
        <input type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($nazev); ?>" value="<?php echo (int) $img; ?>">
        <img src="<?php echo $src ? esc_url($src[0]) : ''; ?>" alt=""<?php echo $src ? '' : ' hidden'; ?>>
        <button type="button" class="button" data-vybrat><?php esc_html_e('Vybrat obrázek', 'marmal-breakdance-plus'); ?></button>
        <button type="button" class="button-link marmal-seo__odebrat" data-odebrat<?php echo $img ? '' : ' hidden'; ?>><?php esc_html_e('Odebrat', 'marmal-breakdance-plus'); ?></button>
    </div>
    <?php
}

/** Box „Další jazyky“ (jen vícejazyčný web s Polylangem / WPML). */
function jazyky_box(array $s): void
{
    $jazyky = jazyky();
    if (!$jazyky) {
        return;
    }
    $vychozi = vychozi_jazyk();
    $ulozene = (array) ($s['jazyky'] ?? array());
    $pole    = array(
        'web'           => array(__('Název webu v titulcích', 'marmal-breakdance-plus'), nazev_webu(), 80),
        'vzor'          => array(__('Vzor titulku', 'marmal-breakdance-plus'), vzor(), MAX_TITLE),
        'titulek_uvod'  => array(__('Titulek úvodní stránky', 'marmal-breakdance-plus'), nahradit(titulek_uvodu_auto()), MAX_TITLE),
        'popis'         => array(__('Výchozí popisek', 'marmal-breakdance-plus'), $s['popis'], MAX_DESC),
        'drobecky_uvod' => array(__('Text „Úvod“ v drobečkové navigaci', 'marmal-breakdance-plus'), function_exists(__NAMESPACE__ . '\drobecky_uvod') ? drobecky_uvod() : '', 60),
    );
    ?>
    <div class="marmal-seo-nast__box" id="jazyky">
        <h2><?php esc_html_e('Další jazyky', 'marmal-breakdance-plus'); ?></h2>
        <p><?php echo esc_html(sprintf(
            /* translators: %s: název výchozího jazyka */
            __('Hodnoty výše platí pro výchozí jazyk (%s). Pro další jazyky tu vyplň jen to, co se má lišit – prázdné pole = stejné jako ve výchozím jazyce (název a popis webu přeloží Polylang / WPML v Překladech řetězců). Titulek a popisek konkrétní stránky píšeš v boxu „SEO a sdílení“ u jejího překladu. Značky hreflang vypisuje Polylang / WPML.', 'marmal-breakdance-plus'),
            $jazyky[$vychozi]['nazev'] ?? $vychozi
        )); ?></p>
        <?php foreach ($jazyky as $j => $l) : ?>
            <?php
            if ($l['vychozi']) {
                continue;
            }
            $h = (array) ($ulozene[$j] ?? array());
            ?>
            <h3 class="marmal-seo-nast__jazyk"><?php echo esc_html($l['nazev']); ?> <code><?php echo esc_html($j); ?></code></h3>
            <table class="form-table" role="presentation">
                <?php foreach ($pole as $klic => list($popisek, $placeholder, $max)) : ?>
                    <tr>
                        <th scope="row"><label for="ms-<?php echo esc_attr($j . '-' . $klic); ?>"><?php echo esc_html($popisek); ?></label></th>
                        <td>
                            <?php if ($klic === 'popis') : ?>
                                <textarea id="ms-<?php echo esc_attr($j . '-' . $klic); ?>" name="<?php echo esc_attr(OPTION . '[jazyky][' . $j . '][' . $klic . ']'); ?>" rows="2" class="large-text" maxlength="<?php echo (int) $max; ?>" placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea((string) ($h[$klic] ?? '')); ?></textarea>
                            <?php else : ?>
                                <input type="text" id="ms-<?php echo esc_attr($j . '-' . $klic); ?>" name="<?php echo esc_attr(OPTION . '[jazyky][' . $j . '][' . $klic . ']'); ?>" value="<?php echo esc_attr((string) ($h[$klic] ?? '')); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" class="regular-text" maxlength="<?php echo (int) $max; ?>">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th scope="row"><?php esc_html_e('Výchozí obrázek pro sdílení', 'marmal-breakdance-plus'); ?></th>
                    <td><?php vyber_obrazku(OPTION . '[jazyky][' . $j . '][obrazek]', (int) ($h['obrazek'] ?? 0), 'ms-' . $j . '-obrazek'); ?>
                        <p class="description"><?php esc_html_e('Jen když má mít jazyk jiný obrázek (např. s anglickým textem).', 'marmal-breakdance-plus'); ?></p></td>
                </tr>
            </table>
        <?php endforeach; ?>
    </div>
    <?php
}

/** Stav sitemap a robots.txt pro kontrolu. */
function stav_indexovani(): array
{
    $verejny  = (bool) get_option('blog_public');
    $sitemap  = (bool) apply_filters('wp_sitemaps_enabled', $verejny);
    $soubor   = file_exists(ABSPATH . 'robots.txt');
    $sitemapF = file_exists(ABSPATH . 'sitemap.xml') || file_exists(ABSPATH . 'sitemap_index.xml');
    return array('verejny' => $verejny, 'sitemap' => $sitemap, 'robots_soubor' => $soubor, 'sitemap_soubor' => $sitemapF);
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s     = nastaveni();
    $typy  = typy_obsahu_vse();
    $stav  = stav_indexovani();
    $prikl = nahradit(vzor(), __('O projektu', 'marmal-breakdance-plus'));
    $img   = (int) $s['obrazek'];
    ?>
    <div class="wrap marmal-seo-nast">
        <h1><?php esc_html_e('MarMal – SEO a sdílení', 'marmal-breakdance-plus'); ?></h1>
        <p><?php esc_html_e('Výchozí hodnoty pro celý web. U jednotlivých stránek je přepíšeš v boxu „SEO a sdílení“ pod editorem stránky.', 'marmal-breakdance-plus'); ?></p>

        <?php settings_errors(OPTION); ?>
        <?php if (isset($_GET['indexnow'])) : // phpcs:ignore WordPress.Security.NonceVerification -- jen zpráva po přesměrování ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(/* translators: %d: počet */ __('IndexNow: odesláno %d adres. Výsledek najdeš v boxu AI vyhledávání.', 'marmal-breakdance-plus'), absint($_GET['indexnow']))); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
        <?php endif; ?>
        <?php foreach (upozorneni() as $u) : ?>
            <div class="notice notice-<?php echo esc_attr($u['typ'] === 'error' ? 'error' : ($u['typ'] === 'warning' ? 'warning' : 'info')); ?> inline"><p><?php echo esc_html($u['text']); ?></p></div>
        <?php endforeach; ?>

        <form method="post" action="options.php">
            <?php settings_fields('marmal_seo'); ?>

            <div class="marmal-seo-nast__box">
                <h2><?php esc_html_e('Titulky', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ms-web"><?php esc_html_e('Název webu v titulcích', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole_text('web', cisty_text(get_bloginfo('name'), 80), 80); ?>
                            <p class="description"><?php esc_html_e('Prázdné = název z Nastavení → Obecné. Použije se za oddělovačem a v og:site_name (např. „Žehrovické byty“ místo „zehrovickebyty.cz“).', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-oddelovac"><?php esc_html_e('Oddělovač', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php vyber(OPTION . '[oddelovac]', array_combine(oddelovace(), oddelovace()), $s['oddelovac'], 'ms-oddelovac'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-vzor"><?php esc_html_e('Vzor titulku', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole_text('vzor', vychozi_vzor()); ?>
                            <p class="description"><?php echo esc_html(sprintf(
                                /* translators: %s: ukázkový titulek */
                                __('Pro stránky bez vlastního titulku. Zástupné symboly %%nazev%%, %%web%%, %%oddelovac%%, %%popis_webu%%. Teď např.: %s', 'marmal-breakdance-plus'),
                                $prikl
                            )); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-titulek_uvod"><?php esc_html_e('Titulek úvodní stránky', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole_text('titulek_uvod', nahradit(popis_webu() !== '' ? '%web% %oddelovac% %popis_webu%' : '%web%')); ?>
                            <p class="description"><?php esc_html_e('Když ho nemá úvodní stránka vyplněný ve svém boxu. Píše se celý, bez „| web“ na konci, např. „Žehrovické byty – nové byty 1+kk až 2+1 u Kladna“.', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                </table>
            </div>

            <div class="marmal-seo-nast__box">
                <h2><?php esc_html_e('Popisek, obrázek a jazyk', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ms-popis"><?php esc_html_e('Výchozí popisek', 'marmal-breakdance-plus'); ?></label></th>
                        <td><textarea id="ms-popis" name="<?php echo esc_attr(OPTION); ?>[popis]" rows="3" class="large-text" maxlength="<?php echo (int) MAX_DESC; ?>"><?php echo esc_textarea($s['popis']); ?></textarea>
                            <p class="description"><?php esc_html_e('Pro stránky bez vlastního popisku (a bez ručně vyplněného výňatku). Doporučeno 120–160 znaků.', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Výchozí obrázek pro sdílení', 'marmal-breakdance-plus'); ?></th>
                        <td>
                            <?php vyber_obrazku(OPTION . '[obrazek]', $img, 'ms-obrazek'); ?>
                            <p class="description"><?php esc_html_e('Pro stránky bez vlastního ani náhledového obrázku. Ideálně 1200 × 630 px – výřez v tomto rozměru (JPEG) se vytvoří sám po uložení.', 'marmal-breakdance-plus'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-locale"><?php esc_html_e('Jazyk pro sdílení (og:locale)', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php vyber(OPTION . '[locale]', array(
                            ''      => sprintf(/* translators: %s: locale */ __('Automaticky podle jazyka stránky (teď %s)', 'marmal-breakdance-plus'), locale_og()),
                            'cs_CZ' => 'cs_CZ', 'sk_SK' => 'sk_SK', 'en_US' => 'en_US', 'en_GB' => 'en_GB', 'de_DE' => 'de_DE',
                        ), $s['locale'], 'ms-locale'); ?>
                            <p class="description"><?php esc_html_e('Na CZ + EN webu s Polylangem nebo WPML nech automaticky – jazyk se vezme z překladu stránky.', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                </table>
            </div>

            <?php jazyky_box($s); ?>

            <?php firma_box($s); ?>

            <div class="marmal-seo-nast__box">
                <h2><?php esc_html_e('Typy obsahu a indexování', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Box „SEO a sdílení“ u', 'marmal-breakdance-plus'); ?></th>
                        <td>
                            <?php foreach ($typy as $slug => $nazev) : ?>
                                <label><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[typy][]" value="<?php echo esc_attr($slug); ?>"<?php checked(!$s['typy'] || in_array($slug, $s['typy'], true)); ?>> <?php echo esc_html($nazev); ?> <code><?php echo esc_html($slug); ?></code></label><br>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Nic nezaškrtnuté = všechny. Titulek a popisek ze vzoru dostanou všechny stránky webu i tak.', 'marmal-breakdance-plus'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Neindexovat', 'marmal-breakdance-plus'); ?></th>
                        <td>
                            <?php zaskrtavatko('noindex_autor', __('Archivy autorů (/author/…) – na webu firmy jen kopie blogu', 'marmal-breakdance-plus')); ?>
                            <?php zaskrtavatko('noindex_datum', __('Archivy podle data (/2026/11/…)', 'marmal-breakdance-plus')); ?>
                            <?php zaskrtavatko('noindex_stitky', __('Štítky příspěvků', 'marmal-breakdance-plus')); ?>
                            <?php zaskrtavatko('noindex_prilohy', __('Stránky příloh (samostatná stránka obrázku)', 'marmal-breakdance-plus')); ?>
                            <p class="description"><?php esc_html_e('Výsledky vyhledávání na webu jsou neindexované vždy. Neindexované archivy zmizí i ze sitemap.', 'marmal-breakdance-plus'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="marmal-seo-nast__box" id="drobecky">
                <h2><?php esc_html_e('Drobečková navigace', 'marmal-breakdance-plus'); ?></h2>
                <p><?php echo wp_kses(__('Na web ji vložíš elementem <strong>Shortcode</strong> s kódem <code>[marmal_drobecky]</code> – nejlépe do šablony (detail bytu, příspěvek, stránka). Na úvodní stránce se nezobrazí. Vzhled přebírá písmo a barvy z místa, kam ji vložíš; volby: <code>oddelovac="»"</code>, <code>uvod="Domů"</code>, <code>aktualni="ne"</code>.', 'marmal-breakdance-plus'), array('strong' => array(), 'code' => array())); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Pro Google', 'marmal-breakdance-plus'); ?></th>
                        <td><?php zaskrtavatko('drobecky_schema', __('Vypisovat strukturovaná data BreadcrumbList (Google pak ve výsledcích ukáže cestu místo adresy)', 'marmal-breakdance-plus')); ?>
                            <p class="description"><?php esc_html_e('Funguje i bez viditelné navigace. Když používáš element Breadcrumbs z Breakdance, ověř v testu výsledků s bohatým obsahem, že BreadcrumbList není na stránce dvakrát.', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-drobecky_uvod"><?php esc_html_e('Text první položky', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php pole_text('drobecky_uvod', __('Úvod', 'marmal-breakdance-plus'), 60); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms-drobecky_oddelovac"><?php esc_html_e('Oddělovač', 'marmal-breakdance-plus'); ?></label></th>
                        <td><?php vyber(OPTION . '[drobecky_oddelovac]', array_combine(drobecky_oddelovace(), drobecky_oddelovace()), (string) $s['drobecky_oddelovac'], 'ms-drobecky_oddelovac'); ?></td>
                    </tr>
                    <?php
                    $vlastni_typy = array_diff_key($typy, array('page' => 1, 'post' => 1));
                    foreach ($vlastni_typy as $slug => $nazev) :
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html(sprintf(/* translators: %s: typ obsahu */ __('Nadřazená stránka – %s', 'marmal-breakdance-plus'), $nazev)); ?></th>
                            <td><?php
                            wp_dropdown_pages(array(
                                'name'              => esc_attr(OPTION . '[drobecky_rodice][' . $slug . ']'),
                                'selected'          => absint($s['drobecky_rodice'][$slug] ?? 0),
                                'show_option_none'  => esc_html__('— žádná (archiv typu, když existuje) —', 'marmal-breakdance-plus'),
                                'option_none_value' => '0',
                                'post_status'       => array('publish'),
                            ));
                            ?>
                                <p class="description"><?php esc_html_e('Např. u jednotek „Přehled bytů“ → Úvod › Přehled bytů › Byt 1.01.', 'marmal-breakdance-plus'); ?></p></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="marmal-seo-nast__box" id="sitemap">
                <h2><?php esc_html_e('Sitemap a robots.txt', 'marmal-breakdance-plus'); ?></h2>
                <table class="marmal-seo-nast__tab">
                    <tr><th><?php esc_html_e('Sitemap', 'marmal-breakdance-plus'); ?></th><td>
                        <?php if ($stav['sitemap']) : ?>
                            <a href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/wp-sitemap.xml')); ?></a> – <?php esc_html_e('sitemap WordPressu; modul z ní vyřazuje neindexované stránky a archivy. Tuto adresu vlož do Google Search Console → Soubory Sitemap.', 'marmal-breakdance-plus'); ?>
                        <?php else : ?>
                            <strong><?php esc_html_e('Vypnutá', 'marmal-breakdance-plus'); ?></strong> – <?php esc_html_e('WordPress ji nevytváří, když je web skrytý před vyhledávači, nebo ji vypnul jiný plugin.', 'marmal-breakdance-plus'); ?>
                        <?php endif; ?>
                        <?php if ($stav['sitemap_soubor']) : ?>
                            <br><strong><?php esc_html_e('Pozor:', 'marmal-breakdance-plus'); ?></strong> <?php esc_html_e('v kořeni webu je soubor sitemap.xml (zbytek po starém webu nebo pluginu) – smaž ho, aby Google nečetl zastaralou sitemap.', 'marmal-breakdance-plus'); ?>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>robots.txt</th><td>
                        <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/robots.txt')); ?></a>
                        <?php if ($stav['robots_soubor']) : ?>
                            <br><strong><?php esc_html_e('Pozor:', 'marmal-breakdance-plus'); ?></strong> <?php esc_html_e('v kořeni webu je fyzický soubor robots.txt – WordPress ani modul ho neovlivní a řádky níže se nepoužijí. Pokud v něm nic zvláštního není, smaž ho (přes FTP / správce souborů hostingu).', 'marmal-breakdance-plus'); ?>
                        <?php else : ?>
                            – <?php esc_html_e('generuje WordPress: zakáže /wp-admin/ a přidá odkaz na sitemap.', 'marmal-breakdance-plus'); ?>
                        <?php endif; ?>
                    </td></tr>
                </table>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ms-robots"><?php esc_html_e('Vlastní řádky robots.txt', 'marmal-breakdance-plus'); ?></label></th>
                        <td><textarea id="ms-robots" name="<?php echo esc_attr(OPTION); ?>[robots]" rows="5" class="large-text code" placeholder="User-agent: *&#10;Disallow: /?s="><?php echo esc_textarea((string) $s['robots']); ?></textarea>
                            <p class="description"><?php esc_html_e('Obvykle nic. Přidají se na konec robots.txt. Povolené jen řádky User-agent, Disallow, Allow, Sitemap, Crawl-delay a komentáře (#). Pozor: „Disallow: /“ zakáže celý web.', 'marmal-breakdance-plus'); ?></p></td>
                    </tr>
                </table>
            </div>

            <?php ai_box($s); ?>

            <?php submit_button(); ?>
        </form>

        <?php import_box(); ?>

        <div class="marmal-seo-nast__box">
            <h2><?php esc_html_e('Kontrola po nasazení', 'marmal-breakdance-plus'); ?></h2>
            <ol class="marmal-seo-nast__kroky">
                <li><?php echo wp_kses(sprintf(/* translators: %s: odkaz */ __('Náhled sdílení: <a href="%s" target="_blank" rel="noopener">Facebook Sharing Debugger</a> → vlož adresu stránky → „Scrape Again“ (Facebook si jinak pamatuje starý náhled).', 'marmal-breakdance-plus'), 'https://developers.facebook.com/tools/debug/'), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))); ?></li>
                <li><?php echo wp_kses(sprintf(/* translators: %s: odkaz */ __('Drobečková navigace pro Google: <a href="%s" target="_blank" rel="noopener">Test výsledků s bohatou podporou</a>.', 'marmal-breakdance-plus'), 'https://search.google.com/test/rich-results'), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))); ?></li>
                <li><?php esc_html_e('Google Search Console → Kontrola URL u úvodu a jedné podstránky → „Požádat o indexování“; Soubory Sitemap → wp-sitemap.xml.', 'marmal-breakdance-plus'); ?></li>
            </ol>
        </div>
    </div>
    <?php
}

/** Box převodu z Yoastu / Rank Mathu (vlastní formulář mimo nastavení). */
function import_box(): void
{
    // phpcs:ignore WordPress.Security.NonceVerification -- jen zobrazení výsledku po přesměrování
    $prevedeno = isset($_GET['prevedeno']) ? sanitize_key(wp_unslash($_GET['prevedeno'])) : '';
    $posledni  = get_option(OPTION . '_import');
    ?>
    <div class="marmal-seo-nast__box" id="prevod">
        <h2><?php esc_html_e('Převod z Yoast SEO / Rank Math', 'marmal-breakdance-plus'); ?></h2>
        <p><?php esc_html_e('Převede titulky, popisky, obrázky pro sdílení a noindex stránek a z nastavení webu oddělovač a výchozí obrázek. Data původního pluginu zůstanou beze změny. Postup: 1. převést, 2. zkontrolovat pár stránek, 3. deaktivovat Yoast / Rank Math – teprve pak modul začne vypisovat.', 'marmal-breakdance-plus'); ?></p>
        <?php if ($prevedeno && is_array($posledni) && ($posledni['zdroj'] ?? '') === $prevedeno) :
            $v = $posledni['vysledek']; ?>
            <div class="notice notice-success inline"><p><?php echo esc_html(sprintf(
                /* translators: 1: zdroj, 2–6: počty */
                __('Převedeno z %1$s: %2$d stránek – titulky %3$d, popisky %4$d, obrázky %5$d, noindex %6$d. Přeskočeno (už vyplněné): %7$d.', 'marmal-breakdance-plus'),
                import_zdroje()[$prevedeno] ?? $prevedeno, $v['stranek'], $v['title'], $v['desc'], $v['image'], $v['noindex'], $v['preskoceno']
            )); ?>
            <?php if (!empty($v['globalni'])) {
                echo ' ' . esc_html(sprintf(/* translators: %s: seznam nastavení */ __('Nastavení webu: %s.', 'marmal-breakdance-plus'), implode(', ', $v['globalni'])));
            } ?></p></div>
        <?php endif; ?>
        <table class="marmal-seo-nast__tab">
            <tr><th><?php esc_html_e('Zdroj', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Stránek s daty', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Titulky', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Popisky', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Obrázky', 'marmal-breakdance-plus'); ?></th><th>noindex</th><th></th></tr>
            <?php foreach (import_zdroje() as $zdroj => $nazev) :
                $p = import_prehled($zdroj); ?>
                <tr>
                    <td><strong><?php echo esc_html($nazev); ?></strong><?php if ($p['globalni']) {
                        echo '<br><small>' . esc_html(sprintf(/* translators: %s: seznam */ __('nastavení webu: %s', 'marmal-breakdance-plus'), implode(', ', array_keys($p['globalni'])))) . '</small>';
                    } ?></td>
                    <td><?php echo (int) $p['stranek']; ?></td>
                    <td><?php echo (int) $p['title']; ?></td>
                    <td><?php echo (int) $p['desc']; ?></td>
                    <td><?php echo (int) $p['image']; ?></td>
                    <td><?php echo (int) $p['noindex']; ?></td>
                    <td>
                        <?php if ($p['stranek'] || $p['globalni']) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('marmal_seo_import'); ?>
                                <input type="hidden" name="action" value="marmal_seo_import">
                                <input type="hidden" name="zdroj" value="<?php echo esc_attr($zdroj); ?>">
                                <label><input type="checkbox" name="prepsat" value="1"> <?php esc_html_e('přepsat už vyplněné', 'marmal-breakdance-plus'); ?></label>
                                <button type="submit" class="button button-primary"><?php esc_html_e('Převést', 'marmal-breakdance-plus'); ?></button>
                            </form>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e('žádná data', 'marmal-breakdance-plus'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php
}
