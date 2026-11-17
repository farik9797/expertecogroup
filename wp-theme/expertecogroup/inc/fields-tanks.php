<?php
/**
 * Раздел «Ёмкости»: поля шаблонов «Ёмкости» (/catalog/emkosti/) и «Тип ёмкости» (/catalog/emkosti/<тип>/),
 * таблица типоразмеров и вспомогательные функции раздела (eeg_tanks_*).
 */
defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'        => 'group_eeg_tanks',
        'title'      => 'Ёмкости: вводная часть',
        'menu_order' => 10,
        'fields'     => [
            ['key' => 'field_eeg_tanks_intro', 'name' => 'tanks_intro', 'label' => 'Текст под шапкой', 'type' => 'textarea',
             'rows' => 8, 'new_lines' => '',
             'instructions' => 'Абзацы разделяйте пустой строкой. Карточки типов ниже собираются сами из дочерних страниц с шаблоном «Тип ёмкости».'],
            ['key' => 'field_eeg_tanks_intro_img', 'name' => 'tanks_intro_img', 'label' => 'Фото рядом с текстом', 'type' => 'image',
             'return_format' => 'id', 'preview_size' => 'medium'],
            ['key' => 'field_eeg_tanks_intro_alt', 'name' => 'tanks_intro_alt', 'label' => 'Описание фото (alt)', 'type' => 'text',
             'instructions' => 'Пусто — описание из медиатеки.'],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/tanks.php']]],
    ]);

    acf_add_local_field_group([
        'key'        => 'group_eeg_tank',
        'title'      => 'Тип ёмкости',
        'menu_order' => 10,
        'fields'     => [
            [
                'key' => 'field_eeg_tank_marks', 'name' => 'tank_marks', 'label' => 'Маркировки', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить маркировку',
                'instructions' => 'Показываются плашками под подзаголовком и строкой «Маркировка» в характеристиках.',
                'sub_fields' => [
                    ['key' => 'field_eeg_tank_mark_code', 'name' => 'code', 'label' => 'Код', 'type' => 'text', 'placeholder' => 'EEG-НР-(Н)-ЦГ'],
                    ['key' => 'field_eeg_tank_mark_what', 'name' => 'what', 'label' => 'Назначение', 'type' => 'text', 'placeholder' => 'вода'],
                ],
            ],
            ['key' => 'field_eeg_tank_gallery', 'name' => 'tank_gallery', 'label' => 'Фото', 'type' => 'gallery',
             'return_format' => 'id', 'preview_size' => 'medium', 'insert' => 'append',
             'instructions' => 'Первое фото — крупное на странице и на карточке типа в разделе «Ёмкости». Порядок меняется перетаскиванием.'],
            [
                'key' => 'field_eeg_tank_specs', 'name' => 'tank_specs', 'label' => 'Коротко о конструкции', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить строку',
                'instructions' => 'В строке «Маркировка» значение можно не заполнять — подставятся коды из поля «Маркировки».',
                'sub_fields' => [
                    ['key' => 'field_eeg_tank_spec_name', 'name' => 'name', 'label' => 'Название', 'type' => 'text'],
                    ['key' => 'field_eeg_tank_spec_value', 'name' => 'value', 'label' => 'Значение', 'type' => 'text'],
                ],
            ],
            ['key' => 'field_eeg_tank_text', 'name' => 'tank_text', 'label' => 'Описание', 'type' => 'textarea', 'rows' => 10, 'new_lines' => '',
             'instructions' => 'Абзацы разделяйте пустой строкой.'],
            [
                'key' => 'field_eeg_tank_uses', 'name' => 'tank_uses', 'label' => 'Где применяют', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить пункт',
                'sub_fields' => [
                    ['key' => 'field_eeg_tank_use_text', 'name' => 'text', 'label' => 'Пункт', 'type' => 'text'],
                ],
            ],
            ['key' => 'field_eeg_tank_shape', 'name' => 'tank_shape', 'label' => 'Форма корпуса', 'type' => 'select',
             'choices' => ['rect' => 'Прямоугольная', 'vert' => 'Вертикальная', 'ground' => 'Горизонтальная наземная', 'under' => 'Горизонтальная подземная'],
             'default_value' => 'under', 'allow_null' => 0,
             'instructions' => 'Задаёт форму в калькуляторе и столбец размеров в таблице: прямоугольная — длина × ширина × высота, '
                             . 'горизонтальная — длина × диаметр, вертикальная — «по запросу».'],
            ['key' => 'field_eeg_tank_code', 'name' => 'tank_code', 'label' => 'Код серии', 'type' => 'text', 'placeholder' => 'EEG-НР-(Н)-П',
             'instructions' => 'Из него и объёма складывается модель в таблице: EEG-НР-(Н)-П-2м3.'],
            [
                'key' => 'field_eeg_tank_sizes', 'name' => 'tank_sizes', 'label' => 'Типоразмеры', 'type' => 'repeater',
                'layout' => 'table', 'button_label' => 'Добавить типоразмер',
                'instructions' => 'Диапазон объёмов на карточке типа считается по этой таблице. Пустой размер — «уточняется», пустой вес — «—».',
                'sub_fields' => [
                    ['key' => 'field_eeg_tank_size_v', 'name' => 'v', 'label' => 'Объём, м³', 'type' => 'number', 'step' => 'any'],
                    ['key' => 'field_eeg_tank_size_l', 'name' => 'l', 'label' => 'Длина, мм', 'type' => 'number'],
                    ['key' => 'field_eeg_tank_size_w', 'name' => 'w', 'label' => 'Ширина или диаметр, мм', 'type' => 'number'],
                    ['key' => 'field_eeg_tank_size_h', 'name' => 'h', 'label' => 'Высота, мм', 'type' => 'number',
                     'instructions' => 'Только для прямоугольной.'],
                    ['key' => 'field_eeg_tank_size_weight', 'name' => 'weight', 'label' => 'Вес, кг', 'type' => 'number', 'step' => 'any'],
                ],
            ],
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/tank-type.php']]],
    ]);
});

/** Число из поля без лишних нулей: «5», «2.5»; пусто — ''. */
function eeg_tanks_num($x)
{
    $x = trim((string) $x);
    if ($x === '' || !is_numeric($x)) {
        return $x;
    }
    $f = (float) $x;
    return floor($f) == $f ? (string) (int) $f : rtrim(rtrim(number_format($f, 3, '.', ''), '0'), '.');
}

/** Маркировки страницы типа: [[код, назначение], ...]. */
function eeg_tanks_marks($post_id)
{
    $out = [];
    foreach ((array) eeg_field('tank_marks', [], $post_id) as $r) {
        $code = trim((string) ($r['code'] ?? ''));
        if ($code !== '') {
            $out[] = [$code, trim((string) ($r['what'] ?? ''))];
        }
    }
    return $out;
}

/** Строки таблицы типоразмеров с заполненным объёмом. */
function eeg_tanks_sizes($post_id)
{
    $out = [];
    foreach ((array) eeg_field('tank_sizes', [], $post_id) as $r) {
        if (trim((string) ($r['v'] ?? '')) !== '') {
            $out[] = array_map('eeg_tanks_num', [
                'v' => $r['v'] ?? '', 'l' => $r['l'] ?? '', 'w' => $r['w'] ?? '', 'h' => $r['h'] ?? '', 'weight' => $r['weight'] ?? '',
            ]);
        }
    }
    return $out;
}

/** Диапазон объёмов «5–50» по таблице; пусто, если таблицы нет. */
function eeg_tanks_range($rows)
{
    $vols = array_map('floatval', array_column($rows, 'v'));
    return $vols ? eeg_tanks_num(min($vols)) . '–' . eeg_tanks_num(max($vols)) : '';
}

/** Габариты строки таблицы (dims_text генератора). */
function eeg_tanks_dims($r, $shape)
{
    if ($shape === 'rect') {
        return $r['l'] !== '' && $r['w'] !== '' && $r['h'] !== '' ? "{$r['l']} × {$r['w']} × {$r['h']} мм" : 'уточняется';
    }
    if ($shape === 'vert') {
        // размеров вертикальных в каталоге нет; если клиент впишет диаметр и высоту — покажем их
        return $r['w'] !== '' && $r['h'] !== '' ? "Ø {$r['w']} × {$r['h']} мм" : 'по запросу';
    }
    return $r['l'] !== '' && $r['w'] !== '' ? "{$r['l']} × Ø {$r['w']} мм" : 'уточняется';
}

/** Таблица типоразмеров (size_table генератора). */
function eeg_tanks_size_table($rows, $shape, $code)
{
    $head = ['rect' => 'Длина × ширина × высота', 'vert' => 'Габариты'][$shape] ?? 'Длина × диаметр';
    $tr = '';
    foreach ($rows as $r) {
        $weight = $r['weight'] !== '' && (float) $r['weight'] ? $r['weight'] . ' кг' : '—';
        $model = $code !== '' ? $code . '-' . $r['v'] . 'м3' : '';
        $tr .= '<tr><td><b>' . esc_html($r['v']) . ' м³</b></td><td>' . esc_html(eeg_tanks_dims($r, $shape)) . '</td><td>'
            . esc_html($weight) . '</td><td>' . esc_html($model) . '</td></tr>';
    }
    return '<div class="size-table-box">
  <div class="size-table-wrap">
    <table class="size-table">
      <thead><tr><th>Объём</th><th>' . esc_html($head) . '</th><th>Вес</th><th>Модель</th></tr></thead>
      <tbody>' . $tr . '</tbody>
    </table>
  </div>
  <p class="size-table-swipe"><i data-lucide="move-horizontal"></i>Листайте таблицу вбок</p>
</div>
<p class="mt-3 text-sm font-semibold text-muted">Размеры по данным каталога. Нужен другой объём или нестандартная форма — изготовим по вашим чертежам.</p>';
}
