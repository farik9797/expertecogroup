<?php
/**
 * Каталог: поля шаблонов «Каталог» (page-templates/catalog.php) и «Раздел каталога» (page-templates/section.php)
 * и функции, общие для этих шаблонов.
 *
 * Плитки разделов на странице каталога собираются из её дочерних страниц с шаблоном «Раздел каталога»:
 * порядок — «Порядок» в атрибутах страницы (menu_order), группа, картинка и подпись плитки — поля раздела.
 * Группы (заголовок и якорь) задаются на странице каталога.
 */
defined('ABSPATH') || exit;

/** Группы плиток по умолчанию (как в макете) — пока на странице каталога не заполнен список групп. */
function eeg_catalog_default_groups()
{
    return [
        ['title' => 'Очистные сооружения', 'anchor' => 'ochistnye'],
        ['title' => 'Дополнительное оборудование и сопутствующие товары', 'anchor' => 'oborudovanie'],
        ['title' => 'Услуги', 'anchor' => 'uslugi'],
    ];
}

/** ID страницы с шаблоном «Каталог». */
function eeg_catalog_page_id()
{
    $ids = get_posts([
        'post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
        'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/catalog.php',
    ]);
    return $ids ? (int) $ids[0] : 0;
}

/** Группы плиток каталога: [['title' => …, 'anchor' => …], …]. */
function eeg_catalog_groups($catalog_id = 0)
{
    $catalog_id = $catalog_id ?: eeg_catalog_page_id();
    $rows = $catalog_id ? eeg_field('catalog_groups', [], $catalog_id) : [];
    $rows = array_values(array_filter((array) $rows, fn($r) => trim($r['title'] ?? '') !== '' && trim($r['anchor'] ?? '') !== ''));
    return $rows ?: eeg_catalog_default_groups();
}

/** Классы обложки раздела (как в section_page генератора): мелкий кадр — sec-cover--small, вытянутый — is-contain. */
function eeg_catalog_cover_class($img)
{
    [$w] = eeg_img_size($img);
    $cls = ($w > 0 && $w < 720) ? ' sec-cover--small' : '';
    return $cls . eeg_fit_class($img);
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    /* ---------- страница «Каталог» ---------- */
    acf_add_local_field_group([
        'key'        => 'group_eeg_catalog',
        'title'      => 'Каталог',
        'menu_order' => 10,
        'fields'     => [
            [
                'key' => 'field_eeg_catalog_directions', 'name' => 'catalog_directions', 'label' => 'Основные направления',
                'instructions' => 'Крупные карточки под шапкой. Порядок — перетаскиванием.',
                'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Добавить направление',
                'sub_fields' => [
                    ['key' => 'field_eeg_catalog_dir_title', 'name' => 'title', 'label' => 'Название', 'type' => 'text'],
                    ['key' => 'field_eeg_catalog_dir_link', 'name' => 'link', 'label' => 'Ссылка', 'type' => 'text',
                     'instructions' => 'Путь на сайте, например catalog/los/', 'placeholder' => 'catalog/los/'],
                    ['key' => 'field_eeg_catalog_dir_tag', 'name' => 'tag', 'label' => 'Плашка на карточке', 'type' => 'text',
                     'placeholder' => '1–50 л/с'],
                    ['key' => 'field_eeg_catalog_dir_text', 'name' => 'text', 'label' => 'Описание', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
                    ['key' => 'field_eeg_catalog_dir_image', 'name' => 'image', 'label' => 'Фото', 'type' => 'image',
                     'return_format' => 'id', 'preview_size' => 'medium'],
                ],
            ],
            [
                'key' => 'field_eeg_catalog_groups', 'name' => 'catalog_groups', 'label' => 'Группы разделов',
                'instructions' => 'Заголовки групп плиток в порядке показа. Плитки — дочерние страницы каталога с шаблоном «Раздел каталога»: группу выбирают на странице раздела, порядок плиток задаёт поле «Порядок» в атрибутах страницы.',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить группу',
                'sub_fields' => [
                    ['key' => 'field_eeg_catalog_group_title', 'name' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
                    ['key' => 'field_eeg_catalog_group_anchor', 'name' => 'anchor', 'label' => 'Якорь', 'type' => 'text',
                     'instructions' => 'Латиницей, для ссылки catalog/#якорь. Не меняйте у существующей группы — разделы привязаны к нему.'],
                ],
            ],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/catalog.php']]],
    ]);

    /* ---------- страница «Раздел каталога» ---------- */
    acf_add_local_field_group([
        'key'        => 'group_eeg_section',
        'title'      => 'Раздел каталога',
        'menu_order' => 10,
        'fields'     => [
            ['key' => 'field_eeg_section_tab_tile', 'label' => 'Плитка в каталоге', 'type' => 'tab'],
            ['key' => 'field_eeg_section_group', 'name' => 'section_group', 'label' => 'Группа в каталоге', 'type' => 'select',
             'instructions' => 'Под каким заголовком плитка стоит на странице «Каталог». Список групп правится на странице каталога.',
             'choices' => [], 'allow_null' => 1, 'return_format' => 'value'],
            ['key' => 'field_eeg_section_tile_image', 'name' => 'section_tile_image', 'label' => 'Картинка плитки', 'type' => 'image',
             'instructions' => 'Пусто — обложка раздела.', 'return_format' => 'id', 'preview_size' => 'thumbnail'],
            ['key' => 'field_eeg_section_tile_tag', 'name' => 'section_tile_tag', 'label' => 'Подпись на плитке', 'type' => 'text',
             'instructions' => 'Пусто — крупный текст первой плашки под заголовком.'],

            ['key' => 'field_eeg_section_tab_text', 'label' => 'Текст и обложка', 'type' => 'tab'],
            ['key' => 'field_eeg_section_cover', 'name' => 'section_cover', 'label' => 'Обложка', 'type' => 'image',
             'instructions' => 'Фото рядом с текстом. Узкие и вертикальные кадры показываются целиком.', 'return_format' => 'id', 'preview_size' => 'medium'],
            ['key' => 'field_eeg_section_text', 'name' => 'section_text', 'label' => 'Текст о разделе', 'type' => 'textarea',
             'instructions' => 'Абзацы разделяйте пустой строкой.', 'rows' => 10, 'new_lines' => ''],

            ['key' => 'field_eeg_section_tab_points', 'label' => 'Что берём на себя', 'type' => 'tab'],
            [
                'key' => 'field_eeg_section_points', 'name' => 'section_points', 'label' => 'Что берём на себя',
                'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Добавить пункт',
                'sub_fields' => [
                    ['key' => 'field_eeg_section_point_icon', 'name' => 'icon', 'label' => 'Иконка', 'type' => 'text',
                     'instructions' => 'Имя иконки Lucide (lucide.dev/icons), например settings-2', 'wrapper' => ['width' => 25]],
                    ['key' => 'field_eeg_section_point_title', 'name' => 'title', 'label' => 'Заголовок', 'type' => 'text', 'wrapper' => ['width' => 75]],
                    ['key' => 'field_eeg_section_point_text', 'name' => 'text', 'label' => 'Текст', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
                ],
            ],

            ['key' => 'field_eeg_section_tab_uses', 'label' => 'Где применяют', 'type' => 'tab'],
            [
                'key' => 'field_eeg_section_uses', 'name' => 'section_uses', 'label' => 'Где применяют',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить строку',
                'sub_fields' => [
                    ['key' => 'field_eeg_section_use_text', 'name' => 'text', 'label' => 'Строка списка', 'type' => 'text'],
                ],
            ],

            ['key' => 'field_eeg_section_tab_photos', 'label' => 'Фото', 'type' => 'tab'],
            [
                'key' => 'field_eeg_section_photos', 'name' => 'section_photos', 'label' => 'Фото',
                'instructions' => 'Блок «Фото» внизу страницы. Показывается, если снимков два и больше. Порядок — перетаскиванием.',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить фото',
                'sub_fields' => [
                    ['key' => 'field_eeg_section_photo_image', 'name' => 'image', 'label' => 'Фото', 'type' => 'image',
                     'return_format' => 'id', 'preview_size' => 'thumbnail'],
                    ['key' => 'field_eeg_section_photo_alt', 'name' => 'alt', 'label' => 'Подпись (alt)', 'type' => 'text',
                     'instructions' => 'Пусто — подпись из медиатеки.'],
                ],
            ],

            ['key' => 'field_eeg_section_tab_sizes', 'label' => 'Типоразмеры', 'type' => 'tab'],
            ['key' => 'field_eeg_section_sizes_mark', 'name' => 'section_sizes_mark', 'label' => 'Заголовок первой колонки', 'type' => 'text',
             'placeholder' => 'Марка жироуловителя'],
            [
                'key' => 'field_eeg_section_sizes', 'name' => 'section_sizes', 'label' => 'Таблица типоразмеров',
                'instructions' => 'Пусто — блока «Типоразмеры» на странице нет.',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить строку',
                'sub_fields' => [
                    ['key' => 'field_eeg_section_size_mark', 'name' => 'mark', 'label' => 'Марка', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_dims', 'name' => 'dims', 'label' => 'Габариты Д×Ш×В, мм', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_q', 'name' => 'q', 'label' => 'м³/ч', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_ls', 'name' => 'ls', 'label' => 'л/с', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_salvo', 'name' => 'salvo', 'label' => 'Залповый сброс, л', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_in', 'name' => 'inlet', 'label' => 'Вход', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_out', 'name' => 'outlet', 'label' => 'Выход', 'type' => 'text'],
                    ['key' => 'field_eeg_section_size_dn', 'name' => 'dn', 'label' => 'Ø', 'type' => 'text'],
                ],
            ],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/section.php']]],
    ]);
});

/* Выбор группы на странице раздела — из списка групп на странице каталога (значение — якорь группы). */
add_filter('acf/load_field/key=field_eeg_section_group', function ($field) {
    $field['choices'] = [];
    foreach (eeg_catalog_groups() as $g) {
        $field['choices'][$g['anchor']] = $g['title'];
    }
    return $field;
});
