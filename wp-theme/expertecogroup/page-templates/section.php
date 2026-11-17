<?php
/**
 * Template Name: Раздел каталога
 * Страница раздела каталога (КНС, ЛОС, жироуловители, компрессоры, услуги…) — section_page в генераторе макета.
 * Поля — inc/fields-catalog.php (группа «Раздел каталога»).
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
$title = wp_strip_all_tags(eeg_field('head_title', get_the_title($id), $id));

echo eeg_page_head(['crumbs' => [['Каталог', eeg_url('catalog/')], [eeg_crumb_name($id), null]]]);

$text = '';
foreach (eeg_paragraphs(eeg_field('section_text', '', $id)) as $p) {
    $text .= '<p class="mt-4">' . wp_kses_post($p) . '</p>';
}
$cover = eeg_field('section_cover', 0, $id);
$cover_html = $cover
    ? '<img class="reveal aspect-[4/3] w-full rounded-[28px] object-cover' . esc_attr(eeg_catalog_cover_class($cover)) . '" data-anim="fade-left" data-delay="300" src="' . esc_url(eeg_img_url($cover)) . '" alt="' . esc_attr($title) . '" loading="lazy">'
    : '';

$points = '';
foreach ((array) eeg_field('section_points', [], $id) as $pt) {
    if (trim(($pt['title'] ?? '') . ($pt['text'] ?? '')) === '') {
        continue;
    }
    $points .= '<li class="feature"><span class="feature__icon"><i data-lucide="' . esc_attr(($pt['icon'] ?? '') ?: 'check') . '"></i></span><div><h3>'
        . esc_html($pt['title'] ?? '') . '</h3><p>' . esc_html($pt['text'] ?? '') . '</p></div></li>';
}

$uses = '';
foreach (eeg_list(eeg_field('section_uses', [], $id)) as $u) {
    $uses .= '<li>' . esc_html($u) . '</li>';
}

$sizes = array_values(array_filter((array) eeg_field('section_sizes', [], $id), fn($r) => trim(implode('', (array) $r)) !== ''));

$photos = '';
$photo_rows = array_values(array_filter((array) eeg_field('section_photos', [], $id), fn($r) => !empty($r['image'])));
if (count($photo_rows) >= 2) { // один кадр рядом с обложкой выглядит пусто — как в макете, блок от двух снимков
    foreach ($photo_rows as $ph) {
        $alt = trim($ph['alt'] ?? '') !== '' ? $ph['alt'] : eeg_img_alt($ph['image'], $title);
        $photos .= '<figure class="album-photo"><img src="' . esc_url(eeg_img_url($ph['image'])) . '" alt="' . esc_attr($alt) . '" loading="lazy"></figure>';
    }
}
?>
<?php if ($text || $cover_html) : ?>

<section class="px-4 pb-10 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="reveal text-[17px] leading-relaxed text-muted" data-anim="fade-right" data-delay="150"><?php echo $text; ?></div>
    <?php echo $cover_html; ?>
  </div>
</section>
<?php endif; ?>
<?php if ($points) : ?>

<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150">Что берём на себя</h2>
    <ul class="reveal mt-10 grid gap-x-8 gap-y-8 md:grid-cols-2" data-anim="fade-up" data-delay="300"><?php echo $points; ?></ul>
  </div>
</section>
<?php endif; ?>

<section class="px-4 pb-12 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="reveal" data-anim="fade-right" data-delay="150">
      <?php if ($uses) : ?>
      <h2 class="section-title">Где применяют</h2>
      <ul class="uses mt-6"><?php echo $uses; ?></ul>
      <?php endif; ?>
    </div>
    <div class="note reveal" data-anim="fade-left" data-delay="300">
      <i data-lucide="file-text"></i>
      <p>Пришлите исходные данные по объекту — расход, состав стоков, глубину подводящей трубы или чертёж. Подберём решение и посчитаем стоимость.</p>
    </div>
  </div>
</section>
<?php if ($sizes) : ?>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150">Типоразмеры</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Ряд собственного производства — выберите модель по производительности и залповому сбросу.</p>
    <div class="reveal mt-8" data-anim="fade-up" data-delay="300"><div class="size-table-box">
  <div class="size-table-wrap">
    <table class="size-table size-table--wide">
      <thead>
        <tr><th rowspan="2"><?php echo esc_html(eeg_field('section_sizes_mark', 'Марка', $id)); ?></th><th rowspan="2">Габариты Д×Ш×В, мм</th><th colspan="2">Производительность</th><th rowspan="2">Залповый сброс, л</th><th colspan="3">Подводящий/отводящий патрубок</th></tr>
        <tr><th>м³/ч</th><th>л/с</th><th>вход</th><th>выход</th><th>Ø</th></tr>
      </thead>
      <tbody><?php
        foreach ($sizes as $r) {
            echo '<tr><td><b>' . esc_html($r['mark'] ?? '') . '</b></td>';
            foreach (['dims', 'q', 'ls', 'salvo', 'inlet', 'outlet', 'dn'] as $col) {
                echo '<td>' . esc_html($r[$col] ?? '') . '</td>';
            }
            echo '</tr>';
        }
      ?></tbody>
    </table>
  </div>
  <p class="size-table-swipe"><i data-lucide="move-horizontal"></i>Листайте таблицу вбок</p>
</div>
<p class="mt-3 text-sm font-semibold text-muted">Высота подводящего и отводящего патрубка указана от дна корпуса. Нестандартный размер изготовим по вашим чертежам.</p></div>
  </div>
</section>
<?php endif; ?>
<?php if ($photos) : ?>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150">Фото</h2>
    <p class="mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">Наши изделия и объекты. Больше снимков — в фотоальбоме.</p>
    <div class="album-grid reveal mt-8" data-anim="fade-up" data-delay="300"><?php echo $photos; ?></div>
    <a href="<?php echo esc_url(eeg_url('proekty/#fotoalbom')); ?>" class="btn btn-ghost mt-8">Открыть фотоальбом <i data-lucide="images" class="size-5"></i></a>
  </div>
</section>
<?php endif; ?>

<?php
echo eeg_cta_band('Подберём оборудование под вашу задачу');
get_footer();
