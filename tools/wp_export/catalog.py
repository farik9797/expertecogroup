"""Каталог: страница /catalog/ и разделы /catalog/<раздел>/ из OTHER (ёмкости catalog/emkosti/ — отдельный модуль).

Тексты шапки и SEO зашиты в вызовах page_head()/layout() внутри catalog_page()/section_page() генератора —
перехватываем аргументы этих вызовов, чтобы не перепечатывать тексты руками.
"""
import re
from contextlib import contextmanager

from wp_export.common import B, img, facts


@contextmanager
def _captured():
    """Аргументы page_head() и layout(), с которыми генератор собирает страницу: got['head'], got['layout']."""
    got = {}
    orig_head, orig_layout = B.page_head, B.layout

    def head(**kw):
        got["head"] = kw
        return orig_head(**kw)

    def layout(**kw):
        got["layout"] = kw
        return orig_layout(**kw)

    B.page_head, B.layout = head, layout
    try:
        yield got
    finally:
        B.page_head, B.layout = orig_head, orig_layout


def _head_fields(got):
    """Общие поля шапки и SEO из перехваченных вызовов."""
    h, lay = got["head"], got["layout"]
    out = {"head_lead": h["lead"], "facts": facts(h["facts"]),
           "seo_title": lay["title"], "seo_description": lay["description"]}
    if lay.get("purpose"):
        out["purpose"] = lay["purpose"]
    return out


def _catalog():
    with _captured() as got:
        html = B.catalog_page()
    # группы плиток в порядке макета: якорь секции и её заголовок
    groups = re.findall(r'<section class="px-4 py-8 md:px-6" id="([^"]*)">\s*<div class="mx-auto max-w-7xl">\s*'
                        r'<h2 [^>]*>([^<]+)</h2>', html)
    fields = {
        "head_title": got["head"]["title"],
        **_head_fields(got),
        "catalog_directions": [
            {"title": title, "link": href, "tag": tag, "text": text,
             "image": img(path.removeprefix("assets/img/"), title)}
            for title, href, path, text, tag in B.DIRECTIONS
        ],
        "catalog_groups": [{"title": t, "anchor": a} for a, t in groups],
    }
    page = {"path": "catalog", "title": "Каталог", "template": "page-templates/catalog.php",
            "menu_order": 10, "fields": fields}
    return page, {t: a for a, t in groups}


def _section(cat, order, group_anchor):
    slug = cat["slug"]
    meta = B.SECTIONS[slug]
    with _captured() as got:
        B.section_page(cat)
    # фото раздела, как в section_page: присланные клиентом, затем выгрузка с Satu; первое — обложка
    photos = [(img(n, a), a) for n, a in meta.get("photos", [])]
    photos += [(img(f'catalog/other/{p["image"]}', p["alt"]), p["alt"]) for p in B.SECTION_PHOTOS.get(slug, [])]
    first = cat["products"][0]
    tile_img = img(f'catalog/other/{first["image"]}', first.get("name", "")) if first.get("image") else img("cat-water.webp")

    fields = {
        **_head_fields(got),
        "section_group": group_anchor,
        "section_cover": photos[0][0] if photos else "",
        "section_tile_image": tile_img,
        "section_text": "\n\n".join(meta["text"]),
        "section_points": [{"icon": i, "title": t, "text": x} for i, t, x in meta["points"]],
        "section_uses": [{"text": u} for u in meta["uses"]],
        "section_photos": [{"image": im, "alt": a} for im, a in photos[1:]],
    }
    if meta.get("sizes"):
        mark = re.search(r'<th rowspan="2">([^<]+)</th>', B.sizes_table(meta["sizes"])).group(1)
        fields["section_sizes_mark"] = mark
        fields["section_sizes"] = [
            {"mark": m, "dims": d, "q": q, "ls": ls, "salvo": z, "inlet": i, "outlet": o, "dn": dn}
            for m, d, q, ls, z, i, o, dn in B.BYT_SIZES
        ]
    return {"path": f"catalog/{slug}", "title": got["head"]["title"], "template": "page-templates/section.php",
            "menu_order": order, "fields": fields}


def pages():
    catalog, anchors = _catalog()
    return [catalog] + [_section(c, (n + 1) * 10, anchors[c["group"]]) for n, c in enumerate(B.OTHER)]
