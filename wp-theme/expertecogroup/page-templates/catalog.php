<?php
/**
 * Template Name: Каталог
 * Страница /catalog/: основные направления (карточки) и группы плиток разделов — catalog_page в генераторе макета.
 * Плитки — дочерние страницы с шаблоном «Раздел каталога» (порядок — menu_order, группа/картинка/подпись — поля раздела).
 * Поля — inc/fields-catalog.php (группа «Каталог»).
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();

echo eeg_page_head();

$dirs = '';
foreach ((array) eeg_field('catalog_directions', [], $id) as $d) {
    $name = $d['title'] ?? '';
    if (trim($name) === '') {
        continue;
    }
    $img = $d['image'] ?? 0;
    $tag = trim($d['tag'] ?? '') !== '' ? "\n    <span class=\"chip self-start\">" . esc_html($d['tag']) . '</span>' : '';
    $dirs .= '<a class="cat-card" href="' . esc_url(eeg_url($d['link'] ?? '')) . '">
  <span class="cat-card__ph' . esc_attr(eeg_fit_class($img)) . '"><img src="' . esc_url(eeg_img_url($img)) . '" alt="' . esc_attr($name) . '" loading="lazy"></span>
  <span class="flex flex-1 flex-col p-6 md:p-7">' . $tag . '
    <span class="mt-4 font-display text-[1.35rem] font-medium leading-tight text-navy md:text-[1.55rem]">' . esc_html($name) . '</span>
    <span class="mt-3 leading-relaxed text-muted">' . esc_html($d['text'] ?? '') . '</span>
    <span class="cat-card__more mt-6">Подробнее <i data-lucide="arrow-right" class="size-4"></i></span>
  </span>
</a>';
}

// плитки разделов по группам
$tiles = [];
$sections = get_posts([
    'post_type' => 'page', 'post_parent' => $id, 'numberposts' => -1,
    'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/section.php',
]);
foreach ($sections as $s) {
    $group = eeg_field('section_group', '', $s->ID);
    if ($group === '') {
        continue;
    }
    $name = eeg_crumb_name($s->ID);
    $img = eeg_field('section_tile_image', 0, $s->ID) ?: eeg_field('section_cover', 0, $s->ID);
    $src = $img ? eeg_img_url($img) : eeg_asset('img/cat-water.webp');
    $tag = eeg_field('section_tile_tag', '', $s->ID);
    if ($tag === '') {
        $facts = eeg_field('facts', [], $s->ID);
        $tag = $facts[0]['a'] ?? '';
    }
    $tiles[$group] = ($tiles[$group] ?? '') . '<a class="prod-tile" href="' . esc_url(get_permalink($s)) . '">
  <span class="prod-tile__head">
    <span>
      <span class="prod-tile__title">' . esc_html($name) . '</span>
      <span class="prod-tile__count">' . esc_html($tag) . '</span>
    </span>
    <i data-lucide="arrow-up-right"></i>
  </span>
  <span class="prod-tile__ph"><img src="' . esc_url($src) . '" alt="' . esc_attr($name) . '" loading="lazy"></span>
</a>';
}
?>
<?php if ($dirs) : ?>

<section id="napravleniya" class="px-4 py-8 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="dir-grid reveal" data-anim="fade-up" data-delay="150"><?php echo $dirs; ?></div>
  </div>
</section>
<?php endif; ?>

<?php foreach (eeg_catalog_groups($id) as $g) :
    if (empty($tiles[$g['anchor']])) {
        continue;
    } ?>
<section class="px-4 py-8 md:px-6" id="<?php echo esc_attr($g['anchor']); ?>">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150"><?php echo esc_html($g['title']); ?></h2>
    <div class="prod-tiles reveal mt-8" data-anim="fade-up" data-delay="300"><?php echo $tiles[$g['anchor']]; ?></div>
  </div>
</section>
<?php endforeach; ?>

<?php
echo eeg_cta_band();
get_footer();
