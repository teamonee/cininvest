<?php
/* Template Name: Подача заявки */
if (!is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_to', urlencode(home_url('/application/')), home_url('/login/')));
    exit;
}
if (cininvest_user_role(get_current_user_id()) !== 'cinematographer') {
    set_transient('cininvest_profile_msg_' . get_current_user_id(), 'Подавать проект на платформе может только кинематографист', 60);
    wp_safe_redirect(cininvest_account_url_for_role(cininvest_user_role(get_current_user_id())));
    exit;
}
$app_uid = get_current_user_id();
$app_error = get_transient('cininvest_app_error_' . $app_uid);
$app_data = get_transient('cininvest_app_data_' . $app_uid) ?: [];
if ($app_error) delete_transient('cininvest_app_error_' . $app_uid);
if ($app_data) delete_transient('cininvest_app_data_' . $app_uid);
get_header();
$has_type = isset($_GET['type']);
$is_ur = $has_type && $_GET['type'] === 'ur';
// после ошибки валидации возвращаемся сразу на форму того же типа (тип пришёл в сохранённом $_POST)
if ($app_error && !empty($app_data['applicant_type'])) {
    $is_ur = $app_data['applicant_type'] === 'ur';
    $has_type = true;
}
?>
<main class="application-flow" data-appflow data-type="<?php echo $is_ur ? 'ur' : 'fiz'; ?>">
    <section class="app-step<?php echo (!$has_type && !$app_error) ? ' is-active' : ''; ?>" data-appstep="type">
        <div class="container app-type">
            <h1 class="app-type__title">Подача заявки</h1>
            <p class="app-type__text">Выберите, будете ли вы реализовывать проект как физическое лицо или представитель организации</p>
            <div class="app-type__options">
                <button class="type-option<?php echo $is_ur ? '' : ' type-option--active'; ?>" type="button" data-type="fiz"><span>Физическое лицо</span></button>
                <button class="type-option<?php echo $is_ur ? ' type-option--active' : ''; ?>" type="button" data-type="ur"><span>Юридическое лицо</span></button>
            </div>
            <div class="app-type__actions">
                <?php // Шаг «type» — первый в потоке, предыдущего шага нет → «Назад» выходит в личный кабинет ?>
                <a class="btn btn--grey btn--icon-left" href="<?php echo esc_url(home_url('/profile-cinematographer/')); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</a>
                <button class="btn btn--primary" type="button" data-appnext="form"><span data-type-label>Продолжить как <?php echo $is_ur ? 'юрлица' : 'физлица'; ?></span><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </div>
    </section>

    <section class="app-step<?php echo ($has_type || $app_error) ? ' is-active' : ''; ?>" data-appstep="form">
        <div class="app-form-head container"><h1 class="app-form__title">Заявка <span data-appform-role><?php echo $is_ur ? 'юрлица' : 'физлица'; ?></span></h1><p class="app-form__req">*поля, обязательные для заполнения</p></div>
        <?php if ($app_error) : ?>
        <div class="container"><p class="app-form__error"><?php echo esc_html($app_error); ?></p></div>
        <?php endif; ?>
        <ol class="stepper container" role="list">
            <?php $steps = [$is_ur ? 'Заявитель' : 'Данные','Проект','Создатели фильма','Календарный план','Финансирование производства','Реквизиты заявителя','Вознаграждения'];
            foreach ($steps as $i => $s) : ?>
            <li class="stepper__item <?php echo $i === 0 ? 'stepper__item--active' : ''; ?>"><span class="stepper__num"><?php printf('%02d', $i+1); ?></span><span class="stepper__label"><?php echo esc_html($s); ?></span></li>
            <?php endforeach; ?>
        </ol>
        <form class="app-form container" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="cininvest_application">
            <input type="hidden" name="applicant_type" value="<?php echo $is_ur ? 'ur' : 'fiz'; ?>" data-applicant-type-field>
            <?php wp_nonce_field('cininvest_application', 'cininvest_app_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
            <?php
            // Юрлицо: первая секция — «Заявитель». Физлицо: «Мои данные».
            if ($is_ur) {
                get_template_part('template-parts/application/section', 'applicant', ['data' => $app_data]);
            } else {
                get_template_part('template-parts/application/section', 'data', ['data' => $app_data]);
            }
            get_template_part('template-parts/application/section', 'project', ['is_ur' => $is_ur, 'data' => $app_data]);
            get_template_part('template-parts/application/section', 'creators', ['is_ur' => $is_ur, 'data' => $app_data]);
            get_template_part('template-parts/application/section', 'schedule', ['data' => $app_data]);
            get_template_part('template-parts/application/section', 'financing', ['data' => $app_data]);
            get_template_part('template-parts/application/section', 'requisites', ['is_ur' => $is_ur, 'data' => $app_data]);
            get_template_part('template-parts/application/section', 'rewards', ['data' => $app_data]);
            ?>
            <div class="app-form__actions">
                <button class="btn btn--grey btn--icon-left" type="submit" name="save_draft" value="1"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Сохранить черновик</button>
                <button class="btn btn--primary" type="submit">Отправить <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </form>
    </section>
</main>
<?php get_footer(); ?>
