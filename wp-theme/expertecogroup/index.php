<?php
/** Запасной шаблон: обычная страница с текстом из редактора. */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) :
    the_post();
    echo eeg_page_head(['actions' => false]);
    ?>
<section class="px-4 pb-16 md:px-6">
  <div class="prose mx-auto max-w-3xl text-[17px] leading-relaxed text-muted"><?php the_content(); ?></div>
</section>
    <?php
endwhile;
echo eeg_cta_band();
get_footer();
