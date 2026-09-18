<?php
/**
 * Откат данных, созданных tools/seed-demo.php.
 * Запуск: wp eval-file wp-content/themes/cininvest-theme/tools/seed-demo-cleanup.php
 *
 * Удаляет: операции в wp_cininvest_operations для 3 тестовых пользователей,
 * самих пользователей, демо-проект и его плейсхолдер-вложения (материалы/документы),
 * а также 4 демо-заявки кинематографиста.
 * Безопасно запускать повторно — просто ничего не найдёт и завершится тихо.
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "Запускать только через wp-cli:\n  wp eval-file wp-content/themes/cininvest-theme/tools/seed-demo-cleanup.php\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/user.php';

$emails = ['cinema@test.local', 'investor@test.local', 'sponsor@test.local'];
$project_slug = 'seed-demo-flip';

global $wpdb;
$ops_table = $wpdb->prefix . 'cininvest_operations';

$user_ids = [];
foreach ($emails as $email) {
    $u = get_user_by('email', $email);
    if ($u) $user_ids[] = (int) $u->ID;
}

if ($user_ids) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
    $deleted_ops = $wpdb->query($wpdb->prepare("DELETE FROM {$ops_table} WHERE user_id IN ({$placeholders})", $user_ids));
    WP_CLI::log("Удалено операций: {$deleted_ops}");

    foreach ($user_ids as $uid) {
        if (wp_delete_user($uid)) {
            WP_CLI::success("Удалён пользователь ID {$uid}");
        } else {
            WP_CLI::warning("Не удалось удалить пользователя ID {$uid}");
        }
    }
} else {
    WP_CLI::log('Тестовые пользователи не найдены — нечего удалять.');
}

// демо-заявки кинематографиста
$applications = get_posts([
    'post_type' => 'application', 'posts_per_page' => -1, 'post_status' => 'publish',
    'meta_key' => '_cininvest_seed_key',
]);
foreach ($applications as $app) {
    wp_delete_post($app->ID, true);
    WP_CLI::log("Удалена заявка ID {$app->ID}");
}

// плейсхолдер-вложения (материалы/документы демо-проекта)
$attachments = get_posts([
    'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'inherit',
    'meta_key' => '_cininvest_seed_key',
]);
foreach ($attachments as $att) {
    wp_delete_attachment($att->ID, true);
    WP_CLI::log("Удалено вложение ID {$att->ID}");
}

$project = get_page_by_path($project_slug, OBJECT, 'project');
if ($project) {
    wp_delete_post($project->ID, true);
    WP_CLI::success("Удалён проект ID {$project->ID}");
} else {
    WP_CLI::log('Демо-проект не найден — нечего удалять.');
}

WP_CLI::success('Откат завершён.');
