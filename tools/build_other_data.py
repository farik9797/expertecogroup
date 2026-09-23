"""Готовит разделы каталога, кроме ёмкостей: КНС, ЛОС, септики, компрессоры и т.д.

Читает content/products-all.json, выбирает фото и ключевые характеристики,
кладёт сжатые картинки в site/assets/img/catalog/other/.
Результат: content/catalog-other.json

Запуск: python3 tools/build_other_data.py
"""
import json
import re
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
IMG_SRC = ROOT / "content/satu-images"
IMG_OUT = ROOT / "site/assets/img/catalog/other"

# путь на Satu → (slug, название раздела, группа, описание)
CATS = {
    "/g3551894-kanalizatsionnaya-nasosnaya-stantsiya": (
        "kns", "Канализационные насосные станции", "Очистные сооружения",
        "КНС из полипропилена для приёма и перекачки хозяйственно-бытовых, ливневых и производственных стоков. Комплектуем насосами, трубопроводной обвязкой и шкафом управления под производительность объекта."),
    "/g7060086-los-lokalnoe-ochistnoe": (
        "los", "Локальные очистные сооружения", "Очистные сооружения",
        "ЛОС для ливневых и хозяйственно-бытовых стоков: песко-нефтеуловители, комбинированные установки и станции очистки. Подбираем по расходу в литрах в секунду и требуемой степени очистки."),
    "/g3924942-septiki": (
        "septiki", "Септики", "Очистные сооружения",
        "Септики для домов и объектов без центральной канализации: собственного производства, а также станции биологической очистки Юнилос и Евролос."),
    "/g3534369-zhirouloviteli": (
        "zhirouloviteli", "Жироуловители", "Очистные сооружения",
        "Жироуловители под мойку и промышленные — для ресторанов, кондитерских, мясоперерабатывающих и пищевых цехов. Задерживают жир и остатки пищи до попадания в канализацию."),
    "/g3962071-peskouloviteli": (
        "peskouloviteli", "Пескоуловители", "Очистные сооружения",
        "Пескоуловители для ливневых очистных сооружений, моек и площадок: задерживают песок и нерастворимые минеральные частицы."),
    "/g3663050-kompressory-sistemy-aeratsii": (
        "kompressory", "Компрессоры и системы аэрации", "Оборудование",
        "Мембранные компрессоры HIBLOW, AirMac и Jecod, а также системы аэрации для очистных сооружений, прудов и водоёмов."),
    "/g9618378-zapchasti-dlya-kompressora": (
        "zapchasti", "Запчасти для компрессоров", "Оборудование",
        "Мембраны, ремкомплекты и системы защиты для компрессоров HIBLOW и AirMac."),
    "/g8594577-pogruzhnye-nasosy": (
        "nasosy", "Погружные насосы", "Оборудование",
        "Погружные насосы для перекачки сточных, дренажных и загрязнённых вод."),
    "/g3543665-elektricheskie-shkafy-upravleniya": (
        "shkafy", "Шкафы управления", "Оборудование",
        "Электрические шкафы управления для канализационных насосных станций и очистных сооружений."),
    "/g9590890-biopreparaty": (
        "biopreparaty", "Биопрепараты", "Оборудование",
        "Биобактерии для септиков и станций биологической очистки: запуск системы и поддержание работы зимой."),
    "/g9592107-tehnofiltr": (
        "tehnofiltr", "Фильтроэлементы «Технофильтр»", "Оборудование",
        "Патронные, капсульные и мешочные фильтроэлементы для очистки жидкостей и газов на производстве."),
    "/g3451246-uslugi-servisnomu-obsluzhivaniyu": ("uslugi", "Услуги", "Услуги", ""),
    "/g3954389-shefmontazh-pusko-naladochnye": ("uslugi", "Услуги", "Услуги", ""),
    "/g3970563-oblitsovka-remont-restavratsiya": ("uslugi", "Услуги", "Услуги", ""),
}
USLUGI_LEAD = ("Пусконаладка, шефмонтаж и сервисное обслуживание оборудования, футеровка и ремонт бетонных, "
               "стальных и полипропиленовых резервуаров. Работаем по Казахстану и странам СНГ.")

SPEC_KEYS = ["Производительность", "Объем", "Объём", "Мощность", "Напор", "Расход", "Производитель",
             "Тип", "Назначение", "Материал", "Бренд", "Страна производитель", "Диаметр", "Вес"]


def pick_specs(attrs, limit=3):
    flat = {}
    for group in attrs.values():
        flat.update(group)
    out = []
    for key in SPEC_KEYS:
        for k, v in flat.items():
            if k.lower().startswith(key.lower()) and (k, v) not in out and len(v) < 42:
                out.append((k, v))
                break
        if len(out) >= limit:
            break
    return out[:limit]


def prepare_image(url):
    name = re.sub(r"[^\w.-]", "_", url.split("/")[-1].split("?")[0])
    src = IMG_SRC / name
    if not src.exists():
        return None
    out = IMG_OUT / (src.stem + ".webp")
    if not out.exists():
        im = Image.open(src).convert("RGB")
        w = min(560, im.width)
        im = im.resize((w, round(im.height * w / im.width)), Image.LANCZOS)
        im.save(out, "WEBP", quality=78, method=6)
    return out.name


def main():
    IMG_OUT.mkdir(parents=True, exist_ok=True)
    items = json.loads((ROOT / "content/products-all.json").read_text())
    cats = {}
    for it in items:
        meta = CATS.get(it["category_path"])
        if not meta:
            continue
        slug, title, group, lead = meta
        cat = cats.setdefault(slug, {"slug": slug, "title": title, "group": group,
                                     "lead": USLUGI_LEAD if slug == "uslugi" else lead, "products": []})
        img = next((prepare_image(u) for u in it["images"][:2] if prepare_image(u)), None)
        cat["products"].append({
            "name": it["name"],
            "image": img,
            "specs": [{"k": k, "v": v} for k, v in pick_specs(it["attributes"])],
        })

    order = ["kns", "los", "septiki", "zhirouloviteli", "peskouloviteli",
             "kompressory", "zapchasti", "nasosy", "shkafy", "biopreparaty", "tehnofiltr", "uslugi"]
    data = [cats[s] for s in order if s in cats]
    (ROOT / "content/catalog-other.json").write_text(json.dumps(data, ensure_ascii=False, indent=2))
    for c in data:
        print(f'{c["slug"]:14s} {len(c["products"]):3d}  {c["title"]}')
    print("всего позиций:", sum(len(c["products"]) for c in data))


if __name__ == "__main__":
    main()
