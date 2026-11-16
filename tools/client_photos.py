"""Фото клиента: из исходников в content/client-photos собирает все версии для сайта
и наносит на каждую неброскую надпись «Expert ECO Group» (правка клиента № 9).

Запуск: python3 tools/client_photos.py   (нужен Pillow: python3 -m pip install pillow)

Исходники не меняются — надпись ставится только на версии в site/assets/img,
поэтому скрипт можно запускать сколько угодно раз. Чтобы заменить фото,
положите новый исходник под тем же именем и запустите скрипт заново.
"""
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont, ImageStat

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "content/client-photos"
IMG = ROOT / "site/assets/img"

FONTS = [
    "/System/Library/Fonts/Supplemental/Times New Roman Italic.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Italic.ttf",
    "C:/Windows/Fonts/timesi.ttf",
]
TEXT = "Expert ECO Group"


def font_path():
    for f in FONTS:
        if Path(f).exists():
            return f
    raise SystemExit("Не найден курсивный шрифт с засечками для надписи — добавьте путь в FONTS")


def watermark(im):
    """Надпись по центру кадра, как на снимках с производства: курсив с засечками,
    полупрозрачная, с лёгким тиснением. Цвет подбирается под яркость фона."""
    im = im.convert("RGB")
    w, h = im.size
    target = min(0.24 * max(w, h), 0.6 * w)
    size = 10
    font = ImageFont.truetype(font_path(), size)
    while font.getlength(TEXT) < target:
        size += 2
        font = ImageFont.truetype(font_path(), size)
    l, t, r, b = font.getbbox(TEXT)
    x = (w - (r - l)) / 2 - l
    y = (h - (b - t)) / 2 - t
    area = im.crop((int(x + l), int(y + t), int(x + r), int(y + b))).convert("L")
    light_bg = ImageStat.Stat(area).mean[0] > 150
    ink, glow = ((45, 45, 45), (255, 255, 255)) if light_bg else ((255, 255, 255), (20, 20, 20))
    off = max(1, size // 40)

    layer = Image.new("RGBA", im.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.text((x, y + off), TEXT, font=font, fill=glow + (56,))
    d.text((x, y), TEXT, font=font, fill=ink + (100,))
    return Image.alpha_composite(im.convert("RGBA"), layer).convert("RGB")


def fit(im, long_side=None, width=None):
    if width:
        scale = width / im.width
    else:
        scale = long_side / max(im.size)
    if scale >= 1:
        return im
    return im.resize((round(im.width * scale), round(im.height * scale)), Image.Resampling.LANCZOS)


def crop43(im, anchor="center"):
    """Кадр 4:3 из широкого снимка: по центру, слева или справа."""
    cw = round(im.height * 4 / 3)
    if cw >= im.width:
        return im
    x0 = {"left": 0, "right": im.width - cw}.get(anchor, (im.width - cw) // 2)
    return im.crop((x0, 0, x0 + cw, im.height))


def pad43(im, color):
    """Вертикальный кадр на ровном фоне — расширяем фон до 4:3, объект остаётся целым."""
    cw = round(im.height * 4 / 3)
    canvas = Image.new("RGB", (cw, im.height), color)
    canvas.paste(im, ((cw - im.width) // 2, 0))
    return canvas


def photo_set(name):
    """Фото для галерей и альбомов: основной кадр и две уменьшенные копии по длинной стороне."""
    return [(f"photo/{name}.webp", {"long_side": 1280}),
            (f"photo/{name}-md.webp", {"long_side": 900}),
            (f"photo/{name}-sm.webp", {"long_side": 520})]


# исходник → [(кадрирование, [(версия на сайте, размер)])]
JOBS = {
    **{f"{n}.jpg": [(None, photo_set(n))] for n in (
        "gorizontalnaya-krupnaya", "gorizontalnaya-tral", "gorizontalnye-u-tseha", "montazh-podzemnoy-1",
        "montazh-podzemnoy-2", "otgruzka-vertikalnyh", "podzemnye-ploschadka-2", "vertikalnaya-lyuk-1",
        "vertikalnaya-obekt-1", "vertikalnye-nasosnaya", "vertikalnye-osnovanie")},
    "pryamougolnaya-nasosnaya.jpg": [(None, photo_set("pryamougolnaya-nasosnaya"))],
    "los-montazh-kran.webp": [(None, [("los-montazh-1400.webp", {"width": 1400}), ("los-montazh-800.webp", {"width": 800}),
                                      ("catalog/other/uslugi-shefmontazh.webp", {"width": 1400})])],
    "scheme-project.webp": [(None, [("scheme-project-1400.webp", {"width": 1400}), ("scheme-project-800.webp", {"width": 800}),
                                    ("catalog/other/uslugi-proektirovanie.webp", {"width": 1400})])],
    "los-razrez.webp": [(None, [(f"hero-los-razrez-{w}.webp", {"width": w}) for w in (1672, 1280, 800)])],
    "kns-kotlovan.jpg": [(None, [(f"hero-kns-kotlovan-{w}.webp", {"width": w}) for w in (1672, 1280, 800)])],
    "ochistnye-stanciya.jpg": [
        (None, [("hero-ochistnye-1280.webp", {"width": 1280}), ("hero-ochistnye-800.webp", {"width": 800}),
                ("catalog/other/ochistnye-stanciya.webp", {"width": 1280})] + photo_set("ochistnye-stanciya")),
        ("crop43", [("cat-ochistnye.webp", {"width": 768})]),
    ],
    "zhiroulovitel-promyshlennyj.jpg": [("pad43", [("catalog/other/zhiroulovitel-promyshlennyj-1200.webp", {"width": 1200})])],
    "servis.jpg": [
        (None, [("servis-1400.webp", {"width": 1400}), ("servis-800.webp", {"width": 800})]),
        ("crop43-left", [("catalog/other/servis-aos-1280.webp", {"width": 1280})]),    # люки станций
        ("crop43-right", [("catalog/other/uslugi-servis.webp", {"width": 1280})]),     # проверка автоматики
    ],
}

PREP = {
    None: lambda im: im,
    "crop43": crop43,
    "crop43-left": lambda im: crop43(im, "left"),
    "crop43-right": lambda im: crop43(im, "right"),
    "pad43": lambda im: pad43(im, (247, 247, 247)),
}


def main():
    for src, variants in JOBS.items():
        base = Image.open(SRC / src).convert("RGB")
        for prep, outputs in variants:
            master = watermark(PREP[prep](base))
            for rel, size in outputs:
                dest = IMG / rel
                dest.parent.mkdir(parents=True, exist_ok=True)
                fit(master, **size).save(dest, "WEBP", quality=84, method=6)
                print(f"{rel:52} {Image.open(dest).size}")


if __name__ == "__main__":
    main()
