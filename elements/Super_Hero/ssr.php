<?php
/**
 * Server-side render elementu „superHERO“ (MarmalElements\SuperHero).
 * Element Studio tento soubor neupravuje – logika je v includes/hero.php.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\hero_render')) {
    return;
}

// hero_render() escapuje každou hodnotu uvnitř (esc_html / esc_attr / esc_url, SVG přes wp_kses).
echo \MarmalElements\hero_render($propertiesData); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
