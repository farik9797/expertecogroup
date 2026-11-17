<?php
/**
 * Главная страница (разметка — как site/index.html между <main> и </main>).
 * Поля — inc/fields-home.php.
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
$f  = fn($name, $default = '') => eeg_field($name, $default, $id);

/* ============ HERO: слайды направлений ============ */
$slides = array_values(array_filter((array) $f('hero_slides', []), fn($s) => !empty($s['title']) || !empty($s['bg'])));
if ($slides) :
    $wa_hero = eeg_wa() . '?text=' . rawurlencode($f('hero_wa_text', 'Здравствуйте! Хочу рассчитать стоимость'));
    ?>
<section id="top" class="hero relative isolate overflow-hidden text-white" data-hero>
  <div class="hero-bg absolute inset-0 -z-20" aria-hidden="true">
    <?php foreach ($slides as $i => $s) :
        $bg = $s['bg'] ?? 0;
        if (!$bg) {
            continue;
        }
        [$w, $h] = eeg_img_size($bg);
        $srcset  = eeg_img_srcset($bg);
        $under   = !empty($s['bg_under']) ? eeg_img_url($s['bg_under']) : '';
        ?>
    <img class="hero-img hero-bg__img<?php echo $under ? ' hero-bg__img--whole' : ''; ?><?php echo $i === 0 ? ' is-active' : ''; ?>" data-bg="<?php echo (int) $i; ?>"<?php echo $under ? ' style="background-image: url(' . esc_url($under) . ')"' : ''; ?>
         src="<?php echo esc_url(eeg_img_url($bg)); ?>"<?php echo $srcset ? ' srcset="' . esc_attr($srcset) . '" sizes="100vw"' : ''; ?>
         alt="<?php echo esc_attr(eeg_img_alt($bg)); ?>"<?php echo $w ? ' width="' . $w . '" height="' . $h . '"' : ''; ?><?php echo $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
    <?php endforeach; ?>
  </div>
  <div class="hero-shade absolute inset-0 -z-10" aria-hidden="true"></div>

  <div class="mx-auto flex min-h-[100svh] max-w-7xl flex-col justify-end px-4 pb-10 pt-32 md:px-6 md:pb-16">
    <div class="hero-tabs hero-in" role="tablist" aria-label="Направления производства">
      <?php foreach ($slides as $i => $s) : ?>
      <button type="button" class="hero-tab<?php echo $i === 0 ? ' is-active' : ''; ?>" data-tab="<?php echo (int) $i; ?>" role="tab" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="hero-pane-<?php echo (int) $i; ?>" id="hero-tab-<?php echo (int) $i; ?>"><?php echo esc_html($s['tab'] ?? ''); ?></button>
      <?php endforeach; ?>
    </div>

    <div class="hero-panes mt-6 md:mt-8">
    <?php foreach ($slides as $i => $s) :
        $facts = array_values(array_filter((array) ($s['aside_facts'] ?? []), fn($r) => ($r['a'] ?? '') !== '' || ($r['b'] ?? '') !== ''));
        ?>
    <div class="hero-pane<?php echo $i === 0 ? ' is-active' : ''; ?>" id="hero-pane-<?php echo (int) $i; ?>" data-pane="<?php echo (int) $i; ?>" role="tabpanel" aria-labelledby="hero-tab-<?php echo (int) $i; ?>" aria-hidden="<?php echo $i === 0 ? 'false' : 'true'; ?>">
      <div class="grid items-end gap-8 lg:grid-cols-[1.4fr_.6fr] lg:gap-12">
        <div class="hero-copy">
          <h1 class="hero-in font-display text-[clamp(1.62rem,4.7vw,4.2rem)] font-medium leading-[1.04] tracking-[-0.015em]">
            <?php echo eeg_home_mark($s['title'] ?? '', 'accent mt-1 block text-sky-soft'); ?>
          </h1>
          <?php if (!empty($s['text'])) : ?>
          <p class="hero-in mt-5 max-w-[37rem] text-[17px] font-semibold leading-relaxed text-white/85 md:text-lg">
            <?php echo esc_html($s['text']); ?>
          </p>
          <?php endif; ?>
          <div class="hero-in mt-8 flex flex-wrap gap-3">
            <?php if (!empty($s['btn_text'])) : ?>
            <a href="<?php echo esc_url(eeg_url($s['btn_link'] ?? '')); ?>" class="btn btn-primary"><?php echo esc_html($s['btn_text']); ?> <i data-lucide="arrow-right" class="size-5"></i></a>
            <?php endif; ?>
            <a href="<?php echo esc_url($wa_hero); ?>" class="btn btn-glass" target="_blank" rel="noopener">
              <img src="https://cdn.simpleicons.org/whatsapp/ffffff" alt="" class="size-5" width="20" height="20">Написать в WhatsApp
            </a>
          </div>
        </div>
        <?php if (!empty($s['aside_aria'])) : ?>

        <aside class="hero-in glass hidden rounded-[28px] p-6 md:block" aria-label="<?php echo esc_attr($s['aside_aria']); ?>">
          <?php if (!empty($s['aside_kicker'])) : ?>
          <p class="text-xs font-bold uppercase tracking-[.14em] text-white/70"><?php echo esc_html($s['aside_kicker']); ?></p>
          <?php endif; ?>
          <?php if (!empty($s['aside_big'])) :
              $fluid = !empty($s['aside_big_fluid']);
              ?>
          <p class="mt-2 font-display <?php echo $fluid ? 'text-[clamp(2rem,2.9vw,2.6rem)]' : 'text-[2.6rem]'; ?> font-medium leading-none"><?php echo eeg_home_mark($s['aside_big'], $fluid ? 'text-[.62em]' : 'text-2xl'); ?></p>
          <?php endif; ?>
          <?php if (!empty($s['aside_sub'])) : ?>
          <p class="mt-2 text-sm font-semibold text-white/75"><?php echo esc_html($s['aside_sub']); ?></p>
          <?php endif; ?>
          <?php if ($facts) : ?>
          <div class="my-5 h-px bg-white/15"></div>
          <div class="grid grid-cols-2 gap-4">
          <?php foreach ($facts as $r) : ?>
          <div>
            <p class="font-display text-xl font-medium"><?php echo esc_html($r['a'] ?? ''); ?></p>
            <p class="mt-1 text-sm font-semibold text-white/75"><?php echo esc_html($r['b'] ?? ''); ?></p>
          </div>
          <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($s['aside_link_text'])) : ?>
          <a href="<?php echo esc_url(eeg_url($s['aside_link'] ?? '')); ?>" class="mt-6 inline-flex items-center gap-2 font-bold text-sky-soft transition-colors hover:text-white"><?php echo esc_html($s['aside_link_text']); ?> <i data-lucide="arrow-right" class="size-4"></i></a>
          <?php endif; ?>
        </aside>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Индикатор: текущий слайд и отсчёт до автопрокрутки -->
    <div class="hero-status" aria-hidden="true">
      <?php foreach ($slides as $i => $s) : ?>
      <span class="hero-status__bar<?php echo $i === 0 ? ' is-active' : ''; ?>"><i></i></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ НАША ПРОДУКЦИЯ ============ */
$tiles = array_values(array_filter((array) $f('catalog_tiles', []), fn($t) => !empty($t['title'])));
if ($tiles) : ?>
<section id="catalog" class="px-4 py-24 md:px-6 md:py-32">
  <div class="mx-auto max-w-7xl">
    <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between" data-anim="fade-right" data-delay="150">
      <h2 class="section-title"><?php echo esc_html($f('catalog_title', 'Наша продукция')); ?></h2>
      <a href="<?php echo esc_url(eeg_url('catalog/')); ?>" class="link-arrow">Весь каталог <i data-lucide="arrow-right" class="size-4"></i></a>
    </div>

    <div class="prod-tiles mt-12">
      <?php foreach ($tiles as $i => $t) : ?>
      <a class="prod-tile reveal" data-anim="fade-up" data-delay="<?php echo 150 + 75 * $i; ?>" href="<?php echo esc_url(eeg_url($t['link'] ?? '')); ?>">
        <span class="prod-tile__head">
          <span class="prod-tile__title"><?php echo esc_html($t['title']); ?></span>
          <i data-lucide="arrow-up-right"></i>
        </span>
        <span class="prod-tile__ph"><?php if (!empty($t['image'])) : ?><img src="<?php echo esc_url(eeg_img_url($t['image'])); ?>" alt="<?php echo esc_attr($t['title']); ?>" loading="lazy"><?php endif; ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ ЛЕНТА ВОЗМОЖНОСТЕЙ ============ */
$marquee = eeg_list($f('marquee', []));
if ($marquee) :
    $items = implode('', array_map(fn($t) => '<li>' . esc_html($t) . '</li>', $marquee));
    ?>
<div class="bg-navy py-7 text-white md:py-9" aria-label="Что мы делаем">
  <div class="marquee">
    <ul class="marquee__track">
      <?php echo $items; ?>
    </ul>
    <ul class="marquee__track" aria-hidden="true">
      <?php echo $items; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<?php
/* ============ НАШИ УСЛУГИ ============ */
$services = array_values(array_filter((array) $f('services', []), fn($s) => !empty($s['title'])));
if ($services) :
    foreach ($services as $i => &$s) {
        $s['slug'] = sanitize_title($s['slug'] ?? '') ?: 'srv-' . ($i + 1);
    }
    unset($s);
    $lead = $f('services_lead');
    ?>
<section id="services" class="services px-4 py-20 md:px-6 md:py-28">
  <div class="mx-auto max-w-7xl">
    <div class="reveal flex flex-col gap-5 md:flex-row md:items-end md:justify-between" data-anim="fade-right" data-delay="150">
      <h2 class="section-title text-white"><?php echo esc_html($f('services_title', 'Наши услуги')); ?></h2>
      <?php if ($lead) : ?>
      <p class="max-w-md text-white/70"><?php echo esc_html($lead); ?></p>
      <?php endif; ?>
    </div>

    <div class="srv reveal mt-12" data-anim="fade-up" data-delay="300">
      <div class="srv__stage">
        <?php foreach ($services as $i => $s) :
            $im = $s['image'] ?? 0;
            $srcset = $im ? eeg_img_srcset($im) : '';
            ?>
        <figure class="srv-panel<?php echo $i === 0 ? ' is-active' : ''; ?>" data-srv="<?php echo esc_attr($s['slug']); ?>">
          <?php if ($im) : ?>
          <img src="<?php echo esc_url(eeg_img_url($im)); ?>"<?php echo $srcset ? ' srcset="' . esc_attr($srcset) . '" sizes="(max-width: 899px) 100vw, 60vw"' : ''; ?> alt="<?php echo esc_attr(eeg_img_alt($im, $s['title'])); ?>" loading="lazy">
          <?php endif; ?>
          <figcaption>
            <h3><?php echo esc_html($s['title']); ?></h3>
            <?php if (!empty($s['text'])) : ?>
            <p><?php echo esc_html($s['text']); ?></p>
            <?php endif; ?>
          </figcaption>
        </figure>
        <?php endforeach; ?>
      </div>
      <div class="srv__tabs srv__tabs--<?php echo count($services); ?>">
        <?php foreach ($services as $i => $s) : ?>
        <button type="button" class="srv-tab<?php echo $i === 0 ? ' is-active' : ''; ?>" data-srv="<?php echo esc_attr($s['slug']); ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>">
          <span><?php echo esc_html($s['title']); ?></span><i data-lucide="arrow-up-right"></i>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ ПРОИЗВОДСТВО / ПОЧЕМУ МЫ ============ */
$p_title = $f('production_title');
if ($p_title) :
    $p_img      = $f('production_image', 0);
    $p_text     = $f('production_text');
    $p_caption  = $f('production_caption');
    $p_address  = $f('production_address', eeg_opt('address'));
    $features   = array_values(array_filter((array) $f('production_features', []), fn($r) => !empty($r['title'])));
    ?>
<section id="production" class="px-4 py-24 md:px-6 md:py-32">
  <div class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-16">
    <?php if ($p_img) :
        [$w, $h] = eeg_img_size($p_img);
        ?>
    <figure class="reveal relative overflow-hidden rounded-[32px]" data-anim="fade-right" data-delay="150">
      <img data-parallax src="<?php echo esc_url(eeg_img_url($p_img)); ?>" alt="<?php echo esc_attr($f('production_image_alt') ?: eeg_img_alt($p_img)); ?>" class="aspect-[4/3] w-full rounded-[32px] object-cover md:aspect-[3/2]" loading="lazy"<?php echo $w ? ' width="' . $w . '" height="' . $h . '"' : ''; ?>>
      <?php if ($p_caption || $p_address) : ?>
      <figcaption class="glass-light absolute bottom-4 left-4 right-4 flex items-start gap-3 rounded-[22px] p-4 md:bottom-6 md:left-6 md:right-auto md:max-w-sm md:p-5">
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand text-white"><i data-lucide="map-pin" class="size-5"></i></span>
        <span>
          <span class="block font-bold text-navy"><?php echo esc_html($p_caption); ?></span>
          <span class="block text-sm font-semibold text-muted"><?php echo esc_html($p_address); ?></span>
        </span>
      </figcaption>
      <?php endif; ?>
    </figure>
    <?php endif; ?>

    <div>
      <div class="reveal">
        <h2 class="section-title"><?php echo eeg_home_mark($p_title, 'accent mt-1 block text-brand'); ?></h2>
        <?php if ($p_text) : ?>
        <p class="mt-5 text-[17px] leading-relaxed text-muted"><?php echo esc_html($p_text); ?></p>
        <?php endif; ?>
      </div>
      <?php if ($features) : ?>
      <ul class="mt-10 grid gap-x-8 gap-y-8 sm:grid-cols-2">
        <?php foreach ($features as $i => $r) : ?>
        <li class="reveal feature" data-anim="fade-up" data-delay="<?php echo 150 + 70 * $i; ?>"><span class="feature__icon"><i data-lucide="<?php echo esc_attr(($r['icon'] ?? '') ?: 'check'); ?>"></i></span><div><h3><?php echo esc_html($r['title']); ?></h3><?php if (!empty($r['text'])) : ?><p><?php echo esc_html($r['text']); ?></p><?php endif; ?></div></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ ЭТАПЫ ============ */
$steps = array_values(array_filter((array) $f('steps', []), fn($r) => !empty($r['title'])));
if ($steps) : ?>
<section class="bg-navy px-4 py-24 text-white md:px-6 md:py-28" aria-labelledby="steps-title">
  <div class="mx-auto max-w-7xl">
    <div class="reveal flex flex-col gap-5 md:flex-row md:items-end md:justify-between" data-anim="fade-right" data-delay="150">
      <div>
        <h2 id="steps-title" class="section-title text-white"><?php echo eeg_home_mark($f('steps_title', 'От заявки до *запуска*'), 'accent text-sky-soft'); ?></h2>
      </div>
      <a href="<?php echo esc_url(eeg_req()); ?>" class="btn btn-glass hidden md:inline-flex">Начать с заявки <i data-lucide="arrow-right" class="size-5"></i></a>
    </div>

    <ol class="steps mt-16">
      <?php foreach ($steps as $i => $r) : ?>
      <li class="step reveal" data-anim="fade-up" data-delay="<?php echo 150 + 90 * $i; ?>"><span class="step__num"><?php echo sprintf('%02d', $i + 1); ?></span><h3><?php echo esc_html($r['title']); ?></h3><?php if (!empty($r['text'])) : ?><p><?php echo esc_html($r['text']); ?></p><?php endif; ?></li>
      <?php endforeach; ?>
    </ol>

    <a href="<?php echo esc_url(eeg_req()); ?>" class="reveal btn btn-glass mt-12 flex w-full justify-center md:hidden">Начать с заявки <i data-lucide="arrow-right" class="size-5"></i></a>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ ФОТО С ПРОИЗВОДСТВА И ОБЪЕКТОВ ============ */
$photos = array_values(array_filter((array) $f('projects_photos', []), fn($r) => !empty($r['image'])));
$p_lead = $f('projects_lead');
?>
<section id="projects" class="px-4 py-24 md:px-6 md:py-32">
  <div class="mx-auto max-w-7xl">
    <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between" data-anim="fade-right" data-delay="150">
      <div class="max-w-2xl">
        <h2 class="section-title"><?php echo eeg_home_mark($f('projects_title', 'С производства *на&nbsp;объект*'), 'accent text-brand'); ?></h2>
        <?php if ($p_lead) : ?>
        <p class="mt-4 max-w-xl leading-relaxed text-muted"><?php echo esc_html($p_lead); ?></p>
        <?php endif; ?>
      </div>
      <div class="flex flex-wrap gap-3">
        <a href="<?php echo esc_url(eeg_url('proekty/#fotoalbom')); ?>" class="btn btn-primary">Фотоальбом <i data-lucide="images" class="size-5"></i></a>
        <a href="<?php echo esc_url(eeg_url('proekty/')); ?>" class="btn btn-ghost">Карта проектов <i data-lucide="map-pin" class="size-5"></i></a>
      </div>
    </div>
    <?php if ($photos) : ?>

    <div class="photo-show reveal mt-12" data-photoshow data-anim="fade-up" data-delay="300">
      <a class="photo-show__frame" href="<?php echo esc_url(eeg_url('proekty/#fotoalbom')); ?>" aria-label="Открыть фотоальбом">
        <span class="photo-show__stack">
          <?php foreach ($photos as $i => $r) :
              [$w, $h] = eeg_img_size($r['image']);
              ?>
          <img class="photo-show__img<?php echo $i === 0 ? ' is-active' : ''; ?>" data-photo="<?php echo (int) $i; ?>" src="<?php echo esc_url(eeg_img_url($r['image'])); ?>" alt="<?php echo esc_attr(($r['alt'] ?? '') ?: eeg_img_alt($r['image'], $r['caption'] ?? '')); ?>"<?php echo $w ? ' width="' . $w . '" height="' . $h . '"' : ''; ?> loading="lazy">
          <?php endforeach; ?>
        </span>
        <span class="photo-show__bar">
          <?php foreach ($photos as $i => $r) : ?>
          <span class="photo-show__cap<?php echo $i === 0 ? ' is-active' : ''; ?>" data-cap="<?php echo (int) $i; ?>"><?php echo esc_html($r['caption'] ?? ''); ?></span>
          <?php endforeach; ?>
          <span class="photo-show__go">Открыть фотоальбом <i data-lucide="arrow-up-right" class="size-4"></i></span>
        </span>
      </a>
      <div class="photo-show__dots" role="group" aria-label="Переключение фотографий">
        <?php foreach ($photos as $i => $r) : ?>
        <button type="button" class="photo-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-dot="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr('Фото ' . ($i + 1) . (($r['caption'] ?? '') !== '' ? ': ' . $r['caption'] : '')); ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php
/* ============ FAQ ============ */
$faq = array_values(array_filter((array) $f('faq', []), fn($r) => !empty($r['q'])));
if ($faq) :
    $faq_lead = $f('faq_lead');
    $main     = eeg_phone_main();
    ?>
<section class="px-4 py-24 md:px-6 md:py-32" aria-labelledby="faq-title">
  <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[.8fr_1.2fr] lg:gap-16">
    <div class="reveal">
      <h2 id="faq-title" class="section-title"><?php echo eeg_home_mark($f('faq_title', 'Частые *вопросы*'), 'accent text-brand'); ?></h2>
      <?php if ($faq_lead) : ?>
      <p class="mt-5 max-w-sm text-[17px] leading-relaxed text-muted"><?php echo esc_html($faq_lead); ?></p>
      <?php endif; ?>
      <?php if ($main) : ?>
      <a href="<?php echo esc_attr(eeg_tel($main)); ?>" class="btn btn-ghost mt-8"><i data-lucide="phone" class="size-5"></i><?php echo esc_html($main); ?></a>
      <?php endif; ?>
    </div>
    <div class="faq reveal" data-anim="fade-left" data-delay="300">
      <?php foreach ($faq as $i => $r) : ?>
      <details<?php echo $i === 0 ? ' open' : ''; ?>>
        <summary><?php echo esc_html($r['q']); ?><i data-lucide="plus"></i></summary>
        <?php foreach (eeg_paragraphs($r['a'] ?? '') as $para) : ?>
        <p><?php echo esc_html($para); ?></p>
        <?php endforeach; ?>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/* ============ ЗАЯВКА ============ */
$r_lead     = $f('request_lead');
$r_discount = $f('request_discount');
?>
<section id="request" class="request relative isolate overflow-hidden bg-navy px-4 py-24 text-white md:px-6 md:py-32">
  <div class="mx-auto grid max-w-7xl items-start gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-16">
    <div class="reveal">
      <h2 class="section-title text-white"><?php echo eeg_home_mark($f('request_title', 'Подберём оборудование *под&nbsp;ваш* объект'), 'accent text-sky-soft'); ?></h2>
      <?php if ($r_lead) : ?>
      <p class="mt-5 max-w-md text-[17px] leading-relaxed text-white/80"><?php echo esc_html($r_lead); ?></p>
      <?php endif; ?>
      <?php if ($r_discount) : ?>
      <p class="discount mt-8"><i data-lucide="percent" class="size-5"></i><?php echo esc_html($r_discount); ?></p>
      <?php endif; ?>
      <ul class="mt-10 space-y-4 text-[17px]">
        <?php foreach (eeg_phones() as $p) : ?>
        <li><a href="<?php echo esc_attr(eeg_tel($p['number'])); ?>" class="contact-row"><i data-lucide="phone"></i><span><b><?php echo esc_html($p['number']); ?></b><small><?php echo esc_html($p['label'] ?? ''); ?></small></span></a></li>
        <?php endforeach; ?>
        <li><a href="<?php echo esc_url(eeg_wa()); ?>" class="contact-row" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/67b5ff" alt="" width="20" height="20"><span><b>WhatsApp</b><small><?php echo esc_html(eeg_home_wa_number()); ?></small></span></a></li>
      </ul>
    </div>

    <form class="request-form reveal rounded-[32px] bg-white p-6 text-ink shadow-soft md:p-9" novalidate data-anim="fade-left" data-delay="300">
      <fieldset>
        <legend class="text-sm font-bold text-muted">Назначение</legend>
        <div class="seg mt-3">
          <label><input type="radio" name="purpose" value="Очистные" checked><span>Очистные</span></label>
          <label><input type="radio" name="purpose" value="КНС"><span>КНС</span></label>
          <label><input type="radio" name="purpose" value="Ёмкости"><span>Ёмкости</span></label>
          <label><input type="radio" name="purpose" value="Другое"><span>Другое</span></label>
        </div>
      </fieldset>
      <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <label class="field"><span>Объём, м³ или расход, л/с</span><input type="text" name="volume" inputmode="decimal" placeholder="Например, 25 м³"></label>
        <label class="field"><span>Ваше имя</span><input type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться"></label>
        <label class="field sm:col-span-2"><span>Телефон <b class="text-brand">*</b></span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 ___ ___-__-__" required><em class="field__err" hidden>Укажите номер, чтобы мы могли перезвонить</em></label>
        <label class="field sm:col-span-2"><span>Комментарий</span><textarea name="comment" rows="3" placeholder="Размеры, место установки, среда, город доставки"></textarea></label>
      </div>
      <?php eeg_form_hidden(); ?>
      <button type="submit" class="btn btn-primary mt-7 w-full justify-center">Отправить заявку <i data-lucide="send" class="size-5"></i></button>
      <p class="mt-4 text-center text-sm font-semibold text-muted">Нажимая кнопку, вы соглашаетесь на обработку персональных данных.</p>
      <div class="form-ok" hidden role="status">
        <span class="grid size-14 place-items-center rounded-full bg-mist text-brand"><i data-lucide="check" class="size-7"></i></span>
        <p class="mt-5 font-display text-2xl font-medium">Заявка принята</p>
        <p class="mt-2 text-muted">Менеджер перезвонит в рабочее время: <?php echo esc_html(mb_strtolower(eeg_hours())); ?>.</p>
      </div>
    </form>
  </div>
</section>

<?php
get_footer();
