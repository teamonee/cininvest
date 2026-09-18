<?php /* Template Name: Настройки */
if (!is_user_logged_in()) { wp_safe_redirect(home_url('/login/')); exit; }
get_header();
$uid = get_current_user_id();
$u   = wp_get_current_user();
$icons = get_template_directory_uri() . '/assets/img/icons';

$email_notif = get_user_meta($uid, 'cininvest_email_notifications', true);
$email_notif = ($email_notif === '') ? '1' : $email_notif; // по умолчанию включено
$tg = get_user_meta($uid, 'cininvest_telegram', true);

$msg = get_transient('cininvest_settings_msg_' . $uid);
if ($msg) delete_transient('cininvest_settings_msg_' . $uid);
$eye = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
?>
<main class="settings container">
    <h1 class="settings__title">Настройки</h1>
    <?php if ($msg) : ?><p class="settings__msg"><?php echo esc_html($msg); ?></p><?php endif; ?>

    <form class="settings__section" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="cininvest_settings_save">
        <?php wp_nonce_field('cininvest_settings_save', 'cininvest_settings_nonce'); ?>
        <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">

        <h2 class="settings__section-title"><img class="settings__ic" src="<?php echo esc_url($icons . '/edit.svg'); ?>" alt="">Email и пароль</h2>
        <label class="field"><span class="field__label">Email</span><input class="field__input" type="email" name="email" value="<?php echo esc_attr($u->user_email); ?>" autocomplete="email" required></label>
        <label class="field field--password"><span class="field__label">Новый пароль</span><span class="field__wrap"><input class="field__input" type="password" name="password" autocomplete="new-password" placeholder="Оставьте пустым, чтобы не менять"><button class="field__eye" type="button" aria-label="Показать пароль"><?php echo $eye; ?></button></span></label>
        <label class="field field--password"><span class="field__label">Повторите новый пароль</span><span class="field__wrap"><input class="field__input" type="password" name="password2" autocomplete="new-password"><button class="field__eye" type="button" aria-label="Показать пароль"><?php echo $eye; ?></button></span></label>

        <h2 class="settings__section-title"><img class="settings__ic" src="<?php echo esc_url($icons . '/bell.svg'); ?>" alt="">Уведомления</h2>
        <label class="settings__toggle"><input type="checkbox" name="email_notifications" value="1" <?php checked($email_notif, '1'); ?>><span>Получать уведомления на email</span></label>

        <h2 class="settings__section-title"><img class="settings__ic" src="<?php echo esc_url($icons . '/telegram.svg'); ?>" alt="">Telegram</h2>
        <label class="field"><span class="field__label">Telegram для уведомлений</span><input class="field__input" type="text" name="telegram" value="<?php echo esc_attr($tg); ?>" placeholder="@username"></label>
        <p class="settings__hint">Укажите ваш @username — привяжем бота для мгновенных уведомлений о статусе проектов и операциях.</p>

        <button class="btn btn--primary settings__save" type="submit">Сохранить изменения</button>
    </form>

    <form class="settings__section settings__section--danger" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="cininvest_delete_account">
        <?php wp_nonce_field('cininvest_delete_account', 'cininvest_delete_nonce'); ?>
        <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
        <h2 class="settings__section-title"><img class="settings__ic" src="<?php echo esc_url($icons . '/exit.svg'); ?>" alt="">Удаление аккаунта</h2>
        <p class="settings__hint">Аккаунт и доступ к личному кабинету будут удалены безвозвратно. Сведения о завершённых операциях сохраняются у оператора платформы согласно закону.</p>
        <label class="checkbox settings__confirm"><input type="checkbox" name="confirm" value="1" required><span>Я понимаю, что действие необратимо</span></label>
        <button class="btn btn--grey settings__delete" type="submit"><img src="<?php echo esc_url($icons . '/exit.svg'); ?>" alt="" width="18" height="18">Удалить аккаунт</button>
    </form>
</main>
<?php get_footer(); ?>
