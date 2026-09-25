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
SECTION_PHOTOS = json.loads((ROOT / "content/section-photos.json").read_text())
MAP_PROJECTS = json.loads((ROOT / "content/projects.json").read_text())["projects"]
INDEX = (SITE / "index.html").read_text()
CALC_HTML = (ROOT / "content/calc-block.html").read_text()  # калькулятор переехал с главной

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
    head = {"rect": "Длина × ширина × высота", "vert": "Габариты"}.get(series["shape"], "Длина × диаметр")
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
# ---------------------------------------------------------------- ёмкости
# Клиент: «описание резервуара, что он подходит и для воды и для химии; разбить типы на отдельные страницы».
# Поэтому вместо категорий вода/пожарные/химия — четыре конструктивных типа, назначение указано в описании.
def series_of(cat_slug, series_slug):
    cat = next(c for c in DATA["categories"] if c["slug"] == cat_slug)
    return next(s for s in cat["series"] if s["slug"] == series_slug)


PURPOSE_TEXT = ("Корпус один и тот же — для питьевой и технической воды, неприкосновенного противопожарного запаса, "
                "кислот, щелочей и реагентов. Отличаются толщина листа, комплектация патрубков и исполнение люков, "
                "а для агрессивных сред подбираем марку полипропилена под состав и температуру.")

TANK_TYPES = [
    {
        "slug": "gorizontalnaya-nazemnaya",
        "src": ("voda", "gorizontalnaya-nazemnaya"),
        "title": "Горизонтальная наземная ёмкость",
        "short": "Горизонтальная наземная",
        "lead": "Цилиндрический резервуар на опорах-сёдлах: встаёт на подготовленную площадку без котлована. Типовые объёмы 5–50 м³.",
        "extra_images": ["pozharnaya-nazemnaya-1", "pozharnaya-nazemnaya-2"],
        "extra_uses": ["Противопожарный запас на площадке", "Технологические ёмкости на производстве"],
        "marks": "EEG-НР-(Н)-ЦГ (вода), EEG-ПР-(Н)-ЦГ (противопожарный запас)",
    },
    {
        "slug": "gorizontalnaya-podzemnaya",
        "src": ("voda", "gorizontalnaya-podzemnaya"),
        "title": "Горизонтальная подземная ёмкость",
        "short": "Горизонтальная подземная",
        "lead": "Подземный резервуар с частыми рёбрами жёсткости и горловиной под люк: не занимает место на участке и не промерзает. Типовые объёмы 5–100 м³.",
        "extra_images": ["pozharnaya-podzemnaya-1", "pozharnaya-podzemnaya-2"],
        "extra_uses": ["Неприкосновенный противопожарный запас", "Склады, логистические комплексы, посёлки"],
        "marks": "EEG-НР-(П)-ЦГ (вода), EEG-ПР-(П)-ЦГ (противопожарный запас)",
    },
    {
        "slug": "vertikalnaya-nazemnaya",
        "src": ("voda", "vertikalnaya-nazemnaya"),
        "title": "Вертикальная наземная ёмкость",
        "short": "Вертикальная наземная",
        "lead": "Вертикальный цилиндр с плоским дном: занимает минимум площади, удобен в насосных и помещениях с высоким потолком. Типовые объёмы 5–30 м³.",
        "extra_images": [],
        "extra_uses": ["Реагентное хозяйство", "Растворные узлы"],
        "marks": "EEG-НР-(Н)-ЦВ (вода), EEG-ХР-(Н)-ЦВ (химические реагенты)",
    },
    {
        "slug": "pryamougolnaya-nazemnaya",
        "src": ("voda", "pryamougolnaya-nazemnaya"),
        "title": "Прямоугольная наземная ёмкость",
        "short": "Прямоугольная наземная",
        "lead": "Резервуар из листового полипропилена в металлическом каркасе: компактно встаёт вдоль стены и в технические помещения. Типовые объёмы 2–6 м³.",
        "extra_images": ["himicheskaya-pryamougolnaya-1", "himicheskaya-pryamougolnaya-2"],
        "extra_uses": ["Кислоты, щёлочи и реагенты", "Гальванические растворы"],
        "marks": "EEG-НР-(Н)-П (вода), EEG-ХР-(Н)-П (химия)",
    },
]


def tank_type(t):
    """Собирает данные типа: ряд из каталога + фото и применения из смежных категорий."""
    s = dict(series_of(*t["src"]))
    if t["slug"] == "pryamougolnaya-nazemnaya":  # вес есть только в химическом ряду, размеры совпадают
        chem = {r["v"]: r for r in series_of("himicheskie", "himicheskaya-pryamougolnaya")["table"]}
        s["table"] = [{**r, "weight": r["weight"] or chem.get(r["v"], {}).get("weight")} for r in s["table"]]
    s["images"] = s["images"] + [i for i in t["extra_images"] if i not in s["images"]]
    s["uses"] = s["uses"] + [u for u in t["extra_uses"] if u not in s["uses"]]
    s["title"] = t["title"]
    s["short"] = t["short"]
    s["lead"] = t["lead"]
    s["text"] = s["text"] + [PURPOSE_TEXT]
    s["marks"] = t["marks"]
    s["slug"] = t["slug"]
    return s


def calc_block(base):
    """Калькулятор переехал с главной на страницу резервуаров (правка клиента)."""
    return CALC_HTML.replace('href="#request"', f'href="{base}index.html#request"')


def tank_type_page(t, base="../../../"):
    s = tank_type(t)
    vols = [r["v"] for r in s["table"]]
    specs = [
        ("Материал", "Полипропилен, лист 8–12 мм"),
        ("Объёмы", f"{min(vols)}–{max(vols)} м³ и под заказ"),
        ("Установка", s["install"].capitalize()),
        ("Маркировка", s["marks"]),
        ("Гарантия", "12 месяцев"),
        ("Срок службы", "около 50 лет"),
    ]
    spec_rows = "".join(f"<div><dt>{k}</dt><dd>{v}</dd></div>" for k, v in specs)
    uses = "".join(f"<li>{u}</li>" for u in s["uses"])
    text = "".join(f'<p class="mt-4">{p}</p>' for p in s["text"])
    body = f"""{page_head(
        title=s["title"],
        lead=s["lead"],
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), ("Ёмкости и резервуары", f"{base}catalog/emkosti/"), (s["short"], None)],
        actions=False,
        facts=[(f'{min(vols)}–{max(vols)} м³', "типовые объёмы"), ("Вода и химия", "любая среда"),
               ("8–12 мм", "толщина листа"), ("По чертежам", "нестандартные размеры")],
    )}

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">
    {gallery(s, base, s["title"])}
    <div>
      <h2 class="section-title">Коротко о конструкции</h2>
      <dl class="spec mt-6">{spec_rows}</dl>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="{base}index.html#request" class="btn btn-primary">Оставить заявку <i data-lucide="arrow-right" class="size-5"></i></a>
        <a href="{WA}" class="btn btn-ghost" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/0e8a4a" alt="" class="size-5" width="20" height="20">WhatsApp</a>
      </div>
      <p class="mt-4 text-sm font-semibold text-muted">Стоимость зависит от объёма, толщины листа и комплектации — считаем под объект.</p>
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
      <h2 class="section-title">Где применяют</h2>
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
    <h2 class="section-title">Типоразмеры</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Ряд «{s["short"]}» — выберите ближайший объём или закажите нестандартный по чертежам.</p>
    <div class="mt-8">{size_table(s, base=base)}</div>
  </div>
</section>

{cta_band(base, "Рассчитаем резервуар под ваш объект")}"""
    return layout(path="", title=f'{s["title"]} из полипропилена — Expert ECO Group',
                  description=f'{s["title"]}: {s["lead"]} Вода, химия и противопожарный запас. Производство в Каскелене, доставка по Казахстану и СНГ.',
                  body=body, base=base)


def tanks_page(base="../../"):
    cards = []
    for t in TANK_TYPES:
        s = tank_type(t)
        vols = [r["v"] for r in s["table"]]
        cards.append(f"""<a class="type-card" href="{base}catalog/emkosti/{t["slug"]}/">
  <span class="type-card__ph"><img src="{base}assets/img/catalog/{s["images"][0]}-sm.webp" alt="{s["title"]}" loading="lazy"></span>
  <span class="type-card__body">
    <span class="type-card__title">{s["short"]}</span>
    <span class="type-card__lead">{s["lead"]}</span>
    <span class="type-card__meta">{min(vols)}–{max(vols)} м³<i data-lucide="arrow-right"></i></span>
  </span>
</a>""")
    body = f"""{page_head(
        title="Ёмкости и резервуары из полипропилена",
        lead="Один корпус — три задачи: питьевая и техническая вода, неприкосновенный противопожарный запас, кислоты и реагенты. Наземные и подземные, от 2 до 100 м³ и по вашим чертежам.",
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), ("Ёмкости и резервуары", None)],
        facts=[("2–100 м³", "типовые объёмы"), ("Вода и химия", "любая среда"),
               ("~50 лет", "срок службы полипропилена"), ("Свой цех", "в Каскелене")],
    )}

<section class="px-4 pb-6 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="text-[17px] leading-relaxed text-muted">
      <p class="mt-4">Полипропилен не ржавеет, не вступает в реакцию с водой и не требует покраски, поэтому один и тот же резервуар используют и для питьевой воды, и для кислот, щелочей и реагентов. Для агрессивных сред подбираем марку материала и толщину листа под состав и температуру.</p>
      <p class="mt-4">Изготавливаем на собственном производстве в Каскелене: раскрой и сварка листа, усиление рёбрами, установка патрубков, люков, переливов и перегородок по вашему заданию. Наземное исполнение ставится на подготовленную площадку, подземное — в котлован с обратной засыпкой.</p>
      <p class="mt-4">Ниже — четыре конструктивных типа. Если типового объёма не хватает, посчитайте габариты в калькуляторе или пришлите чертёж: изготовим ёмкость под ваши размеры.</p>
    </div>
    <img class="aspect-[4/3] w-full rounded-[28px] object-cover" src="{base}assets/img/cat-water.webp" alt="Ёмкости из полипропилена Expert ECO Group" loading="lazy">
  </div>
</section>

<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Типы резервуаров</h2>
    <div class="type-grid mt-8">{"".join(cards)}</div>
  </div>
</section>

<section id="calc" class="bg-mist px-4 py-16 md:px-6 md:py-24">
  <div class="mx-auto max-w-7xl">
{calc_block(base)}
  </div>
</section>

{cta_band(base, "Рассчитаем резервуар под ваш объект")}"""
    return layout(path="", title="Ёмкости и резервуары из полипропилена — Expert ECO Group",
                  description="Ёмкости из полипропилена для воды, противопожарного запаса и химии: горизонтальные наземные и подземные, вертикальные, прямоугольные. От 2 до 100 м³, производство в Каскелене.",
                  body=body, base=base)


# ---------------------------------------------------------------- разделы оборудования
# Клиент: «при нажатии на любую из разновидностей хотим видеть просто описание, фото и оставить заявку.
# Отходим от типа магазина, где куча позиций» — поэтому карточек с позициями на страницах разделов нет.
SECTIONS = {
    "kns": {
        "facts": [("10–100 м³/ч", "типовая производительность"), ("0,75–7,5 кВт", "мощность насосов"),
                  ("Под ключ", "корпус, насосы, шкаф"), ("Монтаж и ПНР", "шефмонтаж и запуск")],
        "text": [
            "Канализационная насосная станция принимает стоки самотёком и перекачивает их напорным трубопроводом туда, куда сток не дойдёт самотёком: в городской коллектор, на очистные сооружения или в накопитель.",
            "Корпус варим из листового полипропилена: он не разрушается от стоков и канализационных газов, весит в разы меньше бетонного и монтируется без тяжёлой техники. Диаметр и высоту подбираем под глубину подводящего коллектора, расход и требуемый напор.",
            "Типовые станции — от 10 до 100 м³/час с напором 10–20 м, насосы мощностью 0,75–7,5 кВт. Делаем и станции индивидуального размера по проекту или опросному листу.",
        ],
        "points": [
            ("settings-2", "Корпус под проект", "Диаметр и высота — под глубину коллектора, расход и напор."),
            ("waves", "Насосы и обвязка", "Погружные насосы, направляющие, запорная арматура и обратные клапаны."),
            ("cpu", "Шкаф управления", "Работа по уровню, чередование насосов, аварийная сигнализация."),
            ("hard-hat", "Монтаж и пусконаладка", "Устанавливаем на объекте, запускаем и передаём в работу."),
        ],
        "uses": ["Хозяйственно-бытовые стоки посёлков и жилых комплексов", "Ливневые стоки с площадок и паркингов",
                 "Производственные стоки цехов", "Объекты, где нет самотёчного уклона к коллектору"],
    },
    "los": {
        "facts": [("1–50 л/с", "типовая производительность"), ("Ливнёвка и быт", "типы стоков"),
                  ("Свой цех", "корпуса из полипропилена"), ("Сервис", "обслуживание после запуска")],
        "text": [
            "Локальные очистные сооружения доводят сток до показателей, при которых его можно сбросить в канализацию, на рельеф или в водоём. Набор ступеней зависит от того, что именно нужно убрать: песок, нефтепродукты, взвеси или органику.",
            "Комбинированные песко-нефтеуловители собственного производства работают на расход от 1 до 20 л/с, очистные ливневых стоков — до 50 л/с и выше по проекту. Корпус из полипропилена, внутри — камеры отстаивания, перегородки и фильтрующие элементы.",
            "Схему подбираем по расходу в литрах в секунду, составу стока и требованиям к сбросу. Разрабатываем чертежи, изготавливаем, монтируем и берём объект на обслуживание.",
        ],
        "points": [
            ("filter", "Подбор по расходу", "Считаем нагрузку в л/с по площади стока или водопотреблению."),
            ("layers", "Ступени очистки", "Пескоуловитель, нефтеуловитель и доочистка — набор под задачу."),
            ("ruler", "Проект и чертежи", "Разрабатываем рабочие чертежи или работаем по вашим."),
            ("badge-check", "Запуск и сервис", "Пусконаладка и регламентное обслуживание после ввода."),
        ],
        "uses": ["Ливнёвка с парковок, АЗС и площадок", "Мойки и автохозяйства",
                 "Производственные стоки", "Объекты без центральной канализации"],
    },
    "septiki": {
        "facts": [("3–20 человек", "станции Юнилос Астра"), ("Свой цех", "септики из полипропилена"),
                  ("Юнилос и Евролос", "поставка и монтаж"), ("Запуск", "монтаж и биопрепараты")],
        "text": [
            "Септик принимает хозяйственно-бытовые стоки дома или объекта, где нет центральной канализации, и очищает их перед сбросом на поля фильтрации, в дренажный колодец или накопитель.",
            "Делаем септики из полипропилена на собственном производстве: объём и число камер считаем под количество проживающих и залповый сброс. Поставляем и монтируем готовые станции биологической очистки Юнилос Астра (на 3–20 человек) и Евролос.",
            "Подбираем решение по числу пользователей, уровню грунтовых вод и способу отвода очищенной воды. Монтируем, запускаем и поставляем биопрепараты для старта и работы зимой.",
        ],
        "points": [
            ("home", "Под объект", "Дом, база отдыха, кафе или вахтовый посёлок."),
            ("droplets", "Собственное производство", "Септики из полипропилена нужного объёма и конфигурации."),
            ("factory", "Готовые станции", "Юнилос Астра и Евролос — поставка, монтаж, запуск."),
            ("sprout", "Биопрепараты", "Бактерии для запуска системы и работы в холодный сезон."),
        ],
        "uses": ["Частные дома и дачи", "Базы отдыха и гостиницы",
                 "Кафе и придорожные объекты", "Вахтовые посёлки и стройплощадки"],
    },
    "zhirouloviteli": {
        "facts": [("0,5–3,6 л/с", "типовой ряд"), ("30–240 л", "объём жироотстойника"),
                  ("Под мойку", "и промышленные"), ("Любой размер", "по вашим чертежам")],
        "text": [
            "Жироуловитель задерживает жир и остатки пищи до того, как они попадут в канализацию: иначе жир застывает на стенках труб, сужает их и приводит к засорам, а сброс без очистки нарушает нормы водоотведения.",
            "Ряд под мойку рассчитан на 0,5–1,5 л/с с отстойником от 30 до 175 литров — такие ставят прямо под раковину или рядом с моечной ванной. Промышленные модели работают на больший расход и монтируются в помещении или в приямке.",
            "Подбираем модель по расходу воды, количеству моек и оборудования. Изготавливаем корпуса индивидуального размера из полипропилена — он не ржавеет и легко моется.",
        ],
        "points": [
            ("utensils-crossed", "Для кухни и цеха", "Рестораны, кондитерские, мясо- и рыбопереработка."),
            ("gauge", "Подбор по расходу", "Считаем по мойкам, оборудованию и посадочным местам."),
            ("box", "Под мойку или в пол", "Компактные бытовые и промышленные исполнения."),
            ("wrench", "Обслуживание", "Чистка, ремонт и замена корпусов."),
        ],
        "uses": ["Рестораны, кафе и столовые", "Кондитерские и пекарни",
                 "Мясо- и рыбоперерабатывающие цеха", "Пищевые производства"],
    },
    "peskouloviteli": {
        "facts": [("5–15 м³", "промышленные объёмы"), ("0,5–1,5 л/с", "бытовой ряд"),
                  ("Ливнёвка", "первая ступень очистки"), ("Свой цех", "корпуса из полипропилена")],
        "text": [
            "Пескоуловитель задерживает песок, гравий и другие нерастворимые минеральные частицы. Это первая ступень ливневых очистных сооружений: без неё песок быстро забивает нефтеуловитель и фильтры.",
            "Бытовой ряд рассчитан на 0,5–1,5 л/с и ставится на выпуске с небольшой площадки или мойки. Промышленные пескоуловители делаем объёмом 5, 10 и 15 м³ — под расход площадки и периодичность обслуживания.",
            "Обычно ставится в связке с нефтеуловителем: подбираем оба аппарата по расходу стока и площади водосбора.",
        ],
        "points": [
            ("layers", "Первая ступень", "Снимает основную нагрузку с нефтеуловителя и фильтров."),
            ("gauge", "Подбор по площади", "Считаем расход по площади стока и интенсивности дождя."),
            ("package", "Бытовые и промышленные", "От компактных на выпуске до аппаратов на 15 м³."),
            ("wrench", "Обслуживание", "Удобный доступ для откачки осадка через горловину."),
        ],
        "uses": ["Парковки и открытые площадки", "Автомойки",
                 "Промышленные и складские территории", "Ливневые очистные сооружения"],
    },
    "kompressory": {
        "facts": [("HIBLOW", "Япония и Филиппины"), ("AirMac и Jecod", "Тайвань"),
                  ("Аэрация", "очистных и водоёмов"), ("Запчасти", "мембраны и ремкомплекты")],
        "text": [
            "Мембранные компрессоры подают воздух в аэротенки станций биологической очистки, септики и водоёмы: без кислорода бактерии не работают, а вода застаивается и цветёт.",
            "Поставляем HIBLOW серии HP — от HP-20 до HP-200, компрессоры AirMac DB и DBMX, а также Jecod. Для прудов и водоёмов собираем комплекты аэрации с распылителями и шлангом — на объём от 100 до 500 м³.",
            "Подбираем модель по требуемой подаче воздуха и глубине погружения аэратора. Держим на складе мембраны и ремкомплекты, ремонтируем компрессоры.",
        ],
        "points": [
            ("wind", "Компрессоры HIBLOW", "Линейка HP-20 … HP-200 для септиков и станций биоочистки."),
            ("waves", "Системы аэрации", "Комплекты для прудов и водоёмов от 100 до 500 м³."),
            ("gauge", "Подбор по подаче", "Считаем по объёму аэротенка и глубине погружения."),
            ("wrench", "Запчасти и ремонт", "Мембраны, ремкомплекты и системы защиты в наличии."),
        ],
        "uses": ["Станции биологической очистки", "Септики частных домов",
                 "Пруды и декоративные водоёмы", "Рыбоводные хозяйства"],
    },
    "zapchasti": {
        "facts": [("HIBLOW", "HP-60 … HP-200"), ("AirMac", "DB-60 … DB-200"),
                  ("Мембраны", "и ремкомплекты"), ("Ремонт", "в нашем сервисе")],
        "text": [
            "Мембрана — расходник компрессора: со временем она теряет эластичность, подача воздуха падает, и станция очистки перестаёт справляться. Замена мембраны обходится в разы дешевле нового компрессора.",
            "Держим мембраны HIBLOW HP-60/80, HP-100/120 и HP-150/200, мембраны AirMac DB-60/80, DB-100, DB-120 и DB-150/200, а также системы защиты компрессора от перегрузки.",
            "Если компрессор уже вышел из строя — принимаем в ремонт: диагностика, замена мембран и клапанов, проверка подачи.",
        ],
        "points": [
            ("package", "Мембраны в наличии", "Основные типоразмеры HIBLOW и AirMac на складе."),
            ("shield", "Системы защиты", "Автоматика, которая отключает компрессор при повреждении мембраны."),
            ("wrench", "Ремонт компрессоров", "Диагностика и восстановление HIBLOW и AirMac."),
            ("clock", "Быстрая замена", "Подскажем по модели и поможем подобрать комплект."),
        ],
        "uses": ["Обслуживание септиков", "Станции биологической очистки",
                 "Аэрация прудов", "Сервисные компании"],
    },
    "nasosy": {
        "facts": [("0,75–15 кВт", "мощность"), ("Leo", "Китай"),
                  ("Сточные воды", "и дренаж"), ("Подбор", "по расходу и напору")],
        "text": [
            "Погружные насосы серий WQ и WQD перекачивают сточные, дренажные и загрязнённые воды с механическими включениями. Их ставят в КНС, приямки, дренажные колодцы и накопители.",
            "В линейке модели мощностью от 0,75 до 15 кВт с патрубками 50, 65, 80, 100 и 150 мм — под разные расходы и напоры. Есть исполнения с поплавковым выключателем.",
            "Подбираем насос по расходу, напору и характеру стоков, комплектуем ими наши канализационные насосные станции.",
        ],
        "points": [
            ("waves", "Для стоков и дренажа", "Работают с загрязнённой водой и механическими включениями."),
            ("gauge", "Подбор по графику", "Считаем рабочую точку по расходу и напору."),
            ("settings-2", "Комплектация КНС", "Ставим в наши станции вместе с обвязкой и автоматикой."),
            ("shield", "Защита", "Поплавковый выключатель и автоматика в шкафу управления."),
        ],
        "uses": ["Канализационные насосные станции", "Дренажные и водосборные приямки",
                 "Откачка стоков на стройплощадке", "Перекачка из накопителей"],
    },
    "shkafy": {
        "facts": [("Под проект", "сборка по схеме"), ("КНС и очистные", "назначение"),
                  ("Автоматика", "работа по уровню"), ("Сигнализация", "аварийные режимы")],
        "text": [
            "Шкаф управления — это мозг насосной станции: он включает насосы по уровню стоков, чередует их, чтобы вырабатывался равный ресурс, и подаёт сигнал при аварии.",
            "Собираем шкафы под проект: количество насосов, мощность, тип пуска, датчики уровня, защита по току и сухому ходу, вывод аварийного сигнала.",
            "Поставляем в комплекте с КНС или отдельно — под уже смонтированное оборудование.",
        ],
        "points": [
            ("cpu", "Работа по уровню", "Поплавки или датчики: пуск, остановка, аварийный уровень."),
            ("repeat", "Чередование насосов", "Равномерная наработка и резерв при отказе."),
            ("shield", "Защита", "От перегрузки, обрыва фазы и сухого хода."),
            ("bell", "Аварийный сигнал", "Световая и звуковая индикация, вывод сигнала диспетчеру."),
        ],
        "uses": ["Канализационные насосные станции", "Очистные сооружения",
                 "Дренажные приямки", "Модернизация существующих станций"],
    },
    "biopreparaty": {
        "facts": [("Unibac", "Start, Effect, Universal"), ("Liquazyme", "жидкий препарат"),
                  ("Запуск", "после монтажа и простоя"), ("Зима", "поддержка в холодный сезон")],
        "text": [
            "Биопрепараты — это концентрат бактерий, которые перерабатывают органику в септике и станции биологической очистки. Их добавляют при запуске системы, после длительного простоя и зимой, когда активность ила падает.",
            "Поставляем линейку Unibac: Start — для запуска, Effect — для восстановления работы, Universal — для регулярного применения, а также жидкий Liquazyme.",
            "Подскажем дозировку под объём септика и режим эксплуатации.",
        ],
        "points": [
            ("sprout", "Запуск системы", "Unibac Start заселяет систему бактериями после монтажа."),
            ("refresh-cw", "Восстановление", "Unibac Effect — после простоя или залпового сброса."),
            ("calendar", "Регулярно", "Unibac Universal для поддержания работы круглый год."),
            ("droplets", "Жидкая форма", "Liquazyme удобен для дозирования в небольшие системы."),
        ],
        "uses": ["Септики частных домов", "Станции биологической очистки",
                 "Выгребные ямы и накопители", "Объекты с сезонной эксплуатацией"],
    },
    "tehnofiltr": {
        "facts": [("Технофильтр", "официальная поставка"), ("Патронные", "капсульные и мешочные"),
                  ("Жидкости и газы", "область применения"), ("Подбор", "по среде и тонкости")],
        "text": [
            "«Технофильтр» — российский производитель фильтроэлементов для очистки жидкостей и газов на производстве. Поставляем его продукцию в Казахстан.",
            "В ассортименте патронные, капсульные и мешочные фильтроэлементы, фильтродержатели, фильтрационные установки и приборы контроля целостности фильтров.",
            "Подбираем элемент по среде, тонкости фильтрации, температуре и типоразмеру корпуса. Нужен подбор — пришлите параметры процесса в заявке.",
        ],
        "points": [
            ("filter", "Фильтроэлементы", "Патронные, капсульные и мешочные под разные среды."),
            ("box", "Фильтродержатели", "Корпуса под стандартные типоразмеры элементов."),
            ("settings-2", "Установки", "Фильтрационные установки под технологический процесс."),
            ("gauge", "Контроль целостности", "Приборы для проверки фильтров перед работой."),
        ],
        "uses": ["Пищевые производства", "Водоподготовка",
                 "Химические и технологические линии", "Фильтрация газов"],
    },
    "uslugi": {
        "facts": [("с 2014 года", "монтаж и сервис"), ("Футеровка", "бетон и сталь"),
                  ("Шефмонтаж", "и пусконаладка"), ("РК и СНГ", "география работ")],
        "text": [
            "Компания начиналась с монтажа и обслуживания очистных сооружений — эти работы мы делаем и сейчас, в том числе для оборудования, которое поставляли не мы.",
            "Футеруем бетонные и стальные резервуары листовым полипропиленом: такая облицовка защищает бетон от агрессивной среды и продлевает срок службы ёмкости без её замены. Ремонтируем и реставрируем полипропиленовое оборудование — свариваем трещины, меняем участки корпуса, восстанавливаем патрубки.",
            "Выполняем шефмонтаж и пусконаладку, обучаем персонал заказчика и берём объекты на регламентное сервисное обслуживание.",
        ],
        "points": [
            ("layers", "Футеровка резервуаров", "Облицовка полипропиленом бетонных и стальных ёмкостей."),
            ("wrench", "Ремонт оборудования", "Сварка трещин, замена участков корпуса и патрубков."),
            ("hard-hat", "Шефмонтаж и ПНР", "Монтаж на объекте, запуск и обучение персонала."),
            ("badge-check", "Сервисное обслуживание", "Регламентные работы по очистным и насосным станциям."),
        ],
        "uses": ["Очистные сооружения и КНС", "Бетонные и стальные резервуары",
                 "Полипропиленовое оборудование", "Компрессоры и насосы"],
    },
}


def section_photos(cat, base):
    """Фото раздела: заранее отобранные непохожие кадры (tools/pick_section_photos.py)."""
    return [(f'{base}assets/img/catalog/other/{p["image"]}', p["alt"]) for p in SECTION_PHOTOS.get(cat["slug"], [])]


def section_page(cat, base="../../"):
    meta = SECTIONS[cat["slug"]]
    photos = section_photos(cat, base)
    text = "".join(f'<p class="mt-4">{p}</p>' for p in meta["text"])
    points = "".join(
        f'<li class="feature"><span class="feature__icon"><i data-lucide="{icon}"></i></span><div><h3>{title}</h3><p>{txt}</p></div></li>'
        for icon, title, txt in meta["points"])
    uses = "".join(f"<li>{u}</li>" for u in meta["uses"])
    lead_photo = (f'<img class="aspect-[4/3] w-full rounded-[28px] object-cover" src="{photos[0][0]}" alt="{cat["title"]}" loading="lazy">'
                  if photos else "")
    rest = "".join(
        f'<figure class="album-photo"><img src="{src}" alt="{alt}" loading="lazy"></figure>'
        for src, alt in photos[1:]) if len(photos) >= 3 else ""
    gallery_block = f"""
<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Фото</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Наши изделия и объекты. Больше снимков — в фотоальбоме.</p>
    <div class="album-grid mt-8">{rest}</div>
    <a href="{base}fotoalbom/" class="btn btn-ghost mt-8">Открыть фотоальбом <i data-lucide="images" class="size-5"></i></a>
  </div>
</section>""" if rest else ""

    body = f"""{page_head(
        title=cat["title"],
        lead=cat["lead"],
        base=base,
        crumb_items=[("Каталог", f"{base}catalog/"), (cat["title"], None)],
        facts=meta["facts"],
    )}

<section class="px-4 pb-10 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="text-[17px] leading-relaxed text-muted">{text}</div>
    {lead_photo}
  </div>
</section>

<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">Что берём на себя</h2>
    <ul class="mt-10 grid gap-x-8 gap-y-8 md:grid-cols-2">{points}</ul>
  </div>
</section>

<section class="px-4 pb-12 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div>
      <h2 class="section-title">Где применяют</h2>
      <ul class="uses mt-6">{uses}</ul>
    </div>
    <div class="note">
      <i data-lucide="file-text"></i>
      <p>Пришлите исходные данные по объекту — расход, состав стоков, глубину подводящей трубы или чертёж. Подберём решение и посчитаем стоимость.</p>
    </div>
  </div>
</section>
{gallery_block}

{cta_band(base, "Подберём оборудование под вашу задачу")}"""
    return layout(path="", title=f'{cat["title"]} — Expert ECO Group',
                  description=cat["lead"][:180], body=body, base=base)


# ---------------------------------------------------------------- каталог
DIRECTIONS = [
    ("Ёмкости и резервуары", "catalog/emkosti/", "assets/img/cat-water.webp",
     "Для питьевой и технической воды, противопожарного запаса, кислот и реагентов. Наземные и подземные, 2–100 м³.",
     "2–100 м³"),
    ("Канализационные насосные станции", "catalog/kns/", "assets/img/hero-kns-1280.webp",
     "Приём и перекачка хозяйственно-бытовых, ливневых и производственных стоков. Корпус, насосы, обвязка и шкаф управления.",
     "10–100 м³/ч"),
    ("Очистные сооружения", "catalog/los/", "assets/img/hero-los-1280.webp",
     "Песко- и нефтеуловители, комбинированные установки, очистные ливневых стоков. Подбор по расходу и составу стока.",
     "1–50 л/с"),
]


def direction_card(title, href, img, text, tag, base):
    return f"""<a class="cat-card" href="{base}{href}">
  <span class="cat-card__ph"><img src="{base}{img}" alt="{title}" loading="lazy"></span>
  <span class="flex flex-1 flex-col p-6 md:p-7">
    <span class="chip self-start">{tag}</span>
    <span class="mt-4 font-display text-[1.35rem] font-medium leading-tight text-navy md:text-[1.55rem]">{title}</span>
    <span class="mt-3 leading-relaxed text-muted">{text}</span>
    <span class="cat-card__more mt-6">Подробнее <i data-lucide="arrow-right" class="size-4"></i></span>
  </span>
</a>"""


def tile(title, href, img, tag, base):
    return f"""<a class="prod-tile" href="{href}">
  <span class="prod-tile__head">
    <span>
      <span class="prod-tile__title">{title}</span>
      <span class="prod-tile__count">{tag}</span>
    </span>
    <i data-lucide="arrow-up-right"></i>
  </span>
  <span class="prod-tile__ph"><img src="{img}" alt="{title}" loading="lazy"></span>
</a>"""


def catalog_page(base="../"):
    dirs = "".join(direction_card(t, h, i, txt, tag, base) for t, h, i, txt, tag in DIRECTIONS)

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
                 SECTIONS[c["slug"]]["facts"][0][0], base)
            for c in cats)
        groups_html += f"""<section class="px-4 py-8 md:px-6" id="{anchors.get(group, '')}">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title">{group}</h2>
    <div class="prod-tiles mt-8">{tiles}</div>
  </div>
</section>"""

    body = f"""{page_head(
        title="Оборудование из полипропилена",
        lead="Три основных направления: ёмкости и резервуары, канализационные насосные станции и очистные сооружения. Плюс оборудование и услуги, которыми их дополняем.",
        base=base,
        crumb_items=[("Каталог", None)],
        facts=[("3 направления", "ёмкости, КНС, очистные"), ("Свой цех", "в Каскелене"),
               ("с 2015 года", "собственное производство"), ("РК и СНГ", "доставка и монтаж")],
    )}

<section id="napravleniya" class="px-4 py-8 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="dir-grid">{dirs}</div>
  </div>
</section>

{groups_html}

{cta_band(base)}"""
    return layout(path="", title="Каталог оборудования из полипропилена — Expert ECO Group",
                  description="Каталог Expert ECO Group: ёмкости и резервуары, канализационные насосные станции, очистные сооружения, септики, жиро- и пескоуловители, компрессоры и услуги.",
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
        facts=[("Фото", "с производства и объектов"), ("Свой цех", "в Каскелене"),
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
        facts=[("Свой цех", "в Каскелене"), ("Объекты", "по Казахстану и СНГ"),
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
    made.append(write("catalog/emkosti/index.html", tanks_page()))
    for t in TANK_TYPES:
        made.append(write(f'catalog/emkosti/{t["slug"]}/index.html', tank_type_page(t)))
    for cat in OTHER:
        made.append(write(f'catalog/{cat["slug"]}/index.html', section_page(cat)))
    made.append(write("proekty/index.html", projects_page()))
    made.append(write("fotoalbom/index.html", albums_page()))
    for slug, title, lead, cover, photos in ALBUMS:
        if photos:
            made.append(write(f"fotoalbom/{slug}/index.html", album_page(slug, title, lead, photos)))
    made.append(write("karta-proektov/index.html", map_page()))
    made.append(write("o-kompanii/index.html", about_page()))
    made.append(write("kontakty/index.html", contacts_page()))
    # страницы старой структуры (категории вода/пожарные/химия, карточки товаров, «Другое оборудование»)
    for old in ["catalog/voda", "catalog/pozharnye", "catalog/himicheskie", "oborudovanie"]:
        shutil.rmtree(SITE / old, ignore_errors=True)
    print(f"страниц собрано: {len(made)}")
    for p in made[:6]:
        print(" ", p)
    print("  ...")


if __name__ == "__main__":
    main()
