<?php
/**
 * Подача заявки (7 секций) + черновик + загрузка файлов.
 */
if (!defined('ABSPATH')) exit;

add_action('admin_post_cininvest_application', 'cininvest_handle_application');
function cininvest_handle_application() {
    if (!is_user_logged_in()) wp_die('Требуется авторизация');
    if (!isset($_POST['cininvest_app_nonce']) || !wp_verify_nonce($_POST['cininvest_app_nonce'], 'cininvest_application')) {
        wp_die('Ошибка безопасности');
    }
    if (cininvest_user_role(get_current_user_id()) !== 'cinematographer') {
        wp_die('Недостаточно прав');
    }
    if (empty($_POST['cininvest_token']) || !cininvest_check_and_consume_token($_POST['cininvest_token'])) {
        wp_die('Форма уже была отправлена или устарела. Обновите страницу и попробуйте снова.');
    }

    $user_id = get_current_user_id();
    $is_draft = !empty($_POST['save_draft']);
    $applicant_type = in_array($_POST['applicant_type'] ?? '', ['fiz', 'ur'], true) ? $_POST['applicant_type'] : 'fiz';

    // название проекта (обязательно для не-черновика)
    $project_name = sanitize_text_field($_POST['project_name'] ?? '');
    if (!$is_draft && !$project_name) {
        set_transient('cininvest_app_error_' . $user_id, 'Укажите название проекта', 60);
        set_transient('cininvest_app_data_' . $user_id, $_POST, 60);
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    // создаём/обновляем заявку
    $app_id = !empty($_POST['app_id']) ? (int) $_POST['app_id'] : 0;

    // защита от двойной отправки: если это новая заявка (app_id ещё не назначен) и у этого
    // пользователя за последние 60 сек уже есть заявка с таким же названием — не дублируем
    if (!$app_id && $project_name) {
        global $wpdb;
        $dup_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'application' AND post_author = %d AND post_title = %s AND post_date_gmt > %s ORDER BY ID DESC LIMIT 1",
            $user_id, $project_name, gmdate('Y-m-d H:i:s', time() - 60)
        ));
        if ($dup_id) {
            set_transient('cininvest_app_ok_' . $user_id, $is_draft ? 'draft' : 'sent', 60);
            wp_safe_redirect($is_draft ? wp_get_referer() : home_url('/application-sent/'));
            exit;
        }
    }

    $postarr = [
        'post_type' => 'application',
        'post_title' => $project_name ?: 'Черновик заявки',
        'post_author' => $user_id,
        'post_status' => 'publish',
    ];
    if ($app_id) { $postarr['ID'] = $app_id; $app_id = wp_update_post($postarr); }
    else { $app_id = wp_insert_post($postarr); }

    if (is_wp_error($app_id)) wp_die('Ошибка сохранения заявки');

    // сохраняем все текстовые поля секций (санитизация)
    $fields = [
        'applicant_type', 'project_name', 'genre', 'duration', 'ptype', 'kind',
        'logline', 'idea', 'setting', 'annotation', 'synopsis', 'locations',
        // заявитель / мои данные
        'org_name', 'ceo_last', 'ceo_first', 'ceo_middle', 'position', 'applicant_phone',
        'education', 'additional',
        // создатели фильма
        'company', 'head_fio',
        'producer_fio', 'producer_kp', 'director_fio', 'director_kp',
        'writer_fio', 'writer_kp', 'operator_fio', 'operator_kp',
        'artist_fio', 'artist_kp', 'composer_fio', 'composer_kp',
        // календарный план
        'total_duration',
        // финансирование производства
        'budget', 'own_funds', 'requested', 'spent',
        // реквизиты (набор зависит от типа — сохраняем что пришло)
        'inn', 'ogrn', 'kpp', 'bik', 'bank_name', 'corr_account', 'settle_account',
        'snils', 'legal_address', 'ogrn_date', 'okved',
    ];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) update_post_meta($app_id, $f, sanitize_textarea_field($_POST[$f]));
    }

    // повторяемые текстовые поля («Мои данные» физлица)
    foreach (['edu_org', 'experience', 'social'] as $f) {
        if (!empty($_POST[$f]) && is_array($_POST[$f])) {
            update_post_meta($app_id, $f, array_map('sanitize_text_field', $_POST[$f]));
        }
    }

    // календарный план (периоды с датами) — массивом
    if (!empty($_POST['periods']) && is_array($_POST['periods'])) {
        $periods = array_map(function ($p) {
            return [
                'name' => sanitize_text_field($p['name'] ?? ''),
                'active' => !empty($p['active']),
                'start' => sanitize_text_field($p['start'] ?? ''),
                'end' => sanitize_text_field($p['end'] ?? ''),
            ];
        }, $_POST['periods']);
        update_post_meta($app_id, 'periods', $periods);
    }

    // вознаграждения (чекбоксы)
    if (!empty($_POST['rewards']) && is_array($_POST['rewards'])) {
        update_post_meta($app_id, 'rewards', array_map('sanitize_text_field', $_POST['rewards']));
    }

    // файлы (сценарий/презентация/смета/рекомендации/паспорт и пр.)
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $file_fields = ['script', 'presentation', 'sizzle', 'estimate', 'recommendations'];
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'xls', 'xlsx', 'doc', 'docx'];
    foreach ($file_fields as $ff) {
        if (!empty($_FILES[$ff]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$ff]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;               // фильтр расширений
            if ($_FILES[$ff]['size'] > 10 * 1024 * 1024) continue;       // лимит 10 МБ
            $att_id = media_handle_upload($ff, $app_id);
            if (!is_wp_error($att_id)) update_post_meta($app_id, $ff . '_file', $att_id);
        }
    }

    // статус заявки
    $status = $is_draft ? 'draft' : 'submitted';
    wp_set_object_terms($app_id, $status, 'application_status');

    set_transient('cininvest_app_ok_' . $user_id, $is_draft ? 'draft' : 'sent', 60);
    // черновик -> назад в форму; отправлено -> экран "Заявка отправлена"
    wp_safe_redirect($is_draft ? wp_get_referer() : home_url('/application-sent/'));
    exit;
}
