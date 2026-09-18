<?php
/**
 * ACF-поля для CPT "project" — регистрация из кода (acf_add_local_field_group)
 * Поля живут в теме, не только в БД. Требует плагин ACF (free).
 */
if (!defined('ABSPATH')) exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key' => 'group_project_fields',
        'title' => 'Данные проекта',
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'project']]],
        'fields' => [
            // --- Сбор средств ---
            ['key' => 'f_collected', 'label' => 'Собрано, ₽', 'name' => 'collected', 'type' => 'number', 'default_value' => 0],
            ['key' => 'f_goal', 'label' => 'Цель раунда, ₽', 'name' => 'goal', 'type' => 'number', 'default_value' => 0],
            ['key' => 'f_share_price', 'label' => 'Цена за 1 долю, ₽', 'name' => 'share_price', 'type' => 'number', 'default_value' => 5000],
            ['key' => 'f_min_package', 'label' => 'Минимальный пакет', 'name' => 'min_package', 'type' => 'text', 'default_value' => '1 доля'],
            ['key' => 'f_shares_available', 'label' => 'Доступно долей', 'name' => 'shares_available', 'type' => 'number'],
            ['key' => 'f_commission', 'label' => 'Комиссия, %', 'name' => 'commission', 'type' => 'number', 'default_value' => 4],
            ['key' => 'f_investors', 'label' => 'Число инвесторов', 'name' => 'investors_count', 'type' => 'number'],
            ['key' => 'f_sponsors', 'label' => 'Число спонсоров', 'name' => 'sponsors_count', 'type' => 'number'],
            ['key' => 'f_org', 'label' => 'Организация (АО …)', 'name' => 'org_name', 'type' => 'text'],
            ['key' => 'f_status', 'label' => 'Статус сбора', 'name' => 'project_status', 'type' => 'select',
                'choices' => ['active' => 'В работе', 'completed' => 'Завершён'], 'default_value' => 'active',
            ],

            // --- Раунды ---
            ['key' => 'f_rounds', 'label' => 'Раунды', 'name' => 'rounds', 'type' => 'repeater', 'button_label' => 'Добавить раунд',
                'sub_fields' => [
                    ['key' => 'f_round_name', 'label' => 'Название', 'name' => 'name', 'type' => 'text', 'default_value' => '1 раунд'],
                    ['key' => 'f_round_active', 'label' => 'Активен', 'name' => 'active', 'type' => 'true_false'],
                ],
            ],

            // --- О проекте ---
            ['key' => 'f_genre', 'label' => 'Жанр (теги через запятую)', 'name' => 'genre', 'type' => 'text'],
            ['key' => 'f_duration', 'label' => 'Хронометраж', 'name' => 'duration', 'type' => 'text'],
            ['key' => 'f_type', 'label' => 'Тип', 'name' => 'ptype', 'type' => 'text'],
            ['key' => 'f_kind', 'label' => 'Вид', 'name' => 'kind', 'type' => 'text'],
            ['key' => 'f_logline', 'label' => 'Логлайн', 'name' => 'logline', 'type' => 'textarea'],
            ['key' => 'f_idea', 'label' => 'Оригинальная идея / Хай-концепт', 'name' => 'idea', 'type' => 'textarea'],
            ['key' => 'f_setting', 'label' => 'Сеттинг', 'name' => 'setting', 'type' => 'textarea'],
            ['key' => 'f_annotation', 'label' => 'Аннотация', 'name' => 'annotation', 'type' => 'textarea'],
            ['key' => 'f_synopsis', 'label' => 'Синопсис', 'name' => 'synopsis', 'type' => 'wysiwyg'],
            ['key' => 'f_locations', 'label' => 'Локации', 'name' => 'locations', 'type' => 'text'],
            ['key' => 'f_video', 'label' => 'Видеообращение (URL)', 'name' => 'video_url', 'type' => 'url'],

            // --- Команда ---
            ['key' => 'f_team', 'label' => 'Команда', 'name' => 'team', 'type' => 'repeater', 'button_label' => 'Добавить участника',
                'sub_fields' => [
                    ['key' => 'f_m_photo', 'label' => 'Фото', 'name' => 'photo', 'type' => 'image', 'return_format' => 'url'],
                    ['key' => 'f_m_name', 'label' => 'ФИО', 'name' => 'name', 'type' => 'text'],
                    ['key' => 'f_m_role', 'label' => 'Роль', 'name' => 'role', 'type' => 'text'],
                    ['key' => 'f_m_about', 'label' => 'Обо мне', 'name' => 'about', 'type' => 'textarea'],
                    ['key' => 'f_m_kp', 'label' => 'Ссылка на Кинопоиск', 'name' => 'kinopoisk', 'type' => 'url'],
                ],
            ],

            // --- Творческие материалы ---
            ['key' => 'f_materials', 'label' => 'Творческие материалы', 'name' => 'materials', 'type' => 'repeater', 'button_label' => 'Добавить файл',
                'sub_fields' => [
                    ['key' => 'f_mat_title', 'label' => 'Название', 'name' => 'title', 'type' => 'text'],
                    ['key' => 'f_mat_file', 'label' => 'Файл', 'name' => 'file', 'type' => 'file', 'return_format' => 'array'],
                ],
            ],

            // --- Документы ---
            ['key' => 'f_docs', 'label' => 'Документы', 'name' => 'docs', 'type' => 'repeater', 'button_label' => 'Добавить документ',
                'sub_fields' => [
                    ['key' => 'f_doc_title', 'label' => 'Название', 'name' => 'title', 'type' => 'text'],
                    ['key' => 'f_doc_file', 'label' => 'Файл', 'name' => 'file', 'type' => 'file', 'return_format' => 'array'],
                ],
            ],

            // --- Вознаграждения ---
            ['key' => 'f_rewards', 'label' => 'Вознаграждения', 'name' => 'rewards', 'type' => 'repeater', 'button_label' => 'Добавить вознаграждение',
                'sub_fields' => [
                    ['key' => 'f_rw_title', 'label' => 'Название', 'name' => 'title', 'type' => 'text'],
                    ['key' => 'f_rw_price', 'label' => 'Цена, ₽', 'name' => 'price', 'type' => 'number'],
                ],
            ],

            // --- Отзывы ---
            ['key' => 'f_reviews', 'label' => 'Отзывы', 'name' => 'reviews', 'type' => 'repeater', 'button_label' => 'Добавить отзыв',
                'sub_fields' => [
                    ['key' => 'f_rv_photo', 'label' => 'Фото', 'name' => 'photo', 'type' => 'image', 'return_format' => 'url'],
                    ['key' => 'f_rv_name', 'label' => 'Имя', 'name' => 'name', 'type' => 'text'],
                    ['key' => 'f_rv_role', 'label' => 'Должность', 'name' => 'role', 'type' => 'text'],
                    ['key' => 'f_rv_text', 'label' => 'Текст', 'name' => 'text', 'type' => 'textarea'],
                ],
            ],
        ],
    ]);
});
