<?php
/**
 * Template Name: Ёмкости
 * /catalog/emkosti/: вводный текст с фото, карточки типов (дочерние страницы с шаблоном «Тип ёмкости»), калькулятор.
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
echo eeg_page_head(['crumbs' => [['Каталог', eeg_url('catalog/')], [eeg_crumb_name($id), null]]]);

$intro = eeg_paragraphs(eeg_field('tanks_intro', '', $id));
$photo = eeg_field('tanks_intro_img', 0, $id);
if ($intro || $photo) : ?>

<section class="px-4 pb-6 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1.1fr_.9fr] lg:gap-14">
    <div class="reveal text-[17px] leading-relaxed text-muted" data-anim="fade-right" data-delay="150">
      <?php foreach ($intro as $p) {
          echo '<p class="mt-4">' . wp_kses_post($p) . '</p>';
      } ?>
    </div>
    <?php if ($photo) : ?>
    <img class="reveal aspect-[4/3] w-full rounded-[28px] object-cover" data-anim="fade-left" data-delay="300" src="<?php echo esc_url(eeg_img_url($photo)); ?>" alt="<?php echo esc_attr(eeg_field('tanks_intro_alt', eeg_img_alt($photo), $id)); ?>" loading="lazy">
    <?php endif; ?>
  </div>
</section>
<?php endif;

// карточки типов — дочерние страницы «Тип ёмкости» в порядке «Порядок» (menu_order)
$types = get_posts([
    'post_type'   => 'page',
    'post_parent' => $id,
    'numberposts' => -1,
    'orderby'     => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'meta_key'    => '_wp_page_template',
    'meta_value'  => 'page-templates/tank-type.php',
]);
if ($types) : ?>

<section class="px-4 py-12 md:px-6">
  <div class="mx-auto max-w-7xl">
    <h2 class="section-title reveal" data-anim="fade-right" data-delay="150">Типы резервуаров</h2>
    <div class="type-grid reveal mt-8" data-anim="fade-up" data-delay="300"><?php
    foreach ($types as $t) {
        $gal   = array_values(array_filter((array) eeg_field('tank_gallery', [], $t->ID)));
        $range = eeg_tanks_range(eeg_tanks_sizes($t->ID));
        $title = wp_strip_all_tags(eeg_field('head_title', get_the_title($t), $t->ID));
        $lead  = eeg_field('head_lead', '', $t->ID);
        $ph    = $gal ? '
  <span class="type-card__ph"><img src="' . esc_url(eeg_img_small($gal[0])) . '" alt="' . esc_attr($title) . '" loading="lazy"></span>' : '';
        echo '<a class="type-card" href="' . esc_url(get_permalink($t)) . '">' . $ph . '
  <span class="type-card__body">
    <span class="type-card__title">' . esc_html(eeg_crumb_name($t->ID)) . '</span>'
            . ($lead !== '' ? '
    <span class="type-card__lead">' . wp_kses_post($lead) . '</span>' : '') . '
    <span class="type-card__meta">' . ($range !== '' ? esc_html($range . ' м³') : '') . '<i data-lucide="arrow-right"></i></span>
  </span>
</a>';
    } ?></div>
  </div>
</section>
<?php endif; ?>

<section id="calc" class="bg-mist px-4 py-16 md:px-6 md:py-24">
  <div class="reveal mx-auto max-w-7xl" data-anim="fade-up" data-delay="150">
<?php get_template_part('parts/tanks-calc', null, ['shape' => 'under']); ?>
  </div>
</section>

<?php
echo eeg_cta_band('Рассчитаем резервуар под ваш объект');
get_footer();
