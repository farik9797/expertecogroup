<?php
/**
 * Главная страница: поля (первый экран со слайдами, продукция, услуги, производство,
 * этапы, фото, вопросы, заявка) и вспомогательные функции шаблона front-page.php.
 */
defined('ABSPATH') || exit;

/**
 * Текст поля с выделением: часть в *звёздочках* оборачивается в <span class="$class">.
 * Остальное экранируется (сущности вроде &nbsp; сохраняются).
 */
function eeg_home_mark($text, $class)
{
    return preg_replace('~\*(.+?)\*~u', '<span class="' . esc_attr($class) . '">$1</span>', esc_html(trim((string) $text)));
}

/** Номер WhatsApp из настроек в виде +7 771 228-22-03. */
function eeg_home_wa_number()
{
    $d = preg_replace('~\D~', '', (string) eeg_opt('whatsapp', '77712282203'));
    if (strlen($d) === 11) {
        return '+' . $d[0] . ' ' . substr($d, 1, 3) . ' ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2);
    }
    return '+' . $d;
}

/* Первый кадр первого экрана грузим заранее (как preload в макете). */
add_action('wp_head', function () {
    if (!is_front_page()) {
        return;
    }
    $slides = eeg_field('hero_slides', [], get_queried_object_id());
    $bg = $slides[0]['bg'] ?? 0;
    if (!$bg) {
        return;
    }
    $srcset = eeg_img_srcset($bg);
    echo '<link rel="preload" as="image" href="' . esc_url(eeg_img_url($bg)) . '"'
        . ($srcset ? ' imagesrcset="' . esc_attr($srcset) . '" imagesizes="100vw"' : '') . ">\n";
}, 3);

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $mark = 'Часть в *звёздочках* выделяется цветом.';
    $link = 'Адрес на сайте, например catalog/kns/ (или полная ссылка).';

    acf_add_local_field_group([
        'key'        => 'group_eeg_home',
        'title'      => 'Главная страница',
        'menu_order' => 0,
        'position'   => 'acf_after_title',
        'hide_on_screen' => ['the_content'],
        'fields'     => [
            /* ---------- первый экран ---------- */
            ['key' => 'field_eeg_home_tab_hero', 'label' => 'Первый экран', 'type' => 'tab', 'placement' => 'top'],
            [
                'key' => 'field_eeg_home_hero_slides', 'name' => 'hero_slides', 'label' => 'Слайды',
                'instructions' => 'Слайды меняются сами; переключатели с подписями — над текстом. Порядок слайдов = порядок вкладок.',
                'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Добавить слайд', 'min' => 1,
                'collapsed' => 'field_eeg_home_hero_tab',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_hero_tab', 'name' => 'tab', 'label' => 'Подпись вкладки', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_hero_title', 'name' => 'title', 'label' => 'Заголовок', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'instructions' => 'Часть в *звёздочках* — вторая строка голубым.'],
                    ['key' => 'field_eeg_home_hero_bg', 'name' => 'bg', 'label' => 'Фото на фоне', 'type' => 'image', 'return_format' => 'id',
                     'preview_size' => 'medium', 'wrapper' => ['width' => 50], 'instructions' => 'Горизонтальное, от 1600 px по ширине. Описание (alt) — в медиатеке.'],
                    ['key' => 'field_eeg_home_hero_bg_under', 'name' => 'bg_under', 'label' => 'Подложка (если фото узкое)', 'type' => 'image', 'return_format' => 'id',
                     'preview_size' => 'medium', 'wrapper' => ['width' => 50],
                     'instructions' => 'Размытая копия фото. Если задана, фото на широком экране показывается целиком, а поля вокруг заполняет подложка.'],
                    ['key' => 'field_eeg_home_hero_text', 'name' => 'text', 'label' => 'Текст под заголовком', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
                    ['key' => 'field_eeg_home_hero_btn_text', 'name' => 'btn_text', 'label' => 'Кнопка: надпись', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_hero_btn_link', 'name' => 'btn_link', 'label' => 'Кнопка: куда ведёт', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'instructions' => $link],
                    ['key' => 'field_eeg_home_hero_aside_aria', 'name' => 'aside_aria', 'label' => 'Карточка справа: название для экранного диктора', 'type' => 'text',
                     'placeholder' => 'Коротко о направлении', 'instructions' => 'На экране не видно. Пусто — карточки нет.'],
                    ['key' => 'field_eeg_home_hero_aside_kicker', 'name' => 'aside_kicker', 'label' => 'Карточка: надпись сверху', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_hero_aside_big', 'name' => 'aside_big', 'label' => 'Карточка: крупно', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'instructions' => 'Часть в *звёздочках* — мельче, например: с 2015 *года*.'],
                    ['key' => 'field_eeg_home_hero_aside_fluid', 'name' => 'aside_big_fluid', 'label' => 'Крупная надпись уменьшается на ноутбуках', 'type' => 'true_false',
                     'ui' => 1, 'default_value' => 0, 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_hero_aside_sub', 'name' => 'aside_sub', 'label' => 'Карточка: подпись под крупным', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    [
                        'key' => 'field_eeg_home_hero_aside_facts', 'name' => 'aside_facts', 'label' => 'Карточка: две цифры', 'type' => 'repeater',
                        'layout' => 'table', 'button_label' => 'Добавить', 'max' => 2,
                        'sub_fields' => [
                            ['key' => 'field_eeg_home_hero_aside_fact_a', 'name' => 'a', 'label' => 'Крупно', 'type' => 'text'],
                            ['key' => 'field_eeg_home_hero_aside_fact_b', 'name' => 'b', 'label' => 'Подпись', 'type' => 'text'],
                        ],
                    ],
                    ['key' => 'field_eeg_home_hero_aside_link_text', 'name' => 'aside_link_text', 'label' => 'Карточка: ссылка, надпись', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_hero_aside_link', 'name' => 'aside_link', 'label' => 'Карточка: ссылка, куда ведёт', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'instructions' => $link],
                ],
            ],
            ['key' => 'field_eeg_home_hero_wa_text', 'name' => 'hero_wa_text', 'label' => 'Сообщение в WhatsApp с кнопки «Написать в WhatsApp»', 'type' => 'text',
             'instructions' => 'Подставляется в чат, когда посетитель нажимает кнопку на первом экране.'],

            /* ---------- продукция ---------- */
            ['key' => 'field_eeg_home_tab_catalog', 'label' => 'Продукция', 'type' => 'tab'],
            ['key' => 'field_eeg_home_catalog_title', 'name' => 'catalog_title', 'label' => 'Заголовок блока', 'type' => 'text'],
            [
                'key' => 'field_eeg_home_catalog_tiles', 'name' => 'catalog_tiles', 'label' => 'Плитки продукции', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить плитку',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_tile_title', 'name' => 'title', 'label' => 'Название', 'type' => 'text', 'instructions' => 'Оно же — описание фото.'],
                    ['key' => 'field_eeg_home_tile_link', 'name' => 'link', 'label' => 'Куда ведёт', 'type' => 'text', 'instructions' => $link],
                    ['key' => 'field_eeg_home_tile_image', 'name' => 'image', 'label' => 'Фото', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail'],
                ],
            ],
            [
                'key' => 'field_eeg_home_marquee', 'name' => 'marquee', 'label' => 'Бегущая строка под продукцией', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить пункт',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_marquee_text', 'name' => 'text', 'label' => 'Пункт', 'type' => 'text'],
                ],
            ],

            /* ---------- услуги ---------- */
            ['key' => 'field_eeg_home_tab_services', 'label' => 'Услуги', 'type' => 'tab'],
            ['key' => 'field_eeg_home_services_title', 'name' => 'services_title', 'label' => 'Заголовок блока', 'type' => 'text'],
            ['key' => 'field_eeg_home_services_lead', 'name' => 'services_lead', 'label' => 'Текст справа от заголовка', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
            [
                'key' => 'field_eeg_home_services', 'name' => 'services', 'label' => 'Услуги', 'type' => 'repeater',
                'instructions' => 'Плитки справа от фото: наведение меняет фото, клик ведёт на страницу услуги.',
                'layout' => 'block', 'button_label' => 'Добавить услугу', 'max' => 4, 'collapsed' => 'field_eeg_home_srv_title',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_srv_title', 'name' => 'title', 'label' => 'Название', 'type' => 'text', 'wrapper' => ['width' => 50]],
                    ['key' => 'field_eeg_home_srv_slug', 'name' => 'slug', 'label' => 'Код для переключателя', 'type' => 'text', 'wrapper' => ['width' => 50],
                     'instructions' => 'Латиницей, без пробелов, у каждой услуги свой. Например: servis.'],
                    ['key' => 'field_eeg_home_srv_link', 'name' => 'link', 'label' => 'Куда ведёт', 'type' => 'text', 'instructions' => $link],
                    ['key' => 'field_eeg_home_srv_text', 'name' => 'text', 'label' => 'Описание', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
                    ['key' => 'field_eeg_home_srv_image', 'name' => 'image', 'label' => 'Фото', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium',
                     'instructions' => 'Описание (alt) — в медиатеке.'],
                ],
            ],

            /* ---------- производство ---------- */
            ['key' => 'field_eeg_home_tab_production', 'label' => 'Производство', 'type' => 'tab'],
            ['key' => 'field_eeg_home_production_title', 'name' => 'production_title', 'label' => 'Заголовок блока', 'type' => 'text',
             'instructions' => 'Часть в *звёздочках* — вторая строка синим.'],
            ['key' => 'field_eeg_home_production_text', 'name' => 'production_text', 'label' => 'Текст', 'type' => 'textarea', 'rows' => 4, 'new_lines' => ''],
            ['key' => 'field_eeg_home_production_image', 'name' => 'production_image', 'label' => 'Фото', 'type' => 'image', 'return_format' => 'id',
             'preview_size' => 'medium', 'wrapper' => ['width' => 50]],
            ['key' => 'field_eeg_home_production_image_alt', 'name' => 'production_image_alt', 'label' => 'Описание фото (alt)', 'type' => 'text',
             'wrapper' => ['width' => 50], 'instructions' => 'Пусто — описание из медиатеки.'],
            ['key' => 'field_eeg_home_production_caption', 'name' => 'production_caption', 'label' => 'Плашка на фото: заголовок', 'type' => 'text', 'wrapper' => ['width' => 50]],
            ['key' => 'field_eeg_home_production_address', 'name' => 'production_address', 'label' => 'Плашка на фото: адрес', 'type' => 'text', 'wrapper' => ['width' => 50],
             'instructions' => 'Пусто — адрес из настроек сайта.'],
            [
                'key' => 'field_eeg_home_production_features', 'name' => 'production_features', 'label' => 'Преимущества', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить преимущество',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_feature_icon', 'name' => 'icon', 'label' => 'Иконка', 'type' => 'text',
                     'instructions' => 'Название иконки с lucide.dev/icons, например factory.', 'wrapper' => ['width' => 20]],
                    ['key' => 'field_eeg_home_feature_title', 'name' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
                    ['key' => 'field_eeg_home_feature_text', 'name' => 'text', 'label' => 'Текст', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
                ],
            ],

            /* ---------- этапы ---------- */
            ['key' => 'field_eeg_home_tab_steps', 'label' => 'Этапы', 'type' => 'tab'],
            ['key' => 'field_eeg_home_steps_title', 'name' => 'steps_title', 'label' => 'Заголовок блока', 'type' => 'text', 'instructions' => $mark],
            [
                'key' => 'field_eeg_home_steps', 'name' => 'steps', 'label' => 'Этапы', 'type' => 'repeater',
                'instructions' => 'Номера 01, 02… ставятся сами по порядку.',
                'layout' => 'table', 'button_label' => 'Добавить этап',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_step_title', 'name' => 'title', 'label' => 'Этап', 'type' => 'text'],
                    ['key' => 'field_eeg_home_step_text', 'name' => 'text', 'label' => 'Текст', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
                ],
            ],

            /* ---------- фото с производства и объектов ---------- */
            ['key' => 'field_eeg_home_tab_projects', 'label' => 'Фото', 'type' => 'tab'],
            ['key' => 'field_eeg_home_projects_title', 'name' => 'projects_title', 'label' => 'Заголовок блока', 'type' => 'text', 'instructions' => $mark],
            ['key' => 'field_eeg_home_projects_lead', 'name' => 'projects_lead', 'label' => 'Текст под заголовком', 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''],
            [
                'key' => 'field_eeg_home_projects_photos', 'name' => 'projects_photos', 'label' => 'Фото в слайд-шоу', 'type' => 'repeater',
                'instructions' => 'Фото меняются сами; клик по кадру ведёт в фотоальбом.',
                'layout' => 'table', 'button_label' => 'Добавить фото',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_photo_image', 'name' => 'image', 'label' => 'Фото', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail'],
                    ['key' => 'field_eeg_home_photo_caption', 'name' => 'caption', 'label' => 'Подпись на кадре', 'type' => 'text'],
                    ['key' => 'field_eeg_home_photo_alt', 'name' => 'alt', 'label' => 'Описание фото (alt)', 'type' => 'text', 'instructions' => 'Пусто — описание из медиатеки.'],
                ],
            ],

            /* ---------- вопросы ---------- */
            ['key' => 'field_eeg_home_tab_faq', 'label' => 'Вопросы', 'type' => 'tab'],
            ['key' => 'field_eeg_home_faq_title', 'name' => 'faq_title', 'label' => 'Заголовок блока', 'type' => 'text', 'instructions' => $mark],
            ['key' => 'field_eeg_home_faq_lead', 'name' => 'faq_lead', 'label' => 'Текст под заголовком', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '',
             'instructions' => 'Под ним — кнопка с основным телефоном из настроек сайта.'],
            [
                'key' => 'field_eeg_home_faq', 'name' => 'faq', 'label' => 'Вопросы и ответы', 'type' => 'repeater',
                'instructions' => 'Первый вопрос открыт сразу.',
                'layout' => 'block', 'button_label' => 'Добавить вопрос', 'collapsed' => 'field_eeg_home_faq_q',
                'sub_fields' => [
                    ['key' => 'field_eeg_home_faq_q', 'name' => 'q', 'label' => 'Вопрос', 'type' => 'text'],
                    ['key' => 'field_eeg_home_faq_a', 'name' => 'a', 'label' => 'Ответ', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '',
                     'instructions' => 'Пустая строка — новый абзац.'],
                ],
            ],

            /* ---------- заявка ---------- */
            ['key' => 'field_eeg_home_tab_request', 'label' => 'Заявка', 'type' => 'tab'],
            ['key' => 'field_eeg_home_request_title', 'name' => 'request_title', 'label' => 'Заголовок блока', 'type' => 'text', 'instructions' => $mark],
            ['key' => 'field_eeg_home_request_lead', 'name' => 'request_lead', 'label' => 'Текст под заголовком', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '',
             'instructions' => 'Ниже — телефоны и WhatsApp из настроек сайта, справа — форма заявки.'],
            ['key' => 'field_eeg_home_request_discount', 'name' => 'request_discount', 'label' => 'Плашка со скидкой', 'type' => 'text', 'instructions' => 'Пусто — плашки нет.'],
        ],
        'location' => [[['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']]],
    ]);
});
