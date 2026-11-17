<?php
/**
 * Тема Expert ECO Group.
 * Разметка перенесена 1:1 со статического макета (site/), контент — в полях SCF/ACF.
 */
defined('ABSPATH') || exit;

define('EEG_VER', '1.0.0');

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/fields-common.php';
require_once __DIR__ . '/inc/request-form.php';
require_once __DIR__ . '/inc/importer.php';
// поля шаблонов страниц: по файлу на шаблон (inc/fields-*.php)
foreach (glob(__DIR__ . '/inc/fields-*.php') as $f) {
    require_once $f;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['script', 'style', 'gallery', 'caption']);
});

/* ---------- стили и скрипты (порядок и версии — как в макете) ---------- */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('eeg-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,500;1,600&family=Nunito:wght@400;600;700;800&family=Unbounded:wght@400;500;600&display=swap', [], null);
    wp_enqueue_style('eeg-tailwind', eeg_asset('css/tailwind.css'), [], EEG_VER);
    wp_enqueue_style('eeg-site', eeg_asset('css/site.css'), ['eeg-tailwind'], EEG_VER);

    wp_enqueue_script('lucide', 'https://cdn.jsdelivr.net/npm/lucide@1.47.0/dist/umd/lucide.min.js', [], null, ['in_footer' => true]);
    wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.15.0/gsap.min.js', [], null, ['in_footer' => true, 'strategy' => 'defer']);
    wp_enqueue_script('gsap-st', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.15.0/ScrollTrigger.min.js', ['gsap'], null, ['in_footer' => true, 'strategy' => 'defer']);
    wp_enqueue_script('eeg-main', eeg_asset('js/main.js'), ['lucide', 'gsap', 'gsap-st'], EEG_VER, ['in_footer' => true, 'strategy' => 'defer']);
    wp_add_inline_script('eeg-main', 'window.EEG = ' . wp_json_encode([
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eeg_request'),
    ]) . ';', 'before');

    // карта проектов — только на странице «Проекты»
    if (is_page_template('page-templates/projects.php')) {
        wp_enqueue_style('leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', [], null);
        wp_enqueue_script('leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', [], null, ['in_footer' => true]);
    }
});

/* Tailwind: в макете — браузерная сборка, в теме — готовый CSS (assets/css/tailwind.css),
   его собирает tools/deploy_wp.sh из классов шаблонов: tools/tailwind/input.css. */

add_action('wp_head', function () {
    echo '<link rel="icon" href="' . esc_url(eeg_asset('img/logo-eeg.webp')) . '">' . "\n";
    $desc = eeg_seo_description();
    if ($desc) {
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    }
}, 2);

/* SEO-заголовок страницы из поля seo_title */
add_filter('pre_get_document_title', function ($title) {
    $id = get_queried_object_id();
    $t = $id ? eeg_field('seo_title', '', $id) : '';
    return $t ?: $title;
});

/* Лишнее из <head> */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

/* Пустые служебные страницы-разделы: /fotoalbom/ → альбомы на «Проектах», /karta-proektov/ → карта */
add_action('template_redirect', function () {
    if (!is_page()) {
        return;
    }
    $to = eeg_field('redirect_to', '', get_queried_object_id());
    if ($to) {
        wp_safe_redirect(eeg_url($to), 301);
        exit;
    }
});
