<?php
/**
 * Хелперы вывода проектных данных с безопасными фолбэками.
 * Позволяют шаблонам не падать, если поле пустое, и убирают placeholder-хардкод.
 */
if (!defined('ABSPATH')) exit;

/** Число в формат "1 000 000 ₽" */
function cininvest_money($n) {
    $n = (float) $n;
    return number_format($n, 0, ',', ' ') . ' ₽';
}

/** Процент собранного от цели */
function cininvest_progress($collected, $goal) {
    $goal = (float) $goal;
    if ($goal <= 0) return 0;
    return min(100, round(((float)$collected / $goal) * 100));
}

/** Безопасно получить ACF-поле с фолбэком (без ACF вернёт фолбэк) */
function cininvest_field($name, $post_id = null, $fallback = '') {
    if (function_exists('get_field')) {
        $v = get_field($name, $post_id);
        return ($v === null || $v === false || $v === '') ? $fallback : $v;
    }
    return $fallback;
}

/** Постер проекта: миниатюра записи, иначе заглушка темы */
function cininvest_poster($post_id = null) {
    if (has_post_thumbnail($post_id)) return get_the_post_thumbnail_url($post_id, 'large');
    return get_template_directory_uri() . '/assets/img/poster.jpg';
}

/** Репопуляция поля формы из ранее сохранённого $_POST (после ошибки валидации), под атрибут value */
function cininvest_repop($data, $key, $fallback = '') {
    return esc_attr($data[$key] ?? $fallback);
}

/**
 * ЗАЩИТА ОТ ДВОЙНОЙ ОТПРАВКИ ФОРМ (idempotency-токен).
 * cininvest_form_token() — вызывается при рендере формы, кладёт одноразовый токен
 * в транзиент на 5 минут и возвращает его для скрытого поля формы.
 * cininvest_check_and_consume_token() — вызывается в admin_post_* обработчике: если
 * токен валиден, гасит его (удаляет транзиент) и возвращает true; иначе — false
 * (нет токена, просрочен, либо уже был использован — значит это повторная отправка).
 */
function cininvest_form_token() {
    $token = wp_generate_password(32, false, false);
    set_transient('cininvest_ftoken_' . $token, 1, 5 * MINUTE_IN_SECONDS);
    return $token;
}

function cininvest_check_and_consume_token($token) {
    $token = (string) $token;
    if ($token === '' || !preg_match('/^[A-Za-z0-9]+$/', $token)) return false;
    $key = 'cininvest_ftoken_' . $token;
    if (get_transient($key) === false) return false;
    delete_transient($key);
    return true;
}

/**
 * УВЕДОМЛЕНИЯ пользователя.
 * Хранилище: user-meta `cininvest_notifications` — массив записей
 * ['id','type','text','url','date','read']. Новые сверху, не больше 50 штук.
 * События навешиваются в inc/notifications.php.
 */
function cininvest_get_notifications($user_id = null, $only_unread = false) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return [];
    $list = get_user_meta($user_id, 'cininvest_notifications', true);
    if (!is_array($list)) return [];
    if ($only_unread) {
        $list = array_values(array_filter($list, function ($n) { return empty($n['read']); }));
    }
    return $list;
}

/** Число непрочитанных уведомлений (для счётчика на колокольчике) */
function cininvest_unread_count($user_id = null) {
    return count(cininvest_get_notifications($user_id, true));
}

/** Добавить уведомление. $args: ['type','text','url'] (text обязателен). */
function cininvest_add_notification($user_id, $args) {
    $user_id = (int) $user_id;
    if (!$user_id) return false;
    $a = wp_parse_args($args, ['type' => 'info', 'text' => '', 'url' => '']);
    $text = sanitize_text_field($a['text']);
    if ($text === '') return false;

    $list = get_user_meta($user_id, 'cininvest_notifications', true);
    if (!is_array($list)) $list = [];

    array_unshift($list, [
        'id'   => function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('n_', true),
        'type' => sanitize_key($a['type']),
        'text' => $text,
        'url'  => esc_url_raw($a['url']),
        'date' => current_time('mysql'),
        'read' => false,
    ]);
    update_user_meta($user_id, 'cininvest_notifications', array_slice($list, 0, 50));
    return true;
}

/** Отметить уведомления прочитанными. $ids = null → все; массив id → только их. */
function cininvest_mark_read($user_id = null, $ids = null) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return false;
    $list = get_user_meta($user_id, 'cininvest_notifications', true);
    if (!is_array($list) || !$list) return true;

    $ids = ($ids === null) ? null : array_map('strval', (array) $ids);
    foreach ($list as &$n) {
        if ($ids === null || in_array((string) ($n['id'] ?? ''), $ids, true)) $n['read'] = true;
    }
    unset($n);
    update_user_meta($user_id, 'cininvest_notifications', $list);
    return true;
}

/** Вознаграждения проекта: [['title'=>..,'price'=>..], ...], с фолбэком на дефолтный набор без ACF */
function cininvest_project_rewards($post_id = null) {
    $rewards = [];
    if (function_exists('have_rows') && have_rows('rewards', $post_id)) {
        while (have_rows('rewards', $post_id)) {
            the_row();
            $rewards[] = ['title' => (string) get_sub_field('title'), 'price' => (float) get_sub_field('price')];
        }
        return $rewards;
    }
    foreach ([
        'Ранний доступ к стримингу (онлайн просмотр)' => 500,
        'Приглашение на один съёмочный день' => 500,
        'Участие в массовке фильма' => 500,
    ] as $title => $price) {
        $rewards[] = ['title' => $title, 'price' => (float) $price];
    }
    return $rewards;
}
