"""Раздел «Ёмкости»: /catalog/emkosti/ и четыре страницы типов (TANK_TYPES генератора).

Данные типа — из B.tank_type(); тексты, которые в генераторе записаны прямо в разметке
(лид и плашки, вводные абзацы, характеристики, title/description), — из HTML,
который отдают B.tanks_page() и B.tank_type_page(), чтобы не перепечатывать их руками.
"""
import re

from wp_export.common import B, img, img_name, facts

PURPOSE = "Ёмкости"
# форма корпуса в калькуляторе (TANK_TYPES.shape) → форма ряда в каталоге (series.shape) для таблицы размеров
SERIES_SHAPE = {"ground": "cyl", "under": "cyl", "vert": "vert", "rect": "rect"}


def _one(pattern, html, what):
    m = re.search(pattern, html, re.S)
    if not m:
        raise SystemExit(f"tanks: не найдено в макете — {what}")
    return m.group(1)


def _seo(html):
    return {"seo_title": _one(r"<title>(.*?)</title>", html, "title"),
            "seo_description": _one(r'<meta name="description" content="(.*?)">', html, "description")}


def _facts(html):
    ul = _one(r'<ul class="page-hero__facts[^"]*"[^>]*>(.*?)</ul>', html, "плашки")
    return facts(re.findall(r"<li><b>(.*?)</b><span>(.*?)</span></li>", ul))


def _num(x):
    return "" if x is None else x


def tanks_page():
    html = B.tanks_page()
    hero = re.search(r'<h1 class="section-title">(.*?)</h1>\s*<p class="mt-5[^"]*">(.*?)</p>', html, re.S)
    intro = _one(r'<section class="px-4 pb-6 md:px-6">(.*?)</section>', html, "вводная часть")
    paras = re.findall(r'<p class="mt-4">(.*?)</p>', intro)
    photo, alt = re.search(r'src="[./]*assets/img/([^"]+)" alt="([^"]*)"', intro).groups()
    return {
        "path": "catalog/emkosti",
        "title": hero.group(1),
        "template": "page-templates/tanks.php",
        "menu_order": 30,
        "fields": {
            "crumb": "Ёмкости и резервуары",
            "head_lead": hero.group(2),
            "facts": _facts(html),
            "purpose": PURPOSE,
            **_seo(html),
            "tanks_intro": "\n\n".join(paras),
            "tanks_intro_img": img(photo, alt),
            "tanks_intro_alt": alt,
        },
    }


def type_page(t, order):
    s = B.tank_type(t)
    if SERIES_SHAPE[t["shape"]] != s["shape"]:
        raise SystemExit(f'tanks: форма {t["shape"]} не совпадает с рядом {s["shape"]} ({t["slug"]})')
    html = B.tank_type_page(t)
    dl = _one(r'<dl class="spec mt-6">(.*?)</dl>', html, "характеристики")
    # строку «Маркировка» шаблон собирает из повторителя маркировок
    specs = [{"name": k, "value": "" if k == "Маркировка" else v}
             for k, v in re.findall(r"<div><dt>(.*?)</dt><dd>(.*?)</dd></div>", dl)]
    return {
        "path": f'catalog/emkosti/{t["slug"]}',
        "title": s["title"],
        "template": "page-templates/tank-type.php",
        "menu_order": order,
        "fields": {
            "crumb": s["short"],
            "head_lead": s["lead"],
            "facts": _facts(html),
            "purpose": PURPOSE,
            **_seo(html),
            "tank_marks": [{"code": c, "what": w} for c, w in s["marks"]],
            "tank_gallery": [img_name(n) for n in s["images"]],
            "tank_specs": specs,
            "tank_text": "\n\n".join(s["text"]),
            "tank_uses": [{"text": u} for u in s["uses"]],
            "tank_shape": t["shape"],
            "tank_code": s["code"],
            "tank_sizes": [{"v": r["v"], "l": _num(r["L"]), "w": _num(r["W"]), "h": _num(r["H"]), "weight": _num(r["weight"])}
                           for r in s["table"]],
        },
    }


def pages():
    return [tanks_page()] + [type_page(t, i) for i, t in enumerate(B.TANK_TYPES, 1)]
