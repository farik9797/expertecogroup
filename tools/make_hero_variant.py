"""Второй вариант первого слайда — отдельная страница для показа клиенту.

Клиент попросил пару вариантов заглавного слайда («на цифру один поставить»),
поэтому страница собирается из готовой главной: подменяются только фон и
содержимое первой панели hero. Отдельной копии index.html не держим — файл
site/varianty/hero-2.html пересобирается этой командой:

    python3 tools/make_hero_variant.py
"""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SITE = ROOT / "site"
s = (SITE / "index.html").read_text()

# пути на уровень выше: страница лежит в site/varianty/
s = re.sub(r'(href|src)="(?!https?:|#|tel:|mailto:|//)([^"]+)"', lambda m: f'{m.group(1)}="../{m.group(2)}"', s)
s = re.sub(r'(imagesrcset|srcset)="([^"]+)"',
           lambda m: f'{m.group(1)}="' + re.sub(r'(^|,\s*)(?!https?:)', lambda x: x.group(1) + '../', m.group(2)) + '"', s)
s = s.replace('href="#', 'href="../index.html#')

# фон первого слайда — монтаж на объекте
s = re.sub(r'src="\.\./assets/img/hero-obzor-1280\.webp" srcset="[^"]+"',
           'src="../assets/img/hero-obzor2-1280.webp" srcset="../assets/img/hero-obzor2-800.webp 800w, ../assets/img/hero-obzor2-1280.webp 1280w"', s)
s = s.replace('href="../assets/img/hero-obzor-1280.webp"', 'href="../assets/img/hero-obzor2-1280.webp"')
s = s.replace('imagesrcset="../assets/img/hero-obzor-800.webp 800w, ../assets/img/hero-obzor-1280.webp 1280w"',
              'imagesrcset="../assets/img/hero-obzor2-800.webp 800w, ../assets/img/hero-obzor2-1280.webp 1280w"')
s = s.replace('alt="Полипропиленовые резервуары у цеха Expert ECO Group" width="1280" height="960"',
              'alt="Монтаж подземного резервуара на объекте" width="1280" height="960"')

# содержимое первой панели: три направления карточками вместо карточки с фактами
pane = re.search(r'    <div class="hero-pane is-active" id="hero-pane-0".*?\n    </div>\n', s, re.S).group(0)
new_pane = '''    <div class="hero-pane is-active" id="hero-pane-0" data-pane="0" role="tabpanel" aria-labelledby="hero-tab-0" aria-hidden="false">
      <div class="hero-copy max-w-5xl">
        <h1 class="hero-in font-display text-[clamp(1.62rem,4.7vw,4.2rem)] font-medium leading-[1.04] tracking-[-0.015em]">
          Полный комплекс <span class="accent mt-1 block text-sky-soft">для&nbsp;воды и&nbsp;стоков</span>
        </h1>
        <p class="hero-in mt-5 max-w-[37rem] text-[17px] font-semibold leading-relaxed text-white/85 md:text-lg">
          Проектируем, изготавливаем из полипропилена, монтируем и обслуживаем. Одно производство на весь объект.
        </p>
        <div class="hero-in mt-6 grid gap-2 sm:mt-7 sm:grid-cols-3 sm:gap-3">
          <a href="../catalog/emkosti/" class="glass rounded-[20px] p-3 transition-colors hover:bg-white/20 md:p-4">
            <span class="block font-display text-[17px] font-medium">Ёмкости и резервуары</span>
            <span class="mt-1 hidden text-sm font-semibold text-white/75 sm:block">2–100 м³, наземные и подземные</span>
          </a>
          <a href="../catalog/kns/" class="glass rounded-[20px] p-3 transition-colors hover:bg-white/20 md:p-4">
            <span class="block font-display text-[17px] font-medium">КНС</span>
            <span class="mt-1 hidden text-sm font-semibold text-white/75 sm:block">корпус, насосы, шкаф управления</span>
          </a>
          <a href="../catalog/los/" class="glass rounded-[20px] p-3 transition-colors hover:bg-white/20 md:p-4">
            <span class="block font-display text-[17px] font-medium">Очистные сооружения</span>
            <span class="mt-1 hidden text-sm font-semibold text-white/75 sm:block">ливневые, бытовые, промышленные</span>
          </a>
        </div>
        <div class="hero-in mt-7 flex flex-wrap gap-3">
          <a href="../catalog/" class="btn btn-primary">Весь каталог <i data-lucide="arrow-right" class="size-5"></i></a>
          <a href="../index.html#request" class="btn btn-glass max-sm:hidden">Рассчитать стоимость</a>
        </div>
      </div>
    </div>
'''
s = s.replace(pane, new_pane)
s = s.replace('<title>', '<title>Вариант 2 — ')

out = SITE / "varianty/hero-2.html"
out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(s)
print("готово:", out.relative_to(ROOT))
