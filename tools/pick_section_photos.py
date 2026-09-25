"""Отбирает по 6 непохожих фото для каждого раздела каталога.

В выгрузке с Satu у разных позиций часто одна и та же картинка, поэтому сравниваем
изображения по average hash и оставляем только визуально разные.

Запуск: python3 tools/pick_section_photos.py → content/section-photos.json
"""
import json
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
IMG_DIR = ROOT / "site/assets/img/catalog/other"
LIMIT = 6


def ahash(path, size=12):
    im = Image.open(path).convert("L").resize((size, size), Image.LANCZOS)
    px = list(im.tobytes())
    avg = sum(px) / len(px)
    return sum(1 << i for i, p in enumerate(px) if p > avg)


def distance(a, b):
    return bin(a ^ b).count("1")


def main():
    out = {}
    for cat in json.loads((ROOT / "content/catalog-other.json").read_text()):
        picked, hashes = [], []
        for p in cat["products"]:
            img = p.get("image")
            if not img or not (IMG_DIR / img).exists():
                continue
            h = ahash(IMG_DIR / img)
            if any(distance(h, o) <= 8 for o in hashes):  # почти одинаковые кадры пропускаем
                continue
            hashes.append(h)
            picked.append({"image": img, "alt": p["name"]})
            if len(picked) >= LIMIT:
                break
        out[cat["slug"]] = picked
        print(f'{cat["slug"]:16} {len(picked)} фото')
    (ROOT / "content/section-photos.json").write_text(json.dumps(out, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
