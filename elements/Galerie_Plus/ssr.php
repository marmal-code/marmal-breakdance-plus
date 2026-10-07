<?php
/**
 * Server-side render elementu „Galerie Plus“ (MarmalElements\GaleriePlus).
 * Element Studio tento soubor neupravuje – logika je v includes/gallery.php.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\gallery_render')) {
    return;
}

echo \MarmalElements\gallery_render($propertiesData);
