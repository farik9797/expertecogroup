<?php
/**
 * Plugin Name: Site hardening
 * Description: Защита сайта в mu-plugin, чтобы не пропала при смене или обновлении темы. Гостям закрыты batch REST (wp2shell) и список пользователей, перечисление авторов через ?author=N; выключены pingback XML-RPC, oEmbed-discovery и номер версии WordPress в коде страниц.
 * Version: 1.0.0
 *
 * Шаблон из ~/.claude/playbooks/wordpress-security.md. Класть в wp-content/mu-plugins/site-hardening.php.
 * Весь REST не выключать: на нём работают редактор блоков, Elementor, WooCommerce, формы и Yoast.
 */

defined( 'ABSPATH' ) || exit;

// REST: гостям не отдавать batch/v1 (через него идёт wp2shell) и /wp/v2/users (логины).
// Вошедшие пользователи и запросы с Application Password работают как раньше.
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/batch/v1' ) || 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
);

// ?author=1 → редирект на /author/<логин>/ выдаёт логины. Гостям — на главную.
// Приоритет 1: раньше redirect_canonical, который и делает этот редирект.
add_action(
	'template_redirect',
	function () {
		if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

// XML-RPC: без методов со входом (подбор паролей через system.multicall) и без pingback (DDoS чужими руками).
// Если Jetpack и мобильное приложение WordPress не нужны, надёжнее закрыть xmlrpc.php на уровне сервера.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter(
	'xmlrpc_methods',
	function ( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}
);
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

// oEmbed: убрать discovery-ссылки и скрипт встраивания с фронта.
// Прокси /oembed/1.0/proxy не трогаем: он только для вошедших и нужен редактору для встраивания видео.
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

// Номер версии WordPress в коде страниц и в RSS — подсказка сканерам уязвимостей.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
