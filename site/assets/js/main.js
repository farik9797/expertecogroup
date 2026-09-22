/* Expert ECO Group: скрипты концепта главной */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fmt = (n) => n.toLocaleString('ru-RU');

  const icons = () => window.lucide && window.lucide.createIcons();
  icons();

  /* ---------- Навигация: стекло над hero, белая после него ---------- */
  const header = $('#nav');
  const hero = $('.hero');
  const quickbar = $('.quickbar');
  new IntersectionObserver(([e]) => {
    header.classList.toggle('is-solid', !e.isIntersecting);
    quickbar.classList.toggle('is-shown', !e.isIntersecting); // в hero свои кнопки, панель не дублирует
  }, { rootMargin: '-90px 0px 0px 0px' }).observe(hero);

  /* ---------- Мобильное меню ---------- */
  const menu = $('#mobile-menu');
  const burger = $('.burger');
  const setMenu = (open) => {
    menu.hidden = !open;
    burger.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) $('.menu-close', menu).focus(); else burger.focus();
  };
  burger.addEventListener('click', () => setMenu(true));
  $('.menu-close', menu).addEventListener('click', () => setMenu(false));
  $$('a', menu).forEach((a) => a.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) setMenu(false); });

  /* ---------- Подбор объёма ----------
     Данные из каталога клиента на Satu.kz (content/satu-products.json), цены не хранятся.
     80 м³ (подземная) исключена: размеры на Satu совпадают с 90 м³, уточнить у клиента.
     Вес прямоугольных взят у химических моделей того же размера. */
  const SERIES = {
    under: {
      label: 'Горизонтальная подземная', code: 'EEG-НР-(П)-ЦГ', shape: 'cyl', underground: true, purpose: 'Вода', def: 25,
      items: [
        { v: 5, L: 3100, D: 1430, w: 297 }, { v: 10, L: 3500, D: 1910, w: 544 }, { v: 15, L: 5240, D: 1910, w: 609 },
        { v: 20, L: 7000, D: 1910, w: 781 }, { v: 25, L: 8800, D: 1910, w: 958 }, { v: 30, L: 10500, D: 1910, w: 1125 },
        { v: 35, L: 10100, D: 2100 }, { v: 40, L: 10250, D: 2230 }, { v: 45, L: 11850, D: 2200 }, { v: 50, L: 12250, D: 2280 },
        { v: 60, L: 13500, D: 2390 }, { v: 70, L: 13000, D: 2620 }, { v: 90, L: 14200, D: 2860 }, { v: 100, L: 15750, D: 2860 },
      ],
    },
    ground: {
      label: 'Горизонтальная наземная', code: 'EEG-НР-(Н)-ЦГ', shape: 'cyl', legs: 300, purpose: 'Вода', def: 20,
      items: [
        { v: 5, L: 3100, D: 1430 }, { v: 10, L: 3500, D: 1910 }, { v: 15, L: 5250, D: 1910 }, { v: 20, L: 7000, D: 1910 },
        { v: 25, L: 8740, D: 1910 }, { v: 30, L: 8700, D: 2100 }, { v: 35, L: 10100, D: 2100 }, { v: 40, L: 11550, D: 2100 },
        { v: 45, L: 11350, D: 2250 }, { v: 50, L: 11550, D: 2350 },
      ],
    },
    rect: {
      label: 'Прямоугольная наземная', code: 'EEG-НР/ХР-(Н)-П', shape: 'rect', purpose: 'Химия', def: 4,
      items: [
        { v: 2, L: 1900, W: 1000, H: 1100, w: 120 }, { v: 3, L: 2500, W: 1000, H: 1200, w: 140 },
        { v: 4, L: 2550, W: 1250, H: 1250, w: 190 }, { v: 5, L: 2750, W: 1350, H: 1350, w: 280 },
        { v: 6, L: 2850, W: 1350, H: 1500, w: 350 },
      ],
    },
  };
  const PERSON = 1750;
  const state = { series: 'under', v: SERIES.under.def };

  const sizer = $('.sizer');
  const stage = $('.stage', sizer);
  const tank = $('.tank', stage);
  const neck = $('.tank__neck', stage);
  const soil = $('.stage__soil', stage);
  const person = $('.stage__person', stage);
  const dimH = $('.dim--h', stage);
  const dimV = $('.dim--v', stage);
  const chips = $('.vol-chips', sizer);
  const cta = $('.sizer-cta', sizer);

  function renderChips() {
    const s = SERIES[state.series];
    chips.innerHTML = s.items
      .map((i) => `<button type="button" class="vol" data-v="${i.v}" aria-pressed="${i.v === state.v}">${i.v}</button>`)
      .join('');
  }

  function renderSpec() {
    const s = SERIES[state.series];
    const it = s.items.find((i) => i.v === state.v);
    $('.spec-code', sizer).textContent = `${s.code}-${it.v}м3`;
    $('.spec-vol', sizer).textContent = it.v;
    const rows = [['Тип', s.label], ['Длина', `${fmt(it.L)} мм`]];
    if (s.shape === 'rect') rows.push(['Ширина × высота', `${fmt(it.W)} × ${fmt(it.H)} мм`]);
    else rows.push(['Диаметр', `${fmt(it.D)} мм`]);
    if (it.w) rows.push(['Вес', `≈ ${fmt(it.w)} кг`]);
    rows.push(['Материал', 'полипропилен']);
    $('.spec', sizer).innerHTML = rows.map(([k, v]) => `<div><dt>${k}</dt><dd>${v}</dd></div>`).join('');
    cta.innerHTML = `Рассчитать стоимость · ${it.v} м³`;
  }

  function renderStage() {
    const s = SERIES[state.series];
    const it = s.items.find((i) => i.v === state.v);
    const W = stage.clientWidth;
    const H = stage.clientHeight;
    const padL = 18, padR = W < 480 ? 78 : 104, padT = 24, padB = 46;
    const hOf = (i) => (s.shape === 'rect' ? i.H : i.D);
    const maxL = Math.max(...s.items.map((i) => i.L));
    const maxH = Math.max(...s.items.map(hOf));
    const legs = s.legs || 0;
    const cover = s.underground ? 450 : 0; // грунт над ёмкостью
    const vertical = s.underground ? maxH + cover + PERSON + 150 : Math.max(maxH + legs, PERSON) + 150;
    const horizontal = maxL + PERSON * 0.75 + 700;
    const k = Math.min((W - padL - padR) / horizontal, (H - padT - padB) / vertical);

    const tw = it.L * k;
    const th = hOf(it) * k;
    const tankBottom = padB + legs * k;
    const ground = s.underground ? padB + (it.D + cover) * k : padB;
    // Фигура в иконке Lucide person-standing занимает x 6…18 и y 4…20 из 24 единиц: подгоняем бокс под рост
    const personH = PERSON * k;
    const box = personH * (24 / 16);
    const personW = box * (12 / 24);
    const tankLeft = padL + personW + 700 * k;

    Object.assign(person.style, {
      left: `${padL - box * (6 / 24)}px`, bottom: `${ground - box * (4 / 24)}px`, width: `${box}px`, height: `${box}px`,
    });
    person.style.setProperty('--label-top', `${box * (20 / 24)}px`);
    Object.assign(soil.style, { height: s.underground ? `${ground}px` : '0px', opacity: s.underground ? 1 : 0 });

    tank.className = `tank tank--${s.shape}${s.underground ? ' tank--under' : ' tank--plain'}${legs ? ' tank--legs' : ''}`;
    if (s.shape === 'rect') tank.classList.remove('tank--plain');
    Object.assign(tank.style, {
      left: `${tankLeft}px`, bottom: `${tankBottom}px`, width: `${tw}px`, height: `${th}px`,
      borderRadius: s.shape === 'cyl' ? `${Math.min(th * 0.22, 22)}px` : '4px',
    });
    tank.style.setProperty('--rib', `${Math.max(420 * k, 5)}px`);
    tank.style.setProperty('--legs', `${legs * k}px`);
    tank.style.setProperty('--leg-gap', `${Math.max(1800 * k, 18)}px`);
    Object.assign(neck.style, { width: `${Math.max(700 * k, 8)}px`, height: `${(cover + 150) * k}px` });

    Object.assign(dimH.style, { left: `${tankLeft}px`, width: `${tw}px`, bottom: `${padB - 18}px` });
    $('span', dimH).textContent = `${fmt(it.L)} мм`;
    Object.assign(dimV.style, { left: `${tankLeft + tw + 12}px`, bottom: `${tankBottom}px`, height: `${th}px` });
    $('span', dimV).textContent = s.shape === 'rect' ? `${fmt(it.H)} мм` : `Ø ${fmt(it.D)}`;
  }

  function render() { renderChips(); renderSpec(); renderStage(); }

  function selectSeries(key, v) {
    state.series = key;
    state.v = v ?? SERIES[key].def;
    $$('.tab', sizer).forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === key)));
    render();
  }

  $$('.tab', sizer).forEach((t) => t.addEventListener('click', () => selectSeries(t.dataset.tab)));
  $('.tabs', sizer).addEventListener('keydown', (e) => {
    if (!['ArrowLeft', 'ArrowRight'].includes(e.key)) return;
    const tabs = $$('.tab', sizer);
    const i = tabs.findIndex((t) => t.getAttribute('aria-selected') === 'true');
    const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
    next.focus();
    selectSeries(next.dataset.tab);
  });
  chips.addEventListener('click', (e) => {
    const b = e.target.closest('.vol');
    if (!b) return;
    state.v = Number(b.dataset.v);
    $$('.vol', chips).forEach((c) => c.setAttribute('aria-pressed', String(c === b)));
    renderSpec();
    renderStage();
  });
  new ResizeObserver(renderStage).observe(stage);
  render();

  // Карточки каталога открывают нужную серию в подборе
  $$('.cat-card[data-series]').forEach((c) => c.addEventListener('click', () => selectSeries(c.dataset.series)));

  /* ---------- Форма заявки ---------- */
  const form = $('.request-form');
  const setPurpose = (p) => { const r = $(`input[name="purpose"][value="${p}"]`, form); if (r) r.checked = true; };
  cta.addEventListener('click', () => {
    form.elements.volume.value = state.v;
    setPurpose(SERIES[state.series].purpose);
  });
  $$('[data-purpose]').forEach((a) => a.addEventListener('click', () => setPurpose(a.dataset.purpose)));

  const phone = form.elements.phone;
  const err = $('#phone-err');
  phone.addEventListener('input', () => { phone.removeAttribute('aria-invalid'); err.hidden = true; });
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (phone.value.replace(/\D/g, '').length < 10) {
      phone.setAttribute('aria-invalid', 'true');
      err.hidden = false;
      phone.focus();
      return;
    }
    // КОНЦЕПТ: отправка не подключена. Канал (email / Telegram / WhatsApp) согласовать с клиентом.
    $('.form-ok', form).hidden = false;
  });

  $$('.year').forEach((y) => { y.textContent = new Date().getFullYear(); });

  /* ---------- Анимации (GSAP) ---------- */
  (() => {
    const { gsap, ScrollTrigger } = window;
    if (reduceMotion || !gsap || !ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    const ease = 'expo.out';

    gsap.from('.hero-in', { y: 28, opacity: 0, duration: 1.1, ease, stagger: 0.09, delay: 0.1 });
    gsap.fromTo('.hero-img', { scale: 1.08 }, { scale: 1, duration: 1.8, ease });
    gsap.to('.hero-copy', {
      y: -60, opacity: 0.2, ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
    });

    gsap.set('.reveal', { y: 26, opacity: 0 });
    ScrollTrigger.batch('.reveal', {
      start: 'top 88%',
      once: true,
      onEnter: (els) => gsap.to(els, { y: 0, opacity: 1, duration: 0.85, ease, stagger: 0.08, overwrite: true }),
    });
  })();
})();
