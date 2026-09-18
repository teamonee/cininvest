<?php
/**
 * REST-заготовка вебхука для будущего банка.
 * Банк шлёт сюда подтверждение смены статуса операции (pending -> completed/failed).
 * Идемпотентно: повторный вызов не ломает состояние.
 */
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('cininvest/v1', '/payment-callback', [
        'methods'  => 'POST',
        'callback' => 'cininvest_payment_callback',
        // ВАЖНО: реальный банк подключит проверку подписи/секрета здесь
        'permission_callback' => function ($request) {
            $secret = get_option('cininvest_webhook_secret');
            if (!$secret) return false; // пока секрет не задан — вебхук закрыт
            return hash_equals($secret, (string) $request->get_header('X-Cininvest-Secret'));
        },
    ]);
});

function cininvest_payment_callback($request) {
    $op_id  = (int) $request->get_param('operation_id');
    $status = sanitize_text_field($request->get_param('status'));

    if (!$op_id || !in_array($status, ['completed', 'failed'], true)) {
        return new WP_REST_Response(['ok' => false, 'error' => 'bad_params'], 400);
    }

    // если статус уже такой — идемпотентно возвращаем ок
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", $op_id));
    if ($current === $status) {
        return new WP_REST_Response(['ok' => true, 'idempotent' => true], 200);
    }

    cininvest_update_operation_status($op_id, $status);

    // при подтверждении вывода/пополнения — здесь позже корректируется баланс реального банка
    do_action('cininvest_payment_confirmed', $op_id, $status);

    return new WP_REST_Response(['ok' => true], 200);
}
