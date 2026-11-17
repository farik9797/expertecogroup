"""Выгрузка контента макета для темы WordPress → wp-theme/expertecogroup/import/content.json.

Каждый модуль tools/wp_export/<раздел>.py отдаёт pages() — список страниц с полями.
Запуск: python3 tools/export_wp.py
"""
import importlib
import json
import pkgutil
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE))
from wp_export import common  # noqa: E402

OUT = HERE.parent / "wp-theme/expertecogroup/import/content.json"


def main():
    import argparse
    import wp_export
    ap = argparse.ArgumentParser()
    ap.add_argument("--only", help="модули через запятую (common выгружается всегда)")
    ap.add_argument("--out", help="куда записать JSON (по умолчанию import/content.json темы)")
    args = ap.parse_args()
    only = set(args.only.split(",")) | {"common"} if args.only else None
    out = Path(args.out) if args.out else OUT
    pages = []
    for m in sorted(pkgutil.iter_modules(wp_export.__path__), key=lambda m: m.name):
        if only and m.name not in only:
            continue
        mod = importlib.import_module(f"wp_export.{m.name}")
        if hasattr(mod, "pages"):
            got = mod.pages()
            pages += got
            print(f"{m.name:10} {len(got)} стр.")
    paths = [p["path"] for p in pages]
    dup = {p for p in paths if paths.count(p) > 1}
    if dup:
        raise SystemExit(f"страницы с одинаковым адресом: {dup}")
    # title/description поверх генератора — правки SEO (content/seo-meta.json)
    seo = json.loads((HERE.parent / "content/seo-meta.json").read_text())
    for p in pages:
        o = seo.get(p["path"].strip("/"), {})
        if "title" in o:
            p["fields"]["seo_title"] = o["title"]
        if "description" in o:
            p["fields"]["seo_description"] = o["description"]
    data = {"options": common.options(), "pages": pages}
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, ensure_ascii=False, indent=1))
    print(f"всего {len(pages)} страниц → {out}")


if __name__ == "__main__":
    main()
