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
  const toTop = $('.to-top');
  const setChrome = (past) => {
    header.classList.toggle('is-solid', past);
    quickbar.classList.toggle('is-shown', past); // в hero свои кнопки, панель не дублирует
    toTop.classList.toggle('is-shown', past);
  };
  if (hero) {
    new IntersectionObserver(([e]) => setChrome(!e.isIntersecting), { rootMargin: '-90px 0px 0px 0px' }).observe(hero);
  } else {
    // внутренние страницы: шапка сразу светлая, панели появляются после первого экрана
    setChrome(false);
    header.classList.add('is-solid');
    const onScroll = () => {
      const past = window.scrollY > 320;
      quickbar.classList.toggle('is-shown', past);
      toTop.classList.toggle('is-shown', past);
    };
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Нативная плавная прокрутка: не зависит от rAF и не конфликтует с CSS scroll-behavior
  toTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
  });

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

  /* ---------- Калькулятор ёмкости ----------
     Габариты пересчитываются по формуле цилиндра: V = pi * (D/2)^2 * L.
     Типоразмеры из каталога клиента на Satu.kz нужны только для подсказки «ближайшая модель»;
     цены нигде не хранятся. */
  const CATALOG = {
    under: {
      code: 'EEG-НР-(П)-ЦГ', label: 'подземная',
      items: [[5, 3100, 1430], [10, 3500, 1910], [15, 5240, 1910], [20, 7000, 1910], [25, 8800, 1910], [30, 10500, 1910],
        [35, 10100, 2100], [40, 10250, 2230], [45, 11850, 2200], [50, 12250, 2280], [60, 13500, 2390], [70, 13000, 2620],
        [90, 14200, 2860], [100, 15750, 2860]],
    },
    ground: {
      code: 'EEG-НР-(Н)-ЦГ', label: 'наземная',
      items: [[5, 3100, 1430], [10, 3500, 1910], [15, 5250, 1910], [20, 7000, 1910], [25, 8740, 1910], [30, 8700, 2100],
        [35, 10100, 2100], [40, 11550, 2100], [45, 11350, 2250], [50, 11550, 2350]],
    },
  };
  const LIM = { v: [1, 120], d: [1000, 3000], l: [1500, 20000] };
  const calc = { type: 'under', v: 10, d: 1900, l: 3530 };

  const clamp = (x, [a, b]) => Math.min(b, Math.max(a, x));
  const round = (x, s) => Math.round(x / s) * s;
  const volOf = (d, l) => (Math.PI * (d / 2) ** 2 * l) / 1e9;
  const lenOf = (v, d) => (v * 1e9) / (Math.PI * (d / 2) ** 2);
  const round1 = (x) => Math.round(x * 10) / 10;

  const sizer = $('.calc');
  const hasCalc = Boolean(sizer);
  const bpSvg = hasCalc ? $('.bp-svg', sizer) : null;
  const note = hasCalc ? $('.bp-note', sizer) : null;
  const cta = hasCalc ? $('.calc-cta', sizer) : null;
  const fieldEl = (f) => $(`.stepper[data-field="${f}"] input`, sizer);
  const stepOf = (f) => (f === 'v' ? (calc.v < 10 ? 1 : 5) : f === 'd' ? 50 : 100);

  function apply(field, raw) {
    const num = parseFloat(String(raw).replace(',', '.').replace(/[^\d.]/g, ''));
    if (!Number.isFinite(num)) return render();
    if (field === 'v') {
      calc.v = round1(clamp(num, LIM.v));
      calc.l = clamp(round(lenOf(calc.v, calc.d), 10), LIM.l);
      if (Math.abs(volOf(calc.d, calc.l) - calc.v) > 0.15) calc.v = round1(volOf(calc.d, calc.l));
    } else if (field === 'd') {
      calc.d = clamp(round(num, 50), LIM.d);
      calc.l = clamp(round(lenOf(calc.v, calc.d), 10), LIM.l);
      if (Math.abs(volOf(calc.d, calc.l) - calc.v) > 0.15) calc.v = round1(volOf(calc.d, calc.l));
    } else {
      calc.l = clamp(round(num, 10), LIM.l);
      calc.v = round1(volOf(calc.d, calc.l));
    }
    render();
  }

  function nearest() {
    const c = CATALOG[calc.type];
    const [v, l, d] = c.items.reduce((a, b) => (Math.abs(b[0] - calc.v) < Math.abs(a[0] - calc.v) ? b : a));
    return { code: `${c.code}-${v}м3`, v, l, d };
  }

  function drawBlueprint() {
    const W = bpSvg.clientWidth;
    const H = bpSvg.clientHeight;
    if (!W || !H) return;
    const under = calc.type === 'under';
    const NECK_H = 420, LID_H = 90, NECK_W = 700, SUP_H = 320;
    const narrow = W < 430;
    const padL = narrow ? 56 : 94, padR = narrow ? 24 : 38, padT = narrow ? 62 : 30, padB = 52;
    const k = Math.min((W - padL - padR) / calc.l, (H - padT - padB) / (calc.d + NECK_H + LID_H + (under ? 0 : SUP_H)));
    const L = calc.l * k, D = calc.d * k;
    const x = padL + (W - padL - padR - L) / 2;
    const yB = H - padB - (under ? 0 : SUP_H * k), yT = yB - D, yM = (yT + yB) / 2;
    const cap = Math.min(D * 0.12, L * 0.1);
    const INK = '#0d2c5c', THIN = '#96acc9', DIM = '#5b7aa6', GLASS = 'rgba(255,255,255,.75)';
    const s = [];

    s.push(`<path d="M${x - 18} ${yM} H${x + L + 18}" stroke="${THIN}" stroke-width="1" stroke-dasharray="16 4 3 4"/>`);

    if (under) {
      const gy = yT - NECK_H * 0.5 * k;
      s.push(`<path d="M${Math.max(padL - 30, 6)} ${gy} H${W - 8}" stroke="#b08a57" stroke-width="1.2" stroke-dasharray="7 5"/>`);
      s.push(`<text x="${W - 10}" y="${gy - 9}" text-anchor="end" font-size="11" font-weight="700" fill="#a8895f">уровень земли</text>`);
    } else {
      const sw = 420 * k, sh = SUP_H * k;
      [0.24, 0.76].forEach((t) => {
        const sx = x + L * t - sw / 2;
        s.push(`<path d="M${sx} ${yB} h${sw} v${sh} h${-sw} z" fill="#fff" stroke="${INK}" stroke-width="1.3"/>`);
      });
      s.push(`<path d="M${Math.max(padL - 30, 6)} ${yB + sh} H${W - 8}" stroke="${INK}" stroke-width="1.4"/>`);
    }

    s.push(`<path d="M${x + cap} ${yT} H${x + L - cap} A${cap} ${D / 2} 0 0 1 ${x + L - cap} ${yB} H${x + cap} A${cap} ${D / 2} 0 0 1 ${x + cap} ${yT} Z" fill="${GLASS}" stroke="${INK}" stroke-width="1.7" stroke-linejoin="round"/>`);
    s.push(`<path d="M${x + cap} ${yT} A${cap} ${D / 2} 0 0 0 ${x + cap} ${yB}" fill="none" stroke="${INK}" stroke-width="1.1"/>`);

    const ribs = Math.min(7, Math.max(2, Math.round(calc.l / 2400)));
    for (let i = 1; i <= ribs; i++) {
      const rx = x + cap + ((L - 2 * cap) * i) / (ribs + 1);
      const rw = Math.max(D * 0.035, 3);
      s.push(`<rect x="${rx - rw / 2}" y="${yT - 3}" width="${rw}" height="${D + 6}" rx="1.5" fill="#fff" fill-opacity=".92" stroke="${INK}" stroke-width="1.2"/>`);
    }

    [0.27, 0.72].forEach((t) => {
      const nx = x + L * t, nw = NECK_W * k, nh = NECK_H * k, lw = nw * 1.24, lh = Math.max(LID_H * k, 3.5);
      s.push(`<path d="M${nx - nw / 2} ${yT} v${-nh} h${nw} v${nh}" fill="#fff" stroke="${INK}" stroke-width="1.3"/>`);
      s.push(`<rect x="${nx - lw / 2}" y="${yT - nh - lh}" width="${lw}" height="${lh}" rx="${lh / 2}" fill="#fff" stroke="${INK}" stroke-width="1.3"/>`);
    });

    const xd = x - 26;
    s.push(`<path d="M${x} ${yT} H${xd - 7}" stroke="${THIN}" stroke-width="1"/>`);
    s.push(`<path d="M${x} ${yB} H${xd - 7}" stroke="${THIN}" stroke-width="1"/>`);
    s.push(`<path d="M${xd} ${yT} V${yB}" stroke="${DIM}" stroke-width="1" marker-start="url(#ar)" marker-end="url(#ar)"/>`);
    s.push(narrow
      ? `<text x="${xd - 8}" y="${yM}" text-anchor="middle" font-size="12" font-weight="700" fill="${DIM}" transform="rotate(-90 ${xd - 8} ${yM})">Ø ${fmt(calc.d)}</text>`
      : `<text x="${xd - 10}" y="${yM}" text-anchor="end" dominant-baseline="middle" font-size="12.5" font-weight="700" fill="${DIM}">Ø ${fmt(calc.d)}</text>`);

    const yd = (under ? yB : yB + SUP_H * k) + 30;
    s.push(`<path d="M${x} ${under ? yB : yB + SUP_H * k} V${yd + 7}" stroke="${THIN}" stroke-width="1"/>`);
    s.push(`<path d="M${x + L} ${under ? yB : yB + SUP_H * k} V${yd + 7}" stroke="${THIN}" stroke-width="1"/>`);
    s.push(`<path d="M${x} ${yd} H${x + L}" stroke="${DIM}" stroke-width="1" marker-start="url(#ar)" marker-end="url(#ar)"/>`);
    s.push(`<text x="${x + L / 2}" y="${yd}" text-anchor="middle" dominant-baseline="middle" font-size="12.5" font-weight="700" fill="${DIM}" stroke="#f7fbff" stroke-width="5" paint-order="stroke">${fmt(calc.l)}</text>`);

    bpSvg.setAttribute('viewBox', `0 0 ${W} ${H}`);
    bpSvg.innerHTML = `<defs><marker id="ar" markerWidth="9" markerHeight="9" refX="8.5" refY="4.5" orient="auto-start-reverse" markerUnits="userSpaceOnUse"><path d="M0 0 L9 4.5 L0 9 Z" fill="${DIM}"/></marker></defs>${s.join('')}`;
    bpSvg.setAttribute('aria-label', `Чертёж: ${calc.type === 'under' ? 'подземная' : 'наземная'} горизонтальная ёмкость ${calc.v} м³, диаметр ${calc.d} мм, длина ${calc.l} мм`);
  }

  function render() {
    fieldEl('v').value = Number.isInteger(calc.v) ? calc.v : calc.v.toFixed(1).replace('.', ',');
    fieldEl('d').value = fmt(calc.d);
    fieldEl('l').value = fmt(calc.l);
    const n = nearest();
    note.innerHTML = `Ближайший типоразмер из каталога: <b>${n.code}</b> · ${fmt(n.l)} × Ø ${fmt(n.d)} мм <button type="button" class="bp-use">подставить</button>`;
    $('.bp-use', note).addEventListener('click', () => {
      calc.d = n.d; calc.l = n.l; calc.v = round1(volOf(n.d, n.l)); render();
    });
    drawBlueprint();
  }

  if (hasCalc) {
  $$('.stepper', sizer).forEach((st) => {
    const f = st.dataset.field;
    const input = $('input', st);
    $$('button', st).forEach((b) => b.addEventListener('click', () => {
      apply(f, calc[f] + Number(b.dataset.step) * stepOf(f));
    }));
    input.addEventListener('change', () => apply(f, input.value));
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); apply(f, input.value); } });
  });

  $$('.bp-type button', sizer).forEach((b) => b.addEventListener('click', () => {
    calc.type = b.dataset.type;
    $$('.bp-type button', sizer).forEach((o) => o.setAttribute('aria-pressed', String(o === b)));
    render();
  }));

  new ResizeObserver(drawBlueprint).observe(bpSvg);
  render();
  }

  // Карточка каталога открывает калькулятор в нужном исполнении



  /* ---------- Формы заявки: в секции и в попапе ---------- */
  const forms = $$('.request-form');
  const modal = $('#request-modal');
  const modalForm = $('.request-form', modal);

  const setPurpose = (form, p) => { const r = $(`input[name="purpose"][value="${p}"]`, form); if (r) r.checked = true; };
  const addComment = (form, text) => {
    const c = form.elements.comment;
    if (!c.value.includes(text)) c.value = c.value ? `${c.value}\n${text}` : text;
  };

  forms.forEach((form, i) => {
    const phone = form.elements.phone;
    const err = $('.field__err', form);
    err.id = `phone-err-${i}`;
    phone.setAttribute('aria-describedby', err.id);
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
  });

  /* ---------- Попап ---------- */
  let lastFocused = null;
  function openModal({ purpose, volume, comment } = {}) {
    if (purpose) setPurpose(modalForm, purpose);
    if (volume) modalForm.elements.volume.value = volume;
    if (comment) addComment(modalForm, comment);
    $('.form-ok', modalForm).hidden = true;
    lastFocused = document.activeElement;
    modal.showModal();
    document.body.style.overflow = 'hidden';
  }
  // Чистим состояние явно: событие close у <dialog> в некоторых браузерах доходит не всегда,
  // а без него страница осталась бы заблокированной от прокрутки
  function cleanup() {
    document.body.style.overflow = '';
    const ok = $('.form-ok', modalForm);
    if (!ok.hidden) { modalForm.reset(); ok.hidden = true; }
    if (lastFocused) { lastFocused.focus(); lastFocused = null; }
  }
  function closeModal() {
    modal.close();
    cleanup();
  }
  modal.addEventListener('close', cleanup);
  modal.addEventListener('cancel', (e) => { e.preventDefault(); closeModal(); }); // Esc
  $('.modal__close', modal).addEventListener('click', closeModal);
  $('.modal__ok-close', modal).addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); }); // клик по фону

  // Все кнопки «на заявку» открывают попап (на внутренних страницах ссылка ведёт на index.html#request)
  $$('a[href$="#request"]').forEach((a) => a.addEventListener('click', (e) => {
    if (a.closest('#request')) return;
    e.preventDefault();
    openModal({ purpose: a.dataset.purpose });
  }));

  // Кнопки «Запросить» в разделах оборудования подставляют название позиции
  $$('[data-request]').forEach((b) => b.addEventListener('click', () => openModal({
    purpose: 'Другое',
    comment: `Интересует: ${b.dataset.request}`,
  })));

  // Кнопка калькулятора передаёт габариты
  if (cta) cta.addEventListener('click', () => openModal({
    purpose: 'Вода',
    volume: Number.isInteger(calc.v) ? calc.v : String(calc.v).replace('.', ','),
    comment: `Горизонтальная ${CATALOG[calc.type].label} ёмкость: диаметр ${calc.d} мм, длина ${calc.l} мм.`,
  }));

  /* ---------- Карта проектов ---------- */
  const mapEl = $('#kz-map');
  if (mapEl && window.L) {
    const projects = JSON.parse(mapEl.dataset.projects);
    const map = L.map(mapEl, { scrollWheelZoom: false }).setView([48.3, 67.5], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const cards = $$('.pin-card');
    const openCard = (id, fly) => {
      cards.forEach((c) => c.classList.toggle('is-open', c.dataset.pin === id));
      const card = cards.find((c) => c.dataset.pin === id);
      const project = projects.find((p) => p.id === id);
      if (card) card.scrollIntoView({ block: 'nearest', behavior: reduceMotion ? 'auto' : 'smooth' });
      if (fly && project) map.flyTo(project.coords, 7, { duration: reduceMotion ? 0 : 0.8 });
    };

    projects.forEach((p) => {
      const icon = L.divIcon({
        className: '',
        html: `<span class="map-pin${p.confirmed ? '' : ' map-pin--draft'}"><span>${p.city[0]}</span></span>`,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
      });
      L.marker(p.coords, { icon, title: `${p.city} — ${p.title}` })
        .addTo(map)
        .bindPopup(`<b>${p.city}</b><br>${p.title}`)
        .on('click', () => openCard(p.id, false));
    });

    cards.forEach((c) => $('.pin-card__head', c).addEventListener('click', () => {
      const isOpen = c.classList.contains('is-open');
      cards.forEach((o) => o.classList.remove('is-open'));
      if (!isOpen) openCard(c.dataset.pin, true);
    }));
    cards[0]?.classList.add('is-open');
  }

  /* ---------- Наши услуги: переключение ---------- */
  const srvTabs = $$('.srv-tab');
  if (srvTabs.length) {
    const panels = $$('.srv-panel');
    const show = (slug) => {
      srvTabs.forEach((t) => {
        const on = t.dataset.srv === slug;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-pressed', String(on));
      });
      panels.forEach((p) => p.classList.toggle('is-active', p.dataset.srv === slug));
    };
    srvTabs.forEach((t) => {
      t.addEventListener('click', () => show(t.dataset.srv));
      t.addEventListener('mouseenter', () => show(t.dataset.srv));
    });
  }

  /* ---------- Галерея товара ---------- */
  const galMain = $('.gal__main');
  if (galMain) {
    const thumbs = $$('.gal__thumb');
    thumbs.forEach((t, i) => {
      if (i === 0) t.setAttribute('aria-current', 'true');
      t.addEventListener('click', () => {
        galMain.src = t.dataset.full;
        thumbs.forEach((o) => o.setAttribute('aria-current', String(o === t)));
      });
    });
  }

  $$('.year').forEach((y) => { y.textContent = new Date().getFullYear(); });

  /* ---------- Анимации (GSAP) ---------- */
  (() => {
    const { gsap, ScrollTrigger } = window;
    if (reduceMotion || !gsap || !ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    const ease = 'expo.out';

    if (hero) {
      gsap.from('.hero-in', { y: 28, opacity: 0, duration: 1.1, ease, stagger: 0.09, delay: 0.1 });
      gsap.fromTo('.hero-img', { scale: 1.08 }, { scale: 1, duration: 1.8, ease });
      gsap.to('.hero-copy', {
        y: -60, opacity: 0.2, ease: 'none',
        scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
      });
      gsap.to('.hero-img', {
        yPercent: 7, ease: 'none',
        scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
      });
    }

    // кольцо прогресса на кнопке «наверх»
    const ring = $('.to-top__progress');
    const RING = 2 * Math.PI * 20.5;
    ScrollTrigger.create({
      start: 0,
      end: 'max',
      onUpdate: (self) => { ring.style.strokeDashoffset = String(RING * (1 - self.progress)); },
    });

    // спокойный параллакс на фото
    $$('[data-parallax]').forEach((img) => {
      gsap.fromTo(img, { yPercent: -5, scale: 1.12 }, {
        yPercent: 5, scale: 1.12, ease: 'none',
        scrollTrigger: { trigger: img.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
      });
    });

    gsap.set('.reveal', { y: 26, opacity: 0 });
    ScrollTrigger.batch('.reveal', {
      start: 'top 88%',
      once: true,
      onEnter: (els) => gsap.to(els, { y: 0, opacity: 1, duration: 0.85, ease, stagger: 0.08, overwrite: true }),
    });
  })();
})();
