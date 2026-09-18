<?php
/**
 * CPT "application" — заявки кинематографистов + статусы.
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('application', [
        'labels' => [
            'name' => 'Заявки', 'singular_name' => 'Заявка',
            'add_new_item' => 'Новая заявка', 'edit_item' => 'Редактировать заявку',
        ],
        'public' => false,            // заявки не публичны в архиве
        'show_ui' => true,            // но видны в админке
        'menu_icon' => 'dashicons-media-document',
        'supports' => ['title', 'author', 'custom-fields'],
        'capability_type' => 'post',
    ]);

    // Статусы заявки (черновик/подана/предоценка/экспертная/одобрена/отклонена)
    register_taxonomy('application_status', 'application', [
        'labels' => ['name' => 'Статус заявки'],
        'public' => false, 'show_ui' => true, 'hierarchical' => true,
    ]);
});

/** Гарантируем базовый набор статусов */
add_action('init', function () {
    $statuses = [
        'draft' => 'Черновик', 'submitted' => 'Подана',
        'pre_review' => 'Предварительная оценка', 'expert_review' => 'Экспертная оценка',
        'approved' => 'Одобрена', 'rejected' => 'Отклонена',
    ];
    foreach ($statuses as $slug => $name) {
        if (!term_exists($slug, 'application_status')) {
            wp_insert_term($name, 'application_status', ['slug' => $slug]);
        }
    }
}, 20);
