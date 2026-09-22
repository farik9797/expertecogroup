"""Собирает ёмкости с текущего сайта клиента (Satu.kz) в content/satu-products.json и .md.
Цены НЕ сохраняются (требование клиента). Запуск: python3 tools/scrape_satu.py"""
import json, re, time, urllib.request
from pathlib import Path
from bs4 import BeautifulSoup

BASE = "https://expertecogroup.kz"
ROOT = Path(__file__).resolve().parent.parent
SUBCATS = {
    "g3554721-rezervuary-yomkosti-pryamougolnye": "Прямоугольные наземные",
    "g3555057-rezervuary-yomkosti-gorizontalnye": "Горизонтальные цилиндрические подземные",
    "g8379420-rezervuary-yomkosti-vertikalnye": "Вертикальные цилиндрические наземные",
    "g8379421-rezervuary-yomkosti-gorizontalnye": "Горизонтальные цилиндрические наземные",
}
MAIN = "g8379332-yomkosti-rezervuary-polipropilena"
PRICE_RE = re.compile(r"(₸|тенге|\bтг\b|цен[аыуе])", re.I)


def get(url):
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, timeout=30) as r:
        return BeautifulSoup(r.read().decode("utf-8"), "html.parser")


def product_links(path):
    urls, page = [], 1
    while True:
        s = get(f"{BASE}/{path}" + (f"/page_{page}" if page > 1 else ""))
        found = [a["href"] for a in s.select("a[href]") if re.search(r"/p\d+-[^/]+\.html$", a["href"])]
        found = [u if u.startswith("http") else BASE + u for u in found]
        new = [u for u in dict.fromkeys(found) if u not in urls]
        if not new:
            break
        urls += new
        page += 1
        time.sleep(0.5)
    return urls


def parse_product(url):
    s = get(url)
    name = s.select_one('[data-qaid="product_name"]').get_text(" ", strip=True)
    name = re.sub(r"\s+", " ", name)
    attrs, group = {}, None
    for row in s.select('table.b-product-info tr'):
        th = row.select_one('[data-qaid="attributes_group"]')
        if th:
            group = th.get_text(strip=True); continue
        n, v = row.select_one('[data-qaid="attribute_name"]'), row.select_one('[data-qaid="attribute_value"]')
        if n and v:
            attrs.setdefault(group or "Прочее", {})[n.get_text(" ", strip=True)] = v.get_text(" ", strip=True)
    d = s.select_one('[data-qaid="product_description"]')
    desc = d.get_text("\n", strip=True) if d else ""
    imgs = []
    for img in s.select("img[src*='images.satu.kz']"):
        alt = img.get("alt", "")
        if alt.startswith(name[:25]) and "фото" in alt:
            src = re.sub(r"_w\d+_h\d+", "", img["src"])
            if src not in imgs:
                imgs.append(src)
    return {
        "url": url, "name": name, "attributes": attrs, "description": desc,
        "images": imgs, "price_mentions_in_description": bool(PRICE_RE.search(desc)),
    }


def main():
    sub_of = {}
    for path, title in SUBCATS.items():
        for u in product_links(path):
            sub_of.setdefault(u, title)
    all_urls = list(dict.fromkeys(product_links(MAIN) + list(sub_of)))
    items = []
    for u in all_urls:
        p = parse_product(u)
        p["subcategory"] = sub_of.get(u, "—")
        n = p["name"].lower()
        p["purpose"] = "химические" if "хим" in n else ("пожарные" if "пожар" in n else "вода")
        items.append(p)
        time.sleep(0.4)
    (ROOT / "content/satu-products.json").write_text(json.dumps(items, ensure_ascii=False, indent=2))
    lines = ["# Ёмкости с текущего сайта (Satu.kz) — без цен", "",
             f"Всего: {len(items)}. Источник: {BASE}/{MAIN}", "",
             "| # | Назначение | Тип | Название | Д×Ш×В / размеры, мм | Вес | Фото | Цена в описании? |",
             "|---|---|---|---|---|---|---|---|"]
    for i, p in enumerate(items, 1):
        dims = p["attributes"].get("Габаритные размеры", {})
        dim = " × ".join(dims.get(k, "") for k in ("Длина", "Ширина", "Высота") if dims.get(k)) or \
              ", ".join(f"{k} {v}" for k, v in dims.items()) or "—"
        w = p["attributes"].get("Основные атрибуты", {}).get("Вес", "—")
        lines.append(f"| {i} | {p['purpose']} | {p['subcategory']} | [{p['name']}]({p['url']}) | {dim} | {w} | {len(p['images'])} | {'⚠️ да' if p['price_mentions_in_description'] else 'нет'} |")
    (ROOT / "content/satu-products.md").write_text("\n".join(lines) + "\n")
    print(len(items), "products")


if __name__ == "__main__":
    main()
