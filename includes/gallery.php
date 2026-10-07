<?php
/**
 * Galerie Plus – vykreslení (volá ho elements/Galerie_Plus/ssr.php).
 *
 * Rozložení mozaiky řeší čistě CSS (css.twig) podle vzoru a breakpointu.
 * PHP jen vypíše dlaždice ve správném pořadí a ke každé přidá data-more="+N"
 * (kolik obrázků následuje), aby CSS mohlo na poslední viditelné dlaždici
 * ukázat „+N“.
 */

namespace MarmalElements;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrace JS/CSS lightboxu a slideru (načtou se jen na stránkách s galerií).
 */
add_action('init', function () {
    wp_register_style('marmal-lightbox', MARMAL_BDP_URL . 'assets/lightbox/lightbox.css', [], MARMAL_BDP_VERSION);
    wp_register_script('marmal-lightbox', MARMAL_BDP_URL . 'assets/lightbox/lightbox.js', [], MARMAL_BDP_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
    wp_register_script('marmal-slider', MARMAL_BDP_URL . 'assets/slider/slider.js', [], MARMAL_BDP_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
});

/**
 * Bezpečné čtení vnořené hodnoty z $propertiesData.
 */
function gallery_get(array $data, string $path, $default = null)
{
    $cur = $data;
    foreach (explode('.', $path) as $key) {
        if (!is_array($cur) || !array_key_exists($key, $cur)) {
            return $default;
        }
        $cur = $cur[$key];
    }
    return ($cur === null || $cur === '') ? $default : $cur;
}

/**
 * Projde libovolnou strukturu (výběr z knihovny médií, ACF galerie – pole,
 * ID nebo URL) a vytáhne z ní obrázky v původním pořadí.
 * Je záměrně tolerantní: nezáleží na přesném tvaru hodnoty.
 */
function gallery_extract($value, array &$out, bool $allowScalar = true): void
{
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        if ($allowScalar && (int) $value > 0 && wp_attachment_is_image((int) $value)) {
            $out[] = ['id' => (int) $value];
        }
        return;
    }

    if (is_string($value)) {
        if ($allowScalar && preg_match('~^(https?:)?//[^\s]+\.(jpe?g|png|gif|webp|avif)(\?\S*)?$~i', $value)) {
            $out[] = ['url' => $value];
        }
        return;
    }

    if (!is_array($value)) {
        return;
    }

    $id = $value['id'] ?? $value['ID'] ?? null;
    if (is_numeric($id) && (int) $id > 0 && wp_attachment_is_image((int) $id)) {
        $out[] = ['id' => (int) $id];
        return;
    }

    if (!empty($value['url']) && is_string($value['url'])) {
        $out[] = [
            'url'     => $value['url'],
            'alt'     => is_string($value['alt'] ?? null) ? $value['alt'] : '',
            'caption' => is_string($value['caption'] ?? null) ? $value['caption'] : '',
        ];
        return;
    }

    // Holá čísla/URL bereme jen ze seznamů (ne z např. 'count' => 5).
    $isList = array_keys($value) === range(0, count($value) - 1);
    foreach ($value as $child) {
        gallery_extract($child, $out, $isList);
    }
}

/**
 * Seznam obrázků podle zdroje (ruční výběr / ACF pole).
 */
function gallery_images(array $p): array
{
    $source = gallery_get($p, 'content.obrazky.zdroj', 'rucne');
    $raw    = null;

    if ($source === 'acf') {
        $field = trim((string) gallery_get($p, 'content.obrazky.acf_pole', ''));
        if ($field !== '' && function_exists('get_field')) {
            $postId = get_the_ID() ?: get_queried_object_id();
            $raw    = $postId ? get_field($field, $postId) : null;
        }
    } else {
        $raw = gallery_get($p, 'content.obrazky.vyber');
    }

    $found = [];
    gallery_extract($raw, $found);

    // Odstranit duplicity (stejné ID nebo URL).
    $seen = [];
    $out  = [];
    foreach ($found as $img) {
        $key = isset($img['id']) ? 'id:' . $img['id'] : 'url:' . $img['url'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $out[]      = $img;
        }
    }
    return $out;
}

/**
 * Převod odkazu na video na bezpečný embed (bez cookies třetích stran, kde to jde).
 * Vrací ['type' => 'iframe'|'file', 'src' => …] nebo null.
 */
function gallery_video_embed(string $url): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return ['type' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0'];
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return ['type' => 'iframe', 'src' => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1'];
    }
    if (preg_match('~\.(mp4|webm|ogv)(\?\S*)?$~i', $url)) {
        return ['type' => 'file', 'src' => $url];
    }
    return null;
}

/**
 * Hlavní vykreslení.
 */
function gallery_render(array $p): string
{
    $images     = gallery_images($p);
    $size       = (string) gallery_get($p, 'content.obrazky.velikost', 'large');
    $lightbox   = !gallery_get($p, 'content.obrazky.bez_lightboxu', false);
    $canEdit    = is_user_logged_in() && current_user_can('edit_posts');
    $isFrontend = !is_admin() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST);

    // Ladění: ukáže správci surovou hodnotu výběru obrázků.
    $debug = '';
    if ($canEdit && gallery_get($p, 'content.obrazky.ladeni', false)) {
        $debug = '<pre class="marmal-gallery__debug">'
            . esc_html(wp_json_encode([
                'zdroj'   => gallery_get($p, 'content.obrazky.zdroj', 'rucne'),
                'vyber'   => gallery_get($p, 'content.obrazky.vyber'),
                'nalezeno' => count($images),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . '</pre>';
    }

    // Položky: obrázky
    $tiles = [];
    foreach ($images as $i => $img) {
        if (isset($img['id'])) {
            $id      = $img['id'];
            $alt     = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            $caption = (string) wp_get_attachment_caption($id);
            $full    = wp_get_attachment_image_url($id, '2048x2048') ?: wp_get_attachment_image_url($id, 'full');
            $imgHtml = wp_get_attachment_image($id, $size, false, [
                'class'    => 'marmal-gallery__img',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => $alt,
            ]);
            $thumb = wp_get_attachment_image($id, 'medium', false, [
                'class'    => 'marmal-slider__thumb-img',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => '',
            ]);
        } else {
            $alt     = $img['alt'] ?? '';
            $caption = $img['caption'] ?? '';
            $full    = $img['url'];
            $imgHtml = '<img class="marmal-gallery__img" src="' . esc_url($img['url']) . '" alt="' . esc_attr($alt) . '" loading="lazy" decoding="async">';
            $thumb   = '<img class="marmal-slider__thumb-img" src="' . esc_url($img['url']) . '" alt="" loading="lazy" decoding="async">';
        }
        if (!$imgHtml || !$full) {
            continue;
        }
        $tiles[] = ['type' => 'image', 'html' => $imgHtml, 'thumb' => $thumb, 'full' => $full, 'caption' => $caption ?: $alt];
    }

    // Položka: video
    $videoUrl = trim((string) gallery_get($p, 'content.video.url', ''));
    if ($videoUrl !== '' && ($tiles || $canEdit)) {
        $pos   = max(1, (int) gallery_get($p, 'content.video.pozice', 6));
        $video = [
            'type'  => 'video',
            'url'   => $videoUrl,
            'embed' => gallery_video_embed($videoUrl),
            'text'  => (string) gallery_get($p, 'content.video.text', __('Videovizualizace', 'marmal-breakdance-plus')),
        ];
        array_splice($tiles, min($pos - 1, count($tiles)), 0, [$video]);
    }

    $typ = gallery_get($p, 'design.rozlozeni.typ', 'mozaika') === 'slider' ? 'slider' : 'mozaika';

    // Prázdná galerie: návštěvník nevidí nic, správce vidí zástupné dlaždice.
    $placeholder = false;
    if (!$tiles) {
        if (!$canEdit) {
            return $debug;
        }
        $placeholder = true;
        for ($i = 0; $i < 6; $i++) {
            $tiles[] = ['type' => 'placeholder'];
        }
    }

    if ($isFrontend && !$placeholder) {
        if ($lightbox) {
            wp_enqueue_style('marmal-lightbox');
            wp_enqueue_script('marmal-lightbox');
        }
        if ($typ === 'slider') {
            wp_enqueue_script('marmal-slider');
        }
    }

    $html = $debug;
    if ($placeholder) {
        $hint = gallery_get($p, 'content.obrazky.zdroj', 'rucne') === 'acf'
            ? __('Galerie Plus: ACF pole je prázdné nebo nenalezené. Na webu se zobrazí obrázky aktuálního příspěvku (v šabloně nastavte náhledový příspěvek).', 'marmal-breakdance-plus')
            : __('Galerie Plus: vyberte obrázky v záložce Content → Obrázky.', 'marmal-breakdance-plus');
        $html .= '<p class="marmal-gallery__hint">' . esc_html($hint) . '</p>';
    }

    $lb = $lightbox && !$placeholder;

    if ($typ === 'slider') {
        return $html . gallery_render_slider($p, $tiles, $lb);
    }

    // ---------- Mozaika ----------
    $total  = count($tiles);
    $mobile = gallery_get($p, 'design.mozaika.mobil', 'auto') === 'rucne' ? 'rucne' : 'auto';
    $html  .= '<div class="marmal-gallery" data-mobile="' . esc_attr($mobile) . '"' . ($lb ? ' data-marmal-lb' : '') . '>';
    $html  .= '<div class="marmal-gallery__grid" data-lb-items data-count="' . (int) $total . '">';

    foreach ($tiles as $i => $tile) {
        $remaining = $total - 1 - $i;
        $more      = $remaining > 0 ? ' data-more="+' . (int) $remaining . '"' : '';
        $html     .= gallery_item_html($tile, $i, $total, $lb, 'marmal-gallery__item', $more, true);
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Jedna položka (dlaždice mozaiky nebo snímek slideru).
 * Položky s atributem data-lb-item otevírá lightbox.
 */
function gallery_item_html(array $tile, int $i, int $total, bool $lb, string $class, string $attrs = '', bool $withCaption = false): string
{
    if ($tile['type'] === 'placeholder') {
        return '<div class="' . esc_attr($class) . ' is-placeholder"' . $attrs . ' aria-hidden="true"></div>';
    }

    if ($tile['type'] === 'video') {
        $inner = '<span class="marmal-gallery__video-inner">'
            . '<span class="marmal-gallery__play" aria-hidden="true"><svg viewBox="0 0 48 48" width="48" height="48"><circle cx="24" cy="24" r="22.5" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M19.5 15.5v17l14-8.5z" fill="currentColor"/></svg></span>'
            . '<span class="marmal-gallery__video-text">' . esc_html($tile['text']) . '</span>'
            . '</span>';

        if ($lb && $tile['embed']) {
            return '<a class="' . esc_attr($class) . ' is-video" href="' . esc_url($tile['url']) . '" data-lb-item'
                . ' data-video="' . esc_url($tile['embed']['src']) . '" data-video-type="' . esc_attr($tile['embed']['type']) . '"'
                . ' data-caption="' . esc_attr($tile['text']) . '"' . $attrs . '>' . $inner . '</a>';
        }
        return '<a class="' . esc_attr($class) . ' is-video" href="' . esc_url($tile['url']) . '" target="_blank" rel="noopener"' . $attrs . '>' . $inner . '</a>';
    }

    // Popisek pro efekt „Popisek zespodu“ (zobrazí ho CSS, jen když je efekt zapnutý)
    $caption = ($withCaption && $tile['caption'] !== '')
        ? '<span class="marmal-gallery__caption" aria-hidden="true">' . esc_html($tile['caption']) . '</span>'
        : '';

    if ($lb) {
        $label = sprintf(
            /* translators: 1: pořadí obrázku, 2: počet položek */
            __('Zvětšit obrázek %1$d z %2$d', 'marmal-breakdance-plus'),
            $i + 1,
            $total
        );
        return '<a class="' . esc_attr($class) . '" href="' . esc_url($tile['full']) . '" data-lb-item'
            . ' data-caption="' . esc_attr($tile['caption']) . '"'
            . ' aria-label="' . esc_attr($label) . '"' . $attrs . '>' . $tile['html'] . $caption . '</a>';
    }
    return '<div class="' . esc_attr($class) . '"' . $attrs . '>' . $tile['html'] . $caption . '</div>';
}

/**
 * Slider s miniaturami.
 * Hlavní pás posouvá prohlížeč sám (CSS scroll-snap, swipe na dotyku),
 * assets/slider/slider.js přidává šipky, miniatury, klávesnici a autoplay.
 */
function gallery_render_slider(array $p, array $tiles, bool $lb): string
{
    $total    = count($tiles);
    $autoplay = max(0, (int) gallery_get($p, 'design.slider.autoplay', 0));
    $arrows   = !gallery_get($p, 'design.slider.bez_sipek', false);
    $counter  = (bool) gallery_get($p, 'design.slider.pocitadlo', false);
    $thumbs   = !gallery_get($p, 'design.slider.bez_nahledu', false);
    $fit      = gallery_get($p, 'design.slider.prizpusobeni', 'cover') === 'contain' ? 'contain' : 'cover';
    $id       = 'marmal-slider-' . wp_unique_id();

    $icon = function (string $d) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    };

    $html  = '<div class="marmal-gallery marmal-gallery--slider" data-marmal-slider data-fit="' . $fit . '"'
        . ($autoplay ? ' data-autoplay="' . $autoplay . '"' : '') . ($lb ? ' data-marmal-lb' : '') . '>';
    $html .= '<div class="marmal-slider__main">';
    $html .= '<div class="marmal-slider__track" id="' . esc_attr($id) . '" data-lb-items tabindex="0" role="region" aria-roledescription="carousel" aria-label="' . esc_attr__('Galerie', 'marmal-breakdance-plus') . '">';

    foreach ($tiles as $i => $tile) {
        $first = $i === 0 && isset($tile['html']) ? str_replace('loading="lazy"', 'loading="eager"', $tile['html']) : null;
        if ($first !== null) {
            $tile['html'] = $first;
        }
        $html .= gallery_item_html($tile, $i, $total, $lb, 'marmal-slider__slide', ' data-index="' . $i . '"');
    }
    $html .= '</div>';

    if ($arrows && $total > 1) {
        $html .= '<button type="button" class="marmal-slider__arrow marmal-slider__prev" aria-controls="' . esc_attr($id) . '" aria-label="' . esc_attr__('Předchozí fotka', 'marmal-breakdance-plus') . '">' . $icon('M15 5l-7 7 7 7') . '</button>';
        $html .= '<button type="button" class="marmal-slider__arrow marmal-slider__next" aria-controls="' . esc_attr($id) . '" aria-label="' . esc_attr__('Další fotka', 'marmal-breakdance-plus') . '">' . $icon('M9 5l7 7-7 7') . '</button>';
    }
    if ($counter && $total > 1) {
        $html .= '<span class="marmal-slider__counter" aria-hidden="true"><span class="marmal-slider__current">1</span> / ' . $total . '</span>';
    }
    $html .= '</div>';

    if ($thumbs && $total > 1) {
        $html .= '<div class="marmal-slider__thumbs">';
        foreach ($tiles as $i => $tile) {
            $label = sprintf(__('Zobrazit fotku %1$d z %2$d', 'marmal-breakdance-plus'), $i + 1, $total);
            if ($tile['type'] === 'image') {
                $inner = $tile['thumb'];
            } elseif ($tile['type'] === 'video') {
                $inner = '<span class="marmal-slider__thumb-video" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><path d="M8 5.5v13l10.5-6.5z" fill="currentColor"/></svg></span>';
                $label = $tile['text'];
            } else {
                $inner = '';
            }
            $html .= '<button type="button" class="marmal-slider__thumb' . ($i === 0 ? ' is-active' : '') . '" data-index="' . $i . '"'
                . ' aria-controls="' . esc_attr($id) . '" aria-label="' . esc_attr($label) . '"' . ($i === 0 ? ' aria-current="true"' : '') . '>' . $inner . '</button>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
