<?php
/**
 * События, порождающие уведомления пользователю (см. хелперы в inc/helpers.php:
 * cininvest_add_notification / cininvest_get_notifications / cininvest_mark_read).
 *
 * Триггеры:
 *  - заявка получила статус «Одобрена» → автору «проект одобрен»;
 *  - заявка получила статус «Отклонена» → автору «требует доработки»;
 *  - CPT project опубликован → автору + всем инвесторам/спонсорам «новый проект»;
 *  - успешная покупка долей → покупателю (inc/ajax-shares.php);
 *  - пополнение кошелька → пользователю (inc/ajax-shares.php).
 */
if (!defined('ABSPATH')) exit;

/** Статус заявки сменился (таксономия application_status) */
add_action('set_object_terms', 'cininvest_notify_application_status', 10, 6);
function cininvest_notify_application_status($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    if ($taxonomy !== 'application_status' || get_post_type($object_id) !== 'application') return;

    $new_slugs = wp_get_object_terms($object_id, 'application_status', ['fields' => 'slugs']);
    if (is_wp_error($new_slugs)) return;

    $old_slugs = [];
    if (!empty($old_tt_ids)) {
        $old = get_terms([
            'taxonomy' => 'application_status', 'term_taxonomy_id' => $old_tt_ids,
            'hide_empty' => false, 'fields' => 'slugs',
        ]);
        if (!is_wp_error($old)) $old_slugs = $old;
    }

    $author = (int) get_post_field('post_author', $object_id);
    if (!$author) return;
    $title = get_the_title($object_id);

    if (in_array('approved', $new_slugs, true) && !in_array('approved', $old_slugs, true)) {
        cininvest_add_notification($author, [
            'type' => 'app_approved',
            'text' => 'Проект одобрен: ' . $title,
            'url'  => home_url('/profile-cinematographer/'),
        ]);
    }
    if (in_array('rejected', $new_slugs, true) && !in_array('rejected', $old_slugs, true)) {
        cininvest_add_notification($author, [
            'type' => 'app_rejected',
            'text' => 'Заявка требует доработки: ' . $title,
            'url'  => home_url('/application/?edit=' . $object_id),
        ]);
    }
}

/** CPT project опубликован (админ одобрил проект и выпустил его на витрину) */
add_action('transition_post_status', 'cininvest_notify_project_published', 10, 3);
function cininvest_notify_project_published($new_status, $old_status, $post) {
    if (!$post || $post->post_type !== 'project') return;
    if ($new_status !== 'publish' || $old_status === 'publish') return;

    $title = get_the_title($post);
    $url   = get_permalink($post);

    // автору проекта
    $author = (int) $post->post_author;
    if ($author) {
        cininvest_add_notification($author, [
            'type' => 'project_published',
            'text' => 'Ваш проект опубликован: ' . $title,
            'url'  => $url,
        ]);
    }

    // всем инвесторам и спонсорам
    $recipients = get_users([
        'fields'     => 'ID',
        'number'     => 500,
        'meta_query' => [[
            'key' => 'cininvest_role', 'value' => ['investor', 'sponsor'], 'compare' => 'IN',
        ]],
    ]);
    foreach ($recipients as $uid) {
        if ((int) $uid === $author) continue;
        cininvest_add_notification((int) $uid, [
            'type' => 'new_project',
            'text' => 'Новый проект: ' . $title,
            'url'  => $url,
        ]);
    }
}

/** AJAX: отметить уведомления прочитанными (вызывается из main.js при открытии панели) */
add_action('wp_ajax_cininvest_notif_read', 'cininvest_ajax_notif_read');
function cininvest_ajax_notif_read() {
    check_ajax_referer('cininvest_notif', 'nonce');
    $ids = isset($_POST['ids']) ? array_map('sanitize_text_field', (array) $_POST['ids']) : null;
    cininvest_mark_read(get_current_user_id(), $ids);
    wp_send_json_success(['unread' => cininvest_unread_count()]);
}
