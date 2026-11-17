"""Главная страница. Тексты и фото берутся из site/index.html (главная написана руками, не генератором),
чтобы в WordPress они совпали с макетом дословно."""
import html
import re
from urllib.parse import unquote

from wp_export.common import B, img

MAIN = re.search(r'<main id="main">(.*?)</main>', B.INDEX, re.S).group(1)


def _find(pattern, text, group=1):
    m = re.search(pattern, text, re.S)
    if not m:
        raise SystemExit(f"home: в site/index.html не найдено: {pattern}")
    return m.group(group)


def _section(start):
    """Блок <section …> целиком по началу открывающего тега."""
    return _find(r'(' + start + r'.*?</section>)', MAIN)


def _text(fragment, mark_any_span=False):
    """Внутренний HTML → значение поля: выделенный <span> → *текст*, иконки <i> убираем, пробелы схлопываем.
    Сущности (&nbsp;) оставляем как есть — шаблон выводит их без изменений."""
    span = r'<span class="[^"]*">(.*?)</span>' if mark_any_span else r'<span class="accent[^"]*">(.*?)</span>'
    t = re.sub(span, r'*\1*', fragment, flags=re.S)
    t = re.sub(r'<i [^>]*></i>', '', t)
    t = re.sub(r'[ \t\r\n]+', ' ', t).strip()
    if '<' in t:
        raise SystemExit(f"home: в тексте осталась разметка: {t}")
    return t


def _attr(tag, name):
    m = re.search(r'\s' + name + r'="([^"]*)"', tag)
    return html.unescape(m.group(1)) if m else ""


def _img(src, alt=""):
    """src из макета (assets/img/…?v=2) → картинка для поля."""
    rel = src.split("?")[0]
    if not rel.startswith("assets/img/"):
        raise SystemExit(f"home: картинка не из assets/img: {src}")
    return img(rel[len("assets/img/"):], alt)


def _main_frame(tag):
    """Основной кадр: самый широкий из srcset, иначе src."""
    srcset = _attr(tag, "srcset")
    if not srcset:
        return _attr(tag, "src")
    items = [e.split() for e in srcset.split(",")]
    return max(items, key=lambda e: int(e[1].rstrip("w")))[0]


def _h2(section):
    return _text(_find(r'<h2[^>]*>(.*?)</h2>', section))


def hero():
    sec = _section(r'<section id="top"')
    tabs = re.findall(r'<button type="button" class="hero-tab[^"]*" data-tab="\d+"[^>]*>(.*?)</button>', sec)
    bgs = re.findall(r'<img class="hero-img hero-bg__img[^"]*"[^>]*>', sec, re.S)
    panes = re.split(r'<div class="hero-pane[ "]', sec)[1:]
    if not (len(tabs) == len(bgs) == len(panes)):
        raise SystemExit(f"home: слайдов {len(panes)}, вкладок {len(tabs)}, фонов {len(bgs)}")
    slides = []
    for i, (tab, bg, pane) in enumerate(zip(tabs, bgs, panes)):
        if _attr(bg, "data-bg") != str(i):
            raise SystemExit(f"home: фон слайда {i} не по порядку")
        under = re.search(r'background-image: url\(([^)]*)\)', bg)
        btn = re.search(r'<a href="([^"]*)" class="btn btn-primary">(.*?)</a>', pane, re.S)
        s = {
            "tab": _text(tab),
            "bg": _img(_main_frame(bg), _attr(bg, "alt")),
            "bg_under": _img(under.group(1)) if under else "",
            "title": _text(_find(r'<h1[^>]*>(.*?)</h1>', pane)),
            "text": _text(_find(r'<p class="hero-in mt-5[^"]*">(.*?)</p>', pane)),
            "btn_text": _text(btn.group(2)) if btn else "",
            "btn_link": btn.group(1) if btn else "",
        }
        aside = re.search(r'<aside [^>]*aria-label="([^"]*)">(.*?)</aside>', pane, re.S)
        if aside:
            a = aside.group(2)
            big = re.search(r'<p class="mt-2 font-display ([^"]*)">(.*?)</p>', a, re.S)
            link = re.search(r'<a href="([^"]*)" class="mt-6[^"]*">(.*?)</a>', a, re.S)
            s.update({
                "aside_aria": aside.group(1),
                "aside_kicker": _text(_find(r'<p class="text-xs[^"]*">(.*?)</p>', a)),
                "aside_big": _text(big.group(2), mark_any_span=True),
                "aside_big_fluid": 1 if "clamp(" in big.group(1) else 0,
                "aside_sub": _text(_find(r'<p class="mt-2 text-sm[^"]*">(.*?)</p>', a)),
                "aside_facts": [{"a": _text(x), "b": _text(y)} for x, y in re.findall(
                    r'<p class="font-display text-xl font-medium">(.*?)</p>\s*<p class="mt-1[^"]*">(.*?)</p>', a, re.S)],
                "aside_link_text": _text(link.group(2)) if link else "",
                "aside_link": link.group(1) if link else "",
            })
        slides.append(s)
    wa = unquote(_find(r'href="https://wa\.me/\d+\?text=([^"]*)"', sec))
    return {"hero_slides": slides, "hero_wa_text": wa}


def catalog():
    sec = _section(r'<section id="catalog"')
    tiles = re.findall(r'<a class="prod-tile[^"]*"[^>]*href="([^"]*)">.*?<span class="prod-tile__title">(.*?)</span>.*?(<img [^>]*>)',
                       sec, re.S)
    track = _find(r'<ul class="marquee__track">(.*?)</ul>', MAIN)
    return {
        "catalog_title": _h2(sec),
        "catalog_tiles": [{"title": _text(t), "link": href, "image": _img(_attr(tag, "src"), _attr(tag, "alt"))}
                          for href, t, tag in tiles],
        "marquee": [{"text": _text(x)} for x in re.findall(r'<li>(.*?)</li>', track)],
    }


def services():
    sec = _section(r'<section id="services"')
    out = []
    for slug, body in re.findall(r'<figure class="srv-panel[^"]*" data-srv="([^"]*)">(.*?)</figure>', sec, re.S):
        tag = _find(r'(<img [^>]*>)', body)
        out.append({
            "title": _text(_find(r'<h3>(.*?)</h3>', body)),
            "slug": slug,
            "text": _text(_find(r'<p>(.*?)</p>', body)),
            "image": _img(_main_frame(tag), _attr(tag, "alt")),
        })
    return {
        "services_title": _h2(sec),
        "services_lead": _text(_find(r'<p class="max-w-md text-white/70">(.*?)</p>', sec)),
        "services": out,
    }


def production():
    sec = _section(r'<section id="production"')
    tag = _find(r'(<img data-parallax [^>]*>)', sec)
    return {
        "production_title": _h2(sec),
        "production_text": _text(_find(r'<p class="mt-5 text-\[17px\][^"]*">(.*?)</p>', sec)),
        "production_image": _img(_attr(tag, "src"), _attr(tag, "alt")),
        "production_image_alt": _attr(tag, "alt"),
        "production_caption": _text(_find(r'<span class="block font-bold text-navy">(.*?)</span>', sec)),
        "production_address": _text(_find(r'<span class="block text-sm font-semibold text-muted">(.*?)</span>', sec)),
        "production_features": [{"icon": icon, "title": _text(t), "text": _text(p)} for icon, t, p in re.findall(
            r'<li class="reveal feature"[^>]*><span class="feature__icon"><i data-lucide="([^"]*)"></i></span>'
            r'<div><h3>(.*?)</h3><p>(.*?)</p></div></li>', sec)],
    }


def steps():
    sec = _section(r'<section class="bg-navy[^"]*" aria-labelledby="steps-title"')
    return {
        "steps_title": _h2(sec),
        "steps": [{"title": _text(t), "text": _text(p)} for t, p in re.findall(
            r'<li class="step[^"]*"[^>]*><span class="step__num">\d+</span><h3>(.*?)</h3><p>(.*?)</p></li>', sec)],
    }


def projects():
    sec = _section(r'<section id="projects"')
    imgs = re.findall(r'(<img class="photo-show__img[^"]*"[^>]*>)', sec)
    caps = re.findall(r'<span class="photo-show__cap[^"]*" data-cap="\d+">(.*?)</span>', sec)
    if len(imgs) != len(caps):
        raise SystemExit(f"home: фото {len(imgs)}, подписей {len(caps)}")
    return {
        "projects_title": _h2(sec),
        "projects_lead": _text(_find(r'<p class="mt-4 max-w-xl[^"]*">(.*?)</p>', sec)),
        "projects_photos": [{"image": _img(_attr(tag, "src"), _attr(tag, "alt")), "caption": _text(cap), "alt": _attr(tag, "alt")}
                            for tag, cap in zip(imgs, caps)],
    }


def faq():
    sec = _section(r'<section [^>]*aria-labelledby="faq-title"')
    items = []
    for q, body in re.findall(r'<details[^>]*>\s*<summary>(.*?)</summary>(.*?)</details>', sec, re.S):
        items.append({"q": _text(q), "a": "\n\n".join(_text(p) for p in re.findall(r'<p>(.*?)</p>', body, re.S))})
    return {
        "faq_title": _h2(sec),
        "faq_lead": _text(_find(r'<p class="mt-5 max-w-sm[^"]*">(.*?)</p>', sec)),
        "faq": items,
    }


def request():
    sec = _section(r'<section id="request"')
    return {
        "request_title": _h2(sec),
        "request_lead": _text(_find(r'<p class="mt-5 max-w-md[^"]*">(.*?)</p>', sec)),
        "request_discount": _text(_find(r'<p class="discount[^"]*">(.*?)</p>', sec)),
    }


def pages():
    fields = {
        "seo_title": html.unescape(_find(r'<title>(.*?)</title>', B.INDEX)),
        "seo_description": html.unescape(_find(r'<meta name="description" content="([^"]*)"', B.INDEX)),
    }
    for part in (hero, catalog, services, production, steps, projects, faq, request):
        fields.update(part())
    return [{
        "path": "",
        "slug": "glavnaya",
        "title": "Главная",
        "template": "default",
        "front": True,
        "menu_order": 0,
        "fields": fields,
    }]
