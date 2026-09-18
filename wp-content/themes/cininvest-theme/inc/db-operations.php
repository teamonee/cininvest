<?php
/**
 * Миграция и доступ к таблице операций wp_cininvest_operations.
 * Статус — колонка (pending/completed/failed). reference — для идемпотентности.
 */
if (!defined('ABSPATH')) exit;

define('CININVEST_DB_VERSION', '1.0.0');

/** Создание/обновление таблицы при активации темы */
function cininvest_install_operations_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(32) NOT NULL,
        status VARCHAR(16) NOT NULL DEFAULT 'pending',
        amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        shares INT(11) DEFAULT NULL,
        project_id BIGINT(20) UNSIGNED DEFAULT NULL,
        commission DECIMAL(15,2) DEFAULT NULL,
        reference VARCHAR(64) DEFAULT NULL,
        meta LONGTEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        UNIQUE KEY reference (reference)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('cininvest_db_version', CININVEST_DB_VERSION);
}
add_action('after_switch_theme', 'cininvest_install_operations_table');

/** Проверка версии БД на каждом запросе (на случай обновления темы) */
add_action('init', function () {
    if (get_option('cininvest_db_version') !== CININVEST_DB_VERSION) {
        cininvest_install_operations_table();
    }
});

/** Записать операцию. Возвращает id или WP_Error. */
function cininvest_add_operation($args) {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';

    $defaults = [
        'user_id' => get_current_user_id(),
        'type' => '', 'status' => 'pending', 'amount' => 0,
        'shares' => null, 'project_id' => null, 'commission' => null,
        'reference' => null, 'meta' => null,
    ];
    $a = wp_parse_args($args, $defaults);

    if (!$a['user_id'] || !$a['type']) return new WP_Error('bad_op', 'user_id и type обязательны');

    // идемпотентность: если reference уже есть — не дублируем
    if ($a['reference']) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE reference = %s", $a['reference']));
        if ($exists) return (int) $exists;
    }

    if (is_array($a['meta'])) $a['meta'] = wp_json_encode($a['meta']);

    $wpdb->insert($table, [
        'user_id' => $a['user_id'], 'type' => $a['type'], 'status' => $a['status'],
        'amount' => $a['amount'], 'shares' => $a['shares'], 'project_id' => $a['project_id'],
        'commission' => $a['commission'], 'reference' => $a['reference'], 'meta' => $a['meta'],
    ]);
    return (int) $wpdb->insert_id;
}

/** История операций пользователя */
function cininvest_get_operations($user_id = null, $limit = 50) {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    $user_id = $user_id ?: get_current_user_id();
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
        $user_id, $limit
    ));
}

/** Была ли у пользователя уже операция данного типа по этому проекту (для счётчиков investors_count/sponsors_count) */
function cininvest_project_user_has_operation($project_id, $user_id, $type) {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE project_id = %d AND user_id = %d AND type = %s",
        $project_id, $user_id, $type
    ));
    return (int) $count > 0;
}

/** Обновить статус операции (для вебхука банка) */
function cininvest_update_operation_status($id, $status) {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    if (!in_array($status, ['pending', 'completed', 'failed'], true)) return false;
    return $wpdb->update($table, ['status' => $status, 'updated_at' => current_time('mysql')], ['id' => $id]);
}
