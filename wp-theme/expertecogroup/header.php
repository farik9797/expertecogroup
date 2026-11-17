<?php
/** Шапка сайта: меню, мобильное меню (как в макете site/index.html). */
defined('ABSPATH') || exit;
$home = is_front_page();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<?php
$purpose = is_page() ? eeg_field('purpose', '', get_queried_object_id()) : '';
$classes = $home ? [] : ['page-inner'];
?>
<body <?php body_class(array_merge(['font-sans', 'text-ink', 'antialiased'], $classes)); ?><?php echo $purpose ? ' data-purpose="' . esc_attr($purpose) . '"' : ''; ?>>
<?php wp_body_open(); ?>
<a href="#main" class="skip-link">К содержимому</a>

<header id="nav" class="fixed inset-x-0 top-0 z-50 px-3 pt-3 md:px-6 md:pt-4">
  <div class="nav-shell mx-auto flex max-w-7xl items-center gap-2 rounded-full py-2 pl-2 pr-2 md:gap-4 md:pr-3">
    <a href="<?php echo esc_url($home ? '#top' : home_url('/')); ?>" class="nav-in flex min-w-0 items-center gap-3 rounded-full pr-2" aria-label="Expert ECO Group — на главную">
      <span class="logo-chip"><img src="<?php echo esc_url(eeg_asset('img/logo-eeg.webp')); ?>" alt="" width="36" height="37"></span>
      <span class="min-w-0 leading-tight max-sm:hidden lg:hidden xl:block">
        <span class="block truncate font-display text-[15px] font-medium">Expert ECO Group</span>
        <span class="nav-sub truncate text-[13px] font-semibold">оборудование из полипропилена</span>
      </span>
    </a>
    <nav class="ml-auto hidden items-center gap-1 text-[15px] font-bold lg:flex" aria-label="Основное меню">
      <a class="nav-in nav-link" href="<?php echo esc_url(eeg_url('catalog/')); ?>">Каталог</a>
      <a class="nav-in nav-link" href="<?php echo esc_url(eeg_url('o-kompanii/')); ?>">О компании</a>
      <a class="nav-in nav-link" href="<?php echo esc_url(eeg_url('proekty/')); ?>">Проекты</a>
      <a class="nav-in nav-link" href="<?php echo esc_url(eeg_url('kontakty/')); ?>">Контакты</a>
    </nav>
    <a href="<?php echo esc_url(eeg_req()); ?>" class="nav-in btn btn-primary btn-sm ml-auto shrink-0 lg:ml-0"><span class="sm:hidden">Заявка</span><span class="max-sm:hidden">Рассчитать стоимость</span></a>
    <button type="button" class="nav-in burger grid size-11 shrink-0 place-items-center rounded-full lg:hidden" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobile-menu">
      <i data-lucide="menu" class="size-6"></i>
    </button>
  </div>
</header>

<div id="mobile-menu" class="mobile-menu fixed inset-0 z-[60] flex flex-col bg-navy-deep px-5 pb-8 pt-5 text-white" hidden>
  <div class="flex items-center justify-between">
    <span class="font-display text-lg">Меню</span>
    <button type="button" class="menu-close grid size-11 place-items-center rounded-full bg-white/10" aria-label="Закрыть меню"><i data-lucide="x" class="size-6"></i></button>
  </div>
  <nav class="mt-10 flex flex-col gap-1 font-display text-2xl" aria-label="Мобильное меню">
    <a href="<?php echo esc_url(eeg_url('catalog/')); ?>" class="py-3">Каталог</a>
    <a href="<?php echo esc_url(eeg_url('o-kompanii/')); ?>" class="py-3">О компании</a>
    <a href="<?php echo esc_url(eeg_url('proekty/')); ?>" class="py-3">Проекты</a>
    <a href="<?php echo esc_url(eeg_url('kontakty/')); ?>" class="py-3">Контакты</a>
  </nav>
  <div class="mt-auto space-y-3">
    <a href="<?php echo esc_attr(eeg_tel(eeg_phone_main())); ?>" class="flex items-center gap-3 text-lg font-bold"><i data-lucide="phone" class="size-5 text-sky"></i><?php echo esc_html(eeg_phone_main()); ?></a>
    <a href="<?php echo esc_url(eeg_req()); ?>" class="btn btn-primary w-full justify-center">Рассчитать стоимость</a>
  </div>
</div>

<main id="main">
