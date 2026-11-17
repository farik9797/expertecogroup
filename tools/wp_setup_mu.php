<?php
/**
 * Одноразовая настройка сайта на хостинге (без SSH и WP-CLI).
 * tools/deploy_wp.sh кладёт копию в wp-content/mu-plugins/ с подставленным токеном, вызывает шаги и сразу удаляет.
 *
 *   ?eeg_setup=ТОКЕН&step=info                       версии, расширения, активная тема и плагины
 *   ?eeg_setup=ТОКЕН&step=activate                   плагин полей, тема, ЧПУ, часовой пояс, закрыть от индексации
 *   ?eeg_setup=ТОКЕН&step=images&offset=0&limit=15   фото в медиатеку партиями
 *   ?eeg_setup=ТОКЕН&step=pages                      страницы и поля из import/content.json темы
 *
 * Ничего не принимает из запроса, кроме номера шага и чисел партии; файлы читает только из папки темы.
 */
defined('ABSPATH') || exit;

const EEG_SETUP_TOKEN = '__TOKEN__';

add_action('wp_loaded', function () {
    // токен подставляет deploy_wp.sh (48 hex-символов); без него модуль ничего не делает
    if (!isset($_GET['eeg_setup']) || strlen(EEG_SETUP_TOKEN) < 32 || !hash_equals(EEG_SETUP_TOKEN, (string) $_GET['eeg_setup'])) {
        return;
    }
    @set_time_limit(300);
    nocache_headers();
    $step = sanitize_key($_GET['step'] ?? 'info');
    $out = ['step' => $step];

    switch ($step) {
        case 'info':
            global $wp_version;
            $out += [
                'php'        => PHP_VERSION,
                'wp'         => $wp_version,
                'theme'      => get_stylesheet(),
                'plugins'    => get_option('active_plugins'),
                'webp_gd'    => function_exists('imagewebp'),
                'imagick'    => extension_loaded('imagick'),
                'memory'     => ini_get('memory_limit'),
                'max_time'   => ini_get('max_execution_time'),
                'blog_public'=> get_option('blog_public'),
                'permalinks' => get_option('permalink_structure'),
                'pages'      => (int) wp_count_posts('page')->publish,
                'images'     => count((array) get_option('eeg_img_map', [])),
            ];
            break;

        case 'activate':
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugin = 'secure-custom-fields/secure-custom-fields.php';
            $r = is_plugin_active($plugin) ? true : activate_plugin($plugin);
            $out['plugin'] = is_wp_error($r) ? $r->get_error_message() : 'ok';
            if (wp_get_theme('expertecogroup')->exists()) {
                switch_theme('expertecogroup');
            }
            $out['theme'] = get_stylesheet();
            update_option('permalink_structure', '/%postname%/');
            update_option('timezone_string', 'Asia/Almaty');
            update_option('date_format', 'd.m.Y');
            update_option('blog_public', 0); // до сдачи сайт закрыт от поисковиков
            update_option('blogname', 'Expert ECO Group');
            update_option('blogdescription', 'Оборудование из полипропилена');
            // стандартные записи и страницы новой установки — в корзину (не удаляем насовсем)
            foreach (get_posts(['post_type' => ['post', 'page'], 'post_status' => 'any', 'numberposts' => 20,
                                'post_name__in' => ['hello-world', 'sample-page', 'privet-mir', 'primer-stranitsy']]) as $p) {
                wp_trash_post($p->ID);
                $out['trashed'][] = $p->post_name;
            }
            flush_rewrite_rules();
            break;

        case 'images':
            if (!function_exists('eeg_import_images')) {
                $out['error'] = 'тема не активна';
                break;
            }
            $data = json_decode((string) file_get_contents(get_theme_file_path('import/content.json')), true);
            $r = eeg_import_images($data, max(0, (int) ($_GET['offset'] ?? 0)), min(50, max(1, (int) ($_GET['limit'] ?? 15))));
            $out += ['total' => $r['total'], 'done' => $r['done'],
                     'failed' => array_keys(array_filter($r['log'], fn($id) => !$id))];
            break;

        case 'pages':
            if (!function_exists('eeg_import_file')) {
                $out['error'] = 'тема не активна';
                break;
            }
            $out['log'] = eeg_import_file();
            flush_rewrite_rules();
            break;

        case 'yoast':
            // ставит Yoast SEO с wordpress.org (адрес зашит здесь) и включает его
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugin = 'wordpress-seo/wp-seo.php';
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/misc.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                $skin = new Automatic_Upgrader_Skin();
                $r = (new Plugin_Upgrader($skin))->install('https://downloads.wordpress.org/plugin/wordpress-seo.latest-stable.zip');
                $out['install'] = is_wp_error($r) ? $r->get_error_message() : ($r ? 'ok' : implode(' | ', (array) $skin->get_upgrade_messages()));
            }
            $r = is_plugin_active($plugin) ? true : activate_plugin($plugin);
            $out['activate'] = is_wp_error($r) ? $r->get_error_message() : 'ok';
            break;

        case 'seo':
            // title/description из import/content.json → Yoast, noindex служебных страниц, настройки Yoast
            if (!function_exists('eeg_import_seo')) {
                $out['error'] = 'тема не активна';
                break;
            }
            $data = json_decode((string) file_get_contents(get_theme_file_path('import/content.json')), true);
            $out['seo'] = eeg_import_seo($data);
            $out['migrate'] = eeg_yoast_migrate();
            break;

        case 'srvlinks':
            // «Наши услуги» на главной: только поле «Куда ведёт» из import/content.json, по коду услуги
            $data = json_decode((string) file_get_contents(get_theme_file_path('import/content.json')), true);
            $front = (int) get_option('page_on_front');
            $links = [];
            foreach ((array) ($data['pages'] ?? []) as $p) {
                if (!empty($p['front'])) {
                    foreach ((array) ($p['fields']['services'] ?? []) as $s) {
                        $links[$s['slug']] = $s['link'] ?? '';
                    }
                }
            }
            for ($i = 0, $n = (int) get_post_meta($front, 'services', true); $i < $n; $i++) {
                $slug = get_post_meta($front, "services_{$i}_slug", true);
                if (!empty($links[$slug]) && get_post_meta($front, "services_{$i}_link", true) === '') {
                    update_post_meta($front, "services_{$i}_link", $links[$slug]);
                    update_post_meta($front, "_services_{$i}_link", 'field_eeg_home_srv_link');
                    $out['set'][$slug] = $links[$slug];
                }
            }
            break;

        case 'heroimg':
            // фон слайда «Ёмкости и резервуары»: кадр без надписи вместо hero-2390.webp (имена зашиты здесь).
            // Старое вложение остаётся в медиатеке.
            $front = (int) get_option('page_on_front');
            $map = get_option('eeg_img_map', []);
            $old = (int) ($map['hero-2390.webp'] ?? 0);
            $new = eeg_import_image('hero-emkosti-2390.webp', 'Кран поднимает подземный резервуар Expert ECO Group');
            $out += ['old' => $old, 'new' => $new];
            for ($i = 0, $n = (int) get_post_meta($front, 'hero_slides', true); $i < $n && $old && $new; $i++) {
                if ((int) get_post_meta($front, "hero_slides_{$i}_bg", true) === $old) {
                    update_post_meta($front, "hero_slides_{$i}_bg", $new);
                    $out['slide'] = $i;
                }
            }
            break;

        case 'cleanup':
            // стандартные темы и выключенные плагины установки, корзина (список зашит здесь)
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            foreach (['twentytwentythree', 'twentytwentyfour', 'twentytwentyfive'] as $t) {
                if (wp_get_theme($t)->exists() && get_stylesheet() !== $t && get_template() !== $t) {
                    $r = delete_theme($t);
                    $out['themes'][$t] = is_wp_error($r) ? $r->get_error_message() : ($r ? 'удалена' : 'не удалось');
                }
            }
            $plugins = array_values(array_filter(['hello.php', 'akismet/akismet.php'],
                fn($p) => file_exists(WP_PLUGIN_DIR . '/' . $p) && !is_plugin_active($p)));
            if ($plugins) {
                $r = delete_plugins($plugins);
                $out['plugins'] = is_wp_error($r) ? $r->get_error_message() : ($r ? $plugins : 'не удалось');
            }
            foreach (get_posts(['post_type' => ['post', 'page'], 'post_status' => 'trash', 'numberposts' => 50]) as $p) {
                wp_delete_post($p->ID, true);
                $out['trash'][] = $p->post_title;
            }
            break;

        default:
            $out['error'] = 'неизвестный шаг';
    }
    wp_send_json($out);
});
