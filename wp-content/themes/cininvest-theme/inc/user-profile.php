<?php
/**
 * Профильные данные пользователя в user-meta: роль, баланс, реквизиты.
 */
if (!defined('ABSPATH')) exit;

/** Допустимые роли платформы. sponsor не выдаётся при регистрации — только вручную через wp-admin. для лысого */
function cininvest_valid_roles() {
    return ['investor', 'sponsor', 'cinematographer'];
}

/** Роль в системе: investor | sponsor | cinematographer, с безопасным дефолтом investor */
function cininvest_user_role($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    $role = get_user_meta($user_id, 'cininvest_role', true);
    return in_array($role, cininvest_valid_roles(), true) ? $role : 'investor';
}
function cininvest_set_user_role($user_id, $role) {
    update_user_meta($user_id, 'cininvest_role', $role);
}

/** URL личного кабинета для роли */
function cininvest_account_url_for_role($role) {
    $map = [
        'investor' => home_url('/profile-investor/'),
        'sponsor' => home_url('/profile-sponsor/'),
        'cinematographer' => home_url('/profile-cinematographer/'),
    ];
    return $map[$role] ?? $map['investor'];
}

/**
 * Гвардия для page-profile-*.php: требует логин, админам можно всё,
 * при несовпадении роли молча уводит пользователя в ЕГО собственный кабинет.
 */
function cininvest_require_account_role($expected_role) {
    if (!is_user_logged_in()) { wp_safe_redirect(home_url('/login/')); exit; }
    if (current_user_can('manage_options')) return;

    $actual_role = cininvest_user_role(get_current_user_id());
    if ($actual_role !== $expected_role) {
        wp_safe_redirect(cininvest_account_url_for_role($actual_role));
        exit;
    }
}

/**
 * АДМИНКА: ручное назначение роли CinInvest в профиле пользователя.
 * Единственный способ выдать роль sponsor — регистрация её не предлагает (как в эталоне).
 */
function cininvest_user_profile_role_field($user) {
    if (!current_user_can('edit_users')) return;
    $current = get_user_meta($user->ID, 'cininvest_role', true);
    ?>
    <h2>CinInvest</h2>
    <table class="form-table">
        <tr>
            <th><label for="cininvest_role">Роль на платформе</label></th>
            <td>
                <select name="cininvest_role" id="cininvest_role">
                    <option value="" <?php selected($current, ''); ?>>— не задана (будет исп. «investor» по умолчанию) —</option>
                    <option value="investor" <?php selected($current, 'investor'); ?>>Инвестор</option>
                    <option value="sponsor" <?php selected($current, 'sponsor'); ?>>Спонсор</option>
                    <option value="cinematographer" <?php selected($current, 'cinematographer'); ?>>Кинематографист</option>
                </select>
                <p class="description">Сейчас в базе сохранено: <code><?php echo esc_html($current ?: '(пусто)'); ?></code>. Определяет, какой личный кабинет открывается пользователю на сайте.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'cininvest_user_profile_role_field');
add_action('edit_user_profile', 'cininvest_user_profile_role_field');

function cininvest_user_profile_role_save($user_id) {
    if (!current_user_can('edit_users', $user_id) || !isset($_POST['cininvest_role'])) return;
    check_admin_referer('update-user_' . $user_id);

    $role = sanitize_key(wp_unslash($_POST['cininvest_role']));
    if (in_array($role, cininvest_valid_roles(), true)) {
        update_user_meta($user_id, 'cininvest_role', $role);
    } elseif ($role === '') {
        delete_user_meta($user_id, 'cininvest_role');
    }
}
add_action('personal_options_update', 'cininvest_user_profile_role_save');
add_action('edit_user_profile_update', 'cininvest_user_profile_role_save');

/** Колонка «Роль CinInvest» в списке пользователей — видно всех сразу, без захода в каждый профиль */
add_filter('manage_users_columns', function ($columns) {
    $columns['cininvest_role'] = 'Роль CinInvest';
    return $columns;
});
add_filter('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name !== 'cininvest_role') return $value;
    $role = get_user_meta($user_id, 'cininvest_role', true);
    $labels = ['investor' => 'Инвестор', 'sponsor' => 'Спонсор', 'cinematographer' => 'Кинематографист'];
    return isset($labels[$role]) ? esc_html($labels[$role]) : '—';
}, 10, 3);

/** Баланс (свободные средства) */
function cininvest_get_balance($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    return (float) get_user_meta($user_id, 'cininvest_balance', true);
}
function cininvest_adjust_balance($user_id, $delta) {
    $new = cininvest_get_balance($user_id) + (float) $delta;
    if ($new < 0) $new = 0;
    update_user_meta($user_id, 'cininvest_balance', $new);
    return $new;
}

/** Прочие кошелёк-показатели */
function cininvest_wallet($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    return [
        'total'       => (float) get_user_meta($user_id, 'cininvest_total', true),      // общая сумма взносов
        'reserved'    => (float) get_user_meta($user_id, 'cininvest_reserved', true),   // зарезервировано
        'free'        => cininvest_get_balance($user_id),                               // свободные средства
        'shares_value'=> (float) get_user_meta($user_id, 'cininvest_shares_value', true),// доля ЦФА (инвестор)
    ];
}

/** Документы/реквизиты пользователя (маскированные) */
function cininvest_user_docs($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    return [
        'passport' => get_user_meta($user_id, 'cininvest_passport_mask', true),
        'inn'      => get_user_meta($user_id, 'cininvest_inn_mask', true),
    ];
}
