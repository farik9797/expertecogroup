<?php
/**
 * Template Name: Тип ёмкости
 * /catalog/emkosti/<тип>/: маркировки, фото, характеристики, описание, применения, типоразмеры, калькулятор с фиксированной формой.
 */
defined('ABSPATH') || exit;
get_header();
$id     = get_queried_object_id();
$parent = wp_get_post_parent_id($id);
$title  = wp_strip_all_tags(eeg_field('head_title', get_the_title($id), $id));
$short  = eeg_crumb_name($id);
$marks  = eeg_tanks_marks($id);
$shape  = eeg_field('tank_shape', 'under', $id);
$sizes  = eeg_tanks_sizes($id);

$crumbs = [['Каталог', eeg_url('catalog/')]];
if ($parent) {
    $crumbs[] = [eeg_crumb_name($parent), get_permalink($parent)];
}
$crumbs[] = [$short, null];

// маркировки — то, что ищут по рекламе: плашками сразу под подзаголовком
$chips = '';
$lines = '';
foreach ($marks as [$code, $what]) {
    $chips .= '<li><b>' . esc_html($code) . '</b><span>' . esc_html($what) . '</span></li>';
    $lines .= '<span class="mark-line"><b>' . esc_html($code) . '</b> ' . esc_html($what) . '</span>';
}
echo eeg_page_head([
    'crumbs'  => $crumbs,
    'actions' => false,
    'extra'   => $chips ? "\n      <ul class=\"mark-chips\" aria-label=\"Маркировки\">$chips</ul>" : '',
]);

$specs = '';
foreach ((array) eeg_field('tank_specs', [], $id) as $r) {
    $k = trim((string) ($r['name'] ?? ''));
    $v = trim((string) ($r['value'] ?? ''));
    if ($k === '' && $v === '') {
        continue;
    }
    $dd = mb_strtolower($k) === 'маркировка' && $lines ? $lines : esc_html($v);
    $specs .= '<div><dt>' . esc_html($k) . '</dt><dd>' . $dd . '</dd></div>';
}
$text = eeg_paragraphs(eeg_field('tank_text', '', $id));
$uses = eeg_list(eeg_field('tank_uses', [], $id));
?>

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">
    <?php echo eeg_gallery(eeg_field('tank_gallery', [], $id), $title); ?>
    <div class="reveal" data-anim="fade-left" data-delay="300">
      <h2 class="section-title">Коротко о конструкции</h2>
      <?php if ($specs) : ?><dl class="spec mt-6"><?php echo $specs; ?></dl><?php endif; ?>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="<?php echo esc_url(eeg_req()); ?>" class="btn btn-primary">Оставить заявку <i data-lucide="arrow-right" class="size-5"></i></a>
        <?php echo eeg_wa_btn('ghost'); ?>
      </div>
      <p class="mt-4 text-sm font-semibold text-muted">Стоимость зависит от объёма, толщины листа и комплектации — считаем под объект.</p>
    </div>
  </div>
</section>

<section class="px-4 py-14 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <?php if ($text) : ?>
    <div class="reveal" data-anim="fade-right" data-delay="150">
      <h2 class="section-title">Описание</h2>
      <div class="mt-2 text-[17px] leading-relaxed text-muted"><?php foreach ($text as $p) {
          echo '<p class="mt-4">' . wp_kses_post($p) . '</p>';
      } ?></div>
    </div>
    <?php endif; ?>
    <div class="reveal" data-anim="fade-left" data-delay="300">
      <?php if ($uses) : ?>
      <h2 class="section-title">Где применяют</h2>
      <ul class="uses mt-6"><?php foreach ($uses as $u) {
          echo '<li>' . esc_html($u) . '</li>';
      } ?></ul>
      <?php endif; ?>
      <div class="note mt-8">
        <i data-lucide="truck"></i>
        <p>Доставляем по Казахстану и странам СНГ, помогаем с погрузкой, монтажом и пусконаладкой.</p>
      </div>
    </div>
  </div>
</section>

<?php if ($sizes) : ?>
<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150">Типоразмеры</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Ряд «<?php echo esc_html($short); ?>» — выберите ближайший объём или закажите нестандартный по чертежам.</p>
    <div class="reveal mt-8" data-anim="fade-up" data-delay="300"><?php echo eeg_tanks_size_table($sizes, $shape, trim((string) eeg_field('tank_code', '', $id))); ?></div>
  </div>
</section>
<?php endif; ?>

<section id="calc" class="px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl"><?php get_template_part('parts/tanks-calc', null, ['shape' => $shape, 'fixed' => true]); ?></div>
</section>

<?php
echo eeg_cta_band('Рассчитаем резервуар под ваш объект');
get_footer();
