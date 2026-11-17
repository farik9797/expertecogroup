<?php
/**
 * Template Name: О компании
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
$intro = eeg_field('about_intro_title', '', $id);
$paras = eeg_paragraphs(eeg_field('about_text', '', $id));
$photo = eeg_field('about_photo', 0, $id);
$steps_title = eeg_field('about_steps_title', '', $id);
$steps = (array) eeg_field('about_steps', [], $id);
$note = eeg_field('about_note', '', $id);
$what_title = eeg_field('about_what_title', '', $id);
$what = (array) eeg_field('about_what', [], $id);

echo eeg_page_head();

if ($intro !== '' || $paras) : ?>

<section class="px-4 pb-4 pt-2 md:px-6">
  <div class="mx-auto max-w-7xl">
    <?php if ($intro !== '') : ?><h2 class="section-title reveal" data-anim="fade-right" data-delay="150"><?php echo eeg_company_marked($intro, '<span class="accent text-brand">', '</span>'); ?></h2><?php endif; ?>
    <div class="about-text reveal mt-2 text-[17px] leading-relaxed text-muted" data-anim="fade-up" data-delay="300">
      <?php foreach ($paras as $p) : ?><p class="mt-5"><?php echo wp_kses_post($p); ?></p>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif;

if ($photo || $steps_title !== '' || $steps || $note !== '') : ?>

<section class="px-4 pb-12 md:px-6">
  <div class="mx-auto grid max-w-7xl items-start gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
    <?php if ($photo) : ?><img class="w-full rounded-[28px] object-cover" src="<?php echo esc_url(eeg_img_url($photo)); ?>" alt="<?php echo esc_attr(eeg_field('about_photo_alt', eeg_img_alt($photo), $id)); ?>" loading="lazy"><?php endif; ?>
    <div>
      <?php if ($steps_title !== '') : ?><h2 class="section-title"><?php echo esc_html($steps_title); ?></h2><?php endif; ?>
      <?php if ($steps) : ?><ol class="steps steps--vertical mt-8"><?php foreach ($steps as $s) : ?><li class="step step--light"><span class="step__num"><?php echo esc_html($s['year'] ?? ''); ?></span><p><?php echo esc_html($s['text'] ?? ''); ?></p></li><?php endforeach; ?></ol><?php endif; ?>
      <?php if ($note !== '') : ?><div class="note mt-8">
        <i data-lucide="factory"></i>
        <p><?php echo esc_html($note); ?></p>
      </div><?php endif; ?>
    </div>
  </div>
</section>
<?php endif;

if ($what) : ?>

<section class="bg-mist px-4 py-16 md:px-6 md:py-20">
  <div class="mx-auto max-w-7xl">
    <?php if ($what_title !== '') : ?><h2 class="section-title"><?php echo esc_html($what_title); ?></h2><?php endif; ?>
    <div class="mt-10 grid gap-8 md:grid-cols-3">
      <?php foreach ($what as $w) : ?><div class="eq-group"><h3 class="font-display text-lg font-medium text-navy"><?php echo esc_html($w['title'] ?? ''); ?></h3><p class="mt-3 leading-relaxed text-muted"><?php echo eeg_company_marked($w['text'] ?? '', '<b class="font-bold text-navy">', '</b>'); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif;

echo eeg_cta_band();
get_footer();
