"""Полная опись каталога клиента на Satu.kz: все категории и товары, БЕЗ ЦЕН.

Результат: content/catalog-full.json и content/catalog-full.md
Запуск: python3 tools/scrape_catalog.py
"""
import json
import re
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

from bs4 import BeautifulSoup

BASE = "https://expertecogroup.kz"
ROOT = Path(__file__).resolve().parent.parent
UA = {"User-Agent": "Mozilla/5.0"}
PROD_RE = re.compile(r"/p\d+-[^/]+\.html$")
CAT_RE = re.compile(r"^/g\d+-")


def get(url, tries=3):
    for i in range(tries):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=40) as r:
                return BeautifulSoup(r.read().decode("utf-8"), "html.parser")
        except Exception:
            if i == tries - 1:
                raise
            time.sleep(2)


def abs_url(href):
    return href if href.startswith("http") else BASE + href


def categories():
    """Дерево разделов каталога: верхний уровень + подкатегории внутри каждого."""
    soup = get(f"{BASE}/product_list")
    top = {}
    for a in soup.select("a[href]"):
        href = a["href"].replace(BASE, "")
        if CAT_RE.match(href) and a.get_text(strip=True):
            top.setdefault(href.split("?")[0], a.get_text(" ", strip=True))
    return top


def listing(path):
    """Товары раздела со всех страниц пагинации + вложенные подкатегории."""
    items, subs, page = {}, {}, 1
    while page <= 40:
        soup = get(f"{BASE}{path}" + (f"/page_{page}" if page > 1 else ""))
        found = {}
        for a in soup.select("a[href]"):
            href = a["href"].replace(BASE, "").split("?")[0]
            if PROD_RE.search(href):
                name = a.get("title") or a.get_text(" ", strip=True)
                if name:
                    found[abs_url(href)] = re.sub(r"\s+", " ", name).strip()
            elif CAT_RE.match(href) and href != path and a.get_text(strip=True):
                subs.setdefault(href, a.get_text(" ", strip=True))
        new = {k: v for k, v in found.items() if k not in items}
        if not new:
            break
        items.update(new)
        page += 1
        time.sleep(0.25)
    return items, subs


def main():
    top = categories()
    print(f"разделов верхнего уровня: {len(top)}")
    result = []
    with ThreadPoolExecutor(5) as ex:
        for (path, title), (items, subs) in zip(top.items(), ex.map(lambda p: listing(p), top)):
            result.append({
                "path": path, "title": title, "url": BASE + path,
                "count": len(items),
                "subcategories": [{"path": k, "title": v} for k, v in subs.items() if k not in top],
                "products": [{"url": u, "name": n} for u, n in items.items()],
            })
            print(f"{len(items):4d}  {title}")

    (ROOT / "content/catalog-full.json").write_text(json.dumps(result, ensure_ascii=False, indent=2))

    total = sum(c["count"] for c in result)
    uniq = {p["url"] for c in result for p in c["products"]}
    lines = [
        "# Каталог Expert ECO Group на Satu.kz — полная опись (без цен)",
        "",
        f"Источник: {BASE}/product_list · разделов: {len(result)} · позиций всего: {total} "
        f"(уникальных URL: {len(uniq)})",
        "",
        "| # | Раздел | Товаров | Подразделы |",
        "|---|---|---|---|",
    ]
    for i, c in enumerate(sorted(result, key=lambda x: -x["count"]), 1):
        subs = ", ".join(s["title"] for s in c["subcategories"]) or "—"
        lines.append(f"| {i} | [{c['title']}]({c['url']}) | {c['count']} | {subs} |")
    for c in sorted(result, key=lambda x: -x["count"]):
        lines += ["", f"## {c['title']} ({c['count']})", ""]
        lines += [f"- [{p['name']}]({p['url']})" for p in c["products"]] or ["- (пусто)"]
    (ROOT / "content/catalog-full.md").write_text("\n".join(lines) + "\n")
    print(f"\nвсего позиций: {total}, уникальных: {len(uniq)}")


if __name__ == "__main__":
    main()
