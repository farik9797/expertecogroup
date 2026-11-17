"""Сравнивает <main> страницы темы WordPress с макетом: текст и разметку (теги + классы).

Нужны два локальных сервера: макет site/ на :5178 и WordPress на :8890.
Запуск: python3 tools/compare_wp.py catalog/kns/ [ещё пути...]   (пусто — главная)
Адреса ссылок и картинок не сравниваются (в WordPress они другие), атрибуты alt/aria — сравниваются.
"""
import difflib
import re
import sys
import urllib.request
from html.parser import HTMLParser

STATIC = "http://localhost:5178/"
import os
WP = os.environ.get("EEG_WP", "http://localhost:8890/")
SKIP_ATTRS = {"src", "srcset", "href", "style", "data-full", "width", "height", "sizes", "imagesrcset", "loading",
              "decoding", "fetchpriority", "id", "aria-describedby", "aria-labelledby", "aria-controls", "for"}


class Flat(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.depth = 0
        self.inside = False
        self.tags, self.text = [], []

    def handle_starttag(self, tag, attrs):
        if tag == "main":
            self.inside = True
            return
        if not self.inside:
            return
        if ("name", "website") in attrs:  # ловушка для ботов в форме заявки — есть только в теме
            return
        a = {k: (v or "") for k, v in attrs if k not in SKIP_ATTRS}
        cls = " ".join(sorted(a.pop("class", "").split()))
        rest = " ".join(f"{k}={v}" for k, v in sorted(a.items()))
        self.tags.append(f"{tag}.{cls} {rest}".rstrip())

    def handle_endtag(self, tag):
        if tag == "main":
            self.inside = False

    def handle_data(self, data):
        if self.inside:
            t = re.sub(r"\s+", " ", data).strip()
            if t:
                self.text.append(t)


def flat(url):
    html = urllib.request.urlopen(url).read().decode("utf-8")
    p = Flat()
    p.feed(html)
    return p


def show(name, a, b, limit=60):
    d = list(difflib.unified_diff(a, b, "макет", "wordpress", lineterm="", n=1))
    if not d:
        print(f"  {name}: совпадает ({len(a)})")
        return 0
    print(f"  {name}: РАЗЛИЧИЯ ({len(d)} строк diff)")
    for line in d[:limit]:
        print("    " + line[:220])
    return 1


def main():
    paths = sys.argv[1:] or [""]
    bad = 0
    for path in paths:
        path = path.strip("/")
        s_url = STATIC + (path + "/" if path else "index.html")
        w_url = WP + (path + "/" if path else "")
        print(f"/{path}")
        s, w = flat(s_url), flat(w_url)
        bad += show("текст", s.text, w.text)
        bad += show("разметка", s.tags, w.tags)
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
