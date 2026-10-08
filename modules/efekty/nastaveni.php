<?php
/**
 * Modul Efekty – nastavení barev a kategorií.
 * Dva režimy barev: „Automaticky z Breakdance“ (napojení na globální barvy) a „Ručně“.
 * Option mm_effects_settings je stejná jako ve starém pluginu MarMal Effects (přechod beze ztráty nastavení).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Barevné sloty knihovny.
 */
function mm_effects_slots() {
	return array(
		'accent'   => array( 'label' => 'Barva 1 – hlavní', 'hint' => 'tlačítka, rámečky, akcenty', 'var' => '--mm-accent', 'default' => '#2563eb', 'bde' => '--bde-brand-primary-color' ),
		'accent_2' => array( 'label' => 'Barva 2 – doplňková', 'hint' => 'přechody, výplně', 'var' => '--mm-accent-2', 'default' => '#0ea5a4', 'bde' => '--bde-links-color' ),
		'accent_3' => array( 'label' => 'Barva 3 – třetí', 'hint' => 'animovaná pozadí, přechody', 'var' => '--mm-accent-3', 'default' => '#f59e0b', 'bde' => '--bde-brand-primary-color-hover' ),
		'dark'     => array( 'label' => 'Tmavá', 'hint' => 'tmavé plochy', 'var' => '--mm-dark', 'default' => '#0f172a', 'bde' => '--bde-headings-color' ),
		'light'    => array( 'label' => 'Světlá', 'hint' => 'světlé plochy', 'var' => '--mm-light', 'default' => '#f8fafc', 'bde' => '--bde-background-color' ),
	);
}

/**
 * Globální barvy Breakdance (Global Settings → Colors).
 */
function mm_effects_bde_globals() {
	return array(
		'--bde-brand-primary-color'       => 'Brand',
		'--bde-brand-primary-color-hover' => 'Brand – hover',
		'--bde-body-text-color'           => 'Text',
		'--bde-headings-color'            => 'Nadpisy',
		'--bde-links-color'               => 'Odkazy',
		'--bde-links-color-hover'         => 'Odkazy – hover',
		'--bde-background-color'          => 'Pozadí',
	);
}

/**
 * Najde CSS proměnné, které Breakdance vygeneroval do uploads/breakdance/css
 * (globální barvy + paleta). Používá se jen v administraci pro náhled a nabídku
 * palety – na webu napojení funguje přímo přes var().
 *
 * @return array [ '--nazev' => 'hodnota' ]
 */
function mm_effects_breakdance_vars() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache   = array();
	$uploads = wp_upload_dir( null, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . 'breakdance/css';
	if ( ! is_dir( $dir ) ) {
		return $cache;
	}
	$files = glob( $dir . '/*global*.css' );
	if ( empty( $files ) ) {
		$files = (array) glob( $dir . '/*.css' );
		usort(
			$files,
			function ( $a, $b ) {
				return filemtime( $b ) - filemtime( $a );
			}
		);
		$files = array_slice( $files, 0, 30 );
	}

	$key    = 'mm_effects_bde_' . md5( implode( '|', array_map( 'filemtime', $files ) ) . implode( '|', $files ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		$cache = $cached;
		return $cache;
	}

	foreach ( $files as $file ) {
		$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! preg_match_all( '/(--(?:bde|hcl)[\w-]*|--[\w-]*palette[\w-]*)\s*:\s*([^;{}<]+)/i', $css, $m, PREG_SET_ORDER ) ) {
			continue;
		}
		foreach ( $m as $row ) {
			$name = strtolower( $row[1] );
			if ( ! isset( $cache[ $name ] ) ) {
				$cache[ $name ] = trim( $row[2] );
			}
		}
	}
	set_transient( $key, $cache, DAY_IN_SECONDS );
	return $cache;
}

/**
 * Nastavení s výchozími hodnotami (a převodem ze staré verze 1.0).
 */
function mm_effects_settings() {
	$raw   = (array) get_option( 'mm_effects_settings', array() );
	$slots = mm_effects_slots();

	$s = array(
		'mode'      => 'breakdance',
		'colors'    => array(),
		'source'    => array(),
		'customvar' => array(),
		'radius'    => '',
		'load_js'   => 1,
		'cats'      => null, // null = všechny kategorie
	);
	foreach ( $slots as $key => $slot ) {
		$s['colors'][ $key ]    = '';
		$s['source'][ $key ]    = $slot['bde'];
		$s['customvar'][ $key ] = '';
	}

	// Verze 1.0 ukládala barvy přímo (accent, accent_2, dark).
	if ( ! isset( $raw['mode'] ) ) {
		$had_color = false;
		foreach ( array( 'accent', 'accent_2', 'dark' ) as $old ) {
			if ( ! empty( $raw[ $old ] ) ) {
				$s['colors'][ $old ] = $raw[ $old ];
				$had_color           = true;
			}
		}
		$s['mode'] = $had_color ? 'custom' : 'breakdance';
	} else {
		$s['mode'] = 'custom' === $raw['mode'] ? 'custom' : 'breakdance';
		foreach ( array( 'colors', 'source', 'customvar' ) as $group ) {
			if ( isset( $raw[ $group ] ) && is_array( $raw[ $group ] ) ) {
				$s[ $group ] = array_merge( $s[ $group ], array_intersect_key( $raw[ $group ], $slots ) );
			}
		}
	}
	if ( isset( $raw['radius'] ) ) {
		$s['radius'] = $raw['radius'];
	}
	if ( isset( $raw['load_js'] ) ) {
		$s['load_js'] = $raw['load_js'] ? 1 : 0;
	}
	if ( isset( $raw['cats'] ) && is_array( $raw['cats'] ) ) {
		$s['cats'] = array_values( array_intersect( array_keys( mm_effects_kategorie() ), $raw['cats'] ) );
	}
	return $s;
}

function mm_effects_valid_var( $name ) {
	return is_string( $name ) && preg_match( '/^--[A-Za-z0-9_-]{1,80}$/', $name );
}

/**
 * CSS výraz pro jeden slot podle režimu.
 */
function mm_effects_slot_value( $key, $s = null ) {
	$s     = $s ? $s : mm_effects_settings();
	$slots = mm_effects_slots();
	$own   = sanitize_hex_color( $s['colors'][ $key ] );

	if ( 'custom' === $s['mode'] ) {
		return $own ? $own : '';
	}

	$fallback = $own ? $own : $slots[ $key ]['default'];
	$source   = $s['source'][ $key ];
	if ( 'custom-var' === $source ) {
		$source = $s['customvar'][ $key ];
	}
	if ( 'own' === $source ) {
		return $fallback;
	}
	if ( ! mm_effects_valid_var( $source ) ) {
		return $fallback;
	}
	return 'var(' . $source . ', ' . $fallback . ')';
}

/**
 * CSS proměnné z nastavení. Deklarujeme na :root i body – Breakdance může mít
 * své proměnné na kterémkoliv z nich.
 */
function mm_effects_inline_vars() {
	$s   = mm_effects_settings();
	$out = '';
	foreach ( mm_effects_slots() as $key => $slot ) {
		$value = mm_effects_slot_value( $key, $s );
		if ( '' !== $value ) {
			$out .= $slot['var'] . ':' . $value . ';';
		}
	}
	if ( '' !== $s['radius'] && is_numeric( $s['radius'] ) ) {
		$out .= '--mm-radius:' . absint( $s['radius'] ) . 'px;';
	}
	return $out ? ':root,body{' . $out . '}' : '';
}

/**
 * V administraci Breakdance neběží, proto jeho proměnné vložíme ručně,
 * aby náhledy ukazovaly skutečné barvy webu.
 */
function mm_effects_admin_bde_css() {
	$vars = mm_effects_breakdance_vars();
	if ( ! $vars ) {
		return '';
	}
	$out = '';
	foreach ( $vars as $name => $value ) {
		$out .= $name . ':' . str_replace( array( '<', '>', '{', '}' ), '', $value ) . ';';
	}
	return ':root{' . $out . '}';
}

/* --------------------------------------------------------------------------
 * Registrace a uložení
 * ----------------------------------------------------------------------- */
add_action(
	'admin_init',
	function () {
		register_setting(
			'mm_effects',
			'mm_effects_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'mm_effects_sanitize_settings',
			)
		);
	}
);

function mm_effects_sanitize_settings( $in ) {
	$in      = (array) $in;
	$slots   = mm_effects_slots();
	$allowed = array_merge( array_keys( mm_effects_bde_globals() ), array_keys( mm_effects_breakdance_vars() ), array( 'own', 'custom-var' ) );
	$out     = array(
		'mode'      => ( isset( $in['mode'] ) && 'custom' === $in['mode'] ) ? 'custom' : 'breakdance',
		'colors'    => array(),
		'source'    => array(),
		'customvar' => array(),
		'radius'    => ( isset( $in['radius'] ) && '' !== $in['radius'] ) ? (string) min( 80, absint( $in['radius'] ) ) : '',
		'load_js'   => empty( $in['load_js'] ) ? 0 : 1,
	);
	// Kategorie: když jsou zapnuté všechny, neukládáme nic (= všechny, i nové v budoucích verzích).
	$vse = array_keys( mm_effects_kategorie() );
	if ( ! empty( $in['cats_sent'] ) ) {
		$cats = array_values( array_intersect( $vse, array_map( 'strval', (array) ( isset( $in['cats'] ) ? $in['cats'] : array() ) ) ) );
		if ( count( $cats ) !== count( $vse ) ) {
			$out['cats'] = $cats;
		}
	} else {
		$puvodni = mm_effects_settings();
		if ( is_array( $puvodni['cats'] ) ) {
			$out['cats'] = $puvodni['cats'];
		}
	}
	foreach ( $slots as $key => $slot ) {
		$color                   = isset( $in['colors'][ $key ] ) ? sanitize_hex_color( $in['colors'][ $key ] ) : '';
		$out['colors'][ $key ]   = $color ? $color : '';
		$source                  = isset( $in['source'][ $key ] ) ? (string) $in['source'][ $key ] : $slot['bde'];
		$out['source'][ $key ]   = ( in_array( $source, $allowed, true ) || mm_effects_valid_var( $source ) ) ? $source : $slot['bde'];
		$var                     = isset( $in['customvar'][ $key ] ) ? trim( (string) $in['customvar'][ $key ] ) : '';
		$out['customvar'][ $key ] = mm_effects_valid_var( $var ) ? $var : '';
	}
	return $out;
}

/* --------------------------------------------------------------------------
 * Stránka nastavení
 * ----------------------------------------------------------------------- */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'marmal-efekty_page_mm-effects-settings' !== $hook && false === strpos( $hook, 'mm-effects-settings' ) ) {
			return;
		}
		$v = mm_effects_version();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'mm-effects-settings', MM_EFFECTS_URL . 'assets/admin/settings.css', array(), $v );
		wp_add_inline_style( 'mm-effects-settings', mm_effects_admin_bde_css() );
		wp_enqueue_script( 'mm-effects-settings', MM_EFFECTS_URL . 'assets/admin/settings.js', array( 'jquery', 'wp-color-picker' ), $v, true );
	}
);

function mm_effects_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s        = mm_effects_settings();
	$slots    = mm_effects_slots();
	$globals  = mm_effects_bde_globals();
	$found    = mm_effects_breakdance_vars();
	$palette  = array_diff_key( $found, $globals );
	$bd_found = ! empty( $found );
	?>
	<div class="wrap mm-s">
		<h1>Efekty – barvy a nastavení</h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'mm_effects' ); ?>

			<fieldset class="mm-s-modes">
				<legend class="screen-reader-text">Odkud brát barvy</legend>
				<label class="mm-s-mode">
					<input type="radio" name="mm_effects_settings[mode]" value="breakdance" <?php checked( $s['mode'], 'breakdance' ); ?>>
					<span>
						<strong>Automaticky z Breakdance</strong>
						Barvy se berou z globálních barev Breakdance (Global Settings → Colors). Když je v Breakdance změníš, efekty se přebarví samy.
					</span>
				</label>
				<label class="mm-s-mode">
					<input type="radio" name="mm_effects_settings[mode]" value="custom" <?php checked( $s['mode'], 'custom' ); ?>>
					<span>
						<strong>Ručně</strong>
						Barvy zadáš sám. Prázdné pole = výchozí barva knihovny.
					</span>
				</label>
			</fieldset>

			<?php if ( ! $bd_found ) : ?>
				<div class="notice notice-info inline mm-s-note">
					<p>Globální barvy Breakdance se v administraci nenačetly (Breakdance není aktivní, nebo ještě nejsou uložené Global Settings). Na webu napojení funguje i tak, jen tady v náhledu uvidíš záložní barvy.</p>
				</div>
			<?php endif; ?>

			<table class="widefat mm-s-table" data-mode="<?php echo esc_attr( $s['mode'] ); ?>">
				<thead>
					<tr>
						<th>Barva v efektech</th>
						<th class="mm-s-col-source">Zdroj v Breakdance</th>
						<th><span class="mm-s-when-custom">Vlastní barva</span><span class="mm-s-when-bd">Záloha / vlastní</span></th>
						<th>Náhled</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $slots as $key => $slot ) :
					$source = $s['source'][ $key ];
					$is_var = ! in_array( $source, array_merge( array_keys( $globals ), array_keys( $palette ), array( 'own' ) ), true );
					$cvar   = $s['customvar'][ $key ];
					if ( $is_var && 'custom-var' !== $source && '' === $cvar ) {
						$cvar = $source;
					}
					?>
					<tr class="mm-s-row" data-slot="<?php echo esc_attr( $key ); ?>" data-default="<?php echo esc_attr( $slot['default'] ); ?>">
						<td>
							<strong><?php echo esc_html( $slot['label'] ); ?></strong><br>
							<span class="description"><?php echo esc_html( $slot['hint'] ); ?> · <code><?php echo esc_html( $slot['var'] ); ?></code></span>
						</td>
						<td class="mm-s-col-source">
							<select name="mm_effects_settings[source][<?php echo esc_attr( $key ); ?>]" class="mm-s-source">
								<optgroup label="Globální barvy Breakdance">
									<?php foreach ( $globals as $var => $label ) : ?>
										<option value="<?php echo esc_attr( $var ); ?>" <?php selected( $source, $var ); ?>><?php echo esc_html( $label . ( isset( $found[ $var ] ) ? '  (' . $found[ $var ] . ')' : '' ) ); ?></option>
									<?php endforeach; ?>
								</optgroup>
								<?php if ( $palette ) : ?>
									<optgroup label="Paleta a další proměnné Breakdance">
										<?php foreach ( $palette as $var => $value ) : ?>
											<option value="<?php echo esc_attr( $var ); ?>" <?php selected( $source, $var ); ?>><?php echo esc_html( $var . '  (' . $value . ')' ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endif; ?>
								<optgroup label="Jiné">
									<option value="own" <?php selected( $source, 'own' ); ?>>Nepropojovat – použít vlastní barvu</option>
									<option value="custom-var" <?php selected( $is_var || 'custom-var' === $source ); ?>>Jiná CSS proměnná…</option>
								</optgroup>
							</select>
							<input type="text" class="regular-text code mm-s-customvar" name="mm_effects_settings[customvar][<?php echo esc_attr( $key ); ?>]" placeholder="--bde-palette-…" value="<?php echo esc_attr( $cvar ); ?>">
						</td>
						<td>
							<input type="text" class="mm-s-color" name="mm_effects_settings[colors][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s['colors'][ $key ] ); ?>" data-default-color="" placeholder="<?php echo esc_attr( $slot['default'] ); ?>">
						</td>
						<td><span class="mm-s-swatch" aria-hidden="true"></span> <code class="mm-s-value"></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description mm-s-when-bd">V režimu Breakdance slouží vlastní barva jako záloha, kdyby proměnná v Breakdance chyběla. Barvy z palety Breakdance vybereš v rozbalovacím seznamu, nebo vlož název proměnné ručně.</p>

			<h2>Kategorie načítané na webu</h2>
			<p class="description">Na web se načte jen CSS zapnutých kategorií. Vypnutá kategorie = její třídy na webu nefungují. Výchozí stav jsou všechny.</p>
			<input type="hidden" name="mm_effects_settings[cats_sent]" value="1">
			<?php $zapnute = is_array( $s['cats'] ) ? $s['cats'] : array_keys( mm_effects_kategorie() ); ?>
			<fieldset class="mm-s-cats" style="columns:2 260px;margin:8px 0 16px">
				<?php foreach ( mm_effects_kategorie() as $id => $nazev ) : ?>
					<label style="display:block;margin:0 0 6px"><input type="checkbox" name="mm_effects_settings[cats][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $zapnute, true ) ); ?>> <?php echo esc_html( $nazev ); ?></label>
				<?php endforeach; ?>
			</fieldset>

			<h2>Ostatní</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mm-radius">Zaoblení (px)</label></th>
					<td><input id="mm-radius" name="mm_effects_settings[radius]" type="number" min="0" max="80" class="small-text" placeholder="14" value="<?php echo esc_attr( $s['radius'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">JavaScript</th>
					<td><label><input name="mm_effects_settings[load_js]" type="checkbox" value="1" <?php checked( $s['load_js'], 1 ); ?>> Načítat skript (animace při scrollu, počítadla, 3D náklon, parallax, pauza pozadí mimo obrazovku)</label></td>
				</tr>
			</table>
			<?php submit_button( 'Uložit' ); ?>
		</form>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mm-effects' ) ); ?>">Otevřít galerii efektů</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . \MarmalElements\Moduly::STRANKA ) ); ?>">Moduly</a>
			&nbsp; Marmal – Breakdance Plus <strong><?php echo esc_html( mm_effects_version() ); ?></strong>
		</p>
	</div>
	<?php
}
