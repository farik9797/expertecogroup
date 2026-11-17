<?php
/**
 * Общие поля: «Настройки сайта» (телефоны, почта, адрес), SEO страницы,
 * шапка внутренней страницы (заголовок, лид, плашки) и перенаправление.
 * Поля шаблонов — в inc/fields-<шаблон>.php.
 */
defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'Настройки сайта',
            'menu_title' => 'Настройки сайта',
            'menu_slug'  => 'eeg-settings',
            'icon_url'   => 'dashicons-phone',
            'position'   => 3,
            'redirect'   => false,
        ]);
    }

    acf_add_local_field_group([
        'key'    => 'group_eeg_options',
        'title'  => 'Контакты и подвал',
        'fields' => [
            [
                'key' => 'field_eeg_phones', 'name' => 'phones', 'label' => 'Телефоны отдела продаж',
                'instructions' => 'Первый номер — основной: он в меню, в попапе заявки и на кнопке «Позвонить» на телефоне.',
                'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Добавить телефон',
                'sub_fields' => [
                    ['key' => 'field_eeg_phone_number', 'name' => 'number', 'label' => 'Номер', 'type' => 'text', 'placeholder' => '+7 777 484-18-22'],
                    ['key' => 'field_eeg_phone_label', 'name' => 'label', 'label' => 'Подпись', 'type' => 'text', 'placeholder' => 'Отдел продаж, Сергей'],
                ],
            ],
            ['key' => 'field_eeg_whatsapp', 'name' => 'whatsapp', 'label' => 'WhatsApp (номер)', 'type' => 'text', 'placeholder' => '77712282203',
             'instructions' => 'Только цифры, с кодом страны.'],
            ['key' => 'field_eeg_email', 'name' => 'email', 'label' => 'Почта', 'type' => 'email'],
            ['key' => 'field_eeg_request_email', 'name' => 'request_email', 'label' => 'Куда приходят заявки с сайта', 'type' => 'text',
             'instructions' => 'Один или несколько адресов через запятую. Пусто — на почту выше.'],
            ['key' => 'field_eeg_address', 'name' => 'address', 'label' => 'Адрес', 'type' => 'text'],
            ['key' => 'field_eeg_address_map', 'name' => 'address_map', 'label' => 'Ссылка на адрес в картах', 'type' => 'url'],
            ['key' => 'field_eeg_hours', 'name' => 'hours', 'label' => 'Часы работы', 'type' => 'text', 'placeholder' => 'Пн–пт, 08:00–18:00'],
            ['key' => 'field_eeg_footer_about', 'name' => 'footer_about', 'label' => 'Текст о компании в подвале', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'field_eeg_company', 'name' => 'company', 'label' => 'Название организации', 'type' => 'text', 'placeholder' => 'ТОО «Expert ECO Group»'],
        ],
        'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => 'eeg-settings']]],
    ]);

    acf_add_local_field_group([
        'key'        => 'group_eeg_page_head',
        'title'      => 'Шапка страницы',
        'menu_order' => 0,
        'position'   => 'acf_after_title',
        'fields'     => [
            ['key' => 'field_eeg_head_title', 'name' => 'head_title', 'label' => 'Заголовок H1', 'type' => 'text',
             'instructions' => 'Пусто — заголовок страницы.'],
            ['key' => 'field_eeg_crumb', 'name' => 'crumb', 'label' => 'Короткое название', 'type' => 'text',
             'instructions' => 'Для хлебных крошек и карточек. Пусто — заголовок страницы.'],
            ['key' => 'field_eeg_head_lead', 'name' => 'head_lead', 'label' => 'Подзаголовок', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
            [
                'key' => 'field_eeg_facts', 'name' => 'facts', 'label' => 'Плашки под заголовком', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить плашку', 'max' => 4,
                'sub_fields' => [
                    ['key' => 'field_eeg_fact_a', 'name' => 'a', 'label' => 'Крупно', 'type' => 'text'],
                    ['key' => 'field_eeg_fact_b', 'name' => 'b', 'label' => 'Подпись', 'type' => 'text'],
                ],
            ],
            ['key' => 'field_eeg_purpose', 'name' => 'purpose', 'label' => 'Назначение в форме заявки', 'type' => 'select',
             'instructions' => 'Если выбрано, в форме на этой странице остаётся только этот вариант.',
             'choices' => ['' => '— все варианты —', 'Очистные' => 'Очистные', 'КНС' => 'КНС', 'Ёмкости' => 'Ёмкости', 'Другое' => 'Другое'],
             'allow_null' => 0, 'default_value' => ''],
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'page_type', 'operator' => '!=', 'value' => 'front_page'],
            ['param' => 'page_template', 'operator' => '!=', 'value' => 'page-templates/redirect.php'],
        ]],
    ]);

    acf_add_local_field_group([
        'key'        => 'group_eeg_seo',
        'title'      => 'SEO',
        'menu_order' => 100,
        'position'   => 'side',
        'fields'     => [
            ['key' => 'field_eeg_seo_title', 'name' => 'seo_title', 'label' => 'Title', 'type' => 'text'],
            ['key' => 'field_eeg_seo_description', 'name' => 'seo_description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4],
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'page_template', 'operator' => '!=', 'value' => 'page-templates/redirect.php'],
        ]],
    ]);

    acf_add_local_field_group([
        'key'    => 'group_eeg_redirect',
        'title'  => 'Перенаправление',
        'fields' => [
            ['key' => 'field_eeg_redirect_to', 'name' => 'redirect_to', 'label' => 'Куда перенаправлять', 'type' => 'text',
             'instructions' => 'Путь на сайте, например proekty/#fotoalbom'],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/redirect.php']]],
    ]);
});
