"""Собирает многостраничный сайт из content/catalog.json.

Общие части (шапка, меню, попап заявки, кнопка «наверх», подвал) берутся из site/index.html,
чтобы главная и внутренние страницы не разъезжались.

Запуск: python3 tools/build_site.py
"""
import json
import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SITE = ROOT / "site"
DATA = json.loads((ROOT / "content/catalog.json").read_text())
OTHER = json.loads((ROOT / "content/catalog-other.json").read_text())
MAP_PROJECTS = json.loads((ROOT / "content/projects.json").read_text())["projects"]
INDEX = (SITE / "index.html").read_text()

PHONE_MAIN = "+7 777 484-18-22"
WA = "https://wa.me/77712282203"


# ---------------------------------------------------------------- общие блоки
def block(pattern):
    m = re.search(pattern, INDEX, re.S)
    if not m:
        raise SystemExit(f"не найден блок: {pattern[:40]}")
    return m.group(0)


HEADER = block(r'<header id="nav".*?</header>')
MOBILE_MENU = block(r'<div id="mobile-menu".*?\n</div>')
MODAL = block(r'<dialog id="request-modal".*?</dialog>')
TOTOP = block(r'<button type="button" class="to-top".*?</button>')
QUICKBAR = block(r'<div class="quickbar.*?\n</div>')
FOOTER = block(r'<footer id="contacts".*?</footer>')
ASSET_V = re.search(r'site\.css\?v=(\d+)', INDEX).group(1)


def relink(html, base):
    """Делает ссылки и пути к файлам относительными для вложенной страницы."""
    if not base:
        return html
    html = re.sub(r'(href|src)="(?!https?:|#|tel:|mailto:|//)([^"]+)"', lambda m: f'{m.group(1)}="{base}{m.group(2)}"', html)
    html = re.sub(r'(imagesrcset|srcset)="([^"]+)"',
                  lambda m: f'{m.group(1)}="' + re.sub(r'(^|,\s*)(?!https?:)', lambda x: x.group(1) + base, m.group(2)) + '"', html)
    html = html.replace('href="#', f'href="{base}index.html#')  # якоря главной на внутренних страницах
    return html


def layout(*, path, title, description, body, base, head_extra="", body_extra=""):
    head_chrome = relink(HEADER + "\n" + MOBILE_MENU, base)
    tail_chrome = relink(MODAL + "\n" + TOTOP + "\n" + QUICKBAR + "\n" + FOOTER, base)
    theme = block(r'<style type="text/tailwindcss">.*?</style>')
    return f"""<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Концепт: не индексировать, чтобы не конкурировать с настоящим сайтом клиента -->
  <meta name="robots" content="noindex, nofollow">
  <title>{title}</title>
  <meta name="description" content="{description}">
  <link rel="icon" href="{base}assets/img/logo-eeg.webp">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,500;1,600&family=Nunito:wght@400;600;700;800&family=Unbounded:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  {theme}
  <link rel="stylesheet" href="{base}assets/css/site.css?v={ASSET_V}">
  {head_extra}
</head>
<body class="font-sans text-ink antialiased page-inner">
<a href="#main" class="skip-link">К содержимому</a>

{head_chrome}

<main id="main">
{body}
</main>

{tail_chrome}

<script src="https://cdn.jsdelivr.net/npm/lucide@1.47.0/dist/umd/lucide.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.15.0/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.15.0/ScrollTrigger.min.js" defer></script>
<script src="{base}assets/js/main.js?v={ASSET_V}" defer></script>
{body_extra}
</body>
</html>
"""


def write(path, html):
    out = SITE / path
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(html)
    return path


def crumbs(items, base):
    """items: [(название, ссылка или None)]"""
    parts = [f'<a href="{base}index.html">Главная</a>']
    for name, href in items:
        parts.append(f'<a href="{href}">{name}</a>' if href else f"<span>{name}</span>")
    return f'<nav class="crumbs" aria-label="Хлебные крошки">{"".join(parts)}</nav>'


def page_head(*, title, lead, base, crumb_items, actions=True, facts=None):
    cta = f"""
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="{base}index.html#request" class="btn btn-primary">Рассчитать стоимость <i data-lucide="arrow-right" class="size-5"></i></a>
        <a href="{WA}" class="btn btn-ghost" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/0e8a4a" alt="" class="size-5" width="20" height="20">WhatsApp</a>
      </div>""" if actions else ""
    facts = facts or [
        ("с 2015 года", "собственное производство"),
        ("до 100 м³", "объём ёмкостей"),
        ("12 месяцев", "гарантия на изделия"),
        ("РК и СНГ", "доставка и монтаж"),
    ]
    facts_html = "".join(f"<li><b>{a}</b><span>{b}</span></li>" for a, b in facts)
    return f"""<section class="page-hero">
  <div class="mx-auto max-w-7xl px-4 md:px-6">
    {crumbs(crumb_items, base)}
    <div class="mt-7 max-w-3xl">
      <h1 class="section-title">{title}</h1>
      <p class="mt-5 text-[17px] leading-relaxed text-muted">{lead}</p>{cta}
    </div>
    <ul class="page-hero__facts">{facts_html}</ul>
  </div>
</section>"""


def dims_text(row, shape):
    if shape == "rect":
        if row["L"] and row["W"] and row["H"]:
            return f'{row["L"]} × {row["W"]} × {row["H"]} мм'
        return "уточняется"
    if shape == "vert":
        return "по запросу"
    if row["L"] and row["W"]:
        return f'{row["L"]} × Ø {row["W"]} мм'
    return "уточняется"


def size_table(series, current=None, base="", cat_slug=None):
    head = "Длина × ширина × высота" if series["shape"] == "rect" else "Длина × диаметр"
    rows = []
    for r in series["table"]:
        link = ""
        if cat_slug and r["v"] in series["models"]:
            href = f'{base}catalog/{cat_slug}/{series["slug"]}-{r["v"]}m3/'
            link = f' <a class="size-table__link" href="{href}">страница модели</a>'
        weight = f'{r["weight"]} кг' if r["weight"] else "—"
        cls = ' class="is-current"' if current == r["v"] else ""
        rows.append(f'<tr{cls}><td><b>{r["v"]} м³</b></td><td>{dims_text(r, series["shape"])}</td><td>{weight}</td><td>{series["code"]}-{r["v"]}м3{link}</td></tr>')
    return f"""<div class="size-table-wrap">
  <table class="size-table">
    <thead><tr><th>Объём</th><th>{head}</th><th>Вес</th><th>Модель</th></tr></thead>
    <tbody>{"".join(rows)}</tbody>
  </table>
</div>
<p class="mt-3 text-sm font-semibold text-muted">Размеры по данным каталога. Нужен другой объём или нестандартная форма — изготовим по вашим чертежам.</p>"""


def gallery(series, base, alt):
    imgs = series["images"]
    if not imgs:
        return ""
    main = imgs[0]
    thumbs = "".join(
        f'<button type="button" class="gal__thumb" data-full="{base}assets/img/catalog/{n}.webp">'
        f'<img src="{base}assets/img/catalog/{n}-sm.webp" alt="" loading="lazy"></button>'
        for n in imgs
    )
    return f"""<figure class="gal">
  <img class="gal__main" src="{base}assets/img/catalog/{main}.webp" alt="{alt}" width="1100" height="760">
  <div class="gal__thumbs">{thumbs}</div>
</figure>"""


def cta_band(base, text="Рассчитаем стоимость под ваш объект"):
    return f"""<section class="px-4 py-16 md:px-6 md:py-20">
  <div class="cta-band mx-auto max-w-7xl">
    <div class="cta-band__grid">
      <div>
        <h2 class="cta-band__title">{text}</h2>
        <p class="mt-4 max-w-xl leading-relaxed text-white/75">Опишите задачу — подберём конструкцию и комплектацию, посчитаем срок и стоимость. Чертёж или опросный лист можно прислать на почту.</p>
        <div class="cta-band__phones">
          <a href="tel:+77774841822"><i data-lucide="phone"></i><span><b>+7 777 484-18-22</b><small>Отдел продаж, Сергей</small></span></a>
          <a href="tel:+77086265749"><i data-lucide="phone"></i><span><b>+7 708 626-57-49</b><small>Отдел продаж, Артём</small></span></a>
        </div>
      </div>
      <div class="cta-band__actions">
        <a href="{base}index.html#request" class="btn btn-primary w-full justify-center">Оставить заявку <i data-lucide="arrow-right" class="size-5"></i></a>
        <a href="{WA}" class="btn btn-glass w-full justify-center" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/ffffff" alt="" class="size-5" width="20" height="20">Написать в WhatsApp</a>
        <p class="cta-band__hours"><i data-lucide="clock"></i>Отвечаем пн–пт, 08:00–18:00</p>
      </div>
    </div>
  </div>
</section>"""


# ---------------------------------------------------------------- страницы
def product_page(cat, series, v, base="../../../"):
    row = next(r for r in series["table"] if r["v"] == v)
    title = f'{series["title"]} {v} м³'
    code = f'{series["code"]}-{v}м3'
    specs = [
        ("Объём", f"{v} м³"),
        ("Модель", code),
        ("Установка", series["install"].capitalize()),
        ("Материал", "Полипропилен"),
    ]
    if series["shape"] == "rect" and row["L"]:
        specs += [("Длина", f'{row["L"]} мм'), ("Ширина", f'{row["W"]} мм'), ("Высота", f'{row["H"]} мм')]
    elif series["shape"] == "cyl" and row["L"]:
        specs += [("Длина", f'{row["L"]} мм'), ("Диаметр", f'{row["W"]} мм')]
    if row["weight"]:
        specs.append(("Вес", f'≈ {row["weight"]} кг'))
    if row["thickness"]:
        specs.append(("Толщина стенки", f'{row["thickness"]} мм'))
    specs.append(("Гарантия", "12 месяцев"))
    spec_rows = "".join(f"<div><dt>{k}</dt><dd>{v_}</dd></div>" for k, v_ in specs)
    uses = "".join(f"<li>{u}</li>" for u in series["uses"])
    text = "".join(f'<p class="mt-4">{t}</p>' for t in series["text"])
    others = [m for m in series["models"] if m != v][:6]
    others_html = "".join(
        f'<a class="chip chip--link" href="{base}catalog/{cat["slug"]}/{series["slug"]}-{m}m3/">{m} м³</a>' for m in others
    )
    body = f"""{page_head(
        title=title,
        lead=series["lead"],
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), (cat["title"], f'{base}catalog/{cat["slug"]}/'), (f"{v} м³", None)],
        actions=False,
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">
    {gallery(series, base, title)}
    <div>
      <p class="font-display text-sm font-medium tracking-wide text-brand">{code}</p>
      <dl class="spec mt-5">{spec_rows}</dl>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="{base}index.html#request" class="btn btn-primary">Рассчитать стоимость <i data-lucide="arrow-right" class="size-5"></i></a>
        <a href="{WA}" class="btn btn-ghost" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/0e8a4a" alt="" class="size-5" width="20" height="20">WhatsApp</a>
      </div>
      <p class="mt-4 text-sm font-semibold text-muted">Цена зависит от толщины листа, патрубков и комплектации — считаем под объект.</p>
      {f'<div class="mt-8"><p class="text-sm font-bold text-muted">Другие объёмы этого ряда</p><div class="mt-3 flex flex-wrap gap-2">{others_html}</div></div>' if others_html else ''}
    </div>
  </div>
</section>

<section class="px-4 py-14 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div>
      <h2 class="section-title">Описание</h2>
      <div class="mt-2 text-[17px] leading-relaxed text-muted">{text}</div>
    </div>
    <div>
      <h2 class="section-title">Применение</h2>
      <ul class="uses mt-6">{uses}</ul>
      <div class="note mt-8">
        <i data-lucide="truck"></i>
        <p>Доставляем по Казахстану и странам СНГ, помогаем с погрузкой, монтажом и пусконаладкой.</p>
      </div>
    </div>
  </div>
</section>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Типоразмеры ряда</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Весь ряд «{series["short"]}» — выберите ближайший объём или закажите нестандартный.</p>
    <div class="mt-8">{size_table(series, current=v, base=base, cat_slug=cat["slug"])}</div>
  </div>
</section>

{cta_band(base)}"""
    return layout(
        path="",
        title=f'{title} — Expert ECO Group',
        description=f'{title} из полипропилена, модель {code}. {series["lead"]} Производство в Каскелене, доставка по Казахстану и СНГ.',
        body=body,
        base=base,
    )


def category_page(cat, base="../../"):
    blocks = []
    for s in cat["series"]:
        cards = []
        for m in s["models"]:
            row = next(r for r in s["table"] if r["v"] == m)
            cards.append(f"""<a class="prod-card" href="{base}catalog/{cat["slug"]}/{s["slug"]}-{m}m3/">
  <div class="prod-card__ph"><img src="{base}assets/img/catalog/{s["images"][0]}-sm.webp" alt="{s["title"]} {m} м³" loading="lazy"></div>
  <div class="prod-card__body">
    <p class="prod-card__vol">{m} <span>м³</span></p>
    <h3>{s["short"]}</h3>
    <p class="prod-card__dims">{dims_text(row, s["shape"])}</p>
    <span class="prod-card__more">Подробнее <i data-lucide="arrow-right" class="size-4"></i></span>
  </div>
</a>""")
        blocks.append(f"""<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div class="max-w-2xl">
        <h2 class="section-title">{s["title"]}</h2>
        <p class="mt-4 text-[17px] leading-relaxed text-muted">{s["lead"]}</p>
      </div>
      <p class="chip shrink-0">{len(s["table"])} типоразмеров</p>
    </div>
    <div class="prod-grid mt-8">{"".join(cards)}</div>
    <details class="table-details mt-8">
      <summary>Все размеры ряда «{s["short"]}»<i data-lucide="plus"></i></summary>
      <div class="mt-5">{size_table(s, base=base, cat_slug=cat["slug"])}</div>
    </details>
  </div>
</section>""")

    intro = "".join(f'<p class="mt-4">{p}</p>' for p in cat["intro"])
    body = f"""{page_head(
        title=cat["title"],
        lead=cat["lead"],
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), (cat["title"], None)],
    )}

<section class="px-4 pb-6 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="text-[17px] leading-relaxed text-muted">{intro}</div>
    <img class="w-full rounded-[28px] object-cover" src="{base}assets/img/{cat["hero"]}" alt="{cat["title"]} Expert ECO Group" loading="lazy">
  </div>
</section>

{"".join(blocks)}

<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="more-eq">
      <div class="more-eq__head">
        <div>
          <h2 class="font-display text-[1.45rem] font-medium leading-tight text-navy md:text-[1.7rem]">Не нашли нужный объём?</h2>
          <p class="mt-3 max-w-xl leading-relaxed text-muted">Посчитайте габариты в калькуляторе или отправьте чертёж — изготовим ёмкость под ваши размеры.</p>
        </div>
        <a href="{base}index.html#sizes" class="btn btn-ghost shrink-0">Открыть калькулятор <i data-lucide="arrow-right" class="size-5"></i></a>
      </div>
    </div>
  </div>
</section>

{cta_band(base)}"""
    return layout(
        path="",
        title=f'{cat["title"]} из полипропилена — Expert ECO Group',
        description=cat["lead"],
        base=base,
        body=body,
    )


def other_category_page(cat, base="../../"):
    cards = []
    for p in cat["products"]:
        specs = "".join(f'<li><span>{sp["k"]}</span><b>{sp["v"]}</b></li>' for sp in p["specs"])
        img = (f'<div class="item-card__ph"><img src="{base}assets/img/catalog/other/{p["image"]}" alt="{p["name"]}" loading="lazy"></div>'
               if p["image"] else '<div class="item-card__ph item-card__ph--empty"><i data-lucide="image"></i></div>')
        cards.append(f"""<article class="item-card">
  {img}
  <div class="item-card__body">
    <h3>{p["name"]}</h3>
    {f'<ul class="item-card__specs">{specs}</ul>' if specs else ''}
    <button type="button" class="btn btn-ghost btn-sm mt-auto" data-request="{p["name"]}">Запросить <i data-lucide="arrow-right" class="size-4"></i></button>
  </div>
</article>""")
    body = f"""{page_head(
        title=cat["title"],
        lead=cat["lead"],
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), (cat["title"], None)],
    )}

<section class="px-4 pb-10 md:px-6">
  <div class="mx-auto max-w-7xl">
    <p class="text-sm font-bold text-muted">{len(cat["products"])} позиций в разделе</p>
    <div class="item-grid mt-6">{"".join(cards)}</div>
    <p class="mt-6 text-sm font-semibold text-muted">Характеристики уточняем под задачу. Цену считаем после заявки — она зависит от комплектации и производительности.</p>
  </div>
</section>

{cta_band(base, "Подберём оборудование под вашу задачу")}"""
    return layout(path="", title=f'{cat["title"]} — Expert ECO Group',
                  description=cat["lead"][:180], body=body, base=base)


def tile(title, href, img, count, base):
    return f"""<a class="prod-tile" href="{href}">
  <span class="prod-tile__head">
    <span>
      <span class="prod-tile__title">{title}</span>
      <span class="prod-tile__count">{count}</span>
    </span>
    <i data-lucide="arrow-up-right"></i>
  </span>
  <span class="prod-tile__ph"><img src="{img}" alt="{title}" loading="lazy"></span>
</a>"""


def catalog_page(base="../"):
    # ёмкости — три категории с отдельными страницами товаров
    tanks = "".join(
        tile(c["title"], f'{base}catalog/{c["slug"]}/', f'{base}assets/img/{c["hero"]}',
             f'{c["products"]} моделей', base)
        for c in DATA["categories"])

    groups = {}
    for c in OTHER:
        groups.setdefault(c["group"], []).append(c)
    anchors = {"Очистные сооружения": "ochistnye", "Оборудование": "oborudovanie", "Услуги": "uslugi"}
    groups_html = ""
    for group, cats in groups.items():
        tiles = "".join(
            tile(c["title"], f'{base}catalog/{c["slug"]}/',
                 f'{base}assets/img/catalog/other/{c["products"][0]["image"]}' if c["products"][0]["image"]
                 else f'{base}assets/img/cat-water.webp',
                 f'{len(c["products"])} позиций', base)
            for c in cats)
        groups_html += f"""<section class="px-4 py-8 md:px-6" id="{anchors.get(group, '')}">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">{group}</h2>
    <div class="prod-tiles mt-8">{tiles}</div>
  </div>
</section>"""

    body = f"""{page_head(
        title="Оборудование из полипропилена",
        lead="Ёмкости и резервуары для воды, противопожарного запаса и химических реагентов, а также оборудование для очистки воды и стоков. Всё производим сами в Каскелене.",
        base=base,
        crumb_items=[("Каталог", None)],
        facts=[("2–100 м³", "объём ёмкостей"), ("15 разделов", "в каталоге"),
               ("190 позиций", "оборудования"), ("РК и СНГ", "доставка и монтаж")],
    )}

<section id="emkosti" class="px-4 py-8 md:px-6">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Ёмкости и резервуары</h2>
    <div class="prod-tiles mt-8">{tanks}</div>
  </div>
</section>

{groups_html}

{cta_band(base)}"""
    return layout(path="", title="Каталог оборудования из полипропилена — Expert ECO Group",
                  description="Каталог Expert ECO Group: ёмкости для воды, противопожарные резервуары, химические ёмкости, КНС, очистные сооружения и компрессоры. Производство в Каскелене.",
                  body=body, base=base)


EQUIPMENT = [
    ("Очистка стоков", ["Канализационные насосные станции (КНС)", "Локальные очистные сооружения (ЛОС)", "Септики", "Жироуловители", "Пескоуловители", "Песко-нефтеуловители", "Шкафы управления для КНС"]),
    ("Аэрация и комплектующие", ["Компрессоры HIBLOW", "Компрессоры AirMac и Jecod", "Системы аэрации воды", "Запчасти и мембраны для компрессоров", "Погружные насосы"]),
    ("Водоподготовка и биология", ["Фильтроэлементы «Технофильтр»", "Станции биоочистки Юнилос и Астра", "Биопрепараты для септиков"]),
]
SERVICES = [
    ("Проектирование и чертежи", "ruler", "Разрабатываем проект и рабочие чертежи под объект или изготавливаем по вашим."),
    ("Шефмонтаж и пусконаладка", "hard-hat", "Монтаж на объекте, запуск оборудования и обучение персонала заказчика."),
    ("Футеровка резервуаров", "layers", "Облицовка полипропиленом бетонных и стальных резервуаров, ремонт и реставрация."),
    ("Ремонт оборудования", "wrench", "Ремонт полипропиленовых ёмкостей и очистных, ремонт компрессоров."),
    ("Сервисное обслуживание", "badge-check", "Регламентное обслуживание очистных сооружений и насосных станций."),
    ("Подбор и консультация", "messages-square", "Считаем производительность и подбираем оборудование под задачу и бюджет."),
]


def equipment_page(base="../"):
    groups = "".join(
        f"""<div class="eq-group">
  <h2 class="font-display text-xl font-medium text-navy">{title}</h2>
  <ul class="more-eq__list mt-5 border-0 pt-0">{"".join(f"<li>{i}</li>" for i in items)}</ul>
</div>""" for title, items in EQUIPMENT)
    services = "".join(
        f"""<li class="feature"><span class="feature__icon"><i data-lucide="{icon}"></i></span><div><h3>{title}</h3><p>{text}</p></div></li>"""
        for title, icon, text in SERVICES)
    body = f"""{page_head(
        title="Другое оборудование под заказ",
        lead="Кроме ёмкостей производим оборудование для очистки воды и стоков, поставляем компрессоры и комплектующие, выполняем монтаж, футеровку и ремонт.",
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), ("Другое оборудование", None)],
    )}

<section class="px-4 pb-8 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-8 md:grid-cols-3">{groups}</div>
</section>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Услуги</h2>
    <ul class="mt-10 grid gap-x-8 gap-y-8 md:grid-cols-2 lg:grid-cols-3">{services}</ul>
  </div>
</section>

{cta_band(base, "Подберём оборудование под ваш объект")}"""
    return layout(path="", title="Оборудование и услуги — Expert ECO Group",
                  description="КНС, локальные очистные сооружения, септики, жироуловители, пескоуловители, компрессоры HIBLOW и услуги: проектирование, шефмонтаж, футеровка и ремонт.",
                  body=body, base=base)


PROJECTS = [
    ("proj-loading", "Отгрузка вертикальных резервуаров", "Погрузка на трал краном, крепление и отправка заказчику."),
    ("proj-trench", "Монтаж подземного резервуара", "Установка в котлован на песчаное основание перед обратной засыпкой."),
    ("proj-horizontal", "Горизонтальная наземная ёмкость", "Резервуар на опорах, подготовлен к подключению обвязки."),
    ("proj-rect", "Прямоугольная ёмкость с патрубками", "Ёмкость в металлическом каркасе с выведенными патрубками."),
    ("plant-hall", "Вертикальные ёмкости в насосной", "Смонтированная пара ёмкостей с насосной группой и щитом управления."),
    ("proj-vertical", "Вертикальные ёмкости на трале", "Отгрузка двух вертикальных резервуаров с производства."),
]


def projects_page(base="../"):
    items = "".join(f"""<a class="proj-card" href="{base}fotoalbom/">
  <img src="{base}assets/img/{img}.webp" alt="{title}" loading="lazy">
  <span class="proj-card__cap"><b>{title}</b><span>{text}</span></span>
</a>""" for img, title, text in PROJECTS)
    body = f"""{page_head(
        title="С производства на объект",
        lead="Фотографии с нашего производства и объектов заказчиков: изготовление, отгрузка и монтаж ёмкостей из полипропилена.",
        base=base,
        crumb_items=[("Проекты", None)],
        actions=False,
    )}

<section class="px-4 pb-8 md:px-6">
  <div class="mx-auto flex max-w-7xl flex-wrap gap-3">
    <a href="{base}karta-proektov/" class="btn btn-primary">Карта проектов <i data-lucide="map-pin" class="size-5"></i></a>
    <a href="{base}fotoalbom/" class="btn btn-ghost">Фотоальбом <i data-lucide="images" class="size-5"></i></a>
  </div>
</section>

<section class="px-4 pb-10 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-6 md:grid-cols-2 lg:grid-cols-3">{items}</div>
  <p class="mx-auto mt-8 max-w-7xl text-sm font-semibold text-muted">Объекты, города и объёмы добавим после согласования с заказчиками.</p>
</section>

{cta_band(base)}"""
    return layout(path="", title="Проекты и объекты — Expert ECO Group",
                  description="Фотографии производства и объектов: изготовление, отгрузка и монтаж ёмкостей из полипропилена по Казахстану.",
                  body=body, base=base)


def about_page(base="../"):
    timeline = [
        ("2014", "Начали с монтажа и обслуживания очистных сооружений."),
        ("2015", "Открыли собственное производство — не устраивало качество оборудования на рынке."),
        ("Сегодня", "Проектируем, производим, поставляем и обслуживаем оборудование из полипропилена по Казахстану и СНГ."),
    ]
    steps = "".join(f"""<li class="step step--light"><span class="step__num">{year}</span><p>{text}</p></li>""" for year, text in timeline)
    departments = ["Отдел продаж", "Проектирование", "Снабжение", "Производство", "Бухгалтерия", "Маркетинг"]
    body = f"""{page_head(
        title="Производим оборудование из полипропилена с 2015 года",
        lead="ТОО «Expert ECO Group» — производство ёмкостей, резервуаров и оборудования для водоочистки и водоотведения. Собственный цех в Каскелене, работа по Казахстану и странам СНГ.",
        base=base,
        crumb_items=[("О компании", None)],
    )}

<section class="px-4 pb-12 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
    <img class="w-full rounded-[28px] object-cover" src="{base}assets/img/plant-hall.webp" alt="Производство Expert ECO Group" loading="lazy">
    <div>
      <h2 class="section-title">Как мы пришли к производству</h2>
      <ol class="steps steps--vertical mt-8">{steps}</ol>
      <div class="note mt-8">
        <i data-lucide="factory"></i>
        <p>Производство и склад: Каскелен, ул. Курылысшы, 1а/1. Отделы: {", ".join(departments).lower()}.</p>
      </div>
    </div>
  </div>
</section>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Что мы делаем</h2>
    <div class="mt-10 grid gap-8 md:grid-cols-3">
      <div class="eq-group"><h3 class="font-display text-lg font-medium text-navy">Производство</h3><p class="mt-3 leading-relaxed text-muted">Ёмкости и резервуары до 100 м³, КНС, локальные очистные сооружения, септики, жиро-, песко- и песконефтеуловители.</p></div>
      <div class="eq-group"><h3 class="font-display text-lg font-medium text-navy">Услуги</h3><p class="mt-3 leading-relaxed text-muted">Проектирование и чертежи, шефмонтаж и пусконаладка, футеровка бетонных и стальных резервуаров, ремонт оборудования и компрессоров.</p></div>
      <div class="eq-group"><h3 class="font-display text-lg font-medium text-navy">Поставка</h3><p class="mt-3 leading-relaxed text-muted">Компрессоры HIBLOW, AirMac и Jecod, фильтроэлементы «Технофильтр», станции биоочистки Юнилос и Астра, биопрепараты.</p></div>
    </div>
  </div>
</section>

{cta_band(base)}"""
    return layout(path="", title="О компании — Expert ECO Group",
                  description="ТОО «Expert ECO Group»: с 2014 года монтаж и обслуживание очистных, с 2015 — собственное производство ёмкостей из полипропилена в Каскелене.",
                  body=body, base=base)


def contacts_page(base="../"):
    phones = [
        ("+7 777 484-18-22", "tel:+77774841822", "Отдел продаж, Сергей"),
        ("+7 708 626-57-49", "tel:+77086265749", "Отдел продаж, Артём"),
        ("+7 771 228-22-03", "tel:+77712282203", "Генеральный директор"),
        ("+7 771 228-22-08", "tel:+77712282208", "Заместитель директора"),
    ]
    rows = "".join(f"""<li><a href="{href}" class="contact-row contact-row--light"><i data-lucide="phone"></i><span><b>{num}</b><small>{who}</small></span></a></li>""" for num, href, who in phones)
    body = f"""{page_head(
        title="Свяжитесь с нами",
        lead="Отвечаем в рабочее время: пн–пт, 08:00–18:00. Расчёт по заявке готовим после уточнения задачи.",
        base=base,
        crumb_items=[("Контакты", None)],
        actions=False,
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
    <div>
      <ul class="space-y-4 text-[17px]">{rows}</ul>
      <ul class="mt-8 space-y-4 text-[17px]">
        <li><a href="{WA}" class="contact-row contact-row--light" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/1e65ff" alt="" width="20" height="20"><span><b>WhatsApp</b><small>+7 771 228-22-03</small></span></a></li>
        <li><a href="mailto:info_eeg@mail.ru" class="contact-row contact-row--light"><i data-lucide="mail"></i><span><b>info_eeg@mail.ru</b><small>Почта для заявок и документов</small></span></a></li>
        <li><a href="https://yandex.kz/maps/?text=%D0%9A%D0%B0%D1%81%D0%BA%D0%B5%D0%BB%D0%B5%D0%BD%2C%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%9A%D1%83%D1%80%D1%8B%D0%BB%D1%8B%D1%81%D1%88%D1%8B%2C%201%D0%B0%2F1" class="contact-row contact-row--light" target="_blank" rel="noopener"><i data-lucide="map-pin"></i><span><b>Каскелен, ул. Курылысшы, 1а/1</b><small>Производство и склад, Алматинская область</small></span></a></li>
        <li><span class="contact-row contact-row--light"><i data-lucide="clock"></i><span><b>Пн–пт, 08:00–18:00</b><small>Суббота и воскресенье — выходной</small></span></span></li>
      </ul>
      <div class="note mt-8"><i data-lucide="file-text"></i><p>ТОО «Expert ECO Group» (Эксперт ЭКО Групп). Работаем по Казахстану и странам СНГ.</p></div>
    </div>
    <div class="rounded-[28px] bg-mist p-6 md:p-8">
      <h2 class="font-display text-xl font-medium text-navy">Оставьте заявку</h2>
      <p class="mt-3 leading-relaxed text-muted">Опишите задачу — перезвоним и подберём решение. Можно приложить чертёж или опросный лист по почте.</p>
      <a href="{base}index.html#request" class="btn btn-primary mt-6">Открыть форму <i data-lucide="arrow-right" class="size-5"></i></a>
    </div>
  </div>
</section>"""
    return layout(path="", title="Контакты — Expert ECO Group",
                  description="Телефоны отдела продаж, WhatsApp, почта и адрес производства Expert ECO Group в Каскелене.",
                  body=body, base=base)



ALBUMS = [
    ("proizvodstvo", "Производство", "Цех в Каскелене: раскрой листа, сварка, сборка ёмкостей и очистных.",
     "plant-hall.webp", ["plant-hall.webp", "proj-rect.webp"]),
    ("emkosti", "Ёмкости и резервуары", "Готовые ёмкости для воды, химии и противопожарного запаса.",
     "cat-water.webp", ["cat-water.webp", "proj-horizontal.webp", "catalog/gorizontalnaya-nazemnaya-1.webp",
                        "catalog/vertikalnaya-nazemnaya-1.webp", "catalog/pryamougolnaya-nazemnaya-1.webp"]),
    ("montazh", "Монтаж на объектах", "Установка подземных и наземных резервуаров, обвязка и пусконаладка.",
     "cat-fire.webp", ["cat-fire.webp", "proj-trench.webp", "catalog/gorizontalnaya-podzemnaya-2.webp"]),
    ("otgruzka", "Отгрузка и доставка", "Погрузка краном, крепление на трале и доставка по Казахстану и СНГ.",
     "proj-loading.webp", ["proj-loading.webp", "proj-vertical.webp", "hero-1200.webp"]),
    ("kns", "КНС и очистные сооружения", "Канализационные насосные станции, ЛОС, жиро- и пескоуловители.", "prod/kns.webp", []),
    ("himiya", "Химическое оборудование", "Ёмкости для кислот, щелочей и реагентов, гальванические ванны.", "cat-chem.webp", []),
    ("septiki", "Септики", "Септики собственного производства, Юнилос и Евролос.", "prod/septiki.webp", []),
    ("futerovka", "Футеровка и ремонт", "Облицовка бетонных и стальных резервуаров, ремонт оборудования.", "prod/los.webp", []),
]


def album_page(slug, title, lead, photos, base="../../"):
    items = "".join(
        f'<figure class="album-photo"><img src="{base}assets/img/{ph}" alt="{title}" loading="lazy"></figure>'
        for ph in photos)
    body = f"""{page_head(
        title=title,
        lead=lead,
        base=base,
        crumb_items=[("Фотоальбом", f"{base}fotoalbom/"), (title, None)],
        actions=False,
        facts=[("Фото", "с производства и объектов"), ("Каскелен", "собственный цех"),
               ("РК и СНГ", "география работ"), ("2015", "год запуска производства")],
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="album-grid">{items}</div>
    <p class="mt-6 text-sm font-semibold text-muted">Фотоальбом пополняется — клиент передаёт съёмку с производства и объектов.</p>
  </div>
</section>

{cta_band(base)}"""
    return layout(path="", title=f"{title} — фотоальбом Expert ECO Group", description=lead, body=body, base=base)


def albums_page(base="../"):
    cards = []
    for slug, title, lead, cover, photos in ALBUMS:
        has = bool(photos)
        badge = f'<span class="album-card__count">{len(photos)} фото</span>' if has else '<span class="album-card__count album-card__count--soon">скоро</span>'
        inner = f"""<span class="album-card__ph"><img src="{base}assets/img/{cover}" alt="{title}" loading="lazy">{badge}</span>
    <span class="album-card__body">
      <span class="album-card__title">{title}</span>
      <span class="album-card__lead">{lead}</span>
    </span>"""
        cards.append(f'<a class="album-card" href="{base}fotoalbom/{slug}/">{inner}</a>' if has
                     else f'<span class="album-card album-card--soon">{inner}</span>')
    body = f"""{page_head(
        title="Фотоальбом",
        lead="Съёмка с производства в Каскелене и с объектов заказчиков: изготовление, отгрузка, монтаж и готовые системы. Альбомы пополняются.",
        base=base,
        crumb_items=[("Фотоальбом", None)],
        facts=[("Производство", "Каскелен"), ("Объекты", "по Казахстану и СНГ"),
               ("с 2015 года", "собственный цех"), ("Фото", "пополняем"),],
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="album-cards">{"".join(cards)}</div>
  </div>
</section>

{cta_band(base)}"""
    return layout(path="", title="Фотоальбом — Expert ECO Group",
                  description="Фотографии производства ёмкостей из полипропилена в Каскелене и объектов заказчиков по Казахстану.",
                  body=body, base=base)


def map_page(base="../"):
    cards = []
    for p in MAP_PROJECTS:
        done = "".join(f"<li>{d}</li>" for d in p["done"])
        meta = " · ".join(x for x in [p["kind"], p["year"]] if x)
        cards.append(f"""<article class="pin-card{'' if p['confirmed'] else ' pin-card--draft'}" data-pin="{p['id']}" id="pin-{p['id']}">
  <button type="button" class="pin-card__head">
    <span>
      <span class="pin-card__city">{p["city"]}{f' · {p["region"]}' if p["region"] else ''}</span>
      <span class="pin-card__title">{p["title"]}</span>
    </span>
    <i data-lucide="plus"></i>
  </button>
  <div class="pin-card__body">
    {f'<p class="pin-card__meta">{meta}</p>' if meta else ''}
    <p>{p["text"]}</p>
    {f'<ul class="uses mt-4">{done}</ul>' if done else ''}
  </div>
</article>""")
    body = f"""{page_head(
        title="Карта проектов",
        lead="Объекты, куда поставляли и монтировали оборудование. Нажмите точку на карте или карточку в списке — раскроется описание работ.",
        base=base,
        crumb_items=[("Проекты", f"{base}proekty/"), ("Карта проектов", None)],
        actions=False,
        facts=[("РК и СНГ", "география поставок"), ("Каскелен", "собственное производство"),
               ("с 2015 года", "на рынке"), ("Данные", "пополняем")],
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="map-layout">
      <div id="kz-map" class="kz-map" data-projects='{json.dumps(MAP_PROJECTS, ensure_ascii=False)}'></div>
      <div class="pin-list">{"".join(cards)}</div>
    </div>
    <p class="mt-5 text-sm font-semibold text-muted">Адреса, объёмы и фотографии объектов уточняем у клиента — карточки со статусом «уточняется» заменим реальными проектами.</p>
  </div>
</section>

{cta_band(base)}"""
    head = ('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" '
            'integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw==" '
            'crossorigin="anonymous" referrerpolicy="no-referrer">')
    body_extra = ('<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" '
                  'integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" '
                  'crossorigin="anonymous" referrerpolicy="no-referrer"></script>')
    return layout(path="", title="Карта проектов — Expert ECO Group",
                  description="Интерактивная карта объектов Expert ECO Group по Казахстану: где поставляли и монтировали ёмкости и очистные сооружения.",
                  body=body, base=base, head_extra=head, body_extra=body_extra)


def main():
    made = []
    made.append(write("catalog/index.html", catalog_page()))
    for cat in DATA["categories"]:
        made.append(write(f'catalog/{cat["slug"]}/index.html', category_page(cat)))
        for s in cat["series"]:
            for m in s["models"]:
                made.append(write(f'catalog/{cat["slug"]}/{s["slug"]}-{m}m3/index.html', product_page(cat, s, m)))
    for cat in OTHER:
        made.append(write(f'catalog/{cat["slug"]}/index.html', other_category_page(cat)))
    made.append(write("oborudovanie/index.html", equipment_page()))
    made.append(write("proekty/index.html", projects_page()))
    made.append(write("fotoalbom/index.html", albums_page()))
    for slug, title, lead, cover, photos in ALBUMS:
        if photos:
            made.append(write(f"fotoalbom/{slug}/index.html", album_page(slug, title, lead, photos)))
    made.append(write("karta-proektov/index.html", map_page()))
    made.append(write("o-kompanii/index.html", about_page()))
    made.append(write("kontakty/index.html", contacts_page()))
    print(f"страниц собрано: {len(made)}")
    for p in made[:6]:
        print(" ", p)
    print("  ...")


if __name__ == "__main__":
    main()
