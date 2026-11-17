<?php
/**
 * Импорт контента макета в WordPress: страницы, шаблоны, поля, фото в медиатеку.
 * Данные готовит tools/export_wp.py → import/content.json.
 *
 * Локально:  wp eval 'print_r(eeg_import_file());'
 * На сервере — одноразовым mu-plugin (см. tools/wp_setup_mu.php), картинки партиями.
 *
 * Формат content.json:
 *   options: {имя_поля: значение}                    — «Настройки сайта»
 *   pages:   [{path, title, template, menu_order, front, fields: {имя: значение}}]
 * Картинка в значении поля — строка "img:путь/в/assets/img.webp" или "img:путь|alt".
 * Повторитель — список словарей {имя_подполя: значение}, галерея — список картинок.
 */
defined('ABSPATH') || exit;

function eeg_import_file($file = null, $opts = [])
{
    $file = $file ?: get_theme_file_path('import/content.json');
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return ['error' => 'не прочитан ' . $file];
    }
    return eeg_import($data, $opts);
}

/** Все картинки, упомянутые в данных: ["путь" => "alt"]. */
function eeg_import_collect_images($value, &$out = [])
{
    if (is_array($value)) {
        foreach ($value as $v) {
            eeg_import_collect_images($v, $out);
        }
    } elseif (is_string($value) && strncmp($value, 'img:', 4) === 0) {
        [$rel, $alt] = array_pad(explode('|', substr($value, 4), 2), 2, '');
        if (!isset($out[$rel]) || $out[$rel] === '') {
            $out[$rel] = $alt;
        }
    }
    return $out;
}

/** Картинки партиями (на сервере, чтобы не упереться в лимит времени запроса). */
function eeg_import_images($data, $offset = 0, $limit = 0)
{
    $all = eeg_import_collect_images($data);
    $keys = array_keys($all);
    $slice = $limit ? array_slice($keys, $offset, $limit) : array_slice($keys, $offset);
    $log = [];
    foreach ($slice as $rel) {
        $log[$rel] = eeg_import_image($rel, $all[$rel]);
    }
    return ['total' => count($keys), 'done' => $offset + count($slice), 'log' => $log];
}

/** Вложение для картинки из assets/img темы (повторно не загружает). */
function eeg_import_image($rel, $alt = '')
{
    $map = get_option('eeg_img_map', []);
    if (!empty($map[$rel]) && get_post($map[$rel])) {
        if ($alt !== '' && !get_post_meta($map[$rel], '_wp_attachment_image_alt', true)) {
            update_post_meta($map[$rel], '_wp_attachment_image_alt', $alt);
        }
        return (int) $map[$rel];
    }
    $src = get_theme_file_path('assets/img/' . $rel);
    if (!is_file($src)) {
        return 0;
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $name = str_replace('/', '-', $rel);
    $up = wp_upload_bits($name, null, file_get_contents($src));
    if (!empty($up['error'])) {
        return 0;
    }
    $type = wp_check_filetype($up['file']);
    $id = wp_insert_attachment([
        'post_mime_type' => $type['type'] ?: 'image/webp',
        'post_title'     => $alt !== '' ? mb_substr($alt, 0, 120) : pathinfo($name, PATHINFO_FILENAME),
        'post_status'    => 'inherit',
    ], $up['file']);
    if (is_wp_error($id) || !$id) {
        return 0;
    }
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $up['file']));
    if ($alt !== '') {
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
    }
    update_post_meta($id, '_eeg_src', $rel);
    $map[$rel] = $id;
    update_option('eeg_img_map', $map, false);
    return (int) $id;
}

/** "img:…" → ID вложения во всём значении (повторители, галереи). */
function eeg_import_resolve($value)
{
    if (is_array($value)) {
        return array_map('eeg_import_resolve', $value);
    }
    if (is_string($value) && strncmp($value, 'img:', 4) === 0) {
        [$rel, $alt] = array_pad(explode('|', substr($value, 4), 2), 2, '');
        return eeg_import_image($rel, $alt);
    }
    return $value;
}

/** Ключи полей, которые относятся к странице (по её шаблону): имя → ключ. */
function eeg_import_field_keys($post_id)
{
    $keys = [];
    $groups = acf_get_field_groups(['post_id' => $post_id, 'post_type' => get_post_type($post_id) ?: 'page']);
    foreach ($groups as $g) {
        foreach ((array) acf_get_fields($g) as $f) {
            $keys[$f['name']] = $f['key'];
        }
    }
    return $keys;
}

function eeg_import($data, $opts = [])
{
    if (!function_exists('update_field')) {
        return ['error' => 'не активен плагин полей (Secure Custom Fields / ACF)'];
    }
    $opts += ['skip_existing' => false];
    $log = [];

    // «Настройки сайта»
    foreach ((array) ($data['options'] ?? []) as $name => $value) {
        $f = acf_get_field($name);
        if ($f) {
            update_field($f['key'], eeg_import_resolve($value), 'option');
        } else {
            $log[] = "options: нет поля $name";
        }
    }

    // родители раньше детей
    $pages = (array) ($data['pages'] ?? []);
    usort($pages, fn($a, $b) => substr_count(trim($a['path'], '/'), '/') <=> substr_count(trim($b['path'], '/'), '/')
        ?: ($a['menu_order'] ?? 0) <=> ($b['menu_order'] ?? 0));

    foreach ($pages as $p) {
        $path = trim($p['path'], '/');
        $existing = $path === '' ? null : get_page_by_path($path, OBJECT, 'page');
        if (!empty($p['front'])) {
            $front = (int) get_option('page_on_front');
            $existing = $front && get_post($front) ? get_post($front) : $existing;
        }
        if ($existing && $opts['skip_existing']) {
            $log[] = "пропущена (уже есть): /$path/";
            continue;
        }
        $parent = 0;
        if (strpos($path, '/') !== false) {
            $pp = get_page_by_path(dirname($path), OBJECT, 'page');
            if (!$pp) {
                // родителя ещё нет (раздел выгружают по частям) — временная страница, обновится при его импорте
                $pid = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => basename(dirname($path)),
                                       'post_name' => basename(dirname($path))]);
                $pp = get_post($pid);
                $log[] = "временный родитель /" . dirname($path) . "/ #$pid";
            }
            $parent = $pp ? $pp->ID : 0;
        }
        $postarr = [
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $p['title'],
            'post_name'    => $path === '' ? sanitize_title($p['slug'] ?? 'glavnaya') : basename($path),
            'post_parent'  => $parent,
            'menu_order'   => (int) ($p['menu_order'] ?? 0),
            'post_content' => $p['content'] ?? '',
        ];
        if ($existing) {
            $postarr['ID'] = $existing->ID;
            $id = wp_update_post($postarr, true);
        } else {
            $id = wp_insert_post($postarr, true);
        }
        if (is_wp_error($id)) {
            $log[] = "/$path/: " . $id->get_error_message();
            continue;
        }
        update_post_meta($id, '_wp_page_template', $p['template'] ?? 'default');
        if (!empty($p['front'])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $id);
        }
        $keys = eeg_import_field_keys($id);
        foreach ((array) ($p['fields'] ?? []) as $name => $value) {
            $yoast = ['seo_title' => '_yoast_wpseo_title', 'seo_description' => '_yoast_wpseo_metadesc'];
            if (isset($yoast[$name]) && function_exists('eeg_has_yoast') && eeg_has_yoast()) {
                update_post_meta($id, $yoast[$name], $value); // с Yoast SEO-поля темы выключены
                continue;
            }
            if (!isset($keys[$name])) {
                $log[] = "/$path/: нет поля $name в шаблоне " . ($p['template'] ?? 'default');
                continue;
            }
            update_field($keys[$name], eeg_import_resolve($value), $id);
        }
        $log[] = ($existing ? 'обновлена' : 'создана') . " /$path/ #$id";
    }
    return $log;
}
