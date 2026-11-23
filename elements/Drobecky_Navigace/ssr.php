<?php
/**
 * Server-side render elementu „Marmal – Drobečková navigace“ (MarmalElements\DrobeckyNavigace).
 * VYGENEROVÁNO skriptem tools/drobecky_element.py – neupravovat ručně.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\Seo\drobecky_element_html')) {
    // Modul SEO je vypnutý (MarMal → Moduly) – návštěvník nic nevidí, správce upozornění.
    if (current_user_can('manage_options')) {
        echo '<p class="marmal-drobecky__hlaska">' . esc_html__('Modul SEO a sdílení je vypnutý – zapni ho v MarMal → Moduly.', 'marmal-breakdance-plus') . '</p>';
    }
    return;
}

// V builderu (náhled přes AJAX / REST) nemá šablona vlastní stránku – element ukáže ukázkovou cestu.
// phpcs:ignore WordPress.Security.NonceVerification -- jen příznak builderu, nic se neukládá
$marmal_dr_builder = wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_admin() || isset($_GET['breakdance']) || isset($_GET['breakdance_iframe']);
$marmal_dr_c       = $propertiesData['content']['drobecky'] ?? [];
// drobecky_element_html() vrací hotové HTML – texty esc_html, odkazy esc_url, ikony přes wp_kses.
echo \MarmalElements\Seo\drobecky_element_html(is_array($marmal_dr_c) ? $marmal_dr_c : [], $marmal_dr_builder); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
