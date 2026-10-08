<?php
/**
 * Modulový systém pluginu Marmal – Breakdance Plus.
 *
 * Každý modul je složka modules/<id>/ s manifest.php (popis, výchozí stav,
 * závislosti) a souborem modulu. Zapnuté moduly se uloží do option
 * marmal_bdp_moduly; vypnutý modul nenačte ani řádek kódu.
 *
 * Nový modul:
 *  1. modules/<id>/manifest.php (vzor: modules/efekty/manifest.php),
 *  2. modules/<id>/modul.php s kódem modulu,
 *  3. zvýšit verzi pluginu. Dashboard i MarMal Agent ho uvidí samy.
 *
 * Moduly s elementy z Element Studia jsou "zamčené" – vypnutím by elementy
 * zmizely ze stránek.
 */

namespace MarmalElements;

if (!defined('ABSPATH')) {
    exit;
}

final class Moduly
{
    const OPTION  = 'marmal_bdp_moduly';
    const STRANKA = 'marmal';

    /** @var array<string,array>|null */
    private static $manifesty = null;

    public static function init(): void
    {
        // Pozdě na plugins_loaded: všechny pluginy jsou načtené, takže jde poznat starý MarMal Effects.
        add_action('plugins_loaded', [__CLASS__, 'nacist_bez_breakdance'], 5);
        // Moduly, které potřebují Breakdance – před registrací elementů (priorita 10).
        add_action('breakdance_loaded', [__CLASS__, 'nacist_s_breakdance'], 8);

        add_action('admin_menu', [__CLASS__, 'menu'], 9);
        add_action('admin_enqueue_scripts', [__CLASS__, 'styly']);
        add_action('admin_post_marmal_bdp_modul', [__CLASS__, 'zpracovat_formular']);
        add_filter('plugin_action_links_' . plugin_basename(MARMAL_BDP_FILE), function ($odkazy) {
            array_unshift($odkazy, '<a href="' . esc_url(admin_url('admin.php?page=' . self::STRANKA)) . '">' . esc_html__('Moduly', 'marmal-breakdance-plus') . '</a>');
            return $odkazy;
        });
    }

    // ================================================================= manifesty a stav

    /** @return array<string,array> id => manifest, seřazené podle pořadí */
    public static function manifesty(): array
    {
        if (null !== self::$manifesty) {
            return self::$manifesty;
        }
        self::$manifesty = [];
        foreach ((array) glob(MARMAL_BDP_DIR . 'modules/*/manifest.php') as $soubor) {
            $m = include $soubor;
            if (!is_array($m) || empty($m['id'])) {
                continue;
            }
            $m['slozka'] = dirname($soubor);
            self::$manifesty[$m['id']] = $m + ['soubor' => null, 'breakdance' => false, 'zamceny' => false, 'poradi' => 50, 'vychozi' => false, 'konflikt' => null];
        }
        uasort(self::$manifesty, function ($a, $b) {
            return $a['poradi'] <=> $b['poradi'];
        });
        return self::$manifesty;
    }

    /**
     * Uložený stav modulů. Modul, který na webu ještě nemá záznam, dostane
     * výchozí hodnotu z manifestu a ta se hned uloží (aby se později nezměnila sama).
     *
     * @return array<string,bool>
     */
    public static function stav(): array
    {
        $ulozeny = get_option(self::OPTION, []);
        $ulozeny = is_array($ulozeny) ? $ulozeny : [];
        $zmena   = false;
        foreach (self::manifesty() as $id => $m) {
            if ($m['zamceny']) {
                continue;
            }
            if (!array_key_exists($id, $ulozeny)) {
                $ulozeny[$id] = (bool) (is_callable($m['vychozi']) ? call_user_func($m['vychozi']) : $m['vychozi']);
                $zmena = true;
            }
        }
        if ($zmena) {
            update_option(self::OPTION, $ulozeny, true);
        }
        return array_map('boolval', $ulozeny);
    }

    public static function je_zapnuty(string $id): bool
    {
        $m = self::manifesty()[$id] ?? null;
        if (!$m) {
            return false;
        }
        return $m['zamceny'] ? true : !empty(self::stav()[$id]);
    }

    /**
     * Proč zapnutý modul neběží (null = běží / je vypnutý).
     * $proZobrazeni: starý plugin, který se právě v tomto požadavku deaktivoval
     * (převod), už jako konflikt nehlásit – od dalšího načtení modul poběží.
     */
    private static function problem(array $m, bool $proZobrazeni = false): ?string
    {
        if (!empty($m['konflikt']['konstanta']) && defined($m['konflikt']['konstanta'])) {
            $stalePlugin = true;
            if ($proZobrazeni && !empty($m['konflikt']['plugin'])) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                $stalePlugin = is_plugin_active($m['konflikt']['plugin']);
            }
            if ($stalePlugin) {
                return 'konflikt';
            }
        }
        if ($m['breakdance'] && !did_action('breakdance_loaded') && !doing_action('breakdance_loaded')) {
            return 'chybi_breakdance';
        }
        return null;
    }

    // ================================================================= načtení

    public static function nacist_bez_breakdance(): void
    {
        self::nacist(false);
    }

    public static function nacist_s_breakdance(): void
    {
        self::nacist(true);
    }

    private static function nacist(bool $sBreakdance): void
    {
        foreach (self::manifesty() as $id => $m) {
            if ((bool) $m['breakdance'] !== $sBreakdance || !$m['soubor'] || !self::je_zapnuty($id)) {
                continue;
            }
            if (self::problem($m) !== null) {
                continue;
            }
            require_once $m['slozka'] . '/' . $m['soubor'];
        }
    }

    // ================================================================= veřejné API (dashboard, MarMal Agent)

    /**
     * Seznam modulů pro dashboard a MarMal Agenta.
     * stav: zapnuto | vypnuto | konflikt (běží starý plugin) | chybi_breakdance
     */
    public static function seznam(): array
    {
        $vystup = [];
        foreach (self::manifesty() as $id => $m) {
            $texty   = is_callable($m['texty'] ?? null) ? call_user_func($m['texty']) : [];
            $zapnuty = self::je_zapnuty($id);
            $problem = $zapnuty ? self::problem($m, true) : null;
            $vystup[] = [
                'id'       => $id,
                'nazev'    => $texty['nazev'] ?? $id,
                'popis'    => $texty['popis'] ?? '',
                'aktivni'  => $zapnuty,
                'zamceny'  => (bool) $m['zamceny'],
                'stav'     => !$zapnuty ? 'vypnuto' : ($problem ?? 'zapnuto'),
                'konflikt' => $problem === 'konflikt' ? ($m['konflikt']['nazev'] ?? '') : null,
            ];
        }
        return $vystup;
    }

    /**
     * Zapne / vypne modul. Projeví se od dalšího načtení stránky.
     * @return true|\WP_Error
     */
    public static function nastavit(string $id, bool $aktivni)
    {
        $m = self::manifesty()[$id] ?? null;
        if (!$m) {
            return new \WP_Error('marmal_bdp_modul', sprintf('Modul „%s“ neexistuje.', $id));
        }
        if ($m['zamceny']) {
            return new \WP_Error('marmal_bdp_modul', sprintf('Modul „%s“ je vždy zapnutý a nejde vypnout.', $id));
        }
        $stav      = self::stav();
        $stav[$id] = $aktivni;
        update_option(self::OPTION, $stav, true);
        do_action('marmal_bdp_modul_zmenen', $id, $aktivni);
        return true;
    }

    /**
     * Převod ze starého samostatného pluginu: deaktivuje ho a zapne modul.
     * Nastavení (option) zůstává, modul ho převezme.
     * @return true|\WP_Error
     */
    public static function prevest(string $id)
    {
        $m = self::manifesty()[$id] ?? null;
        if (!$m || empty($m['konflikt']['plugin'])) {
            return new \WP_Error('marmal_bdp_modul', 'Tento modul nemá co převádět.');
        }
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($m['konflikt']['plugin'], false, is_plugin_active_for_network($m['konflikt']['plugin']));
        return self::nastavit($id, true);
    }

    // ================================================================= administrace

    public static function menu(): void
    {
        add_menu_page(
            'MarMal',
            'MarMal',
            'manage_options',
            self::STRANKA,
            [__CLASS__, 'stranka'],
            'dashicons-screenoptions',
            59
        );
        add_submenu_page(self::STRANKA, __('Moduly', 'marmal-breakdance-plus'), __('Moduly', 'marmal-breakdance-plus'), 'manage_options', self::STRANKA, [__CLASS__, 'stranka']);
    }

    public static function styly(string $hook): void
    {
        if ($hook !== 'toplevel_page_' . self::STRANKA) {
            return;
        }
        wp_enqueue_style('marmal-bdp-moduly', MARMAL_BDP_URL . 'assets/admin/moduly.css', [], MARMAL_BDP_VERSION);
    }

    public static function zpracovat_formular(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemáš oprávnění.', 'marmal-breakdance-plus'), 403);
        }
        check_admin_referer('marmal_bdp_modul');

        $id       = sanitize_key(wp_unslash($_POST['modul'] ?? ''));
        $operace  = sanitize_key(wp_unslash($_POST['operace'] ?? ''));
        $vysledek = $operace === 'prevest'
            ? self::prevest($id)
            : self::nastavit($id, $operace === 'zapnout');

        $zprava = is_wp_error($vysledek) ? 'chyba' : $operace;
        wp_safe_redirect(add_query_arg(['page' => self::STRANKA, 'zprava' => $zprava, 'modul' => $id], admin_url('admin.php')));
        exit;
    }

    public static function stranka(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $zpravy = [
            'zapnout' => __('Modul je zapnutý.', 'marmal-breakdance-plus'),
            'vypnout' => __('Modul je vypnutý – jeho kód ani CSS se už nenačítají.', 'marmal-breakdance-plus'),
            'prevest' => __('Hotovo: starý plugin je deaktivovaný a modul zapnutý. Nastavení zůstalo. Starý plugin teď můžeš v Pluginech smazat.', 'marmal-breakdance-plus'),
            'chyba'   => __('Změnu se nepodařilo uložit.', 'marmal-breakdance-plus'),
        ];
        $zprava = sanitize_key(wp_unslash($_GET['zprava'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification
        $stavy  = [
            'zapnuto'          => [__('Zapnuto', 'marmal-breakdance-plus'), 'is-on'],
            'vypnuto'          => [__('Vypnuto', 'marmal-breakdance-plus'), 'is-off'],
            'konflikt'         => [__('Čeká na převod', 'marmal-breakdance-plus'), 'is-warn'],
            'chybi_breakdance' => [__('Chybí Breakdance', 'marmal-breakdance-plus'), 'is-warn'],
        ];
        ?>
        <div class="wrap marmal-moduly">
            <h1><?php esc_html_e('MarMal – moduly', 'marmal-breakdance-plus'); ?></h1>
            <p class="marmal-moduly__uvod"><?php esc_html_e('Zapni jen to, co tento web používá. Vypnutý modul nenačte žádný kód, CSS ani JavaScript.', 'marmal-breakdance-plus'); ?></p>

            <?php if (isset($zpravy[$zprava])) : ?>
                <div class="notice <?php echo $zprava === 'chyba' ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html($zpravy[$zprava]); ?></p></div>
            <?php endif; ?>

            <div class="marmal-moduly__mrizka">
                <?php foreach (self::seznam() as $modul) :
                    $m       = self::manifesty()[$modul['id']];
                    $stav    = $stavy[$modul['stav']] ?? [$modul['stav'], 'is-off'];
                    $nacteny = $modul['stav'] === 'zapnuto';
                    $odkazy  = ($nacteny && is_callable($m['odkazy'] ?? null)) ? (array) call_user_func($m['odkazy']) : [];
                    $info    = ($nacteny && is_callable($m['info'] ?? null)) ? (string) call_user_func($m['info']) : '';
                    ?>
                    <div class="marmal-modul <?php echo esc_attr($stav[1]); ?>">
                        <div class="marmal-modul__hlava">
                            <h2><?php echo esc_html($modul['nazev']); ?></h2>
                            <span class="marmal-modul__stav"><?php echo esc_html($modul['zamceny'] ? __('Vždy zapnuto', 'marmal-breakdance-plus') : $stav[0]); ?></span>
                        </div>
                        <p><?php echo esc_html($modul['popis']); ?></p>

                        <?php if ($modul['stav'] === 'konflikt') : ?>
                            <p class="marmal-modul__varovani">
                                <?php
                                /* translators: %s: název starého pluginu */
                                echo esc_html(sprintf(__('Na webu ještě běží starý plugin %s, proto se modul nenačítá. Převod starý plugin deaktivuje a zapne modul – nastavení i třídy na stránkách zůstanou.', 'marmal-breakdance-plus'), $modul['konflikt']));
                                ?>
                            </p>
                        <?php elseif ($modul['stav'] === 'chybi_breakdance') : ?>
                            <p class="marmal-modul__varovani"><?php esc_html_e('Modul potřebuje aktivní Breakdance.', 'marmal-breakdance-plus'); ?></p>
                        <?php endif; ?>

                        <?php if ($info) : ?>
                            <p class="marmal-modul__info"><?php echo esc_html($info); ?></p>
                        <?php endif; ?>

                        <div class="marmal-modul__akce">
                            <?php foreach ($odkazy as $text => $url) : ?>
                                <a class="button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($text); ?></a>
                            <?php endforeach; ?>

                            <?php if (!$modul['zamceny']) : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <?php wp_nonce_field('marmal_bdp_modul'); ?>
                                    <input type="hidden" name="action" value="marmal_bdp_modul">
                                    <input type="hidden" name="modul" value="<?php echo esc_attr($modul['id']); ?>">
                                    <?php if ($modul['stav'] === 'konflikt') : ?>
                                        <button class="button button-primary" name="operace" value="prevest"><?php esc_html_e('Převést ze starého pluginu', 'marmal-breakdance-plus'); ?></button>
                                    <?php elseif ($modul['aktivni']) : ?>
                                        <button class="button" name="operace" value="vypnout"><?php esc_html_e('Vypnout', 'marmal-breakdance-plus'); ?></button>
                                    <?php else : ?>
                                        <button class="button button-primary" name="operace" value="zapnout"><?php esc_html_e('Zapnout', 'marmal-breakdance-plus'); ?></button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="marmal-moduly__pata">
                Marmal – Breakdance Plus <strong><?php echo esc_html(MARMAL_BDP_VERSION); ?></strong>
                <?php if (class_exists('MarMal_Agent')) : ?>
                    · <?php esc_html_e('Moduly jde přepínat i z aplikace sprava.marmal.cz (MarMal Agent).', 'marmal-breakdance-plus'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}
