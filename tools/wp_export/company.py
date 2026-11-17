"""Выгрузка страниц «Проекты» (карта + фотоальбом), альбомов, «О компании» и «Контакты».

Тексты, которые в генераторе заданы внутри функций страниц (абзацы, этапы, телефоны контактов),
берём из собранной генератором разметки — так они совпадают с макетом дословно.
"""
import html
import re

from wp_export.common import B, img, facts

SITE_IMG = "site/assets/img/"


def _one(pattern, text, flags=re.S):
    m = re.search(pattern, text, flags)
    if not m:
        raise SystemExit(f"company: не найдено в разметке макета: {pattern}")
    return m.group(1)


def _seo(page_html):
    return {
        "seo_title": html.unescape(_one(r"<title>(.*?)</title>", page_html)),
        "seo_description": html.unescape(_one(r'<meta name="description" content="(.*?)">', page_html)),
    }


def _main(page_html):
    """Только <main>: в шапке и подвале макета есть похожая разметка (телефоны, почта)."""
    return _one(r'<main id="main">(.*?)</main>', page_html)


def _head(page_html):
    """H1, лид и плашки шапки страницы."""
    hero = _one(r'(<section class="page-hero">.*?</section>)', page_html)
    out = {
        "head_title": _one(r'<h1 class="section-title">(.*?)</h1>', hero),
        "head_lead": _one(r'<p class="mt-5 text-\[17px\] leading-relaxed text-muted">(.*?)</p>', hero),
    }
    pairs = re.findall(r"<li><b>(.*?)</b><span>(.*?)</span></li>", hero)
    if pairs:
        out["facts"] = facts(pairs)
    return out


def _stars(text, tag_re):
    """<span class="accent …">и как</span> / <b class="…">Salher</b> → *и как* / *Salher* (так выделение правит клиент)."""
    return re.sub(tag_re, r"*\1*", text)


def projects():
    full = B.projects_page()
    page = _main(full)
    head = _head(page)
    pins = [{
        "slug": p["id"],
        "city": p["city"],
        "region": p["region"],
        "lat": p["coords"][0],
        "lng": p["coords"][1],
        "title": p["title"],
        "kind": p["kind"],
        "year": p["year"],
        "text": p["text"],
        "done": "\n".join(p["done"]),
        "confirmed": bool(p["confirmed"]),
    } for p in B.MAP_PROJECTS]
    note = _one(r'<p class="mt-5 text-sm font-semibold text-muted">(.*?)</p>', page)
    return {
        "path": "proekty", "title": "Проекты", "template": "page-templates/projects.php", "menu_order": 40,
        "fields": {"head_lead": head["head_lead"], "proj_pins": pins, "proj_map_note": note, **_seo(full)},
    }


def albums():
    out = []
    for i, (slug, title, lead, photos) in enumerate(B.ALBUMS, start=1):
        full = B.album_page(slug, title, lead, photos)
        head = _head(_main(full))
        fields = {
            "head_lead": lead,
            "facts": head["facts"],
            "album_photos": [img(ph) for ph in photos],   # alt не задаём: у фото альбома alt — название альбома
            "album_icon": B.ALBUM_EMPTY_ICON.get(slug, "camera"),
            **_seo(full),
        }
        out.append({"path": f"fotoalbom/{slug}", "title": title, "template": "page-templates/album.php",
                    "menu_order": i, "fields": fields})
    return out


def about():
    full = B.about_page()
    page = _main(full)
    head = _head(page)
    intro = _one(r'<h2 class="section-title reveal" data-anim="fade-right" data-delay="150">(.*?)</h2>', page)
    text = _one(r'<div class="about-text[^"]*"[^>]*>(.*?)</div>', page)
    paras = re.findall(r'<p class="mt-5">(.*?)</p>', text, re.S)
    photo_alt = _one(r'<img class="w-full rounded-\[28px\] object-cover" src="[^"]*plant-hall\.webp" alt="(.*?)"', page)
    steps_title = _one(r'<h2 class="section-title">(Как мы.*?)</h2>', page)
    steps = re.findall(r'<li class="step step--light"><span class="step__num">(.*?)</span><p>(.*?)</p></li>', page)
    note = _one(r'<i data-lucide="factory"></i>\s*<p>(.*?)</p>', page)
    what_title = _one(r'<section class="bg-mist[^"]*">.*?<h2 class="section-title">(.*?)</h2>', page)
    what = re.findall(r'<div class="eq-group"><h3[^>]*>(.*?)</h3><p[^>]*>(.*?)</p></div>', page)
    return {
        "path": "o-kompanii", "title": "О компании", "template": "page-templates/about.php", "menu_order": 60,
        "fields": {
            **head,
            "about_intro_title": _stars(intro, r'<span class="accent text-brand">(.*?)</span>'),
            "about_text": "\n\n".join(paras),
            "about_photo": img("plant-hall.webp"),   # alt — отдельным полем: этот кадр на главной подписан иначе
            "about_photo_alt": photo_alt,
            "about_steps_title": steps_title,
            "about_steps": [{"year": y, "text": t} for y, t in steps],
            "about_note": note,
            "about_what_title": what_title,
            "about_what": [{"title": t, "text": _stars(x, r'<b class="font-bold text-navy">(.*?)</b>')} for t, x in what],
            **_seo(full),
        },
    }


def contacts():
    full = B.contacts_page()
    page = _main(full)
    head = _head(page)
    phones = re.findall(r'<i data-lucide="phone"></i><span><b>(.*?)</b><small>(.*?)</small>', page)
    return {
        "path": "kontakty", "title": "Контакты", "template": "page-templates/contacts.php", "menu_order": 70,
        "fields": {
            **head,
            "contacts_phones": [{"number": n, "label": w} for n, w in phones],
            "contacts_email_note": _one(r'<i data-lucide="mail"></i><span><b>.*?</b><small>(.*?)</small>', page),
            "contacts_address_note": _one(r'<i data-lucide="map-pin"></i><span><b>.*?</b><small>(.*?)</small>', page),
            "contacts_hours_note": _one(r'<i data-lucide="clock"></i><span><b>.*?</b><small>(.*?)</small>', page),
            "contacts_note": _one(r'<i data-lucide="file-text"></i><p>(.*?)</p>', page),
            "contacts_form_title": _one(r'<h2 class="font-display text-xl font-medium text-navy">(.*?)</h2>', page),
            "contacts_form_text": _one(r'<p class="mt-3 leading-relaxed text-muted">(.*?)</p>', page),
            **_seo(full),
        },
    }


def pages():
    return [projects(), *albums(), about(), contacts()]
