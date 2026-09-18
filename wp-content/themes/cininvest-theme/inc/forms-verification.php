<?php
/**
 * Верификация: загрузка документов, сохранение статуса.
 * Тип: fiz | ip | ur — определяет набор шагов/документов.
 */
if (!defined('ABSPATH')) exit;

add_action('admin_post_cininvest_verification', 'cininvest_handle_verification');
function cininvest_handle_verification() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_verify_nonce']) || !wp_verify_nonce($_POST['cininvest_verify_nonce'], 'cininvest_verification')) {
        wp_die('Ошибка безопасности');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }

    $user_id = get_current_user_id();
    $vtype = in_array($_POST['vtype'] ?? '', ['fiz', 'ip', 'ur'], true) ? $_POST['vtype'] : 'fiz';
    update_user_meta($user_id, 'cininvest_verify_type', $vtype);

    // текстовые поля (адрес, ИНН, ОГРН/ОГРНИП, реквизиты счёта)
    $fields = ['reg_address', 'inn', 'ogrn', 'account', 'bik', 'bank_name', 'corr_account'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) update_user_meta($user_id, 'cininvest_v_' . $f, sanitize_text_field($_POST[$f]));
    }

    // ответ на вопрос про ПДЛ (публичное должностное лицо)
    $is_pdl = in_array($_POST['is_pdl'] ?? '', ['yes', 'no'], true) ? $_POST['is_pdl'] : '';
    update_user_meta($user_id, 'cininvest_is_pdl', $is_pdl);

    // документы (паспорт-фото, паспорт-прописка, ЕГРИП, устав, полномочия)
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $docs = ['passport_photo', 'passport_reg', 'egrip', 'charter', 'authority'];
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'tiff'];
    foreach ($docs as $d) {
        if (!empty($_FILES[$d]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$d]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;
            if ($_FILES[$d]['size'] > 10 * 1024 * 1024) continue;
            $att_id = media_handle_upload($d, 0);
            if (!is_wp_error($att_id)) update_user_meta($user_id, 'cininvest_v_' . $d, $att_id);
        }
    }

    // «Проверка данных» — отдельный шаг внутри самой формы (data-vstep="checking"),
    // здесь пользователь уже подтвердил распознанные данные на шаге «Почти готово».
    update_user_meta($user_id, 'cininvest_verify_status', 'completed');

    // маскированные реквизиты для профиля
    if (!empty($_POST['inn'])) {
        $inn = preg_replace('/\D/', '', $_POST['inn']);
        update_user_meta($user_id, 'cininvest_inn_mask', '**** **** ' . substr($inn, -4));
    }

    wp_safe_redirect(home_url('/verification-done/'));
    exit;
}

/** Заготовка: подтверждение верификации (позже — из проверки/банка) */
function cininvest_complete_verification($user_id) {
    update_user_meta($user_id, 'cininvest_verify_status', 'completed');
    update_user_meta($user_id, 'cininvest_verified', 1);
}
