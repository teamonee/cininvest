<?php
/**
 * Покупка/продажа долей + AJAX-пересчёт суммы<->долей<->комиссии.
 * Все деньги идут через платёжный слой (gateway), никогда напрямую.
 */
if (!defined('ABSPATH')) exit;

/** AJAX: пересчёт калькулятора в модалке покупки */
add_action('wp_ajax_cininvest_calc_shares', 'cininvest_calc_shares');
add_action('wp_ajax_nopriv_cininvest_calc_shares', 'cininvest_calc_shares');
function cininvest_calc_shares() {
    check_ajax_referer('cininvest_shares', 'nonce');

    $project_id = (int) ($_POST['project_id'] ?? 0);
    $share_price = (float) cininvest_field('share_price', $project_id, 5000);
    $commission_pct = (float) cininvest_field('commission', $project_id, 4);

    // считаем либо от количества, либо от суммы
    if (isset($_POST['shares'])) {
        $shares = max(0, (int) $_POST['shares']);
        $base = $shares * $share_price;
    } else {
        $amount = max(0, (float) $_POST['amount']);
        $shares = $share_price > 0 ? (int) floor($amount / $share_price) : 0;
        $base = $shares * $share_price;
    }
    $commission = round($base * $commission_pct / 100, 2);
    $total = $base + $commission;

    wp_send_json_success([
        'shares' => $shares,
        'share_price' => $share_price,
        'base' => $base,
        'commission' => $commission,
        'total' => $total,
        'total_fmt' => cininvest_money($total),
    ]);
}

/** ПОКУПКА долей */
add_action('admin_post_cininvest_buy_shares', 'cininvest_handle_buy_shares');
function cininvest_handle_buy_shares() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_buy_nonce']) || !wp_verify_nonce($_POST['cininvest_buy_nonce'], 'cininvest_buy_shares')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $user_id = get_current_user_id();
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $shares = max(1, (int) ($_POST['shares'] ?? 0));

    // согласия обязательны
    if (empty($_POST['agree_limit']) || empty($_POST['agree_docs'])) {
        set_transient('cininvest_buy_error_' . $user_id, 'Подтвердите согласия', 60);
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    $share_price = (float) cininvest_field('share_price', $project_id, 5000);
    $commission_pct = (float) cininvest_field('commission', $project_id, 4);
    $base = $shares * $share_price;
    $commission = round($base * $commission_pct / 100, 2);
    $total = $base + $commission;

    // считаем ДО списания: первая ли это покупка данного юзера в этом проекте
    $already_invested = cininvest_project_user_has_operation($project_id, $user_id, 'buy');

    // списание через платёжный слой; share_price/base — плоско, на одном уровне с
    // остальными ключами meta (operation-modal.php читает их без вложенности)
    $result = cininvest_gateway()->charge($user_id, $total, [
        'op_type' => 'buy', 'shares' => $shares, 'project_id' => $project_id,
        'commission' => $commission,
        'share_price' => $share_price, 'base' => $base,
    ]);

    if (is_wp_error($result)) {
        set_transient('cininvest_buy_error_' . $user_id, $result->get_error_message(), 60);
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    // витринные счётчики проекта: собрано, число инвесторов, остаток долей
    if (function_exists('update_field')) {
        update_field('collected', (float) cininvest_field('collected', $project_id, 0) + $base, $project_id);
        if (!$already_invested) {
            update_field('investors_count', (int) cininvest_field('investors_count', $project_id, 0) + 1, $project_id);
        }
        $shares_left = max(0, (int) cininvest_field('shares_available', $project_id, 0) - $shares);
        update_field('shares_available', $shares_left, $project_id);
    }

    set_transient('cininvest_buy_ok_' . $user_id, 'Покупка оформлена', 60);
    if (function_exists('cininvest_add_notification')) {
        cininvest_add_notification($user_id, [
            'type' => 'buy',
            'text' => sprintf('Покупка долей оформлена: %s — %d шт.', get_the_title($project_id), $shares),
            'url'  => get_permalink($project_id),
        ]);
    }
    wp_safe_redirect(get_permalink($project_id));
    exit;
}

/** ПОДДЕРЖКА ПРОЕКТА / ПОКУПКА ВОЗНАГРАЖДЕНИЙ (вкладка «Вознаграждения») */
add_action('admin_post_cininvest_buy_perks', 'cininvest_handle_buy_perks');
// гость может нажать «Купить» на вкладке «Вознаграждения» → ведём на регистрацию, а не в белый экран
add_action('admin_post_nopriv_cininvest_buy_perks', 'cininvest_handle_buy_perks_nopriv');
function cininvest_handle_buy_perks_nopriv() {
    wp_safe_redirect(home_url('/register/'));
    exit;
}
function cininvest_handle_buy_perks() {
    if (!is_user_logged_in()) { wp_safe_redirect(home_url('/register/')); exit; }
    if (!isset($_POST['cininvest_buy_perks_nonce']) || !wp_verify_nonce($_POST['cininvest_buy_perks_nonce'], 'cininvest_buy_perks')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $user_id = get_current_user_id();
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $chosen = array_map('sanitize_text_field', (array) ($_POST['perks'] ?? []));

    // сумму и состав вознаграждений всегда берём из данных проекта, а не доверяем цифрам из формы
    $items = array_values(array_filter(
        cininvest_project_rewards($project_id),
        function ($r) use ($chosen) { return in_array($r['title'], $chosen, true); }
    ));
    $total = $items ? array_sum(wp_list_pluck($items, 'price')) : max(0, (float) ($_POST['total'] ?? 0));

    if ($total <= 0) {
        set_transient('cininvest_perks_error_' . $user_id, 'Укажите сумму или выберите хотя бы одно вознаграждение', 60);
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    // считаем ДО списания: первая ли это покупка данного юзера в этом проекте
    $already_sponsored = cininvest_project_user_has_operation($project_id, $user_id, 'privileges');

    // списание через платёжный слой; блоки «01» и «02» — одна и та же операция типа privileges,
    // блок «02» дополнительно кладёт meta.items для отображения состава в модалке
    $meta = ['op_type' => 'privileges', 'project_id' => $project_id];
    if ($items) $meta['items'] = $items;
    $result = cininvest_gateway()->charge($user_id, $total, $meta);

    if (is_wp_error($result)) {
        set_transient('cininvest_perks_error_' . $user_id, $result->get_error_message(), 60);
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    // витринные счётчики проекта: собрано (учитываем и «01» свободную сумму, и «02» вознаграждения
    // — обе формы это одна операция privileges) + число спонсоров
    if (function_exists('update_field')) {
        update_field('collected', (float) cininvest_field('collected', $project_id, 0) + $total, $project_id);
        if (!$already_sponsored) {
            update_field('sponsors_count', (int) cininvest_field('sponsors_count', $project_id, 0) + 1, $project_id);
        }
    }

    set_transient('cininvest_perks_ok_' . $user_id, 'Спасибо за поддержку!', 60);
    wp_safe_redirect(get_permalink($project_id) . '#rewards');
    exit;
}

/** ПОПОЛНЕНИЕ / ВЫВОД (кнопки в кошельке) */
add_action('admin_post_cininvest_wallet', 'cininvest_handle_wallet');
function cininvest_handle_wallet() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_wallet_nonce']) || !wp_verify_nonce($_POST['cininvest_wallet_nonce'], 'cininvest_wallet')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $user_id = get_current_user_id();
    $action = $_POST['wallet_action'] ?? '';
    $amount = max(0, (float) ($_POST['amount'] ?? 0));

    if ($action === 'deposit') {
        $res = cininvest_gateway()->deposit($user_id, $amount);
        if (is_wp_error($res)) {
            set_transient('cininvest_wallet_error_' . $user_id, $res->get_error_message(), 60);
        } elseif (function_exists('cininvest_add_notification')) {
            cininvest_add_notification($user_id, [
                'type' => 'deposit',
                'text' => 'Счёт пополнен на ' . cininvest_money($amount),
            ]);
        }
    } elseif ($action === 'withdraw') {
        $res = cininvest_gateway()->withdraw($user_id, $amount);
        if (is_wp_error($res)) set_transient('cininvest_wallet_error_' . $user_id, $res->get_error_message(), 60);
    }
    wp_safe_redirect(wp_get_referer() ?: home_url('/'));
    exit;
}
