<?php
/** Подвал, попап заявки, кнопка «наверх», мобильная панель связи (как в макете site/index.html). */
defined('ABSPATH') || exit;
$home    = is_front_page();
$phones  = eeg_phones();
$main    = eeg_phone_main();
$address = eeg_opt('address', 'Каскелен, ул. Курылысшы, 1а/1');
$map     = eeg_opt('address_map', 'https://yandex.kz/maps/?text=%D0%9A%D0%B0%D1%81%D0%BA%D0%B5%D0%BB%D0%B5%D0%BD%2C%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%9A%D1%83%D1%80%D1%8B%D0%BB%D1%8B%D1%81%D1%88%D1%8B%2C%201%D0%B0%2F1');
$company = eeg_opt('company', 'ТОО «Expert ECO Group»');
?>
</main>

<footer id="contacts" class="bg-navy-deep px-4 pb-28 pt-20 text-white/80 md:px-6 md:pb-12">
  <div class="mx-auto grid max-w-7xl gap-12 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.2fr]">
    <div class="reveal" data-anim="zoom-in-up" data-delay="150">
      <a href="<?php echo esc_url($home ? '#top' : home_url('/')); ?>" class="flex items-center gap-3 text-white">
        <span class="logo-chip"><img src="<?php echo esc_url(eeg_asset('img/logo-eeg.webp')); ?>" alt="" width="36" height="37" loading="lazy"></span>
        <span class="font-display text-lg font-medium">Expert ECO Group</span>
      </a>
      <p class="mt-5 max-w-xs leading-relaxed"><?php echo esc_html(eeg_opt('footer_about', 'ТОО «Expert ECO Group». С 2014 года — оборудование из полипропилена для водоочистки и водоотведения. Работаем по Казахстану и странам СНГ.')); ?></p>
    </div>
    <div class="reveal" data-anim="zoom-in-up" data-delay="300">
      <h3 class="footer-h">Каталог</h3>
      <ul class="footer-list">
        <li><a href="<?php echo esc_url(eeg_url('catalog/emkosti/')); ?>">Ёмкости и резервуары</a></li>
        <li><a href="<?php echo esc_url(eeg_url('catalog/kns/')); ?>">Канализационные насосные станции</a></li>
        <li><a href="<?php echo esc_url(eeg_url('catalog/los/')); ?>">Очистные сооружения</a></li>
        <li><a href="<?php echo esc_url(eeg_url('catalog/')); ?>">Всё оборудование</a></li>
        <li><a href="<?php echo esc_url(eeg_url('catalog/emkosti/#calc')); ?>">Калькулятор ёмкости</a></li>
      </ul>
    </div>
    <div class="reveal" data-anim="zoom-in-up" data-delay="450">
      <h3 class="footer-h">Компания</h3>
      <ul class="footer-list">
        <li><a href="<?php echo esc_url(eeg_url('o-kompanii/')); ?>">О компании</a></li>
        <li><a href="<?php echo esc_url(eeg_url('proekty/')); ?>">Проекты</a></li>
        <li><a href="<?php echo esc_url(eeg_url('proekty/#fotoalbom')); ?>">Фотоальбом</a></li>
        <li><a href="<?php echo esc_url(eeg_url('proekty/')); ?>">Карта проектов</a></li>
        <li><a href="<?php echo esc_url(eeg_req()); ?>">Расчёт стоимости</a></li>
      </ul>
    </div>
    <div class="reveal" data-anim="zoom-in-up" data-delay="600">
      <h3 class="footer-h">Контакты</h3>
      <ul class="footer-list">
        <?php foreach ($phones as $p) : ?>
        <li><a href="<?php echo esc_attr(eeg_tel($p['number'])); ?>" class="text-white"><?php echo esc_html($p['number']); ?></a> <small class="block text-white/55"><?php echo esc_html($p['label'] ?? ''); ?></small></li>
        <?php endforeach; ?>
        <li><a href="mailto:<?php echo esc_attr(eeg_email()); ?>"><?php echo esc_html(eeg_email()); ?></a></li>
        <li><a href="<?php echo esc_url($map); ?>" target="_blank" rel="noopener"><?php echo esc_html($address); ?></a></li>
        <li><?php echo esc_html(eeg_hours()); ?></li>
      </ul>
    </div>
  </div>
  <div class="mx-auto mt-16 flex max-w-7xl flex-col gap-3 border-t border-white/10 pt-6 text-sm text-white/50 md:flex-row md:justify-between">
    <p>© <span class="year"><?php echo esc_html(wp_date('Y')); ?></span> <?php echo esc_html($company); ?></p>
    <p>Информация на сайте не является публичной офертой</p>
  </div>
</footer>

<dialog id="request-modal" class="modal" aria-labelledby="modal-title">
  <div class="modal__card">
    <button type="button" class="modal__close" aria-label="Закрыть"><i data-lucide="x"></i></button>
    <h2 id="modal-title" class="modal__title ">Рассчитаем <span class="accent text-brand">стоимость</span></h2>
    <p class="modal__lead mt-2">Оставьте номер — перезвоним в рабочее время, <?php echo esc_html(mb_strtolower(eeg_hours())); ?>.</p>

    <form class="request-form mt-5" novalidate>
      <fieldset>
        <legend class="text-sm font-bold text-muted">Назначение</legend>
        <div class="seg mt-3">
          <label><input type="radio" name="purpose" value="Очистные" checked><span>Очистные</span></label>
          <label><input type="radio" name="purpose" value="КНС"><span>КНС</span></label>
          <label><input type="radio" name="purpose" value="Ёмкости"><span>Ёмкости</span></label>
          <label><input type="radio" name="purpose" value="Другое"><span>Другое</span></label>
        </div>
      </fieldset>
      <div class="mt-4 grid grid-cols-2 gap-3">
        <label class="field"><span>Объём, м³ или расход, л/с</span><input type="text" name="volume" inputmode="decimal" placeholder="Например, 25 м³"></label>
        <label class="field"><span>Ваше имя</span><input type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться"></label>
        <label class="field col-span-2"><span>Телефон <b class="text-brand">*</b></span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 ___ ___-__-__" required><em class="field__err" hidden>Укажите номер, чтобы мы могли перезвонить</em></label>
        <label class="field col-span-2"><span>Комментарий</span><textarea name="comment" rows="2" placeholder="Размеры, место установки, среда, город"></textarea></label>
      </div>
      <?php eeg_form_hidden(); ?>
      <button type="submit" class="btn btn-primary mt-5 w-full justify-center">Отправить заявку <i data-lucide="send" class="size-5"></i></button>
      <p class="mt-3 text-center text-xs font-semibold text-muted">Нажимая кнопку, вы соглашаетесь на обработку персональных данных.</p>
      <div class="form-ok" hidden role="status">
        <span class="grid size-12 place-items-center rounded-full bg-mist text-brand"><i data-lucide="check" class="size-7"></i></span>
        <p class="mt-4 font-display text-xl font-medium">Заявка принята</p>
        <p class="mt-2 text-muted">Менеджер перезвонит в рабочее время: <?php echo esc_html(mb_strtolower(eeg_hours())); ?>.</p>
        <button type="button" class="btn btn-ghost btn-sm mt-5 modal__ok-close">Закрыть</button>
      </div>
    </form>

    <div class="modal__alt">
      <span>Или напишите сразу:</span>
      <a href="<?php echo esc_url(eeg_wa()); ?>" class="btn btn-wa btn-sm" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/ffffff" alt="" class="size-4" width="16" height="16">WhatsApp</a>
      <a href="<?php echo esc_attr(eeg_tel($main)); ?>" class="btn btn-ghost btn-sm"><i data-lucide="phone" class="size-4"></i><?php echo esc_html($main); ?></a>
    </div>
  </div>
</dialog>

<button type="button" class="to-top" aria-label="Наверх">
  <svg class="to-top__ring" viewBox="0 0 44 44" aria-hidden="true">
    <circle cx="22" cy="22" r="20.5" />
    <circle cx="22" cy="22" r="20.5" class="to-top__progress" />
  </svg>
  <i data-lucide="arrow-up"></i>
</button>

<div class="quickbar fixed inset-x-3 bottom-3 z-40 grid grid-cols-2 gap-2 md:hidden">
  <a href="<?php echo esc_attr(eeg_tel($main)); ?>" class="btn btn-primary justify-center"><i data-lucide="phone" class="size-5"></i>Позвонить</a>
  <a href="<?php echo esc_url(eeg_wa()); ?>" class="btn btn-wa justify-center" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/whatsapp/ffffff" alt="" class="size-5" width="20" height="20">WhatsApp</a>
</div>

<?php wp_footer(); ?>
</body>
</html>
