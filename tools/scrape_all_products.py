"""Собирает карточки всех остальных разделов каталога Satu (КНС, ЛОС, септики, компрессоры и т.д.).

Цены не сохраняются. Фото кладутся в content/satu-images/ (по одному-двум на товар).
Результат: content/products-all.json

Запуск: python3 tools/scrape_all_products.py
"""
import json
import re
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

from bs4 import BeautifulSoup

ROOT = Path(__file__).resolve().parent.parent
IMG_DIR = ROOT / "content/satu-images"
UA = {"User-Agent": "Mozilla/5.0"}
PRICE_RE = re.compile(r"(₸|тенге|\bтг\b|цен[аыуе])", re.I)

# разделы, которые уже собраны подробно скриптом scrape_satu.py
SKIP = {"g8379332-yomkosti-rezervuary-polipropilena"}


def get(url, tries=3):
    for i in range(tries):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=40) as r:
                return BeautifulSoup(r.read().decode("utf-8"), "html.parser")
        except Exception:
            if i == tries - 1:
                raise
            time.sleep(2)


def parse(url):
    s = get(url)
    name_el = s.select_one('[data-qaid="product_name"]')
    if not name_el:
        return None
    name = re.sub(r"\s+", " ", name_el.get_text(" ", strip=True))
    attrs, group = {}, None
    for row in s.select("table.b-product-info tr"):
        th = row.select_one('[data-qaid="attributes_group"]')
        if th:
            group = th.get_text(strip=True)
            continue
        n, v = row.select_one('[data-qaid="attribute_name"]'), row.select_one('[data-qaid="attribute_value"]')
        if n and v:
            attrs.setdefault(group or "Прочее", {})[n.get_text(" ", strip=True)] = v.get_text(" ", strip=True)
    d = s.select_one('[data-qaid="product_description"]')
    desc = d.get_text("\n", strip=True) if d else ""
    images = []
    for img in s.select("img[src*='images.satu.kz']"):
        alt = img.get("alt", "")
        if alt.startswith(name[:20]) and "фото" in alt:
            src = re.sub(r"_w\d+_h\d+", "", img["src"])
            if src not in images:
                images.append(src)
    return {
        "url": url, "name": name, "attributes": attrs,
        "description": desc, "images": images,
        "price_in_description": bool(PRICE_RE.search(desc)),
    }


def download(url):
    name = re.sub(r"[^\w.-]", "_", url.split("/")[-1].split("?")[0])
    dest = IMG_DIR / name
    if dest.exists():
        return name
    try:
        with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=40) as r:
            dest.write_bytes(r.read())
        return name
    except Exception:
        return None


def main():
    catalog = json.loads((ROOT / "content/catalog-full.json").read_text())
    todo = []
    for cat in catalog:
        if cat["path"].lstrip("/") in SKIP:
            continue
        for p in cat["products"]:
            todo.append((cat["title"], cat["path"], p["url"]))

    print(f"товаров к сбору: {len(todo)}")
    with ThreadPoolExecutor(6) as ex:
        parsed = list(ex.map(lambda t: parse(t[2]), todo))

    items = []
    for (cat_title, cat_path, url), data in zip(todo, parsed):
        if not data:
            continue
        data["category"] = cat_title
        data["category_path"] = cat_path
        items.append(data)

    # качаем по два фото на товар
    img_urls = [u for it in items for u in it["images"][:2]]
    IMG_DIR.mkdir(parents=True, exist_ok=True)
    with ThreadPoolExecutor(8) as ex:
        list(ex.map(download, img_urls))

    (ROOT / "content/products-all.json").write_text(json.dumps(items, ensure_ascii=False, indent=2))
    with_price = [i["name"] for i in items if i["price_in_description"]]
    print(f"собрано карточек: {len(items)}, скачано фото: {len(set(img_urls))}")
    print("упоминания цен в описаниях:", with_price or "нет")


if __name__ == "__main__":
    main()
