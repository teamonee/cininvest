<?php
/**
 * Автонастройка при активации темы: страницы, главная, меню, ЧПУ.
 * Хук after_switch_theme — рядом с cininvest_install_operations_table (db-operations.php).
 * Идемпотентно: каждый шаг сам проверяет, что уже сделано, прежде чем что-то создавать.
 */
if (!defined('ABSPATH')) exit;

define('CININVEST_SETUP_VERSION', '1.5.0'); // 1.5.0: ID созданных страниц сохраняются в опцию cininvest_pages (для редиректов); поддержка parent в карте

/**
 * Самовосстановление ЧПУ (rewrite rules), по образцу версионирования БД в db-operations.php.
 * after_switch_theme срабатывает только при (пере)активации темы — если состав CPT/таксономий
 * меняется обновлением файлов темы на уже работающем сайте (как при удалении таксономии
 * project_status из inc/cpt.php), кэш правил в опции rewrite_rules не обновится сам и ссылки
 * на CPT project начнут отдавать 404, пока кто-то вручную не зайдёт в Настройки → Постоянные
 * ссылки → Сохранить. Проверяем на каждом init и обновляем правила сами, без ручных действий.
 */
define('CININVEST_REWRITE_VERSION', '1.1.0'); // 1.1.0: убрана таксономия project_status (inc/cpt.php)
add_action('init', function () {
    if (get_option('cininvest_rewrite_version') === CININVEST_REWRITE_VERSION) return;
    flush_rewrite_rules();
    update_option('cininvest_rewrite_version', CININVEST_REWRITE_VERSION);
}, 20);

/**
 * Карта страниц темы: slug => [заголовок, файл шаблона, (опц.) slug родителя].
 * Родитель, если указан, должен идти в карте раньше ребёнка — его ID берётся из уже созданных.
 * /projects/ здесь намеренно нет: это архив CPT project (archive-project.php), а не страница.
 */
function cininvest_setup_pages_map() {
    return [
        'login'                   => ['Вход', 'page-login.php'],
        'register'                => ['Регистрация', 'page-register.php'],
        'application'             => ['Подача заявки', 'page-application.php'],
        'application-sent'        => ['Заявка отправлена', 'page-application-sent.php'],
        'verification'            => ['Верификация', 'page-verification.php'],
        'verification-done'       => ['Верификация завершена', 'page-verification-done.php'],
        'qualification'           => ['Квалификация инвестора', 'page-qualification.php'],
        'settings'                => ['Настройки', 'page-settings.php'],
        'faq'                     => ['FAQ', 'page-faq.php'],
        'docs'                    => ['Документы', 'page-docs.php'],
        'for-cinematographers'    => ['Для кинематографистов', 'page-for-cinematographers.php'],
        'for-investors'           => ['Для инвесторов', 'page-for-investors.php'],
        'profile-investor'        => ['Профиль — Инвестор', 'page-profile-investor.php'],
        'profile-sponsor'         => ['Профиль — Спонсор', 'page-profile-sponsor.php'],
        'profile-cinematographer' => ['Профиль — Кинематографист', 'page-profile-cinematographer.php'],
    ];
}

/** Найти страницу по slug или создать её, назначить шаблон и (опц.) родителя. Возвращает ID (0 при ошибке). */
function cininvest_setup_ensure_page($slug, $title, $template = '', $parent_id = 0) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        $page_id = (int) $page->ID; // уже есть — не дублируем
    } else {
        $page_id = wp_insert_post([
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => (int) $parent_id,
            'post_content' => '',
        ], true);
        if (is_wp_error($page_id)) return 0;
        $page_id = (int) $page_id;
    }
    if ($template) update_post_meta($page_id, '_wp_page_template', $template);
    return $page_id;
}

/**
 * ID / URL страницы темы по slug — для редиректов и ссылок.
 * Быстрый путь: опция cininvest_pages (slug => ID), заполняется установщиком.
 * Фолбэк: get_page_by_path (если опция ещё не создана, до первого запуска установщика).
 */
function cininvest_page_id($slug) {
    $pages = get_option('cininvest_pages');
    if (is_array($pages) && !empty($pages[$slug])) return (int) $pages[$slug];
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page ? (int) $page->ID : 0;
}
function cininvest_page_url($slug, $fallback_path = '') {
    $id = cininvest_page_id($slug);
    if ($id) return get_permalink($id);
    return home_url('/' . ($fallback_path !== '' ? $fallback_path : trim($slug, '/') . '/'));
}

/** Страница «Главная» + статическая главная (show_on_front/page_on_front) */
function cininvest_setup_front_page() {
    $home_id = cininvest_setup_ensure_page('home', 'Главная');
    if (!$home_id) return;
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_id);
}

/** Главное меню (theme_location 'primary') + базовые пункты. Не трогает уже настроенное меню. */
function cininvest_setup_primary_menu($page_ids) {
    $locations = get_nav_menu_locations();
    if (!empty($locations['primary'])) return; // уже назначено — ничего не делаем

    $menu_name = 'Главное меню';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id) || !$menu_id) return;

    // пункты добавляем только в пустое меню — защита от дублей при повторном запуске
    if (empty(wp_get_nav_menu_items($menu_id))) {
        $items = [
            ['title' => 'Наша платформа', 'url' => home_url('/')],
            ['title' => 'Проекты', 'url' => get_post_type_archive_link('project')],
            ['title' => 'FAQ', 'page' => 'faq'],
            ['title' => 'Документы', 'page' => 'docs'],
        ];
        foreach ($items as $i => $it) {
            $args = [
                'menu-item-title'    => $it['title'],
                'menu-item-status'   => 'publish',
                'menu-item-position' => $i + 1,
            ];
            $page_id = !empty($it['page']) ? ($page_ids[$it['page']] ?? 0) : 0;
            if ($page_id) {
                $args['menu-item-object']    = 'page';
                $args['menu-item-object-id'] = $page_id;
                $args['menu-item-type']      = 'post_type';
            } else {
                $args['menu-item-url']  = $it['url'] ?? home_url('/' . $it['page'] . '/');
                $args['menu-item-type'] = 'custom';
            }
            wp_update_nav_menu_item($menu_id, 0, $args);
        }
    }

    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}

/** Миграция: раньше установщик создавал страницу 'projects' с шаблоном page-projects.php —
 *  теперь /projects/ отдаёт архив CPT (archive-project.php), этого шаблона в теме больше нет.
 *  Пустую/нетронутую страницу отправляем в корзину; если в неё вписали контент — не удаляем,
 *  просто снимаем шаблон, чтобы не ссылаться на несуществующий файл. */
function cininvest_setup_cleanup_projects_page() {
    $page = get_page_by_path('projects', OBJECT, 'page');
    if (!$page) return;
    if (get_post_meta($page->ID, '_wp_page_template', true) !== 'page-projects.php') return;

    if (trim((string) $page->post_content) === '') {
        wp_trash_post($page->ID);
    } else {
        delete_post_meta($page->ID, '_wp_page_template');
    }
}

/** Точка входа установщика */
function cininvest_run_setup() {
    if (get_option('cininvest_setup_version') === CININVEST_SETUP_VERSION) return;

    $page_ids = [];
    foreach (cininvest_setup_pages_map() as $slug => $def) {
        $title    = $def[0];
        $template = $def[1] ?? '';
        $parent   = !empty($def[2]) ? ($page_ids[$def[2]] ?? 0) : 0;
        $page_ids[$slug] = cininvest_setup_ensure_page($slug, $title, $template, $parent);
    }

    // slug => ID всех созданных/найденных страниц — источник для cininvest_page_id()/cininvest_page_url()
    update_option('cininvest_pages', array_filter($page_ids));

    cininvest_setup_front_page();
    cininvest_setup_primary_menu($page_ids);
    cininvest_setup_cleanup_projects_page();

    // CPT project регистрируется на init, который на этом же запросе уже отработал —
    // ЧПУ (/projects/) заработают сразу, без ручного захода в Настройки → Постоянные ссылки.
    flush_rewrite_rules();

    update_option('cininvest_setup_version', CININVEST_SETUP_VERSION);
}
add_action('after_switch_theme', 'cininvest_run_setup');

/** Ручной сброс (например, через wp eval 'cininvest_reset_setup();') — заново запустит установщик
 *  при следующей активации темы. Существующие страницы/меню не удаляет, только снимает флаг. */
function cininvest_reset_setup() {
    delete_option('cininvest_setup_version');
}
