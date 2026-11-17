<?php
/**
 * Yoast SEO. Когда плагин включён, <title>, description, Open Graph, canonical, schema и карту сайта
 * выводит он, а тема своё не выводит и поля «SEO» не показывает (см. functions.php, fields-common.php).
 * eeg_yoast_migrate() один раз переносит title/description из полей темы в Yoast и настраивает плагин.
 */
defined('ABSPATH') || exit;

function eeg_has_yoast()
{
    return defined('WPSEO_VERSION');
}

/** Страницы не для поиска: переадресации (/fotoalbom/, /karta-proektov/) и альбомы, пока в них нет фото. */
function eeg_yoast_should_noindex($post_id)
{
    $tpl = get_page_template_slug($post_id);
    if ($tpl === 'page-templates/redirect.php') {
        return true;
    }
    return $tpl === 'page-templates/album.php' && !get_post_meta($post_id, 'album_photos', true);
}

function eeg_yoast_migrate($force = false)
{
    if (!eeg_has_yoast()) {
        return ['error' => 'Yoast SEO не активен'];
    }
    $log = [];
    foreach (get_pages(['post_status' => 'publish']) as $p) {
        $id = $p->ID;
        $pairs = ['seo_title' => '_yoast_wpseo_title', 'seo_description' => '_yoast_wpseo_metadesc'];
        foreach ($pairs as $ours => $yoast) {
            $v = trim((string) get_post_meta($id, $ours, true));
            if ($v !== '' && ($force || get_post_meta($id, $yoast, true) === '')) {
                update_post_meta($id, $yoast, $v);
            }
        }
        $noindex = eeg_yoast_should_noindex($id);
        if ($noindex) {
            update_post_meta($id, '_yoast_wpseo_meta-robots-noindex', '1');
        } else {
            delete_post_meta($id, '_yoast_wpseo_meta-robots-noindex');
        }
        wp_update_post(['ID' => $id]); // Yoast пересобирает свою копию данных страницы (Indexables)
        $log[] = '/' . get_page_uri($id) . '/' . ($noindex ? ' — noindex' : '');
    }

    if (class_exists('WPSEO_Options')) {
        $logo = function_exists('eeg_import_image') ? eeg_import_image('logo-eeg.webp', 'Логотип Expert ECO Group') : 0;
        $og = function_exists('eeg_import_image') ? eeg_import_image('photo/gorizontalnye-u-tseha.webp') : 0;
        $set = [
            'company_or_person'    => 'company',
            'company_name'         => 'Expert ECO Group',
            'website_name'         => 'Expert ECO Group',
            'company_logo'         => $logo ? wp_get_attachment_url($logo) : '',
            'company_logo_id'      => $logo,
            'og_default_image'     => $og ? wp_get_attachment_url($og) : '',
            'og_default_image_id'  => $og,
            'disable-author'       => true,   // записей и авторов на сайте нет
            'disable-date'         => true,
            'disable-attachment'   => true,   // страницы вложений → сам файл
            'noindex-post'         => true,
            'noindex-tax-category' => true,
            'noindex-tax-post_tag' => true,
            'noindex-tax-post_format' => true,
            'breadcrumbs-enable'   => false,  // крошки выводит тема
        ];
        foreach ($set as $k => $v) {
            WPSEO_Options::set($k, $v);
        }
        $log[] = 'настройки Yoast: организация, логотип, картинка для соцсетей, лишние архивы закрыты';
    }
    return $log;
}

/**
 * Только title/description из import/content.json (поля seo_title, seo_description), без перезаписи контента.
 * С Yoast пишет в его поля, без Yoast — в поля темы.
 */
function eeg_import_seo($data)
{
    $log = [];
    foreach ((array) ($data['pages'] ?? []) as $p) {
        $path = trim($p['path'], '/');
        $post = !empty($p['front']) ? get_post((int) get_option('page_on_front')) : get_page_by_path($path, OBJECT, 'page');
        if (!$post) {
            $log[] = "нет страницы /$path/";
            continue;
        }
        foreach (['seo_title' => '_yoast_wpseo_title', 'seo_description' => '_yoast_wpseo_metadesc'] as $ours => $yoast) {
            if (!isset($p['fields'][$ours])) {
                continue;
            }
            eeg_has_yoast() ? update_post_meta($post->ID, $yoast, $p['fields'][$ours]) : update_field($ours, $p['fields'][$ours], $post->ID);
        }
        wp_update_post(['ID' => $post->ID]);
    }
    return $log;
}

/** Главная картинка страницы для превью в соцсетях и мессенджерах (поля шаблонов темы). */
function eeg_page_main_image($id)
{
    $first = function ($v) {
        $v = maybe_unserialize($v);
        return is_array($v) ? (int) reset($v) : (int) $v;
    };
    foreach (['tank_gallery', 'album_photos'] as $gallery) {
        if ($img = $first(get_post_meta($id, $gallery, true))) {
            return $img;
        }
    }
    foreach (['section_cover', 'tanks_intro_img', 'about_photo', 'hero_slides_0_bg', 'production_image'] as $single) {
        if ($img = (int) get_post_meta($id, $single, true)) {
            return $img;
        }
    }
    return 0;
}

add_action('wpseo_add_opengraph_images', function ($images) {
    if (is_page() && ($img = eeg_page_main_image(get_queried_object_id()))) {
        $images->add_image_by_id($img);
    }
});
