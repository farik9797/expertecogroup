<?php
/**
 * Template Name: Контакты
 * Телефоны — свой список страницы (их здесь больше, чем в шапке и подвале), пусто — из «Настроек сайта».
 * WhatsApp, почта, адрес и часы работы — из «Настроек сайта».
 */
defined('ABSPATH') || exit;
get_header();
$id = get_queried_object_id();
$phones = array_values(array_filter((array) eeg_field('contacts_phones', [], $id), fn($r) => !empty($r['number']))) ?: eeg_phones();
$email = eeg_email();
$address = eeg_opt('address', 'Каскелен, ул. Курылысшы, 1а/1');
$address_map = eeg_opt('address_map');
$hours = eeg_hours();
$wa_view = eeg_company_phone_view(eeg_opt('whatsapp', '77712282203'));
$note = eeg_field('contacts_note', '', $id);
$form_title = eeg_field('contacts_form_title', '', $id);
$form_text = eeg_field('contacts_form_text', '', $id);

echo eeg_page_head(['actions' => false]);
?>

<section class="px-4 pb-16 md:px-6">
  <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
    <div>
      <ul class="space-y-4 text-[17px]"><?php foreach ($phones as $p) : ?><li><a href="<?php echo esc_attr(eeg_tel($p['number'])); ?>" class="contact-row contact-row--light"><i data-lucide="phone"></i><span><b><?php echo esc_html($p['number']); ?></b><small><?php echo esc_html($p['label'] ?? ''); ?></small></span></a></li><?php endforeach; ?></ul>
      <ul class="mt-8 space-y-4 text-[17px]">
        <li><a href="<?php echo esc_url(eeg_wa()); ?>" class="contact-row contact-row--light" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/1e65ff" alt="" width="20" height="20"><span><b>WhatsApp</b><small><?php echo esc_html($wa_view); ?></small></span></a></li>
        <?php if ($email) : ?><li><a href="<?php echo esc_attr('mailto:' . $email); ?>" class="contact-row contact-row--light"><i data-lucide="mail"></i><span><b><?php echo esc_html($email); ?></b><small><?php echo esc_html(eeg_field('contacts_email_note', '', $id)); ?></small></span></a></li><?php endif; ?>
        <?php if ($address) :
            $addr_inner = '<i data-lucide="map-pin"></i><span><b>' . esc_html($address) . '</b><small>' . esc_html(eeg_field('contacts_address_note', '', $id)) . '</small></span>'; ?>
        <li><?php if ($address_map) : ?><a href="<?php echo esc_url($address_map); ?>" class="contact-row contact-row--light" target="_blank" rel="noopener"><?php echo $addr_inner; ?></a><?php else : ?><span class="contact-row contact-row--light"><?php echo $addr_inner; ?></span><?php endif; ?></li>
        <?php endif; ?>
        <?php if ($hours) : ?><li><span class="contact-row contact-row--light"><i data-lucide="clock"></i><span><b><?php echo esc_html($hours); ?></b><small><?php echo esc_html(eeg_field('contacts_hours_note', '', $id)); ?></small></span></span></li><?php endif; ?>
      </ul>
      <?php if ($note !== '') : ?><div class="note mt-8"><i data-lucide="file-text"></i><p><?php echo esc_html($note); ?></p></div><?php endif; ?>
    </div>
    <div class="rounded-[28px] bg-mist p-6 md:p-8">
      <?php if ($form_title !== '') : ?><h2 class="font-display text-xl font-medium text-navy"><?php echo esc_html($form_title); ?></h2><?php endif; ?>
      <?php if ($form_text !== '') : ?><p class="mt-3 leading-relaxed text-muted"><?php echo esc_html($form_text); ?></p><?php endif; ?>
      <a href="<?php echo esc_url(eeg_req()); ?>" class="btn btn-primary mt-6">Открыть форму <i data-lucide="arrow-right" class="size-5"></i></a>
    </div>
  </div>
</section>

<?php
get_footer();
