<?php
/**
 * Template Name: Перенаправление
 * Служебная страница: сразу ведёт на адрес из поля «Куда перенаправлять» (см. functions.php).
 */
defined('ABSPATH') || exit;
wp_safe_redirect(home_url('/'), 301);
exit;
