<?php
/**
 * Слой абстракции платежей.
 * Вся логика обращается ТОЛЬКО к интерфейсу. Реальный банк = новый класс под тот же контракт.
 */
if (!defined('ABSPATH')) exit;

/** Контракт платёжного провайдера */
interface CININVEST_Payment_Gateway {
    public function deposit($user_id, $amount, $meta = []);   // пополнение
    public function withdraw($user_id, $amount, $meta = []);  // вывод
    public function charge($user_id, $amount, $meta = []);    // списание (покупка долей/привилегий)
    public function get_status($operation_id);                // статус операции
}

/**
 * Заглушка. Рабочая: меняет баланс в user-meta, пишет операции.
 * Имитирует мгновенное подтверждение (status=completed).
 * Реальный банк будет ставить pending и подтверждать через вебхук.
 */
class CININVEST_Mock_Gateway implements CININVEST_Payment_Gateway {

    public function deposit($user_id, $amount, $meta = []) {
        $amount = (float) $amount;
        $ref = 'mock_dep_' . $user_id . '_' . time() . '_' . wp_generate_password(6, false);
        $op = cininvest_add_operation([
            'user_id' => $user_id, 'type' => 'deposit', 'status' => 'completed',
            'amount' => $amount, 'reference' => $ref, 'meta' => $meta,
        ]);
        cininvest_adjust_balance($user_id, +$amount);
        return $op;
    }

    public function withdraw($user_id, $amount, $meta = []) {
        $amount = (float) $amount;
        if (cininvest_get_balance($user_id) < $amount) {
            return new WP_Error('insufficient', 'Недостаточно средств');
        }
        $ref = 'mock_wd_' . $user_id . '_' . time() . '_' . wp_generate_password(6, false);
        $op = cininvest_add_operation([
            'user_id' => $user_id, 'type' => 'withdraw', 'status' => 'completed',
            'amount' => $amount, 'reference' => $ref, 'meta' => $meta,
        ]);
        cininvest_adjust_balance($user_id, -$amount);
        return $op;
    }

    public function charge($user_id, $amount, $meta = []) {
        $amount = (float) $amount;
        if (cininvest_get_balance($user_id) < $amount) {
            return new WP_Error('insufficient', 'Недостаточно средств');
        }
        $type = $meta['op_type'] ?? 'buy';
        $ref = 'mock_ch_' . $user_id . '_' . time() . '_' . wp_generate_password(6, false);
        $op = cininvest_add_operation([
            'user_id' => $user_id, 'type' => $type, 'status' => 'completed',
            'amount' => $amount,
            'shares' => $meta['shares'] ?? null,
            'project_id' => $meta['project_id'] ?? null,
            'commission' => $meta['commission'] ?? null,
            'reference' => $ref, 'meta' => $meta,
        ]);
        cininvest_adjust_balance($user_id, -$amount);
        return $op;
    }

    public function get_status($operation_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cininvest_operations';
        return $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", $operation_id));
    }
}

/** Фабрика активного провайдера. Позже: return new CININVEST_RealBank_Gateway(); */
function cininvest_gateway() {
    $provider = apply_filters('cininvest_payment_provider', 'mock');
    switch ($provider) {
        // case 'realbank': return new CININVEST_RealBank_Gateway();
        default: return new CININVEST_Mock_Gateway();
    }
}
