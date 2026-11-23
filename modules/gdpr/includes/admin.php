<?php
/**
 * Modul Ochrana osobních údajů – stránka MarMal → Ochrana osobních údajů.
 *
 * Nastavení přes options.php (register_setting: nonce + manage_options hlídá WordPress).
 * Akce (vytvořit návrh, zveřejnit) přes admin-post: manage_options + publish_pages + nonce.
 * Zveřejněná stránka se nikdy nepřepisuje – pracuje se vždy na konceptu.
 */

namespace MarmalElements\Gdpr;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA     = 'marmal-gdpr';
const META_UCELY  = '_marmal_gdpr_ucely';     // účely, ze kterých byl text vygenerován
const META_PUVODNI = '_marmal_gdpr_puvodni';  // ID dosavadní stránky, kterou zveřejnění nahradilo

add_action('admin_init', function () {
    register_setting('marmal_gdpr', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    add_submenu_page('marmal', __('Ochrana osobních údajů', 'marmal-breakdance-plus'), __('Ochrana os. údajů', 'marmal-breakdance-plus'), 'manage_options', STRANKA, __NAMESPACE__ . '\stranka');
}, 20);

function url_stranky(array $arg = array()): string
{
    return add_query_arg(array_merge(array('page' => STRANKA), $arg), admin_url('admin.php'));
}

/** Upozornění na nedoplněná místa – na stránkách MarMal a v editoru stránky zásad. */
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    $obr = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$obr) {
        return;
    }
    $id    = stranka_modulu();
    $nase  = strpos((string) $obr->id, 'marmal') !== false && (string) $obr->id !== 'marmal_page_' . STRANKA;
    $editor = $obr->base === 'post' && $id > 0 && isset($_GET['post']) && (int) $_GET['post'] === $id; // phpcs:ignore WordPress.Security.NonceVerification
    if (!$nase && !$editor) {
        return;
    }
    $n = pocet_doplnit();
    if ($n <= 0) {
        return;
    }
    echo '<div class="notice notice-warning"><p>' . esc_html(sprintf(
        /* translators: %d: počet míst */
        _n('Na stránce Zásady ochrany osobních údajů zbývá doplnit %d místo (na webu je zvýrazněné žlutě).', 'Na stránce Zásady ochrany osobních údajů zbývá doplnit %d míst (na webu jsou zvýrazněná žlutě).', $n, 'marmal-breakdance-plus'),
        $n
    )) . ' <a href="' . esc_url(url_stranky()) . '">' . esc_html__('Doplnit', 'marmal-breakdance-plus') . '</a></p></div>';
});

/** Vytvoří koncept, nebo přegeneruje existující koncept (předchozí obsah zůstane v revizích). */
function vytvorit_navrh()
{
    $data = array(
        'post_type'    => 'page',
        'post_status'  => 'draft',
        'post_title'   => __('Zásady ochrany osobních údajů', 'marmal-breakdance-plus'),
        'post_content' => vzor_gdpr(),
    );
    $id = navrh();
    if ($id > 0) {
        $data['ID'] = $id;
        unset($data['post_title']);
        $id = wp_update_post(wp_slash($data), true);
    } else {
        $puvodni = stranka_zasad();
        if ($puvodni > 0) {
            $data['post_parent'] = (int) wp_get_post_parent_id($puvodni);
        }
        $id = wp_insert_post(wp_slash($data), true);
    }
    if (is_wp_error($id) || !$id) {
        return is_wp_error($id) ? $id : new \WP_Error('marmal_gdpr', 'insert');
    }
    update_post_meta((int) $id, META_NAVRH, 1);
    update_post_meta((int) $id, META_UCELY, implode(',', (array) nastaveni()['ucely']));
    return (int) $id;
}

/**
 * Zveřejní koncept: převezme adresu (slug) a rodiče dosavadní stránky zásad, dosavadní stránku
 * přepne na koncept „(původní)“, nastaví ji jako stránku zásad ve WordPressu i v Patičce.
 */
function zverejnit_navrh()
{
    $novy = navrh();
    if ($novy <= 0) {
        return new \WP_Error('marmal_gdpr', 'no-draft');
    }
    $stary = stranka_zasad();
    $slug  = 'zasady-ochrany-osobnich-udaju';
    $rodic = (int) wp_get_post_parent_id($novy);
    if ($stary > 0 && $stary !== $novy) {
        $slug  = (string) get_post_field('post_name', $stary) ?: $slug;
        $rodic = (int) wp_get_post_parent_id($stary);
        $r = wp_update_post(wp_slash(array(
            'ID'          => $stary,
            'post_status' => 'draft',
            'post_name'   => $slug . '-puvodni',
            /* translators: %s: původní název stránky */
            'post_title'  => sprintf(__('%s (původní)', 'marmal-breakdance-plus'), (string) get_the_title($stary)),
        )), true);
        if (is_wp_error($r)) {
            return $r;
        }
        delete_post_meta($stary, META_NAVRH);   // původní stránka už není návrh modulu
        update_post_meta($novy, META_PUVODNI, $stary);
    }
    $r = wp_update_post(wp_slash(array(
        'ID'          => $novy,
        'post_status' => 'publish',
        'post_name'   => $slug,
        'post_parent' => $rodic,
    )), true);
    if (is_wp_error($r)) {
        return $r;
    }
    update_option('wp_page_for_privacy_policy', $novy);
    if (function_exists('MarmalElements\Paticka\nastaveni') && defined('MarmalElements\Paticka\OPTION')) {
        $p = (array) get_option(\MarmalElements\Paticka\OPTION, array());
        if (!empty($p['zasady_stranka']) && (int) $p['zasady_stranka'] === $stary) {
            $p['zasady_stranka'] = $novy;
            update_option(\MarmalElements\Paticka\OPTION, $p);
        }
    }
    $s = nastaveni();
    if ($s['ucinnost'] === '') {
        $s['ucinnost'] = wp_date('Y-m-d');
        update_option(OPTION, $s);
    }
    return $novy;
}

add_action('admin_post_marmal_gdpr_navrh', function () {
    if (!current_user_can('manage_options') || !current_user_can('publish_pages')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_gdpr_navrh');
    $id = vytvorit_navrh();
    wp_safe_redirect(url_stranky(array('zprava' => is_wp_error($id) ? 'chyba' : 'navrh')));
    exit;
});

add_action('admin_post_marmal_gdpr_zverejnit', function () {
    if (!current_user_can('manage_options') || !current_user_can('publish_pages')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_gdpr_zverejnit');
    $id = zverejnit_navrh();
    wp_safe_redirect(url_stranky(array('zprava' => is_wp_error($id) ? 'chyba' : 'zverejneno')));
    exit;
});

function pole(string $k, string $popisek, string $napoveda = '', string $typ = 'text', bool $chybi = false): void
{
    $s = nastaveni();
    $n = OPTION . '[' . $k . ']';
    echo '<tr><th scope="row"><label for="mmg-' . esc_attr($k) . '">' . esc_html($popisek) . '</label></th><td>';
    echo '<input type="' . esc_attr($typ) . '" id="mmg-' . esc_attr($k) . '" name="' . esc_attr($n) . '" value="' . esc_attr((string) $s[$k]) . '" class="regular-text' . ($chybi ? ' mmg-chybi' : '') . '">';
    if ($napoveda !== '') {
        echo '<p class="description">' . esc_html($napoveda) . '</p>';
    }
    echo '</td></tr>';
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s       = nastaveni();
    $ch      = chybejici();
    $zprava  = isset($_GET['zprava']) ? sanitize_key(wp_unslash($_GET['zprava'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $navrh   = navrh();
    $zasady  = stranka_zasad();
    $modul   = $zasady > 0 && get_post_meta($zasady, META_NAVRH, true);
    $pocet   = pocet_doplnit();
    $jina_ucely = $modul && !$navrh && (string) get_post_meta($zasady, META_UCELY, true) !== implode(',', (array) $s['ucely']);
    ?>
    <style>
        .mmg .mmg-box{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:14px 18px;margin:16px 0;max-width:980px}
        .mmg .mmg-box h2{margin-top:4px}
        .mmg mark,.mmg .mmg-chybi{background:#ffe066!important;color:#5c3c00}
        .mmg mark{padding:0 .3em;border-radius:3px;font-weight:600}
        .mmg .mmg-chybi{border-color:#c99a00!important}
        .mmg .mmg-ok{color:#008a20;font-weight:600}
        .mmg ol li,.mmg ul.mmg-seznam li{margin-bottom:6px}
        .mmg .mmg-ucely label{display:block;margin:4px 0}
        .mmg .mmg-ucely small{color:#646970}
        .mmg code{user-select:all}
    </style>
    <div class="wrap mmg">
        <h1><?php esc_html_e('MarMal – Ochrana osobních údajů', 'marmal-breakdance-plus'); ?></h1>
        <p><?php esc_html_e('Připraví stránku Zásady ochrany osobních údajů podle čl. 13 GDPR a zákona č. 110/2019 Sb. Všechno se vyplňuje tady – údaje o správci si odsud bere i Patička. Co chybí, je na stránce zvýrazněné žlutě – nejde to přehlédnout.', 'marmal-breakdance-plus'); ?></p>

        <?php settings_errors(); ?>
        <?php if ($zprava === 'navrh') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Návrh stránky je připravený jako koncept. Prohlédněte si ho, doplňte žlutá místa a zveřejněte.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'zverejneno') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Zásady jsou zveřejněné a nastavené jako stránka ochrany osobních údajů – odkaz v Patičce vede na ně. Dosavadní stránka zůstala jako koncept „(původní)“.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'chyba') : ?>
            <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Akce se nepodařila. Zkuste to znovu, případně zkontrolujte stránky v koši.', 'marmal-breakdance-plus'); ?></p></div>
        <?php endif; ?>

        <div class="mmg-box">
            <h2><?php esc_html_e('Stav', 'marmal-breakdance-plus'); ?></h2>
            <p><strong><?php esc_html_e('Stránka zásad na webu:', 'marmal-breakdance-plus'); ?></strong>
                <?php if ($zasady > 0) : ?>
                    <a href="<?php echo esc_url((string) get_permalink($zasady)); ?>" target="_blank" rel="noopener"><?php echo esc_html(get_the_title($zasady)); ?></a>
                    (<?php echo $modul ? esc_html__('text z tohoto modulu', 'marmal-breakdance-plus') : esc_html__('vlastní text – modul ho nemění', 'marmal-breakdance-plus'); ?>)
                <?php else : ?>
                    <?php esc_html_e('žádná', 'marmal-breakdance-plus'); ?>
                <?php endif; ?></p>
            <p><strong><?php esc_html_e('Návrh (koncept):', 'marmal-breakdance-plus'); ?></strong>
                <?php if ($navrh > 0) : ?>
                    <a href="<?php echo esc_url((string) get_preview_post_link($navrh)); ?>" target="_blank" rel="noopener"><?php esc_html_e('Náhled', 'marmal-breakdance-plus'); ?></a> ·
                    <a href="<?php echo esc_url((string) get_edit_post_link($navrh, 'url')); ?>"><?php esc_html_e('Upravit text', 'marmal-breakdance-plus'); ?></a>
                <?php else : ?>
                    <?php esc_html_e('není', 'marmal-breakdance-plus'); ?>
                <?php endif; ?></p>

            <?php if ($ch) : ?>
                <p><strong><?php esc_html_e('Zbývá vyplnit:', 'marmal-breakdance-plus'); ?></strong></p>
                <ul class="mmg-seznam">
                    <?php foreach ($ch as $polozka) : ?>
                        <li><mark><?php echo esc_html($polozka[0]); ?></mark> – <?php esc_html_e('vyplňte níže', 'marmal-breakdance-plus'); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="mmg-ok"><?php esc_html_e('Všechny potřebné údaje jsou vyplněné.', 'marmal-breakdance-plus'); ?></p>
            <?php endif; ?>
            <?php if ($pocet > count($ch)) : ?>
                <p><mark><?php esc_html_e('V textu stránky zůstalo ručně ponechané zvýraznění „DOPLŇTE“ – najdete ho v editoru stránky.', 'marmal-breakdance-plus'); ?></mark></p>
            <?php endif; ?>
            <?php if ($jina_ucely) : ?>
                <p><mark><?php esc_html_e('Od zveřejnění se změnily účely zpracování. Vytvořte nový návrh, zkontrolujte ho a zveřejněte – nahradí současnou stránku.', 'marmal-breakdance-plus'); ?></mark></p>
            <?php endif; ?>

            <p>
                <a class="button<?php echo $navrh ? '' : ' button-primary'; ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_gdpr_navrh'), 'marmal_gdpr_navrh')); ?>"
                    <?php if ($navrh) : ?>onclick="return confirm('<?php echo esc_js(__('Text konceptu se vygeneruje znovu podle nastavení. Ruční úpravy konceptu se přepíšou (zůstanou v revizích). Pokračovat?', 'marmal-breakdance-plus')); ?>')"<?php endif; ?>>
                    <?php echo $navrh ? esc_html__('Vygenerovat návrh znovu', 'marmal-breakdance-plus') : esc_html__('Vytvořit návrh stránky', 'marmal-breakdance-plus'); ?></a>
                <?php if ($navrh) : ?>
                    <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_gdpr_zverejnit'), 'marmal_gdpr_zverejnit')); ?>"
                        onclick="return confirm('<?php echo esc_js($zasady > 0 ? __('Návrh se zveřejní na adrese dosavadní stránky zásad a ta se přepne na koncept „(původní)“. Pokračovat?', 'marmal-breakdance-plus') : __('Návrh se zveřejní a nastaví jako stránka ochrany osobních údajů. Pokračovat?', 'marmal-breakdance-plus')); ?>')">
                        <?php esc_html_e('Zveřejnit návrh', 'marmal-breakdance-plus'); ?></a>
                <?php endif; ?>
            </p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('marmal_gdpr'); ?>
            <div class="mmg-box">
                <h2><?php esc_html_e('Správce a kontakt', 'marmal-breakdance-plus'); ?></h2>
                <p><?php esc_html_e('Údaje o správci jsou zároveň údaji o podnikateli podle § 435 občanského zákoníku – modul Patička je bere odsud (vyplňují se jen tady).', 'marmal-breakdance-plus'); ?></p>
                <table class="form-table" role="presentation">
                    <?php
                    pole('firma', __('Firma / jméno a příjmení', 'marmal-breakdance-plus'), __('Např. Noitami akustika s.r.o. nebo Jan Novák. Povinné.', 'marmal-breakdance-plus'), 'text', isset($ch['firma']));
                    pole('sidlo', __('Sídlo', 'marmal-breakdance-plus'), __('Ulice 123, 252 28 Černošice. Povinné.', 'marmal-breakdance-plus'), 'text', isset($ch['sidlo']));
                    pole('ico', __('IČO', 'marmal-breakdance-plus'), __('Kontroluje se kontrolní číslice. Povinné.', 'marmal-breakdance-plus'), 'text', isset($ch['ico']));
                    pole('dic', __('DIČ', 'marmal-breakdance-plus'), __('Jen u plátců DPH (nepovinné).', 'marmal-breakdance-plus'));
                    pole('rejstrik', __('Zápis v rejstříku', 'marmal-breakdance-plus'), __('Např. „Zapsáno v obchodním rejstříku vedeném Městským soudem v Praze, oddíl C, vložka 123456“ nebo u OSVČ „Zapsán v živnostenském rejstříku vedeném Městským úřadem Černošice“.', 'marmal-breakdance-plus'));
                    pole('email', __('E-mail pro ochranu údajů', 'marmal-breakdance-plus'), __('Na tento e-mail lidé posílají žádosti (přístup, výmaz…). Povinné.', 'marmal-breakdance-plus'), 'email', isset($ch['email']));
                    pole('telefon', __('Telefon', 'marmal-breakdance-plus'), __('Nepovinné.', 'marmal-breakdance-plus'));
                    pole('adresa', __('Korespondenční adresa', 'marmal-breakdance-plus'), __('Jen když se liší od sídla.', 'marmal-breakdance-plus'));
                    pole('ds', __('Datová schránka', 'marmal-breakdance-plus'), __('ID datové schránky (7 znaků). Nepovinné.', 'marmal-breakdance-plus'));
                    pole('poverenec', __('Pověřenec (DPO)', 'marmal-breakdance-plus'), __('Jméno a kontakt. Malé firmy a OSVČ ho obvykle mít nemusí – pak nechte prázdné a na stránce bude uvedeno, že nebyl jmenován.', 'marmal-breakdance-plus'));
                    ?>
                </table>
            </div>

            <div class="mmg-box">
                <h2><?php esc_html_e('Co web a firma s údaji dělají', 'marmal-breakdance-plus'); ?></h2>
                <p><?php esc_html_e('Zaškrtněte jen to, co opravdu děláte. Každý účel přidá na stránku oddíl: jaké údaje, proč, právní základ, jak dlouho a zda je jejich poskytnutí povinné. Provoz webu a cookies jsou na stránce vždy.', 'marmal-breakdance-plus'); ?></p>
                <div class="mmg-ucely">
                    <?php foreach (ucely() as $k => $u) : ?>
                        <label><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[ucely][]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, (array) $s['ucely'], true)); ?>>
                            <strong><?php echo esc_html($u[0]); ?></strong> <small>– <?php echo esc_html($u[1]); ?></small></label>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e('Po změně účelů klikněte nahoře na „Vytvořit návrh“ / „Vygenerovat návrh znovu“ – text účelů je v konceptu pevný, aby ho šlo ručně upravit.', 'marmal-breakdance-plus'); ?></p>
            </div>

            <div class="mmg-box">
                <h2><?php esc_html_e('Komu údaje předáváte (zpracovatelé)', 'marmal-breakdance-plus'); ?></h2>
                <p><?php esc_html_e('Seznam na stránce se aktualizuje sám po uložení – není potřeba nic generovat znovu.', 'marmal-breakdance-plus'); ?></p>
                <table class="form-table" role="presentation">
                    <?php
                    pole('hosting', __('Webhosting', 'marmal-breakdance-plus'), __('Např. WEDOS Internet, a.s.; Forpsi (INTERNET CZ, a.s.); Websupport s.r.o.', 'marmal-breakdance-plus'), 'text', isset($ch['hosting']));
                    pole('emailsluzba', __('E-mail', 'marmal-breakdance-plus'), __('Kdo provozuje e-mailové schránky: např. Seznam.cz, Google Workspace, Microsoft 365, nebo stejný hosting.', 'marmal-breakdance-plus'), 'text', isset($ch['emailsluzba']));
                    if (je_ucel('ucetnictvi')) :
                        pole('ucetni', __('Účetní / daňový poradce', 'marmal-breakdance-plus'), __('Jméno nebo firma.', 'marmal-breakdance-plus'), 'text', isset($ch['ucetni'])); ?>
                        <tr><th scope="row"></th><td><label><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[ucetni_sami]" value="1" <?php checked($s['ucetni_sami']); ?>> <?php esc_html_e('Účetnictví vedeme sami (řádek se na stránce nezobrazí)', 'marmal-breakdance-plus'); ?></label></td></tr>
                    <?php else : ?>
                        <input type="hidden" name="<?php echo esc_attr(OPTION); ?>[ucetni]" value="<?php echo esc_attr($s['ucetni']); ?>">
                        <?php if ($s['ucetni_sami']) : ?><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[ucetni_sami]" value="1"><?php endif; ?>
                    <?php endif;
                    if (je_ucel('newsletter')) {
                        pole('newsletter_sluzba', __('Newsletter', 'marmal-breakdance-plus'), __('Např. Ecomail, Mailchimp, SmartEmailing.', 'marmal-breakdance-plus'), 'text', isset($ch['newsletter_sluzba']));
                    } else {
                        echo '<input type="hidden" name="' . esc_attr(OPTION) . '[newsletter_sluzba]" value="' . esc_attr($s['newsletter_sluzba']) . '">';
                    }
                    if (je_woo()) {
                        pole('doprava', __('Dopravci', 'marmal-breakdance-plus'), __('Např. Zásilkovna s.r.o., PPL CZ s.r.o., Česká pošta, s.p.', 'marmal-breakdance-plus'), 'text', isset($ch['doprava']));
                        pole('platby', __('Platební brána', 'marmal-breakdance-plus'), __('Např. ComGate, GoPay, Stripe.', 'marmal-breakdance-plus'), 'text', isset($ch['platby']));
                    } else {
                        echo '<input type="hidden" name="' . esc_attr(OPTION) . '[doprava]" value="' . esc_attr($s['doprava']) . '"><input type="hidden" name="' . esc_attr(OPTION) . '[platby]" value="' . esc_attr($s['platby']) . '">';
                    }
                    ?>
                    <tr><th scope="row"><label for="mmg-dalsi"><?php esc_html_e('Další příjemci', 'marmal-breakdance-plus'); ?></label></th><td>
                        <textarea id="mmg-dalsi" name="<?php echo esc_attr(OPTION); ?>[dalsi]" rows="3" class="large-text"><?php echo esc_textarea($s['dalsi']); ?></textarea>
                        <p class="description"><?php esc_html_e('Každý na nový řádek, např. „Rezervační systém: Reservio, s.r.o.“ nebo „Správce webu: Martin Malý – marmal.cz“.', 'marmal-breakdance-plus'); ?></p></td></tr>
                </table>
            </div>

            <div class="mmg-box">
                <h2><?php esc_html_e('Účinnost', 'marmal-breakdance-plus'); ?></h2>
                <table class="form-table" role="presentation">
                    <?php pole('ucinnost', __('Zásady účinné od', 'marmal-breakdance-plus'), __('Prázdné = doplní se samo při zveřejnění. Při podstatné změně zásad datum posuňte.', 'marmal-breakdance-plus'), 'date'); ?>
                </table>
            </div>
            <?php submit_button(__('Uložit nastavení', 'marmal-breakdance-plus')); ?>
        </form>

        <div class="mmg-box">
            <h2><?php esc_html_e('Jak nasadit – krok za krokem', 'marmal-breakdance-plus'); ?></h2>
            <ol>
                <li><?php esc_html_e('Výše vyplňte údaje o správci (jméno nebo firmu, sídlo, IČO, případně DIČ a zápis v rejstříku) a e-mail pro ochranu údajů. Stejné údaje se zobrazí v patičce webu (modul Patička je přebírá odsud).', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Zaškrtněte účely, které opravdu děláte, a doplňte zpracovatele (hosting, e-mail, účetní…). Uložte.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Klikněte na „Vytvořit návrh stránky“. Vznikne koncept – zveřejněná stránka se zatím nemění.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Otevřete Náhled a přečtěte text. Všechno žluté „[DOPLŇTE: …]“ doplňte v nastavení výše – stránka se aktualizuje sama. Text účelů můžete upravit v editoru stránky (Upravit text).', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Pokud web sbírá i jiné údaje, než nabízí zaškrtávátka (např. rezervační systém, chat, věrnostní program), doplňte do konceptu vlastní oddíl ve stejné struktuře: jaké údaje, proč, právní základ, jak dlouho, zda je to povinné.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Když je vše v pořádku (nahoře „Všechny potřebné údaje jsou vyplněné“), klikněte na „Zveřejnit návrh“. Stránka převezme adresu dosavadní stránky zásad (např. /gdpr/), stane se stránkou ochrany osobních údajů ve WordPressu (Nastavení → Soukromí) a odkaz v Patičce povede na ni. Dosavadní stránka zůstane jako koncept „(původní)“ – po kontrole ji můžete smazat.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Zobrazení: stránka používá šablonu Breakdance pro stránky (s elementem Post Content) – stejně jako Zásady cookies. Pokud by se zobrazila bez hlavičky a patičky, vytvořte v Breakdance → Templates šablonu pro stránky s elementem Post Content.', 'marmal-breakdance-plus'); ?></li>
                <li><?php esc_html_e('Pod formuláře vložte krátkou informaci shortcodem v elementu Shortcode (nebo Text):', 'marmal-breakdance-plus'); ?> <code>[marmal_gdpr_formular]</code>
                    <?php esc_html_e('– zobrazí „Údaje z formuláře použijeme jen k vyřízení vašeho dotazu. Jak chráníme osobní údaje“ s odkazem. U kontaktního formuláře nepřidávejte povinné zaškrtávátko „souhlasím se zpracováním“ – zpracování stojí na vyřízení dotazu, ne na souhlasu; souhlas (nezaškrtnutý) patří jen k newsletteru.', 'marmal-breakdance-plus'); ?>
                    <?php esc_html_e('Vlastní text:', 'marmal-breakdance-plus'); ?> <code>[marmal_gdpr_formular text="Údaje použijeme jen k vyřízení objednávky."]</code></li>
                <li><?php esc_html_e('Kontrola jednou ročně a při každé změně (nový formulář, nová služba, jiný hosting): upravte nastavení, případně vygenerujte nový návrh, a posuňte datum účinnosti.', 'marmal-breakdance-plus'); ?></li>
            </ol>
            <p><strong><?php esc_html_e('Shortcody na stránce', 'marmal-breakdance-plus'); ?></strong> <?php esc_html_e('(mění se samy podle nastavení):', 'marmal-breakdance-plus'); ?>
                <code>[marmal_gdpr_spravce]</code> <code>[marmal_gdpr_kontakt]</code> <code>[marmal_gdpr_prijemci]</code> <code>[marmal_gdpr_cookies]</code> <code>[marmal_gdpr_ucinnost]</code></p>
            <p class="description"><?php esc_html_e('Text vychází z čl. 13 GDPR, zákona č. 110/2019 Sb. a doporučení ÚOOÚ (uoou.gov.cz). Je to vzor, ne právní služba – u neobvyklého zpracování (zdravotní údaje, kamerový systém, údaje dětí ve větším rozsahu) ho nechte zkontrolovat odborníkem.', 'marmal-breakdance-plus'); ?></p>
        </div>
    </div>
    <?php
}
