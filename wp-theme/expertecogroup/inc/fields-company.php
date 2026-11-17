<?php
/**
 * Поля шаблонов «Проекты» (карта объектов + фотоальбом), «Фотоальбом», «О компании», «Контакты»
 * и функции этих страниц (префикс eeg_company_).
 */
defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    /* ---------- Проекты ---------- */
    acf_add_local_field_group([
        'key'    => 'group_eeg_proj',
        'title'  => 'Проекты: карта объектов',
        'fields' => [
            [
                'key' => 'field_eeg_proj_albums_info', 'name' => '', 'label' => 'Фотоальбом',
                'type' => 'message', 'new_lines' => 'wpautop',
                'message' => 'Альбомы на вкладке «Фотоальбом» — дочерние страницы страницы «Фотоальбом» (Страницы → Фотоальбом → дочерние). Порядок карточек — поле «Порядок» у страницы альбома.',
            ],
            [
                'key' => 'field_eeg_proj_pins', 'name' => 'proj_pins', 'label' => 'Объекты на карте',
                'instructions' => 'Каждый объект — точка на карте и карточка в списке рядом. Первая карточка открыта сразу.',
                'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Добавить объект', 'collapsed' => 'field_eeg_proj_pin_city',
                'sub_fields' => [
                    ['key' => 'field_eeg_proj_pin_city', 'name' => 'city', 'label' => 'Город', 'type' => 'text', 'wrapper' => ['width' => 34]],
                    ['key' => 'field_eeg_proj_pin_region', 'name' => 'region', 'label' => 'Область', 'type' => 'text', 'wrapper' => ['width' => 33],
                     'instructions' => 'Можно оставить пустым.'],
                    ['key' => 'field_eeg_proj_pin_slug', 'name' => 'slug', 'label' => 'Код объекта', 'type' => 'text', 'wrapper' => ['width' => 33],
                     'instructions' => 'Латиницей, без пробелов, у каждого объекта свой: aktau. Пусто — номер по порядку.'],
                    ['key' => 'field_eeg_proj_pin_lat', 'name' => 'lat', 'label' => 'Широта', 'type' => 'number', 'step' => 'any', 'wrapper' => ['width' => 50],
                     'instructions' => 'Например 43.641. Координаты точки — в Яндекс.Картах или Google Картах (правая кнопка мыши по месту).'],
                    ['key' => 'field_eeg_proj_pin_lng', 'name' => 'lng', 'label' => 'Долгота', 'type' => 'number', 'step' => 'any', 'wrapper' => ['width' => 50],
                     'instructions' => 'Например 51.198. Объект без координат на сайт не выводится.'],
                    ['key' => 'field_eeg_proj_pin_title', 'name' => 'title', 'label' => 'Название объекта', 'type' => 'text'],
                    ['key' => 'field_eeg_proj_pin_kind', 'name' => 'kind', 'label' => 'Вид работ', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'placeholder' => 'Поставка, Монтаж, Производство'],
                    ['key' => 'field_eeg_proj_pin_year', 'name' => 'year', 'label' => 'Год', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_proj_pin_text', 'name' => 'text', 'label' => 'Описание', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
                    ['key' => 'field_eeg_proj_pin_done', 'name' => 'done', 'label' => 'Что сделали', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '',
                     'instructions' => 'По одному пункту в строке. Можно оставить пустым.'],
                    ['key' => 'field_eeg_proj_pin_confirmed', 'name' => 'confirmed', 'label' => 'Данные подтверждены', 'type' => 'true_false',
                     'ui' => 1, 'default_value' => 1,
                     'instructions' => 'Без галочки точка и карточка бледные — это пример, данные объекта ещё уточняются.'],
                ],
            ],
            ['key' => 'field_eeg_proj_map_note', 'name' => 'proj_map_note', 'label' => 'Подсказка под картой', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/projects.php']]],
    ]);

    /* ---------- Фотоальбом ---------- */
    acf_add_local_field_group([
        'key'    => 'group_eeg_album',
        'title'  => 'Фотоальбом',
        'fields' => [
            [
                'key' => 'field_eeg_album_info', 'name' => '', 'label' => 'Как устроен альбом',
                'type' => 'message', 'new_lines' => 'wpautop',
                'message' => "Название альбома — заголовок страницы, описание — поле «Подзаголовок» в шапке страницы. Оба выводятся и на карточке альбома на странице «Проекты».\nПока фотографий нет, карточка помечена «скоро» и не открывается, а адрес альбома ведёт на «Проекты» → «Фотоальбом».",
            ],
            ['key' => 'field_eeg_album_photos', 'name' => 'album_photos', 'label' => 'Фотографии', 'type' => 'gallery',
             'return_format' => 'id', 'preview_size' => 'medium', 'insert' => 'append', 'library' => 'all',
             'instructions' => 'Первое фото — обложка карточки альбома. Порядок меняется перетаскиванием.'],
            ['key' => 'field_eeg_album_icon', 'name' => 'album_icon', 'label' => 'Значок, пока фото нет', 'type' => 'text', 'placeholder' => 'camera',
             'instructions' => 'Название значка с сайта lucide.dev/icons, например camera или users. Пусто — camera.'],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/album.php']]],
    ]);

    /* ---------- О компании ---------- */
    $stars = 'Слова между звёздочками выделяются: Кто мы *и как* работаем.';
    acf_add_local_field_group([
        'key'    => 'group_eeg_about',
        'title'  => 'О компании',
        'fields' => [
            ['key' => 'field_eeg_about_tab_text', 'label' => 'Кто мы', 'type' => 'tab'],
            ['key' => 'field_eeg_about_intro_title', 'name' => 'about_intro_title', 'label' => 'Заголовок', 'type' => 'text', 'instructions' => $stars],
            ['key' => 'field_eeg_about_text', 'name' => 'about_text', 'label' => 'Текст о компании', 'type' => 'textarea', 'rows' => 14, 'new_lines' => '',
             'instructions' => 'Абзацы разделяйте пустой строкой.'],

            ['key' => 'field_eeg_about_tab_steps', 'label' => 'История', 'type' => 'tab'],
            ['key' => 'field_eeg_about_photo', 'name' => 'about_photo', 'label' => 'Фото', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium'],
            ['key' => 'field_eeg_about_photo_alt', 'name' => 'about_photo_alt', 'label' => 'Подпись фото для поисковиков (alt)', 'type' => 'text',
             'instructions' => 'Что на фото, коротко.'],
            ['key' => 'field_eeg_about_steps_title', 'name' => 'about_steps_title', 'label' => 'Заголовок', 'type' => 'text'],
            [
                'key' => 'field_eeg_about_steps', 'name' => 'about_steps', 'label' => 'Этапы', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить этап',
                'sub_fields' => [
                    ['key' => 'field_eeg_about_step_year', 'name' => 'year', 'label' => 'Год', 'type' => 'text', 'wrapper' => ['width' => 20]],
                    ['key' => 'field_eeg_about_step_text', 'name' => 'text', 'label' => 'Что произошло', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
                ],
            ],
            ['key' => 'field_eeg_about_note', 'name' => 'about_note', 'label' => 'Плашка под этапами', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],

            ['key' => 'field_eeg_about_tab_what', 'label' => 'Что мы делаем', 'type' => 'tab'],
            ['key' => 'field_eeg_about_what_title', 'name' => 'about_what_title', 'label' => 'Заголовок', 'type' => 'text'],
            [
                'key' => 'field_eeg_about_what', 'name' => 'about_what', 'label' => 'Направления', 'type' => 'repeater',
                'layout' => 'block', 'button_label' => 'Добавить направление',
                'sub_fields' => [
                    ['key' => 'field_eeg_about_what_name', 'name' => 'title', 'label' => 'Название', 'type' => 'text'],
                    ['key' => 'field_eeg_about_what_text', 'name' => 'text', 'label' => 'Описание', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '',
                     'instructions' => 'Слова между звёздочками выделяются жирным: *Salher*.'],
                ],
            ],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/about.php']]],
    ]);

    /* ---------- Контакты ---------- */
    acf_add_local_field_group([
        'key'    => 'group_eeg_contacts',
        'title'  => 'Контакты',
        'fields' => [
            [
                'key' => 'field_eeg_contacts_phones', 'name' => 'contacts_phones', 'label' => 'Телефоны на странице «Контакты»',
                'instructions' => 'Здесь можно указать больше номеров, чем в «Настройках сайта». Пусто — номера из «Настроек сайта». WhatsApp, почта, адрес и часы работы — из «Настроек сайта».',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить телефон',
                'sub_fields' => [
                    ['key' => 'field_eeg_contacts_phone_number', 'name' => 'number', 'label' => 'Номер', 'type' => 'text', 'placeholder' => '+7 777 484-18-22'],
                    ['key' => 'field_eeg_contacts_phone_label', 'name' => 'label', 'label' => 'Подпись', 'type' => 'text', 'placeholder' => 'Отдел продаж, Сергей'],
                ],
            ],
            ['key' => 'field_eeg_contacts_email_note', 'name' => 'contacts_email_note', 'label' => 'Подпись под почтой', 'type' => 'text'],
            ['key' => 'field_eeg_contacts_address_note', 'name' => 'contacts_address_note', 'label' => 'Подпись под адресом', 'type' => 'text'],
            ['key' => 'field_eeg_contacts_hours_note', 'name' => 'contacts_hours_note', 'label' => 'Подпись под часами работы', 'type' => 'text'],
            ['key' => 'field_eeg_contacts_note', 'name' => 'contacts_note', 'label' => 'Плашка с реквизитами', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
            ['key' => 'field_eeg_contacts_form_title', 'name' => 'contacts_form_title', 'label' => 'Блок заявки: заголовок', 'type' => 'text'],
            ['key' => 'field_eeg_contacts_form_text', 'name' => 'contacts_form_text', 'label' => 'Блок заявки: текст', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/contacts.php']]],
    ]);
});

/**
 * Текст со *звёздочками*: выделенные слова оборачиваются в $open…$close, остальное экранируется.
 * eeg_company_marked('Кто мы *и как* работаем', '<span class="accent text-brand">', '</span>')
 */
function eeg_company_marked($text, $open, $close)
{
    $parts = explode('*', (string) $text);
    $out = '';
    foreach ($parts as $i => $part) {
        $part = esc_html($part);
        // нечётные куски — между звёздочками; непарная последняя звёздочка выводится как есть
        if ($i % 2 === 1 && $i < count($parts) - 1) {
            $out .= $open . $part . $close;
        } else {
            $out .= ($i % 2 === 1 ? '*' : '') . $part;
        }
    }
    return $out;
}

/**
 * JSON для data-атрибута в том же виде, что выдаёт генератор макета (json.dumps: «, » и «: »),
 * чтобы разметка совпадала с макетом. Внутри строк JSON переносов нет — их можно убирать.
 */
function eeg_company_json($data)
{
    $json = (string) wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $json = preg_replace('~,\n\s*~', ', ', $json);
    return preg_replace('~\n\s*~', '', $json);
}

/** Объекты карты со страницы «Проекты» в формате content/projects.json (его ждёт main.js). Без координат — пропускаем. */
function eeg_company_pins($post_id)
{
    $out = [];
    foreach ((array) eeg_field('proj_pins', [], $post_id) as $i => $r) {
        $lat = trim((string) ($r['lat'] ?? ''));
        $lng = trim((string) ($r['lng'] ?? ''));
        if (!is_numeric($lat) || !is_numeric($lng)) {
            continue;
        }
        $slug = sanitize_title((string) ($r['slug'] ?? ''));
        $out[] = [
            'id'        => $slug !== '' ? $slug : 'obj-' . ($i + 1),
            'city'      => trim((string) ($r['city'] ?? '')),
            'region'    => trim((string) ($r['region'] ?? '')),
            'coords'    => [(float) $lat, (float) $lng],
            'title'     => trim((string) ($r['title'] ?? '')),
            'year'      => trim((string) ($r['year'] ?? '')),
            'kind'      => trim((string) ($r['kind'] ?? '')),
            'text'      => trim((string) ($r['text'] ?? '')),
            'done'      => eeg_list($r['done'] ?? ''),
            'confirmed' => !empty($r['confirmed']),
        ];
    }
    return $out;
}

/** Страницы альбомов — дочерние страницы «Фотоальбома» в порядке menu_order. */
function eeg_company_albums()
{
    $parent = get_page_by_path('fotoalbom', OBJECT, 'page');
    if (!$parent) {
        return [];
    }
    return get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_parent'    => $parent->ID,
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'posts_per_page' => -1,
    ]);
}

/** Фото альбома: ID вложений из галереи. */
function eeg_company_album_photos($post_id)
{
    return array_values(array_filter(array_map(
        fn($im) => is_array($im) ? (int) ($im['ID'] ?? 0) : (int) $im,
        (array) eeg_field('album_photos', [], $post_id)
    )));
}

/** Карточки альбомов для вкладки «Фотоальбом» (album_cards в генераторе). Альбом без фото — карточка «скоро» без ссылки. */
function eeg_company_album_cards()
{
    $cards = '';
    foreach (eeg_company_albums() as $p) {
        $title = eeg_field('head_title', get_the_title($p), $p->ID);
        $lead = eeg_field('head_lead', '', $p->ID);
        $photos = eeg_company_album_photos($p->ID);
        if ($photos) {
            $ph = '<span class="album-card__ph' . eeg_fit_class($photos[0]) . '">'
                . '<img src="' . esc_url(eeg_img_url($photos[0])) . '" alt="' . esc_attr(wp_strip_all_tags($title)) . '" loading="lazy">'
                . '<span class="album-card__count">' . count($photos) . ' фото</span></span>';
        } else {
            $icon = eeg_field('album_icon', 'camera', $p->ID);
            $ph = '<span class="album-card__ph album-card__ph--empty"><i data-lucide="' . esc_attr($icon) . '"></i>'
                . '<span class="album-card__count album-card__count--soon">скоро</span></span>';
        }
        $inner = $ph . '
    <span class="album-card__body">
      <span class="album-card__title">' . wp_kses_post($title) . '</span>
      <span class="album-card__lead">' . wp_kses_post($lead) . '</span>
    </span>';
        $cards .= $photos
            ? '<a class="album-card" href="' . esc_url(get_permalink($p)) . '">' . $inner . '</a>'
            : '<span class="album-card album-card--soon">' . $inner . '</span>';
    }
    return $cards;
}

/** Номер WhatsApp из настроек в виде +7 771 228-22-03. */
function eeg_company_phone_view($digits)
{
    $d = preg_replace('~\D~', '', (string) $digits);
    if (strlen($d) === 11) {
        return '+' . $d[0] . ' ' . substr($d, 1, 3) . ' ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2);
    }
    return $d !== '' ? '+' . $d : '';
}
