<?php
/**
 * Modul SEO – boxy „Firma pro vyhledávače a AI“ a „AI vyhledávání (GEO)“ na stránce MarMal → SEO
 * (součást formuláře options.php) a úkon „Ohlásit celý web“ (admin-post, nonce + manage_options).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

function firma_box(array $s): void
{
    $z    = firma_zdroj();
    $f    = firma();
    list($ulice, $psc, $mesto) = rozdelit_adresu($z['sidlo']);
    $dny  = array('po' => __('Pondělí', 'marmal-breakdance-plus'), 'ut' => __('Úterý', 'marmal-breakdance-plus'), 'st' => __('Středa', 'marmal-breakdance-plus'), 'ct' => __('Čtvrtek', 'marmal-breakdance-plus'), 'pa' => __('Pátek', 'marmal-breakdance-plus'), 'so' => __('Sobota', 'marmal-breakdance-plus'), 'ne' => __('Neděle', 'marmal-breakdance-plus'));
    $odkud = $z['odkud'] === 'gdpr'
        ? array(__('MarMal → Ochrana os. údajů', 'marmal-breakdance-plus'), admin_url('admin.php?page=marmal-gdpr'))
        : ($z['odkud'] === 'paticka' ? array(__('MarMal → Patička', 'marmal-breakdance-plus'), admin_url('admin.php?page=marmal-paticka')) : null);
    $chybi = firma_chybi();
    $pole  = function (string $klic, string $placeholder, int $max = 120, string $typ = 'text') use ($s) {
        printf(
            '<input type="%5$s" id="ms-%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" class="regular-text" maxlength="%6$d">',
            esc_attr($klic),
            esc_attr(OPTION),
            esc_attr((string) $s[$klic]),
            esc_attr($placeholder),
            esc_attr($typ),
            (int) $max
        );
    };
    ?>
    <div class="marmal-seo-nast__box" id="firma">
        <h2><?php esc_html_e('Firma pro vyhledávače a AI', 'marmal-breakdance-plus'); ?></h2>
        <p><?php esc_html_e('Na úvodní stránce se vypíšou strukturovaná data o firmě (Organization / LocalBusiness) a o webu. Google, Seznam i AI vyhledávače (ChatGPT, Gemini, Perplexity) z nich jednoznačně poznají, kdo firma je, kde sídlí a jak ji kontaktovat.', 'marmal-breakdance-plus'); ?></p>
        <?php if ($odkud) : ?>
            <p class="description"><?php echo esc_html(sprintf(
                /* translators: 1: název firmy, 2: sídlo, 3: místo v administraci */
                __('Název, sídlo, IČO a DIČ se berou z %3$s (teď: %1$s, %2$s). Šedé texty ukazují, co se použije, když pole necháš prázdné.', 'marmal-breakdance-plus'),
                $z['firma'] !== '' ? $z['firma'] : '—',
                $z['sidlo'] !== '' ? $z['sidlo'] : '—',
                $odkud[0]
            )); ?> <a href="<?php echo esc_url($odkud[1]); ?>"><?php esc_html_e('Upravit', 'marmal-breakdance-plus'); ?></a></p>
        <?php else : ?>
            <p class="description"><?php esc_html_e('Modul Ochrana osobních údajů ani Patička není zapnutý – vyplň název a adresu tady.', 'marmal-breakdance-plus'); ?></p>
        <?php endif; ?>
        <?php if ($chybi && $s['firma_schema']) : ?>
            <div class="notice notice-warning inline"><p><?php echo esc_html(sprintf(/* translators: %s: seznam */ __('Doplň: %s.', 'marmal-breakdance-plus'), implode('; ', $chybi))); ?></p></div>
        <?php endif; ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Vypisovat', 'marmal-breakdance-plus'); ?></th>
                <td><?php zaskrtavatko('firma_schema', __('Strukturovaná data o firmě a webu na úvodní stránce', 'marmal-breakdance-plus')); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_typ"><?php esc_html_e('Typ firmy', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php vyber(OPTION . '[firma_typ]', array_map(function ($t) {
                    return $t[0];
                }, firma_typy()), (string) $s['firma_typ'], 'ms-firma_typ'); ?>
                    <p class="description"><?php esc_html_e('Místní podnik (provozovna, kam chodí zákazníci, nebo služby v regionu) dostane adresu, otevírací dobu a souřadnice. Firma bez provozovny pro zákazníky = první volba.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_nazev"><?php esc_html_e('Název firmy', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php $pole('firma_nazev', $z['firma'] !== '' ? $z['firma'] : nazev_webu()); ?>
                    <p class="description"><?php esc_html_e('Jen když se má lišit (např. obchodní značka místo „s.r.o.“).', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_telefon"><?php esc_html_e('Telefon a e-mail', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php $pole('firma_telefon', $z['telefon'] !== '' ? $z['telefon'] : '+420 …', 30, 'tel'); ?><br>
                    <?php $pole('firma_email', $z['email'] !== '' ? $z['email'] : 'info@…', 100, 'email'); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_ulice"><?php esc_html_e('Adresa provozovny', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php $pole('firma_ulice', $ulice !== '' ? $ulice : __('Ulice a číslo', 'marmal-breakdance-plus')); ?><br>
                    <?php $pole('firma_psc', $psc !== '' ? $psc : __('PSČ', 'marmal-breakdance-plus'), 12); ?>
                    <?php $pole('firma_mesto', $mesto !== '' ? $mesto : __('Město', 'marmal-breakdance-plus'), 80); ?>
                    <input type="text" name="<?php echo esc_attr(OPTION); ?>[firma_zeme]" value="<?php echo esc_attr((string) $s['firma_zeme']); ?>" class="small-text" maxlength="2" aria-label="<?php esc_attr_e('Země (kód)', 'marmal-breakdance-plus'); ?>">
                    <p class="description"><?php esc_html_e('Prázdné = sídlo firmy. Vyplň, jen když se provozovna liší od sídla.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Otevírací doba', 'marmal-breakdance-plus'); ?></th>
                <td><table class="marmal-seo-nast__hodiny">
                    <?php foreach ($dny as $den => $nazev) : ?>
                        <tr><th scope="row"><label for="ms-hod-<?php echo esc_attr($den); ?>"><?php echo esc_html($nazev); ?></label></th>
                            <td><input type="text" id="ms-hod-<?php echo esc_attr($den); ?>" name="<?php echo esc_attr(OPTION . '[firma_hodiny][' . $den . ']'); ?>" value="<?php echo esc_attr((string) ($s['firma_hodiny'][$den] ?? '')); ?>" placeholder="<?php echo esc_attr($den === 'po' ? __('např. 8:00–16:00', 'marmal-breakdance-plus') : __('zavřeno', 'marmal-breakdance-plus')); ?>" maxlength="60"></td></tr>
                    <?php endforeach; ?>
                </table>
                    <p class="description"><?php esc_html_e('Např. „8:00–16:00“ nebo s pauzou „8–12, 13–16“. Prázdné = zavřeno. Jen u místního podniku.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logo', 'marmal-breakdance-plus'); ?></th>
                <td><?php vyber_obrazku(OPTION . '[firma_logo]', (int) $s['firma_logo'], 'ms-firma_logo'); ?>
                    <p class="description"><?php esc_html_e('Čtvercové nebo na šířku, aspoň 112 × 112 px (PNG / SVG převedené do PNG). Prázdné = logo webu, jinak ikona webu.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_site"><?php esc_html_e('Profily firmy', 'marmal-breakdance-plus'); ?></label></th>
                <td><textarea id="ms-firma_site" name="<?php echo esc_attr(OPTION); ?>[firma_site]" rows="4" class="large-text code" placeholder="https://www.facebook.com/…&#10;https://www.instagram.com/…&#10;https://www.firmy.cz/detail/…&#10;https://www.linkedin.com/company/…"><?php echo esc_textarea((string) $s['firma_site']); ?></textarea>
                    <p class="description"><?php esc_html_e('Jeden odkaz (https) na řádek: sociální sítě, Firmy.cz, profil na Google Mapách. Vyhledávače a AI pak propojí web s firmou.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><label for="ms-firma_gps"><?php esc_html_e('Souřadnice provozovny', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php $pole('firma_gps', '49.9917, 14.3378', 40); ?>
                    <p class="description"><?php esc_html_e('Nepovinné. Na Mapy.cz / Google Mapách pravým tlačítkem na místo → zkopírovat souřadnice.', 'marmal-breakdance-plus'); ?></p></td>
            </tr>
        </table>
        <p class="description"><?php echo wp_kses(sprintf(
            /* translators: %s: odkaz */
            __('Kontrola po uložení: <a href="%s" target="_blank" rel="noopener">Validátor schema.org</a> → adresa úvodní stránky.', 'marmal-breakdance-plus'),
            'https://validator.schema.org/'
        ), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))); ?>
            <?php if ($f['mistni']) : ?><?php esc_html_e('Vypisuje se jako místní podnik.', 'marmal-breakdance-plus'); ?><?php endif; ?></p>
    </div>
    <?php
}

function ai_box(array $s): void
{
    $o        = get_option(OPT_INDEXNOW, array());
    $posledni = is_array($o) ? ($o['posledni'] ?? null) : null;
    $muze     = indexnow_povoleno();
    $llmsSoubor = file_exists(ABSPATH . 'llms.txt');
    ?>
    <div class="marmal-seo-nast__box" id="ai">
        <h2><?php esc_html_e('AI vyhledávání (GEO)', 'marmal-breakdance-plus'); ?></h2>
        <p><?php esc_html_e('Do odpovědí ChatGPT, Gemini, Perplexity a AI přehledů Googlu se web dostane hlavně stejně jako do vyhledávání: kvalitní obsah s konkrétními fakty, správné titulky a popisky, data o firmě. Tady jsou technické doplňky.', 'marmal-breakdance-plus'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="ms-ai_roboti"><?php esc_html_e('AI roboti', 'marmal-breakdance-plus'); ?></label></th>
                <td><?php vyber(OPTION . '[ai_roboti]', array(
                    'vse'          => __('Povolit vše (výchozí)', 'marmal-breakdance-plus'),
                    'bez_treninku' => __('Zakázat trénování AI, povolit AI vyhledávání', 'marmal-breakdance-plus'),
                ), (string) $s['ai_roboti'], 'ms-ai_roboti'); ?>
                    <p class="description"><?php echo esc_html(sprintf(
                        /* translators: %s: seznam robotů */
                        __('„Zakázat trénování“ přidá do robots.txt zákaz pro %s. Roboti AI vyhledávání (OAI-SearchBot, ChatGPT-User, Claude-SearchBot, PerplexityBot) i Google zůstanou povolení. Google-Extended zakáže i použití obsahu v aplikaci Gemini – ve Vyhledávání Google a AI přehledech zůstane web beze změny.', 'marmal-breakdance-plus'),
                        implode(', ', ai_treninkovi_roboti())
                    )); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('IndexNow', 'marmal-breakdance-plus'); ?></th>
                <td><?php zaskrtavatko('indexnow', __('Ohlašovat nové a změněné stránky Seznamu a Bingu (IndexNow)', 'marmal-breakdance-plus')); ?>
                    <p class="description"><?php esc_html_e('Po publikování nebo úpravě stránky pošle modul do minuty její adresu vyhledávačům Seznam a Bing (IndexNow ji sdílí i s dalšími). Bing používá i Copilot. Google IndexNow nepodporuje. Odesílá se jen adresa, nic jiného, a jen z ostrého webu viditelného pro vyhledávače.', 'marmal-breakdance-plus'); ?></p>
                    <?php if ($s['indexnow']) : ?>
                        <p>
                            <?php if (!$muze) : ?>
                                <em><?php esc_html_e('Teď se nic neodesílá – web je skrytý před vyhledávači, není ostrý (WP_ENVIRONMENT_TYPE) nebo běží jiný SEO plugin.', 'marmal-breakdance-plus'); ?></em>
                            <?php elseif (is_array($posledni)) : ?>
                                <?php
                                $cile = array();
                                foreach ((array) ($posledni['cile'] ?? array()) as $kam => $kod) {
                                    $cile[] = $kam . ': ' . (is_int($kod) ? ($kod >= 200 && $kod < 300 ? 'OK (' . $kod . ')' : (string) $kod) : (string) $kod);
                                }
                                echo esc_html(sprintf(
                                    /* translators: 1: datum, 2: počet adres, 3: výsledek */
                                    __('Naposledy odesláno %1$s: %2$d adres – %3$s.', 'marmal-breakdance-plus'),
                                    wp_date('j. n. Y H:i', (int) ($posledni['cas'] ?? 0)),
                                    (int) ($posledni['pocet'] ?? 0),
                                    implode(', ', $cile)
                                ));
                                if (!empty($posledni['zprava'])) {
                                    echo ' <code>' . esc_html((string) $posledni['zprava']) . '</code>';
                                }
                                ?>
                            <?php else : ?>
                                <?php esc_html_e('Zatím nic neodesláno.', 'marmal-breakdance-plus'); ?>
                            <?php endif; ?>
                            <?php if ($muze) : ?>
                                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_seo_indexnow'), 'marmal_seo_indexnow')); ?>"><?php esc_html_e('Ohlásit celý web', 'marmal-breakdance-plus'); ?></a>
                                <span class="description"><?php esc_html_e('(po spuštění nového webu)', 'marmal-breakdance-plus'); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if ($muze) : ?>
                            <p class="description">
                                <?php esc_html_e('Kontrola:', 'marmal-breakdance-plus'); ?>
                                <a href="<?php echo esc_url(indexnow_soubor()); ?>" target="_blank" rel="noopener"><?php esc_html_e('soubor s klíčem', 'marmal-breakdance-plus'); ?></a>
                                <?php esc_html_e('(musí ukázat jen řetězec znaků)', 'marmal-breakdance-plus'); ?> ·
                                <a href="<?php echo esc_url(add_query_arg(array('url' => rawurlencode(home_url('/')), 'key' => indexnow_klic()), 'https://search.seznam.cz/indexnow')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('ověřit u Seznamu', 'marmal-breakdance-plus'); ?></a>
                                <?php esc_html_e('(ohlásí úvodní stránku přímo Seznamu; „200“ = v pořádku, „403“ = Seznam si nedokázal stáhnout klíč – zkontroluj firewall / bezpečnostní plugin)', 'marmal-breakdance-plus'); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?></td>
            </tr>
            <tr>
                <th scope="row">llms.txt</th>
                <td><?php zaskrtavatko('llms', __('Vytvářet /llms.txt (přehled webu pro jazykové modely)', 'marmal-breakdance-plus')); ?>
                    <p class="description"><?php esc_html_e('Nepovinné. Je to návrh standardu, který velké AI vyhledávače zatím nečtou (Google uvádí, že ho nepotřebuje). Neuškodí – hodí se hlavně, když se na něj klient ptá. Obsahuje název a popis webu, indexované stránky s popisky a kontakt.', 'marmal-breakdance-plus'); ?></p>
                    <?php if ($s['llms']) : ?>
                        <p><a href="<?php echo esc_url(home_url('/llms.txt')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/llms.txt')); ?></a></p>
                        <?php if ($llmsSoubor) : ?>
                            <p><strong><?php esc_html_e('Pozor:', 'marmal-breakdance-plus'); ?></strong> <?php esc_html_e('v kořeni webu je soubor llms.txt – server vrací ten a tento se nepoužije. Smaž ho, pokud ho nechceš.', 'marmal-breakdance-plus'); ?></p>
                        <?php endif; ?>
                        <p><label for="ms-llms_uvod"><?php esc_html_e('Text pod nadpisem (nepovinné)', 'marmal-breakdance-plus'); ?></label><br>
                            <textarea id="ms-llms_uvod" name="<?php echo esc_attr(OPTION); ?>[llms_uvod]" rows="3" class="large-text" maxlength="1000" placeholder="<?php esc_attr_e('Např. komu firma pomáhá, ve kterém regionu působí a čím se liší.', 'marmal-breakdance-plus'); ?>"><?php echo esc_textarea((string) $s['llms_uvod']); ?></textarea></p>
                    <?php else : ?>
                        <input type="hidden" name="<?php echo esc_attr(OPTION); ?>[llms_uvod]" value="<?php echo esc_attr((string) $s['llms_uvod']); ?>">
                    <?php endif; ?></td>
            </tr>
        </table>
    </div>
    <?php
}

add_action('admin_post_marmal_seo_indexnow', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáš oprávnění.', 'marmal-breakdance-plus'), '', array('response' => 403));
    }
    check_admin_referer('marmal_seo_indexnow');
    $pocet = indexnow_cely_web();
    $vysledek = indexnow_odeslat();
    wp_safe_redirect(add_query_arg(array('page' => STRANKA, 'indexnow' => $vysledek ? $pocet : 0), admin_url('admin.php')) . '#ai');
    exit;
});
