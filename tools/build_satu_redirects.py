"""301 со старых адресов Satu (товары /p…, группы /g…) на разделы нового сайта.

Старые адреса — из выгрузки каталога Satu (content/products-all.json, satu-products.json, catalog-full.json).
Результат: wp-theme/expertecogroup/inc/satu-redirects-map.php (подключает inc/redirects.php).
Запуск: python3 tools/build_satu_redirects.py
"""
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "wp-theme/expertecogroup/inc/satu-redirects-map.php"

TANKS = "catalog/emkosti/"
GROUPS = {  # группа Satu → раздел нового сайта
    "g8379332": TANKS,
    "g3554721": TANKS + "pryamougolnaya-nazemnaya/",
    "g3555057": TANKS + "gorizontalnaya-podzemnaya/",
    "g8379420": TANKS + "vertikalnaya-nazemnaya/",
    "g8379421": TANKS + "gorizontalnaya-nazemnaya/",
    "g3551894": "catalog/kns/",
    "g7060086": "catalog/los/",
    "g3924942": "catalog/avtonomnye-ochistnye/", "g7163142": "catalog/avtonomnye-ochistnye/",
    "g8313220": "catalog/avtonomnye-ochistnye/", "g8313227": "catalog/avtonomnye-ochistnye/",
    "g3534369": "catalog/zhirouloviteli-bytovye/",
    "g3962071": "catalog/peskouloviteli-bytovye/", "g3962283": "catalog/peskouloviteli-bytovye/",
    "g3663050": "catalog/kompressory/", "g9644497": "catalog/kompressory/", "g9644496": "catalog/kompressory/",
    "g9644500": "catalog/kompressory/", "g9644503": "catalog/kompressory/",
    "g9618378": "catalog/zapchasti/", "g9618384": "catalog/zapchasti/", "g9618385": "catalog/zapchasti/",
    "g9590890": "catalog/biopreparaty/",
    "g9592107": "catalog/tehnofiltr/",
    "g3543665": "catalog/shkafy/",
    "g8594577": "catalog/",  # погружные насосы — раздел убран по правке клиента
    "g3970563": "catalog/futerovka/",
    "g3451246": "catalog/futerovka/",  # «Услуги по сервисному обслуживанию» — футеровка и ремонт ёмкостей
    "g3954389": "catalog/shefmontazh/",
}
TANK_SUB = {
    "Горизонтальные цилиндрические подземные": "gorizontalnaya-podzemnaya/",
    "Горизонтальные цилиндрические наземные": "gorizontalnaya-nazemnaya/",
    "Вертикальные цилиндрические наземные": "vertikalnaya-nazemnaya/",
    "Прямоугольные наземные": "pryamougolnaya-nazemnaya/",
}
CATEGORY = {
    "Канализационная насосная станция (кнс)": "catalog/kns/",
    "ЛОС (Локальное очистное сооружение)": "catalog/los/",
    "Септики": "catalog/avtonomnye-ochistnye/",
    "Компрессоры и системы аэрации воды": "catalog/kompressory/",
    "Запчасти для компрессора": "catalog/zapchasti/",
    "Биопрепараты": "catalog/biopreparaty/",
    "Технофильтр": "catalog/tehnofiltr/",
    "Электрические шкафы управления для КНС": "catalog/shkafy/",
    "Погружные насосы": "catalog/",
    "Облицовка, ремонт и реставрация резервуаров, емкостей, цистерн и баков": "catalog/futerovka/",
    "Шефмонтаж и пуско-наладочные работы": "catalog/shefmontazh/",
}


def product_target(p):
    name, cat = p["name"].lower(), p["category"]
    if cat == "Жироуловители":
        return "catalog/zhirouloviteli-bytovye/" if "под мойку" in name else "catalog/zhirouloviteli-promyshlennye/"
    if cat == "Пескоуловители":
        return "catalog/peskouloviteli-bytovye/" if "бытов" in name else "catalog/peskouloviteli-promyshlennye/"
    if cat == "Услуги по сервисному обслуживанию":  # на Satu там только футеровка и ремонт ёмкостей
        return "catalog/futerovka/"
    return CATEGORY[cat]


def pid(url):
    return re.search(r"/(p\d+)-", url).group(1)


def main():
    m = dict(GROUPS)
    for p in json.loads((ROOT / "content/products-all.json").read_text()):
        m[pid(p["url"])] = product_target(p)
    for p in json.loads((ROOT / "content/satu-products.json").read_text()):
        m[pid(p["url"])] = TANKS + TANK_SUB[p["subcategory"]]
    # проверка: все группы из выгрузки каталога учтены
    for g in json.loads((ROOT / "content/catalog-full.json").read_text()):
        for path in [g["path"]] + [s["path"] for s in g.get("subcategories", [])]:
            key = re.match(r"/(g\d+)", path).group(1)
            assert key in m, f"нет группы {path}"
    rows = "".join(f"    '{k}' => '{v}',\n" for k, v in sorted(m.items()))
    OUT.write_text("<?php\n// Собрано tools/build_satu_redirects.py — не править руками.\n"
                   "defined('ABSPATH') || exit;\n\nreturn [\n" + rows + "];\n")
    print(f"{len(m)} адресов → {OUT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
