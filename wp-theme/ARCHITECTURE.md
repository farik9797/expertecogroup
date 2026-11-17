# Тема WordPress «Expert ECO Group» — как устроена

Перенос статического макета `site/` (генератор `tools/build_site.py`, главная — `site/index.html`) в тему WordPress.
**Разметка страниц должна совпадать с макетом 1:1** (те же теги, классы, тексты): CSS и JS берутся из макета без изменений
(`wp-theme/expertecogroup/assets/` — копия `site/assets/`). Весь контент — в полях Secure Custom Fields (это ACF Pro,
API тот же: `get_field`, `have_rows`, `acf_add_local_field_group`, повторители, галереи, гибкие поля).

## Файлы

```
wp-theme/expertecogroup/
  functions.php            подключает inc/*, стили/скрипты (как в макете), SEO title/description, редиректы
  header.php / footer.php  шапка, меню, подвал, попап заявки, quickbar — ГОТОВО, не трогать
  index.php                запасной шаблон
  inc/helpers.php          общие функции (см. ниже) — ГОТОВО, не трогать
  inc/fields-common.php    общие поля: настройки сайта, шапка страницы, SEO, перенаправление — не трогать
  inc/importer.php         импорт import/content.json — не трогать
  inc/request-form.php     приём заявок (admin-ajax) — не трогать
  inc/fields-<раздел>.php  ПОЛЯ ШАБЛОНОВ РАЗДЕЛА (пишет исполнитель раздела)
  page-templates/<x>.php   ШАБЛОНЫ СТРАНИЦ (пишет исполнитель раздела), заголовок `Template Name: …`
  parts/<раздел>-*.php     части шаблонов, если нужны
tools/wp_export/<раздел>.py  выгрузка контента раздела: функция pages() (пишет исполнитель раздела)
tools/export_wp.py           собирает import/content.json из всех модулей
tools/compare_wp.py          сравнивает <main> страницы WP с макетом
```

Файлы других разделов не редактировать. Нужна общая функция — заведите свою с префиксом раздела в своём
`inc/fields-<раздел>.php` (например `eeg_tanks_size_table()`) и упомяните в отчёте.

## Шаблон страницы — каркас

```php
<?php
/**
 * Template Name: Раздел каталога
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
echo eeg_page_head(['crumbs' => [['Каталог', eeg_url('catalog/')], [eeg_crumb_name($id), null]]]);
?>
<section class="px-4 pb-16 md:px-6"> … разметка из макета, значения из полей … </section>
<?php
echo eeg_cta_band('Подберём оборудование под вашу задачу');
get_footer();
```

`header.php` уже открывает `<main id="main">`, `footer.php` его закрывает. Класс `page-inner` и `data-purpose`
у `<body>` ставит `header.php` (поле `purpose` в общей группе «Шапка страницы»).

## Общие поля (уже есть у каждой страницы, кроме главной)

| поле | тип | что это |
|---|---|---|
| `head_title` | text | H1 (пусто — заголовок страницы WP) |
| `crumb` | text | короткое название для крошек и карточек |
| `head_lead` | textarea | лид под H1 (HTML не экранируется через wp_kses_post — допускается `&nbsp;`, `<b>`) |
| `facts` | repeater `a`, `b` | плашки под шапкой (до 4) |
| `purpose` | select | Очистные / КНС / Ёмкости / Другое — назначение в форме заявки на странице |
| `seo_title`, `seo_description` | text, textarea | `<title>` и meta description |

`eeg_page_head()` без аргументов берёт всё это сам. Аргументы перекрывают: `title`, `lead`, `crumbs`
(`[['Каталог', url], ['Название', null]]`; по умолчанию — по родителям страницы), `actions` (кнопки «Рассчитать стоимость» +
WhatsApp, по умолчанию true), `facts`, `extra` (HTML после лида).

## Функции из inc/helpers.php

| функция | что делает |
|---|---|
| `eeg_url('catalog/kns/')` | абсолютная ссылка на страницу сайта (`#…`, `tel:`, `https:` — как есть) |
| `eeg_asset('img/x.webp')` | файл из assets темы |
| `eeg_req()` | ссылка «на заявку» (макет: `index.html#request` / `#request`) |
| `eeg_home_anchor('catalog')` | якорь главной |
| `eeg_field($name, $default, $post_id)` / `eeg_opt($name)` | поле страницы / настройка сайта |
| `eeg_list($v)` | строки из повторителя `[{text}]` или многострочного текста |
| `eeg_paragraphs($text)` | абзацы textarea (пустая строка — новый абзац) |
| `eeg_phones()`, `eeg_phone_main()`, `eeg_tel($n)`, `eeg_wa()`, `eeg_email()`, `eeg_hours()` | контакты из настроек |
| `eeg_wa_btn('ghost'|'glass', $label, $extra_class)` | кнопка WhatsApp как в макете |
| `eeg_img_url($img, $size)` | URL картинки (ID / массив ACF / URL). Размер `full` — основной кадр |
| `eeg_img_small($img)` | уменьшенная копия (в макете `-sm`) |
| `eeg_img_srcset($img)`, `eeg_img_size($img)` → `[w, h]`, `eeg_img_alt($img, $fallback)` | |
| `eeg_fit_class($img)` | `' is-contain'` для вытянутых кадров в рамке 4:3 (как `fit_class` генератора) |
| `eeg_crumbs($items)`, `eeg_crumb_name($post_id)` | хлебные крошки |
| `eeg_page_head($args)` | шапка внутренней страницы (`page_head` генератора) |
| `eeg_cta_band($text)` | синяя полоса «Рассчитаем стоимость…» (`cta_band`) |
| `eeg_gallery($images, $alt)` | галерея: крупный кадр + миниатюры (`gallery`) |

Ссылки в разметке — всегда через `eeg_url()` / `eeg_req()`; экранирование при выводе — `esc_html`, `esc_attr`, `esc_url`.
Иконки — как в макете: `<i data-lucide="имя"></i>` (Lucide подключён). SVG-иконки руками не рисовать.

## Поля шаблона — inc/fields-<раздел>.php

```php
add_action('acf/init', function () {
    acf_add_local_field_group([
        'key' => 'group_eeg_section', 'title' => 'Раздел каталога',
        'fields' => [ … ключи вида field_eeg_section_<имя> — уникальные на весь сайт … ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/section.php']]],
    ]);
});
```

- Типы: заголовки — text; абзацы — textarea (абзацы через пустую строку) или повторитель `{text}`; карточки и списки — repeater;
  фото — image (`return_format` => `id`); набор фото — gallery (`return_format` => `id`); таблицы — repeater со столбцами.
- Ярлыки и подсказки полей — по-русски, понятные клиенту (он будет править сам).
- **Имена полей не должны совпадать с общими** (`head_title`, `crumb`, `head_lead`, `facts`, `purpose`, `seo_title`,
  `seo_description`, `redirect_to`) и должны быть уникальны в пределах шаблона.
- Шаблон должен работать, если поле пустое: не выводить пустые блоки, не падать.

## Выгрузка — tools/wp_export/<раздел>.py

```python
from wp_export.common import B, img, img_name, facts   # B — модуль tools/build_site.py со всеми данными макета

def pages():
    return [{
        "path": "catalog/kns",            # адрес как в макете, без слешей по краям; родитель создаётся отдельно
        "title": "Канализационные насосные станции",   # заголовок страницы WP (H1, если head_title пуст)
        "template": "page-templates/section.php",
        "menu_order": 10,                 # порядок среди соседних страниц (карточки, списки)
        "fields": {"head_lead": "…", "facts": facts([...]), "purpose": "КНС",
                   "seo_title": "…", "seo_description": "…",
                   "cover": img("catalog/other/x.webp", "alt"), "gallery": [img_name("photo/y")], …},
    }]
```

- Данные брать из `B` (функции и константы генератора), а не перепечатывать руками — чтобы тексты совпали.
- Картинка: `img("путь/в/site/assets/img.webp", "alt")` — проверяет, что файл есть. В медиатеку грузится основной кадр;
  уменьшенные копии (`-sm`, `-md`, `-800`) WordPress нарежет сам — в выгрузку их не включать.
- `seo_title` / `seo_description` — из `title=` / `description=` вызова `layout()` в генераторе.

## Проверка

```bash
python3 tools/export_wp.py --only <раздел> --out /tmp/eeg-<раздел>.json
wp --path=wp-local eval 'print_r(eeg_import_file("/tmp/eeg-<раздел>.json"));'
python3 tools/compare_wp.py catalog/kns/ catalog/los/
for f in wp-theme/expertecogroup/page-templates/*.php wp-theme/expertecogroup/inc/*.php; do php -l "$f"; done
tail -20 wp-local/wp-content/debug.log   # PHP-ошибки темы (строки про wp-cli/Colors.php — шум, игнорировать)
```

Локальный WordPress: http://localhost:8890 (SQLite, `wp --path=wp-local …`), макет: http://localhost:5178.
Если WP-CLI пишет «database is locked» — повторить через несколько секунд (с базой работают несколько исполнителей).
`compare_wp.py` должен показывать «совпадает» и для текста, и для разметки. Допустимые отличия — только те, что
объяснены (например, srcset/размеры картинок) — перечислить в отчёте.
