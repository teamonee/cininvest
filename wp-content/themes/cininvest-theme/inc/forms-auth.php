<?php
/**
 * Формы авторизации: регистрация (с ролью), вход, восстановление.
 * Все через admin-post + nonce + санитизация.
 */
if (!defined('ABSPATH')) exit;

/**
 * Rate-limit по IP на транзиентах. Возвращает true, если запрос в пределах лимита
 * (счётчик при этом инкрементится), false — если лимит исчерпан и запрос надо отклонить.
 *
 * @param string $action     имя действия (login|register|lostpass|…)
 * @param int    $max        макс. число попыток за окно
 * @param int    $window_sec длина окна в секундах (окно скользящее: TTL продлевается каждой попыткой)
 */
function cininvest_check_rate_limit($action, $max = 5, $window_sec = 900) {
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    $key = 'cininvest_rl_' . sanitize_key($action) . '_' . md5($ip);
    $hits = (int) get_transient($key);
    if ($hits >= $max) return false;
    set_transient($key, $hits + 1, max(1, (int) $window_sec));
    return true;
}

/**
 * Требования к паролю: не короче 8 символов, минимум одна буква и одна цифра.
 * Возвращает текст ошибки или '' если пароль подходит.
 */
function cininvest_validate_password($pass) {
    $pass = (string) $pass;
    $len  = function_exists('mb_strlen') ? mb_strlen($pass) : strlen($pass);
    if ($len < 8) {
        return 'Пароль должен быть не короче 8 символов';
    }
    if (!preg_match('/\p{L}/u', $pass) || !preg_match('/\d/', $pass)) {
        return 'Пароль должен содержать хотя бы одну букву и одну цифру';
    }
    return '';
}

/** РЕГИСТРАЦИЯ */
add_action('admin_post_nopriv_cininvest_register', 'cininvest_handle_register');
function cininvest_handle_register() {
    if (!isset($_POST['cininvest_reg_nonce']) || !wp_verify_nonce($_POST['cininvest_reg_nonce'], 'cininvest_register')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    if (!cininvest_check_rate_limit('register', 5, 15 * MINUTE_IN_SECONDS)) {
        $token = wp_generate_password(20, false);
        set_transient('cininvest_reg_errors_' . $token, ['Слишком много попыток регистрации. Попробуйте через 15 минут.'], 60);
        wp_safe_redirect(add_query_arg('reg_err', $token, wp_get_referer() ?: home_url('/register/')));
        exit;
    }
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $last  = sanitize_text_field($_POST['last_name'] ?? '');
    $first = sanitize_text_field($_POST['first_name'] ?? '');
    $middle= sanitize_text_field($_POST['middle_name'] ?? '');
    $citizenship = sanitize_text_field($_POST['citizenship'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $role  = in_array($_POST['role'] ?? '', ['cinematographer', 'investor'], true) ? $_POST['role'] : 'investor';
    $agree = !empty($_POST['agree']);

    $errors = [];
    if (!is_email($email)) $errors[] = 'Некорректный email';
    if (email_exists($email)) $errors[] = 'Email уже зарегистрирован';
    if ($pw_err = cininvest_validate_password($pass)) $errors[] = $pw_err;
    if ($pass !== $pass2) $errors[] = 'Пароли не совпадают';
    if (!$agree) $errors[] = 'Нужно согласие на обработку ПДн';

    if ($errors) {
        $token = wp_generate_password(20, false);
        set_transient('cininvest_reg_errors_' . $token, $errors, 60);
        wp_safe_redirect(add_query_arg('reg_err', $token, wp_get_referer() ?: home_url('/register/')));
        exit;
    }

    $user_id = wp_insert_user([
        'user_login' => $email, 'user_email' => $email, 'user_pass' => $pass,
        'first_name' => $first, 'last_name' => $last,
        'display_name' => trim("$last $first $middle"),
        'role' => 'subscriber',
    ]);
    if (is_wp_error($user_id)) {
        $token = wp_generate_password(20, false);
        set_transient('cininvest_reg_errors_' . $token, [$user_id->get_error_message()], 60);
        wp_safe_redirect(add_query_arg('reg_err', $token, wp_get_referer() ?: home_url('/register/')));
        exit;
    }

    // профиль в user-meta
    cininvest_set_user_role($user_id, $role);
    update_user_meta($user_id, 'cininvest_phone', $phone);
    update_user_meta($user_id, 'cininvest_middle_name', $middle);
    update_user_meta($user_id, 'cininvest_citizenship', $citizenship);

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // редирект в профиль по роли
    wp_safe_redirect(cininvest_account_url_for_role($role));
    exit;
}

/** СОХРАНЕНИЕ ПРОФИЛЯ — форма «Мои данные» в личном кабинете (карандаш → редактирование) */
add_action('admin_post_cininvest_profile_save', 'cininvest_handle_profile_save');
function cininvest_handle_profile_save() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_profile_nonce']) || !wp_verify_nonce($_POST['cininvest_profile_nonce'], 'cininvest_profile_save')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $uid = get_current_user_id();
    $me = wp_get_current_user();
    $errors = [];

    // ФИО -> display_name
    $fio = sanitize_text_field($_POST['fio'] ?? '');
    if ($fio !== '' && $fio !== $me->display_name) {
        wp_update_user(['ID' => $uid, 'display_name' => $fio]);
    }

    // телефон
    if (isset($_POST['phone'])) {
        update_user_meta($uid, 'cininvest_phone', sanitize_text_field($_POST['phone']));
    }

    // email
    $email = sanitize_email($_POST['email'] ?? '');
    if ($email && strtolower($email) !== strtolower($me->user_email)) {
        if (!is_email($email))        $errors[] = 'Некорректный email';
        elseif (email_exists($email)) $errors[] = 'Email уже занят';
        else                          wp_update_user(['ID' => $uid, 'user_email' => $email]);
    }

    // пароль (только если введён новый)
    $pass = (string) ($_POST['password'] ?? '');
    $pass_changed = false;
    if ($pass !== '') {
        if ($pw_err = cininvest_validate_password($pass)) {
            $errors[] = $pw_err;
        } else {
            wp_set_password($pass, $uid);
            $pass_changed = true;
        }
    }

    // расширенные поля кинематографиста
    foreach (['education', 'about', 'kinopoisk'] as $f) {
        if (isset($_POST[$f])) update_user_meta($uid, 'cininvest_' . $f, sanitize_text_field($_POST[$f]));
    }
    foreach (['edu_org', 'experience', 'social'] as $f) {
        if (isset($_POST[$f]) && is_array($_POST[$f])) {
            update_user_meta($uid, 'cininvest_' . $f, array_values(array_filter(array_map('sanitize_text_field', $_POST[$f]))));
        }
    }

    // аватар
    if (!empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $att = media_handle_upload('avatar', 0);
            if (!is_wp_error($att)) update_user_meta($uid, 'cininvest_avatar_id', (int) $att);
        }
    }

    // смена пароля разлогинивает — восстановим сессию
    if ($pass_changed) {
        wp_set_current_user($uid);
        wp_set_auth_cookie($uid, true);
    }

    set_transient('cininvest_profile_msg_' . $uid, $errors ? implode('. ', $errors) : 'Данные сохранены', 60);
    wp_safe_redirect(wp_get_referer() ?: cininvest_account_url_for_role(cininvest_user_role($uid)));
    exit;
}

/** ВХОД */
add_action('admin_post_nopriv_cininvest_login', 'cininvest_handle_login');
function cininvest_handle_login() {
    if (!isset($_POST['cininvest_login_nonce']) || !wp_verify_nonce($_POST['cininvest_login_nonce'], 'cininvest_login')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    if (!cininvest_check_rate_limit('login', 5, 15 * MINUTE_IN_SECONDS)) {
        set_transient('cininvest_login_error', 'Слишком много попыток входа. Попробуйте через 15 минут.', 60);
        wp_safe_redirect(wp_get_referer() ?: home_url('/login/'));
        exit;
    }
    $creds = [
        'user_login' => sanitize_email($_POST['log'] ?? ''),
        'user_password' => $_POST['pwd'] ?? '',
        'remember' => !empty($_POST['rememberme']),
    ];
    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        set_transient('cininvest_login_error', 'Неверный email или пароль', 60);
        wp_safe_redirect(wp_get_referer() ?: home_url('/login/'));
        exit;
    }
    wp_safe_redirect(cininvest_account_url_for_role(cininvest_user_role($user->ID)));
    exit;
}

/** НАСТРОЙКИ аккаунта — смена email/пароля, email-уведомления, Telegram (page-settings.php) */
add_action('admin_post_cininvest_settings_save', 'cininvest_handle_settings_save');
function cininvest_handle_settings_save() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_settings_nonce']) || !wp_verify_nonce($_POST['cininvest_settings_nonce'], 'cininvest_settings_save')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $uid = get_current_user_id();
    $me  = wp_get_current_user();
    $errors = [];
    $pass_changed = false;

    // email
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if ($email && strtolower($email) !== strtolower($me->user_email)) {
        if (!is_email($email))        $errors[] = 'Некорректный email';
        elseif (email_exists($email)) $errors[] = 'Email уже занят';
        else                          wp_update_user(['ID' => $uid, 'user_email' => $email]);
    }

    // пароль (только если введён новый)
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    if ($pass !== '') {
        if ($pw_err = cininvest_validate_password($pass)) $errors[] = $pw_err;
        elseif ($pass !== $pass2) $errors[] = 'Пароли не совпадают';
        else { wp_set_password($pass, $uid); $pass_changed = true; }
    }

    // email-уведомления
    update_user_meta($uid, 'cininvest_email_notifications', empty($_POST['email_notifications']) ? '0' : '1');

    // Telegram (@username)
    $tg = preg_replace('/[^A-Za-z0-9_]/', '', ltrim(sanitize_text_field(wp_unslash($_POST['telegram'] ?? '')), '@'));
    update_user_meta($uid, 'cininvest_telegram', $tg !== '' ? '@' . $tg : '');

    // смена пароля разлогинивает — восстановим сессию
    if ($pass_changed) { wp_set_current_user($uid); wp_set_auth_cookie($uid, true); }

    set_transient('cininvest_settings_msg_' . $uid, $errors ? implode('. ', $errors) : 'Настройки сохранены', 60);
    wp_safe_redirect(wp_get_referer() ?: home_url('/settings/'));
    exit;
}

/** УДАЛЕНИЕ аккаунта самим пользователем (page-settings.php) */
add_action('admin_post_cininvest_delete_account', 'cininvest_handle_delete_account');
function cininvest_handle_delete_account() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_delete_nonce']) || !wp_verify_nonce($_POST['cininvest_delete_nonce'], 'cininvest_delete_account')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    $uid = get_current_user_id();

    if (empty($_POST['confirm'])) {
        set_transient('cininvest_settings_msg_' . $uid, 'Подтвердите удаление галочкой', 60);
        wp_safe_redirect(wp_get_referer() ?: home_url('/settings/'));
        exit;
    }
    // администратора со страницы настроек не удаляем — чтобы не заблокировать сайт
    if (user_can($uid, 'manage_options')) {
        set_transient('cininvest_settings_msg_' . $uid, 'Аккаунт администратора нельзя удалить со страницы настроек', 60);
        wp_safe_redirect(home_url('/settings/'));
        exit;
    }

    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_logout();
    wp_delete_user($uid);
    wp_safe_redirect(home_url('/?account_deleted=1'));
    exit;
}

/** ВОССТАНОВЛЕНИЕ ПАРОЛЯ — форма «Забыли пароль?» на page-login.php */
add_action('admin_post_nopriv_cininvest_lostpass', 'cininvest_handle_lostpass');
add_action('admin_post_cininvest_lostpass', 'cininvest_handle_lostpass');
function cininvest_handle_lostpass() {
    if (!isset($_POST['cininvest_lostpass_nonce']) || !wp_verify_nonce($_POST['cininvest_lostpass_nonce'], 'cininvest_lostpass')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }
    if (!cininvest_check_rate_limit('lostpass', 5, 15 * MINUTE_IN_SECONDS)) {
        set_transient('cininvest_lostpass_msg', 'Слишком много попыток. Попробуйте через 15 минут.', 60);
        wp_safe_redirect(wp_get_referer() ?: home_url('/login/'));
        exit;
    }
    $login = sanitize_text_field(wp_unslash($_POST['user_login'] ?? ''));
    if ($login !== '' && function_exists('retrieve_password')) {
        retrieve_password($login); // WP сам не раскрывает, существует ли аккаунт
    }
    // единый нейтральный ответ — не даём перечислять пользователей
    set_transient('cininvest_lostpass_msg', 'Если аккаунт с таким email существует, мы отправили письмо со ссылкой для сброса пароля.', 60);
    wp_safe_redirect(wp_get_referer() ?: home_url('/login/'));
    exit;
}
