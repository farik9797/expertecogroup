<?php
/**
 * Заявки с сайта: форма в попапе и на главной отправляется сюда (admin-ajax, действие eeg_request).
 * Каждая заявка сохраняется в админке («Заявки») и уходит письмом на адрес из «Настроек сайта».
 */
defined('ABSPATH') || exit;

/** Скрытые поля формы: ловушка для ботов и адрес страницы. */
function eeg_form_hidden()
{
    echo '<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">';
}

add_action('init', function () {
    register_post_type('eeg_request', [
        'label'           => 'Заявки',
        'labels'          => ['name' => 'Заявки', 'singular_name' => 'Заявка', 'menu_name' => 'Заявки с сайта'],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 4,
        'supports'        => ['title', 'editor'],
        'capability_type' => 'post',
        'capabilities'    => ['create_posts' => 'do_not_allow'],
        'map_meta_cap'    => true,
    ]);
});

add_action('wp_ajax_eeg_request', 'eeg_handle_request');
add_action('wp_ajax_nopriv_eeg_request', 'eeg_handle_request');

function eeg_handle_request()
{
    if (!check_ajax_referer('eeg_request', 'nonce', false)) {
        wp_send_json_error(['msg' => 'Страница устарела — обновите её и отправьте заявку ещё раз.'], 403);
    }
    $f = fn($k, $max = 500) => mb_substr(trim(sanitize_textarea_field(wp_unslash($_POST[$k] ?? ''))), 0, $max);
    if ($f('website') !== '') {
        wp_send_json_success(); // бот заполнил скрытое поле — делаем вид, что всё хорошо
    }
    $phone = $f('phone', 40);
    if (strlen(preg_replace('~\D~', '', $phone)) < 10) {
        wp_send_json_error(['msg' => 'Укажите номер, чтобы мы могли перезвонить'], 400);
    }
    $data = [
        'Назначение'  => $f('purpose', 40),
        'Объём/расход' => $f('volume', 100),
        'Имя'         => $f('name', 100),
        'Телефон'     => $phone,
        'Комментарий' => $f('comment', 2000),
        'Страница'    => esc_url_raw(wp_unslash($_POST['page'] ?? '')),
    ];
    $lines = [];
    foreach ($data as $k => $v) {
        if ($v !== '') {
            $lines[] = "$k: $v";
        }
    }
    $body  = implode("\n", $lines);
    $title = 'Заявка: ' . $phone . ($data['Назначение'] ? ' — ' . $data['Назначение'] : '');

    wp_insert_post(['post_type' => 'eeg_request', 'post_status' => 'private', 'post_title' => $title, 'post_content' => $body]);

    $to = eeg_opt('request_email', '') ?: eeg_email();
    $to = array_filter(array_map('trim', explode(',', $to)), 'is_email');
    $sent = $to ? wp_mail($to, 'Заявка с сайта expertecogroup.kz', $body) : false;

    wp_send_json_success(['mailed' => (bool) $sent]);
}
