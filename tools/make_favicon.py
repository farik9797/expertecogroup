"""Фавикон из эмблемы логотипа (капля, лист, ключ — без надписи, на 16–32 px её не прочитать).

Источник: site/assets/img/logo-eeg.webp (338×347, белый фон). Результат: site/assets/img/favicon/
(favicon.ico 16/32/48, favicon-32.png, favicon-192.png, apple-touch-icon.png 180). favicon.ico кладётся
ещё и в корень сайта на хостинге — туда браузеры и поисковики идут без подсказок.
Запуск: python3 tools/make_favicon.py  (нужен Pillow)
"""
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "site/assets/img/logo-eeg.webp"
OUT = ROOT / "site/assets/img/favicon"
EMBLEM_BOTTOM = 240  # ниже — надпись «EEG Expert ECO Group»


def emblem():
    im = Image.open(SRC).convert("RGB")
    top = im.crop((0, 0, im.width, EMBLEM_BOTTOM))
    mask = top.convert("L").point(lambda v: 255 if v < 235 else 0)
    box = mask.getbbox()
    top = top.crop(box)
    side = round(max(top.size) * 1.08)  # небольшой отступ, чтобы капля не упиралась в край
    sq = Image.new("RGB", (side, side), "white")
    sq.paste(top, ((side - top.width) // 2, (side - top.height) // 2))
    return sq


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    sq = emblem()
    sq.resize((32, 32), Image.Resampling.LANCZOS).save(OUT / "favicon-32.png", optimize=True)
    sq.resize((192, 192), Image.Resampling.LANCZOS).save(OUT / "favicon-192.png", optimize=True)
    sq.resize((180, 180), Image.Resampling.LANCZOS).save(OUT / "apple-touch-icon.png", optimize=True)
    sq.resize((48, 48), Image.Resampling.LANCZOS).save(OUT / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])
    for f in sorted(OUT.iterdir()):
        print(f.name, f.stat().st_size, "байт")


if __name__ == "__main__":
    main()
