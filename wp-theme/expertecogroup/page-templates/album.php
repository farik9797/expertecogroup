<?php
/**
 * Template Name: Фотоальбом
 * Страница альбома — дочерняя страница «Фотоальбома». Название — заголовок страницы, описание — «Подзаголовок».
 * Пока фото нет, в макете у альбома нет страницы: временно (302) ведём на «Проекты» → вкладка «Фотоальбом»,
 * чтобы пустая страница не попала к посетителям и в поиск; как только фото добавят, страница откроется сама.
 */
defined('ABSPATH') || exit;
$id = get_queried_object_id();
$photos = eeg_company_album_photos($id);
$proj = get_page_by_path('proekty', OBJECT, 'page');
$proj_url = $proj ? get_permalink($proj) : eeg_url('proekty/');
if (!$photos) {
    wp_safe_redirect($proj_url . '#fotoalbom', 302);
    exit;
}

get_header();
$title = eeg_field('head_title', get_the_title($id), $id);
$alt = wp_strip_all_tags($title);

echo eeg_page_head([
    'crumbs'  => [
        [$proj ? eeg_crumb_name($proj->ID) : 'Проекты', $proj_url],
        ['Фотоальбом', $proj_url . '#fotoalbom'],
        [eeg_crumb_name($id), null],
    ],
    'actions' => false,
]);
?>

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="album-grid"><?php foreach ($photos as $ph) :
        $srcset = eeg_img_srcset($ph); ?><figure class="album-photo<?php echo esc_attr(eeg_fit_class($ph)); ?>"><img src="<?php echo esc_url(eeg_img_url($ph)); ?>"<?php if ($srcset) : ?> srcset="<?php echo esc_attr($srcset); ?>" sizes="(max-width: 767px) 100vw, (max-width: 1099px) 50vw, 33vw"<?php endif; ?> alt="<?php echo esc_attr($alt); ?>" loading="lazy"></figure><?php endforeach; ?></div>
    <a href="<?php echo esc_url($proj_url . '#fotoalbom'); ?>" class="btn btn-ghost mt-8">Все альбомы <i data-lucide="images" class="size-5"></i></a>
  </div>
</section>

<?php
echo eeg_cta_band();
get_footer();
