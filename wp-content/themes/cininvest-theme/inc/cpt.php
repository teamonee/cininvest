<?php
/**
 * Регистрация Custom Post Type "project"
 * Этап 1 динамики CININVEST
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('project', [
        'labels' => [
            'name'               => 'Проекты',
            'singular_name'      => 'Проект',
            'add_new'            => 'Добавить проект',
            'add_new_item'       => 'Новый проект',
            'edit_item'          => 'Редактировать проект',
            'all_items'          => 'Все проекты',
            'search_items'       => 'Искать проекты',
            'not_found'          => 'Проекты не найдены',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-video-alt2',
        'menu_position'=> 5,
        'rewrite'      => ['slug' => 'projects'],
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);
});
