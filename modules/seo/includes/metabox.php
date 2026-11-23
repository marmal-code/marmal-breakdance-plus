<?php
/**
 * Modul SEO – box „SEO a sdílení“ u stránek, příspěvků a dalších veřejných typů obsahu.
 *
 * Funguje v klasickém i blokovém editoru (blokový editor ukládá boxy samostatným požadavkem
 * na post.php – save_post se spustí stejně). U stránek z Breakdance je box na obrazovce
 * úpravy stránky, ne v builderu.
 *
 * Ukládá se jen s nonce `marmal_seo_ulozit` a oprávněním edit_post; hodnoty bez HTML
 * s limitem délky (titulek 120, popisek 300 znaků).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function ($post_type) {
    if (!vypisuje() || !in_array($post_type, typy_obsahu(), true)) {
        return;
    }
    add_meta_box('marmal-seo-box', __('SEO a sdílení', 'marmal-breakdance-plus'), __NAMESPACE__ . '\box', $post_type, 'normal', 'default');
});

function box(\WP_Post $post): void
{
    wp_nonce_field('marmal_seo_ulozit', 'marmal_seo_nonce');
    $v   = vlastni((int) $post->ID);
    $img = $v['image_id'] && wp_attachment_is_image($v['image_id']) ? $v['image_id'] : 0;
    ?>
    <div class="marmal-seo" id="marmal-seo">
        <div class="marmal-seo__pole">
            <p>
                <label for="marmal-seo-title"><strong><?php esc_html_e('Titulek', 'marmal-breakdance-plus'); ?></strong></label>
                <span class="marmal-seo__pocet" data-pocet="title"></span>
            </p>
            <input type="text" id="marmal-seo-title" name="marmal_seo[title]" class="widefat" maxlength="<?php echo (int) MAX_TITLE; ?>" value="<?php echo esc_attr($v['title']); ?>">
            <p class="description"><?php esc_html_e('Ve výsledcích Googlu a v záložce prohlížeče. Doporučeno do 60 znaků. Prázdné = šedý text. Lze použít %nazev%, %web%, %oddelovac%.', 'marmal-breakdance-plus'); ?></p>

            <p>
                <label for="marmal-seo-desc"><strong><?php esc_html_e('Popisek', 'marmal-breakdance-plus'); ?></strong></label>
                <span class="marmal-seo__pocet" data-pocet="desc"></span>
            </p>
            <textarea id="marmal-seo-desc" name="marmal_seo[desc]" class="widefat" rows="3" maxlength="<?php echo (int) MAX_DESC; ?>"><?php echo esc_textarea($v['description']); ?></textarea>
            <p class="description"><?php esc_html_e('Text pod titulkem ve výsledcích Googlu a při sdílení. Doporučeno 120–160 znaků, delší Google zkrátí.', 'marmal-breakdance-plus'); ?></p>

            <p><strong><?php esc_html_e('Obrázek pro sdílení', 'marmal-breakdance-plus'); ?></strong></p>
            <input type="hidden" id="marmal-seo-image" name="marmal_seo[image]" value="<?php echo (int) $img; ?>">
            <div class="marmal-seo__obr">
                <button type="button" class="button" data-seo="vybrat"><?php esc_html_e('Vybrat obrázek', 'marmal-breakdance-plus'); ?></button>
                <button type="button" class="button-link marmal-seo__odebrat" data-seo="odebrat"<?php echo $img ? '' : ' hidden'; ?>><?php esc_html_e('Odebrat (použít výchozí)', 'marmal-breakdance-plus'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Ideálně 1200 × 630 px (poměr 1,91 : 1) – výřez se vytvoří sám. Prázdné = náhledový obrázek stránky, jinak výchozí obrázek webu. Půdorys ani text v obrázku se v náhledu nečte dobře.', 'marmal-breakdance-plus'); ?></p>

            <p>
                <label>
                    <input type="checkbox" name="marmal_seo[noindex]" value="1"<?php checked($v['noindex']); ?>>
                    <?php esc_html_e('Neindexovat (noindex) – stránka se nezobrazí ve vyhledávačích a zmizí ze sitemap', 'marmal-breakdance-plus'); ?>
                </label>
            </p>
            <p class="description marmal-seo__filtr-noindex" hidden><?php esc_html_e('Neindexování nastavil jiný plugin (např. Developerské projekty).', 'marmal-breakdance-plus'); ?></p>
        </div>

        <div class="marmal-seo__nahledy">
            <p class="marmal-seo__nadpis"><?php esc_html_e('Náhled ve výsledcích Googlu', 'marmal-breakdance-plus'); ?></p>
            <div class="marmal-seo__google">
                <div class="marmal-seo__g-web"><span class="marmal-seo__g-ikona" aria-hidden="true"></span><span><span class="marmal-seo__g-nazev"></span><span class="marmal-seo__g-url"></span></span></div>
                <div class="marmal-seo__g-titulek"></div>
                <div class="marmal-seo__g-popis"></div>
            </div>
            <p class="marmal-seo__nadpis"><?php esc_html_e('Náhled při sdílení (Facebook, Messenger, WhatsApp)', 'marmal-breakdance-plus'); ?></p>
            <div class="marmal-seo__fb">
                <div class="marmal-seo__fb-obr"><img alt="" hidden><span class="marmal-seo__fb-bez"><?php esc_html_e('bez obrázku', 'marmal-breakdance-plus'); ?></span></div>
                <div class="marmal-seo__fb-text">
                    <div class="marmal-seo__fb-domena"></div>
                    <div class="marmal-seo__fb-titulek"></div>
                    <div class="marmal-seo__fb-popis"></div>
                </div>
            </div>
            <p class="description marmal-seo__noindex-info" hidden><?php esc_html_e('Stránka je neindexovaná – ve výsledcích Googlu se neobjeví.', 'marmal-breakdance-plus'); ?></p>
        </div>
    </div>
    <?php
}

/** Adresa náhledu obrázku pro administraci. */
function nahled_obrazku(int $id): string
{
    if (!$id) {
        return '';
    }
    $src = wp_get_attachment_image_src($id, 'medium_large') ?: wp_get_attachment_image_src($id, 'large');
    return $src ? (string) $src[0] : '';
}

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true) || !vypisuje()) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, typy_obsahu(), true)) {
        return;
    }
    global $post;
    $post_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
    $d       = $post_id ? data_prispevku($post_id) : array('zaloha' => array('title' => '', 'description' => '', 'image_id' => 0), 'zdroj' => array('noindex_filtr' => false));
    $vlastni = $post_id ? vlastni($post_id) : array('image_id' => 0);
    $odkaz   = $post_id ? (string) get_permalink($post_id) : home_url('/');

    wp_enqueue_media();
    wp_enqueue_style('marmal-seo-admin', MARMAL_BDP_URL . 'modules/seo/assets/seo-admin.css', array(), MARMAL_BDP_VERSION);
    wp_enqueue_script('marmal-seo-admin', MARMAL_BDP_URL . 'modules/seo/assets/seo-admin.js', array('media-editor'), MARMAL_BDP_VERSION, true);
    $cfg = array(
        'web'        => nazev_webu(),
        'popisWebu'  => popis_webu(),
        'oddelovac'  => oddelovac(),
        'nazev'      => $post_id ? nazev_prispevku($post_id) : '',
        'url'        => $odkaz,
        'domena'     => (string) wp_parse_url(home_url('/'), PHP_URL_HOST),
        'ikona'      => function_exists('get_site_icon_url') ? (string) get_site_icon_url(32) : '',
        'zaloha'     => array(
            'title'       => $d['zaloha']['title'],
            'description' => $d['zaloha']['description'],
            'obrazek'     => nahled_obrazku((int) $d['zaloha']['image_id']),
        ),
        'obrazek'    => nahled_obrazku((int) $vlastni['image_id']),
        'noindexFiltr' => !empty($d['zdroj']['noindex_filtr']),
        'limity'     => array('title' => DOP_TITLE, 'descMin' => DOP_DESC_MIN, 'descMax' => DOP_DESC_MAX),
        'i18n'       => array(
            'vybrat'  => __('Obrázek pro sdílení', 'marmal-breakdance-plus'),
            'pouzit'  => __('Použít obrázek', 'marmal-breakdance-plus'),
            /* translators: %d: počet znaků */
            'znaku'   => __('%d znaků', 'marmal-breakdance-plus'),
            'dlouhy'  => __('– Google zkrátí', 'marmal-breakdance-plus'),
            'kratky'  => __('– krátký', 'marmal-breakdance-plus'),
            'zaloha'  => __('(použije se šedý text)', 'marmal-breakdance-plus'),
        ),
    );
    wp_add_inline_script('marmal-seo-admin', 'window.marmalSeo = ' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

/** Sanitizace hodnot z boxu (bez wp_unslash – volající odslashuje). */
function sanitizovat_box($in): array
{
    $in  = is_array($in) ? $in : array();
    $img = absint($in['image'] ?? 0);
    return array(
        'title'   => cisty_text($in['title'] ?? '', MAX_TITLE),
        'desc'    => cisty_text($in['desc'] ?? '', MAX_DESC),
        'image'   => ($img && wp_attachment_is_image($img)) ? $img : 0,
        'noindex' => !empty($in['noindex']),
    );
}

add_action('save_post', function ($post_id, $post) {
    if (!isset($_POST['marmal_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['marmal_seo_nonce'])), 'marmal_seo_ulozit')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!$post instanceof \WP_Post || !in_array($post->post_type, typy_obsahu(), true)) {
        return;
    }
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitizuje sanitizovat_box()
    $d = sanitizovat_box(isset($_POST['marmal_seo']) ? wp_unslash($_POST['marmal_seo']) : array());

    $ulozit = array(META_TITLE => $d['title'], META_DESC => $d['desc'], META_IMAGE => $d['image'], META_NOINDEX => $d['noindex'] ? '1' : '');
    foreach ($ulozit as $klic => $hodnota) {
        if ($hodnota === '' || $hodnota === 0) {
            delete_post_meta($post_id, $klic);
        } else {
            update_post_meta($post_id, $klic, is_string($hodnota) ? wp_slash($hodnota) : $hodnota);
        }
    }
    // Výřez 1200 × 630 pro obrázek, který se opravdu použije (vlastní / z pluginu / náhledový / výchozí).
    $obr = (int) data_prispevku((int) $post_id)['image_id'];
    if ($obr) {
        og_vytvorit($obr);
    }
}, 10, 2);
