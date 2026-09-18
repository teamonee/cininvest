<?php
/* Template Name: Регистрация */
$reg_errors = [];
if (!empty($_GET['reg_err']) && preg_match('/^[A-Za-z0-9]{20}$/', $_GET['reg_err'])) {
    $reg_token = $_GET['reg_err'];
    $reg_errors = get_transient('cininvest_reg_errors_' . $reg_token) ?: [];
    delete_transient('cininvest_reg_errors_' . $reg_token);
}
// роль можно передать ссылкой (?role=cinematographer) — сразу открываем форму с ней
$reg_role = in_array($_GET['role'] ?? '', ['cinematographer', 'investor'], true) ? $_GET['role'] : null;
get_header();
?>
<main class="auth register" data-register>
    <section class="register__step<?php echo ($reg_errors || $reg_role) ? '' : ' is-active'; ?>" data-step="role">
        <div class="auth__box register__role">
            <a class="auth__close" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Закрыть"><svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>
            <h1 class="register__role-title">Выберите свою роль</h1>
            <div class="register__role-btns">
                <button class="btn btn--primary" type="button" data-role="cinematographer" data-next="form">Кинематографист</button>
                <button class="btn btn--primary" type="button" data-role="investor" data-next="form">Инвестор</button>
            </div>
            <p class="register__role-note">Если захотите сменить свою роль, будет необходимо создать новый аккаунт</p>
        </div>
    </section>
    <section class="register__step<?php echo ($reg_errors || $reg_role) ? ' is-active' : ''; ?>" data-step="form">
        <div class="auth__box register__form-box">
            <a class="auth__close" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Закрыть"><svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>
            <h1 class="auth__title">Регистрация</h1>
            <?php if ($reg_errors) : ?>
            <div class="auth__error">
                <?php foreach ($reg_errors as $reg_error) : ?><p><?php echo esc_html($reg_error); ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="register__subtitle">Если вы уже зарегистрированы, просто войдите <a href="<?php echo esc_url(home_url('/login/')); ?>">в свой аккаунт</a></p>
            <form class="auth-form register__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cininvest_register">
                <input type="hidden" name="role" value="<?php echo esc_attr($reg_role ?: 'investor'); ?>" data-role-field>
                <?php wp_nonce_field('cininvest_register', 'cininvest_reg_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <label class="field"><span class="field__label">Email*</span><input class="field__input" type="email" name="email" placeholder="Ваш email, будет использоваться для входа в систему и восстановления пароля" required></label>
                <label class="field"><span class="field__label">Мобильный телефон*</span><input class="field__input" type="tel" name="phone" placeholder="Введите корректный номер мобильного телефона" required></label>
                <label class="field"><span class="field__label">Фамилия*</span><input class="field__input" name="last_name" required></label>
                <label class="field"><span class="field__label">Имя*</span><input class="field__input" name="first_name" required></label>
                <label class="field"><span class="field__label">Отчество*</span><input class="field__input" name="middle_name"></label>
                <label class="field"><span class="field__label">Гражданство*</span><input class="field__input" name="citizenship" required></label>
                <label class="field"><span class="field__label">Пароль*</span><input class="field__input" type="password" name="password" placeholder="Создать пароль для входа в систему" required></label>
                <label class="field"><span class="field__label">Повторить пароль*</span><input class="field__input" type="password" name="password2" placeholder="Повторите ввод пароля" required></label>
                <label class="checkbox register__agree"><input type="checkbox" name="agree" required><span>Даю свое согласие на обработку персональных данных.</span></label>
                <div class="register__actions">
                    <button class="btn btn--grey btn--icon-left" type="button" data-back="role"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                    <button class="btn btn--primary" type="submit">Зарегистрироваться <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </div>
            </form>
        </div>
    </section>
</main>
<?php get_footer(); ?>
