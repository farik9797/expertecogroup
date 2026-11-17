<?php
/**
 * Template Name: Проекты
 * Вкладки «Карта проектов» (Leaflet, объекты — поле «Объекты на карте») и «Фотоальбом»
 * (карточки — дочерние страницы «Фотоальбома»). Вкладки и карту оживляет assets/js/main.js:
 * ему нужны .proj-tabs, панели #karta / #fotoalbom, #kz-map[data-projects] и .pin-card[data-pin].
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
$pins = eeg_company_pins($id);
$note = eeg_field('proj_map_note', '', $id);

echo eeg_page_head(['actions' => false]);
?>

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto max-w-7xl">
    <div class="proj-tabs" role="tablist" aria-label="Проекты">
      <button type="button" class="btn btn-primary" role="tab" id="tab-karta" aria-controls="karta" aria-selected="true" data-tab="karta">Карта проектов <i data-lucide="map-pin" class="size-5"></i></button>
      <button type="button" class="btn btn-ghost" role="tab" id="tab-fotoalbom" aria-controls="fotoalbom" aria-selected="false" data-tab="fotoalbom">Фотоальбом <i data-lucide="images" class="size-5"></i></button>
    </div>

    <div class="proj-pane mt-8" id="karta" role="tabpanel" aria-labelledby="tab-karta">
      <div class="map-layout">
        <div id="kz-map" class="kz-map" data-projects="<?php echo esc_attr(eeg_company_json($pins)); ?>"></div>
        <div class="pin-list"><?php foreach ($pins as $p) :
            $meta = implode(' · ', array_filter([$p['kind'], $p['year']], 'strlen')); ?><article class="pin-card<?php echo $p['confirmed'] ? '' : ' pin-card--draft'; ?>" data-pin="<?php echo esc_attr($p['id']); ?>" id="pin-<?php echo esc_attr($p['id']); ?>">
  <button type="button" class="pin-card__head">
    <span>
      <span class="pin-card__city"><?php echo esc_html($p['city'] . ($p['region'] !== '' ? ' · ' . $p['region'] : '')); ?></span>
      <span class="pin-card__title"><?php echo esc_html($p['title']); ?></span>
    </span>
    <i data-lucide="plus"></i>
  </button>
  <div class="pin-card__body">
    <?php if ($meta !== '') : ?><p class="pin-card__meta"><?php echo esc_html($meta); ?></p><?php endif; ?>
    <?php if ($p['text'] !== '') : ?><p><?php echo esc_html($p['text']); ?></p><?php endif; ?>
    <?php if ($p['done']) : ?><ul class="uses mt-4"><?php foreach ($p['done'] as $d) : ?><li><?php echo esc_html($d); ?></li><?php endforeach; ?></ul><?php endif; ?>
  </div>
</article><?php endforeach; ?></div>
      </div>
      <?php if ($note !== '') : ?><p class="mt-5 text-sm font-semibold text-muted"><?php echo esc_html($note); ?></p><?php endif; ?>
    </div>

    <div class="proj-pane mt-8" id="fotoalbom" role="tabpanel" aria-labelledby="tab-fotoalbom" hidden>
      <div class="album-cards"><?php echo eeg_company_album_cards(); ?></div>
    </div>
  </div>
</section>

<?php
echo eeg_cta_band();
get_footer();
