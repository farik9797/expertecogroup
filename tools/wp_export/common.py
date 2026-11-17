"""Общее для выгрузки в WordPress: настройки сайта, служебные страницы, ссылки на картинки."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import build_site as B  # noqa: E402  — данные и тексты макета берём из генератора, чтобы не расходились

ROOT = B.ROOT
IMG_DIR = ROOT / "site/assets/img"


def img(rel, alt=""):
    """Картинка для поля: путь внутри site/assets/img (с расширением). Импорт загрузит её в медиатеку."""
    if not (IMG_DIR / rel).is_file():
        raise SystemExit(f"нет картинки: site/assets/img/{rel}")
    return f"img:{rel}|{alt}" if alt else f"img:{rel}"


def img_name(name, alt=""):
    """Имя кадра в нотации генератора (img_src): 'photo/x' → photo/x.webp, иначе catalog/x.webp."""
    folder = "" if name.startswith("photo/") else "catalog/"
    return img(f"{folder}{name}.webp", alt)


def facts(pairs):
    return [{"a": a, "b": b} for a, b in pairs]


def options():
    return {
        "phones": [
            {"number": "+7 777 484-18-22", "label": "Отдел продаж, Сергей"},
            {"number": "+7 708 626-57-49", "label": "Отдел продаж, Артём"},
        ],
        "whatsapp": "77712282203",
        "email": "info_eeg@mail.ru",
        "request_email": "",
        "address": "Каскелен, ул. Курылысшы, 1а/1",
        "address_map": "https://yandex.kz/maps/?text=%D0%9A%D0%B0%D1%81%D0%BA%D0%B5%D0%BB%D0%B5%D0%BD%2C%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%9A%D1%83%D1%80%D1%8B%D0%BB%D1%8B%D1%81%D1%88%D1%8B%2C%201%D0%B0%2F1",
        "hours": "Пн–пт, 08:00–18:00",
        "footer_about": "ТОО «Expert ECO Group». С 2014 года — оборудование из полипропилена для водоочистки и водоотведения. Работаем по Казахстану и странам СНГ.",
        "company": "ТОО «Expert ECO Group»",
    }


def pages():
    """Служебные страницы: старые адреса макета ведут на «Проекты»."""
    return [
        {"path": "fotoalbom", "title": "Фотоальбом", "template": "page-templates/redirect.php", "menu_order": 50,
         "fields": {"redirect_to": "proekty/#fotoalbom"}},
        {"path": "karta-proektov", "title": "Карта проектов", "template": "page-templates/redirect.php", "menu_order": 51,
         "fields": {"redirect_to": "proekty/"}},
    ]
