<?php
/**
 * CININVEST theme functions
 */
if (!defined('ABSPATH')) exit;

// --- Подключение backend-модулей ---
$cininvest_inc = [
    'helpers', 'cpt', 'cpt-application', 'acf-fields',
    'db-operations', 'theme-setup', 'user-profile', 'payment-gateway', 'rest-webhook',
    'forms-auth', 'forms-application', 'forms-verification', 'ajax-shares', 'notifications',
];
foreach ($cininvest_inc as $file) {
    $path = get_template_directory() . '/inc/' . $file . '.php';
    if (file_exists($path)) require_once $path;
}

// --- Theme supports ---
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height' => 40, 'width' => 160, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'primary' => 'Главное меню',
        'footer'  => 'Меню подвала',
    ]);
    load_theme_textdomain('cininvest', get_template_directory() . '/languages');
});

// --- Assets ---
add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');
    // Локальные шрифты: Montserrat (основной) + Palui SP Demo (дисплейный) — assets/fonts/fonts.css
    wp_enqueue_style('cininvest-fontface', get_template_directory_uri() . '/assets/fonts/fonts.css', [], $ver);
    wp_enqueue_style('cininvest', get_stylesheet_uri(), ['cininvest-fontface'], $ver);
    wp_enqueue_script('cininvest', get_template_directory_uri() . '/assets/js/main.js', [], $ver, true);

    // передаём в JS ajax url + nonce для калькулятора долей
    wp_localize_script('cininvest', 'CININVEST', [
        'ajax_url'    => admin_url('admin-ajax.php'),
        'shares_nonce'=> wp_create_nonce('cininvest_shares'),
        'notif_nonce' => wp_create_nonce('cininvest_notif'),
    ]);
});
