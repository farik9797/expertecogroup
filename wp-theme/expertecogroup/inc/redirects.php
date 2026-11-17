<?php
/**
 * 301 со старых адресов сайта на Satu: товары /p…-….html, группы /g…, служебные страницы.
 * Срабатывает только там, где WordPress отдал бы 404, — существующие страницы не трогает.
 * Карта адресов — inc/satu-redirects-map.php (собирает tools/build_satu_redirects.py).
 */
defined('ABSPATH') || exit;

function eeg_satu_target($path)
{
    $path = preg_replace('~^/(ru|kz)(?=/)~', '', (string) $path); // языковые версии Satu
    if (preg_match('~^/([pg]\d+)(?:[-/.]|$)~', $path, $m)) {
        static $map = null;
        if ($map === null) {
            $map = require __DIR__ . '/satu-redirects-map.php';
        }
        return $map[$m[1]] ?? 'catalog/';
    }
    $pages = [
        '~^/product_list~'  => 'catalog/',
        '~^/contacts~'      => 'kontakty/',
        '~^/about_us~'      => 'o-kompanii/',
        '~^/testimonials~'  => 'o-kompanii/',
        '~^/delivery_info~' => 'kontakty/',
    ];
    foreach ($pages as $re => $to) {
        if (preg_match($re, $path)) {
            return $to;
        }
    }
    return '';
}

add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }
    $to = eeg_satu_target(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if ($to !== '') {
        wp_safe_redirect(eeg_url($to), 301);
        exit;
    }
}, 1);
