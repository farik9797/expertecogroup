<?php
/**
 * Общие функции темы: ссылки, поля, картинки и повторяющиеся блоки макета
 * (шапка страницы, хлебные крошки, полоса «Рассчитаем стоимость», галерея).
 */
defined('ABSPATH') || exit;

/**
 * Нижний регистр для русского текста. На хостинге может не быть расширения mbstring
 * (expertecogroup.kz, 17.11.2026), поэтому без него переводим кириллицу таблицей.
 */
function eeg_lower($s)
{
    $s = (string) $s;
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }
    static $map = null;
    if ($map === null) {
        $up = preg_split('//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯӘҒҚҢӨҰҮҺІ', -1, PREG_SPLIT_NO_EMPTY);
        $lo = preg_split('//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяәғқңөұүһі', -1, PREG_SPLIT_NO_EMPTY);
        $map = array_combine($up, $lo);
    }
    return strtr(strtolower($s), $map);
}

/* ---------- ссылки ---------- */

/** Ссылка на страницу сайта по пути из макета: eeg_url('catalog/kns/'), eeg_url('proekty/#fotoalbom'). */
function eeg_url($path = '')
{
    if (preg_match('~^(https?:|tel:|mailto:|#)~', $path)) {
        return $path;
    }
    return home_url('/' . ltrim($path, '/'));
}

/** Файл темы из assets/: eeg_asset('img/logo-eeg.webp'). */
function eeg_asset($path)
{
    return get_theme_file_uri('assets/' . ltrim($path, '/'));
}

/** Ссылка «на заявку»: на главной — якорь формы, на остальных страницах main.js открывает попап. */
function eeg_req()
{
    return is_front_page() ? '#request' : home_url('/#request');
}

/** Якорь главной: на главной — просто #id, на внутренних — главная + #id. */
function eeg_home_anchor($id)
{
    return is_front_page() ? '#' . $id : home_url('/#' . $id);
}

/* ---------- поля ---------- */

/** Значение поля страницы; пустое — значение по умолчанию. */
function eeg_field($name, $default = '', $post_id = null)
{
    if (function_exists('get_field')) {
        $v = get_field($name, $post_id ?: false);
        if ($v !== null && $v !== '' && $v !== false && $v !== []) {
            return $v;
        }
    }
    return $default;
}

/** Общая настройка сайта (страница «Настройки сайта»). */
function eeg_opt($name, $default = '')
{
    return eeg_field($name, $default, 'option');
}

/** Список строк из многострочного поля или повторителя [{text}]. */
function eeg_list($value)
{
    if (is_array($value)) {
        $out = [];
        foreach ($value as $row) {
            $t = is_array($row) ? reset($row) : $row;
            if (trim((string) $t) !== '') {
                $out[] = trim($t);
            }
        }
        return $out;
    }
    return array_values(array_filter(array_map('trim', preg_split('~\R~u', (string) $value))));
}

/** Абзацы из textarea: пустая строка — новый абзац. */
function eeg_paragraphs($text)
{
    return array_values(array_filter(array_map('trim', preg_split('~\R\s*\R~u', (string) $text))));
}

/* ---------- контакты ---------- */

/** Телефоны отдела продаж: [['number' => '+7 777 484-18-22', 'label' => 'Отдел продаж, Сергей'], ...] */
function eeg_phones()
{
    $rows = eeg_opt('phones', []);
    if (!$rows) {
        $rows = [
            ['number' => '+7 777 484-18-22', 'label' => 'Отдел продаж, Сергей'],
            ['number' => '+7 708 626-57-49', 'label' => 'Отдел продаж, Артём'],
        ];
    }
    return array_values(array_filter($rows, fn($r) => !empty($r['number'])));
}

/** Основной телефон — первый в списке. */
function eeg_phone_main()
{
    $p = eeg_phones();
    return $p ? $p[0]['number'] : '';
}

function eeg_tel($number)
{
    $d = preg_replace('~\D~', '', (string) $number);
    if (strlen($d) === 11 && $d[0] === '8') {
        $d = '7' . substr($d, 1);
    }
    return 'tel:+' . $d;
}

/** Ссылка на WhatsApp по номеру из настроек. */
function eeg_wa()
{
    $n = preg_replace('~\D~', '', (string) eeg_opt('whatsapp', '77712282203'));
    return 'https://wa.me/' . $n;
}

function eeg_email()
{
    return eeg_opt('email', 'info_eeg@mail.ru');
}

function eeg_hours()
{
    return eeg_opt('hours', 'Пн–пт, 08:00–18:00');
}

/* ---------- картинки ---------- */

/** URL картинки: ID вложения, массив ACF или готовый URL. */
function eeg_img_url($img, $size = 'full')
{
    if (!$img) {
        return '';
    }
    if (is_numeric($img)) {
        $u = wp_get_attachment_image_url((int) $img, $size);
        return $u ?: '';
    }
    if (is_array($img)) {
        if (!empty($img['ID'])) {
            return eeg_img_url($img['ID'], $size);
        }
        return $img['url'] ?? '';
    }
    return (string) $img;
}

/** [ширина, высота] оригинала картинки. */
function eeg_img_size($img)
{
    $id = is_array($img) ? ($img['ID'] ?? 0) : (is_numeric($img) ? (int) $img : 0);
    if ($id) {
        $m = wp_get_attachment_metadata($id);
        if (!empty($m['width'])) {
            return [(int) $m['width'], (int) $m['height']];
        }
    }
    return [0, 0];
}

/** srcset вложения (WordPress сам перечисляет нарезанные размеры). */
function eeg_img_srcset($img, $size = 'full')
{
    $id = is_array($img) ? ($img['ID'] ?? 0) : (is_numeric($img) ? (int) $img : 0);
    return $id ? (string) wp_get_attachment_image_srcset($id, $size) : '';
}

/** alt вложения или запасной текст. */
function eeg_img_alt($img, $fallback = '')
{
    $id = is_array($img) ? ($img['ID'] ?? 0) : (is_numeric($img) ? (int) $img : 0);
    $alt = $id ? trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) : '';
    return $alt !== '' ? $alt : $fallback;
}

/**
 * ' is-contain', если рамка 4:3 срезала бы слишком много кадра (как fit_class в генераторе макета):
 * вертикальные и очень вытянутые фото показываем целиком на подложке.
 */
function eeg_fit_class($img, $box_ar = 4 / 3, $limit = 0.72)
{
    [$w, $h] = eeg_img_size($img);
    if (!$w || !$h) {
        return '';
    }
    $ar = $w / $h;
    $keep = min($ar, $box_ar) / max($ar, $box_ar);
    return $keep < $limit ? ' is-contain' : '';
}

/** Миниатюра (-sm в макете) — средний размер WordPress. */
function eeg_img_small($img)
{
    return eeg_img_url($img, 'medium_large') ?: eeg_img_url($img);
}

/* ---------- блоки макета ---------- */

/** Хлебные крошки: [['Каталог', url], ['КНС', null]]. Без аргумента — по родителям страницы. */
function eeg_crumbs($items = null)
{
    if ($items === null) {
        $items = [];
        $id = get_queried_object_id();
        foreach (array_reverse(get_post_ancestors($id)) as $a) {
            $items[] = [eeg_crumb_name($a), get_permalink($a)];
        }
        $items[] = [eeg_crumb_name($id), null];
    }
    $parts = ['<a href="' . esc_url(home_url('/')) . '">Главная</a>'];
    foreach ($items as [$name, $href]) {
        $parts[] = $href ? '<a href="' . esc_url($href) . '">' . esc_html($name) . '</a>' : '<span>' . esc_html($name) . '</span>';
    }
    return '<nav class="crumbs" aria-label="Хлебные крошки">' . implode('', $parts) . '</nav>';
}

/** Короткое название страницы для крошек и карточек (поле crumb), иначе заголовок. */
function eeg_crumb_name($post_id)
{
    return eeg_field('crumb', '', $post_id) ?: get_the_title($post_id);
}

/** Кнопка WhatsApp. $style: ghost (на светлом), glass (на тёмном). */
function eeg_wa_btn($style = 'ghost', $label = 'WhatsApp', $extra = '')
{
    $color = $style === 'ghost' ? '0e8a4a' : 'ffffff';
    return '<a href="' . esc_url(eeg_wa()) . '" class="btn btn-' . $style . ($extra ? ' ' . $extra : '') . '" target="_blank" rel="noopener">'
        . '<img src="https://cdn.simpleicons.org/whatsapp/' . $color . '" alt="" class="size-5" width="20" height="20">' . esc_html($label) . '</a>';
}

/**
 * Шапка внутренней страницы (page_head в генераторе макета).
 * $a: title, lead, crumbs (null — по родителям), actions (кнопки), facts ([[a, b], ...] или пусто), extra (HTML после лида).
 * По умолчанию заголовок, лид и плашки берутся из общих полей страницы.
 */
function eeg_page_head($a = [])
{
    $id = get_queried_object_id();
    $a += [
        'title'   => eeg_field('head_title', get_the_title($id), $id),
        'lead'    => eeg_field('head_lead', '', $id),
        'crumbs'  => null,
        'actions' => true,
        'facts'   => eeg_field('facts', [], $id),
        'extra'   => '',
    ];
    $cta = '';
    if ($a['actions']) {
        $cta = '
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="' . esc_url(eeg_req()) . '" class="btn btn-primary">Рассчитать стоимость <i data-lucide="arrow-right" class="size-5"></i></a>
        ' . eeg_wa_btn('ghost') . '
      </div>';
    }
    $facts = '';
    foreach ((array) $a['facts'] as $f) {
        $x = $f['a'] ?? ($f[0] ?? '');
        $y = $f['b'] ?? ($f[1] ?? '');
        if ($x !== '' || $y !== '') {
            $facts .= '<li><b>' . esc_html($x) . '</b><span>' . esc_html($y) . '</span></li>';
        }
    }
    $facts_block = $facts ? "\n    <ul class=\"page-hero__facts reveal\" data-anim=\"fade-up\" data-delay=\"300\">$facts</ul>" : '';
    $lead = $a['lead'] !== '' ? '<p class="mt-5 text-[17px] leading-relaxed text-muted">' . wp_kses_post($a['lead']) . '</p>' : '';
    return '<section class="page-hero">
  <div class="mx-auto max-w-7xl px-4 md:px-6">
    ' . eeg_crumbs($a['crumbs']) . '
    <div class="reveal mt-7 max-w-3xl" data-anim="fade-right" data-delay="150">
      <h1 class="section-title">' . wp_kses_post($a['title']) . '</h1>
      ' . $lead . $a['extra'] . $cta . '
    </div>' . $facts_block . '
  </div>
</section>';
}

/** Полоса «Рассчитаем стоимость под ваш объект» с телефонами отдела продаж. */
function eeg_cta_band($text = 'Рассчитаем стоимость под ваш объект')
{
    $phones = '';
    foreach (array_slice(eeg_phones(), 0, 2) as $p) {
        $phones .= '<a href="' . esc_attr(eeg_tel($p['number'])) . '"><i data-lucide="phone"></i><span><b>' . esc_html($p['number']) . '</b><small>' . esc_html($p['label'] ?? '') . '</small></span></a>';
    }
    return '<section class="px-4 py-16 md:px-6 md:py-20">
  <div class="cta-band mx-auto max-w-7xl">
    <div class="cta-band__grid">
      <div class="reveal" data-anim="fade-right" data-delay="150">
        <h2 class="cta-band__title">' . esc_html($text) . '</h2>
        <p class="mt-4 max-w-xl leading-relaxed text-white/75">Опишите задачу — подберём конструкцию и комплектацию, посчитаем срок и стоимость. Чертёж или опросный лист можно прислать на почту.</p>
        <div class="cta-band__phones">' . $phones . '</div>
      </div>
      <div class="cta-band__actions reveal" data-anim="fade-left" data-delay="300">
        <a href="' . esc_url(eeg_req()) . '" class="btn btn-primary w-full justify-center">Оставить заявку <i data-lucide="arrow-right" class="size-5"></i></a>
        ' . eeg_wa_btn('glass', 'Написать в WhatsApp', 'w-full justify-center') . '
        <p class="cta-band__hours"><i data-lucide="clock"></i>Отвечаем ' . esc_html(eeg_lower(eeg_hours())) . '</p>
      </div>
    </div>
  </div>
</section>';
}

/**
 * Галерея: крупный кадр + полоса миниатюр (gallery в генераторе).
 * $images — массив ID вложений (поле «Галерея» отдаёт массивы или ID — подходят оба).
 */
function eeg_gallery($images, $alt)
{
    $images = array_values(array_filter((array) $images));
    if (!$images) {
        return '';
    }
    $thumbs = '';
    foreach ($images as $im) {
        $thumbs .= '<button type="button" class="gal__thumb" data-full="' . esc_url(eeg_img_url($im)) . '">'
            . '<img src="' . esc_url(eeg_img_small($im)) . '" alt="" loading="lazy"></button>';
    }
    $row = count($images) > 1 ? "\n  <div class=\"gal__thumbs\">$thumbs</div>" : '';
    return '<figure class="gal">
  <img class="gal__main" src="' . esc_url(eeg_img_url($images[0])) . '" alt="' . esc_attr($alt) . '" width="1100" height="760">' . $row . '
</figure>';
}

/** SEO-описание текущей страницы. */
function eeg_seo_description()
{
    $id = get_queried_object_id();
    return $id ? eeg_field('seo_description', '', $id) : '';
}
