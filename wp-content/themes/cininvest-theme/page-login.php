<?php /* Template Name: Вход */ get_header(); ?>
<main class="auth">
    <div class="auth__box">
        <a class="auth__close" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Закрыть"><svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>
        <h1 class="auth__title">Вход в аккаунт</h1>
        <?php if ($err = get_transient('cininvest_login_error')) { echo '<p class="auth__error">' . esc_html($err) . '</p>'; delete_transient('cininvest_login_error'); } ?>
        <?php if ($lp = get_transient('cininvest_lostpass_msg')) { echo '<p class="auth__note">' . esc_html($lp) . '</p>'; delete_transient('cininvest_lostpass_msg'); } ?>
        <form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cininvest_login">
            <?php wp_nonce_field('cininvest_login', 'cininvest_login_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
            <label class="field"><span class="field__label">Email</span><input class="field__input" type="email" name="log" autocomplete="username" required></label>
            <label class="field field--password"><span class="field__label">Пароль</span><span class="field__wrap"><input class="field__input" type="password" name="pwd" autocomplete="current-password" required><button class="field__eye" type="button" aria-label="Показать пароль"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button></span></label>
            <button class="btn btn--primary auth__submit" type="submit">Войти</button>
        </form>
        <details class="auth__reset">
            <summary class="auth__forgot">Забыли пароль?</summary>
            <form class="auth-form auth__reset-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cininvest_lostpass">
                <?php wp_nonce_field('cininvest_lostpass', 'cininvest_lostpass_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <label class="field"><span class="field__label">Email для восстановления</span><input class="field__input" type="email" name="user_login" autocomplete="username" required></label>
                <button class="btn btn--grey auth__submit" type="submit">Отправить ссылку для сброса</button>
            </form>
        </details>
        <p class="auth__switch">Еще не зарегистрировались в CININVEST?<a href="<?php echo esc_url(home_url('/register/')); ?>">Зарегистрироваться</a></p>
    </div>
</main>
<?php get_footer(); ?>
