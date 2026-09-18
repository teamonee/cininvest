<?php
/* Template Name: Верификация */
if (!is_user_logged_in()) { wp_safe_redirect(home_url('/login/')); exit; }
get_header();
?>
<main class="verify" data-verify>

    <section class="verify-step is-active container" data-vstep="type">
        <div class="verify-type">
            <p class="verify-type__pre">Прежде чем стать инвестором, необходимо пройти верификацию</p>
            <h1 class="verify__title">Верификация аккаунта</h1>
            <p class="verify-type__lead">Для инвестиций в кинопроекты нам нужно будет заключить договор и получить от вас юридические и банковские данные</p>
            <p class="verify-type__q">Выберите, будете ли вы проводить операции как физическое лицо или представитель организации</p>
            <div class="verify-type__options">
                <button class="type-option type-option--active" type="button" data-vt="fiz"><span>Физическое лицо</span></button>
                <button class="type-option" type="button" data-vt="ip"><span>ИП</span></button>
                <button class="type-option" type="button" data-vt="ur"><span>Юридическое лицо</span></button>
            </div>
            <p class="verify-type__warn">После прохождения верификации поменять тип профиля будет невозможно!</p>
            <div class="verify__actions">
                <button class="btn btn--grey btn--icon-left" type="button" data-vlater><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Вернуться к верификации позже</button>
                <button class="btn btn--primary" type="button" data-vnext="passport"><span data-vt-label>Продолжить как физлицо</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </div>
    </section>

    <form class="verify-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="cininvest_verification">
        <input type="hidden" name="vtype" value="fiz" data-vtype-field>
        <input type="hidden" name="is_pdl" value="" data-pdl-field>
        <?php wp_nonce_field('cininvest_verification', 'cininvest_verify_nonce'); ?>
        <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">

        <section class="verify-step container" data-vstep="passport">
            <h1 class="verify__title" data-vstep-title>Верификация физлица</h1>
            <p class="verify__lead">Сначала, нужно загрузить два разворота паспорта. Мы распознаем большую часть информации на них и заполним поля для договора. Дополнительно потребуется ввести адрес регистрации</p>
            <h2 class="verify__subtitle">Паспортные данные</h2>
            <div class="field"><span class="field__label">Паспорт, разворот с фотографией</span><label class="file-upload"><span class="file-upload__check"></span><span class="file-upload__text">Загрузите разворот паспорта с фотографией</span><span class="file-upload__btn">Выбрать файл<input type="file" name="passport_photo" hidden></span></label></div>
            <div class="field"><span class="field__label">Паспорт, разворот с пропиской</span><label class="file-upload"><span class="file-upload__check"></span><span class="file-upload__text">Загрузите разворот паспорта с пропиской</span><span class="file-upload__btn">Выбрать файл<input type="file" name="passport_reg" hidden></span></label></div>
            <label class="field"><span class="field__label">Адрес регистрации</span><input class="field__input" name="reg_address" placeholder="Введите свой адрес и выберите подходящий вариант"></label>
            <label class="checkbox verify__agree"><input type="checkbox" required><span>Нажимая кнопку «Отправить», я соглашаюсь с <a href="#">правилами платформы</a></span></label>
            <label class="checkbox verify__agree"><input type="checkbox" required><span>Нажимая кнопку «Отправить», я соглашаюсь с <a href="#">декларацией о рисках</a></span></label>
            <div class="verify__actions">
                <button class="btn btn--grey btn--icon-left" type="button" data-vback="type"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                <button class="btn btn--primary" type="button" data-vnext="pdl">Отправить <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </section>

        <section class="verify-step container" data-vstep="legal">
            <h1 class="verify__title" data-vstep-title>Верификация ИП</h1>
            <h2 class="verify__subtitle">Юридические данные</h2>
            <label class="field"><span class="field__label">ИНН</span><input class="field__input" name="inn" placeholder="Введите свой ИНН"></label>
            <label class="field" data-ogrn-label><span class="field__label">ОГРНИП</span><input class="field__input" name="ogrn" placeholder="Введите свой ОГРНИП"></label>
            <div class="field" data-egrip><span class="field__label">Копия выписки из ЕГРИП</span><label class="file-upload"><span class="file-upload__check"></span><span class="file-upload__text">Загрузите копию выписки из ЕГРИП</span><span class="file-upload__btn">Выбрать файл<input type="file" name="egrip" hidden></span></label></div>
            <div class="field" data-ur-only hidden><span class="field__label">Копия устава</span><label class="file-upload"><span class="file-upload__check"></span><span class="file-upload__text">Загрузите копию устава</span><span class="file-upload__btn">Выбрать файл<input type="file" name="charter" hidden></span></label></div>
            <div class="field" data-ur-only hidden><span class="field__label">Документ о полномочиях</span><label class="file-upload"><span class="file-upload__check"></span><span class="file-upload__text">Загрузите документ о полномочиях</span><span class="file-upload__btn">Выбрать файл<input type="file" name="authority" hidden></span></label></div>
            <div class="verify__actions">
                <button class="btn btn--grey btn--icon-left" type="button" data-vback="passport"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                <button class="btn btn--primary" type="button" data-vnext="account">Отправить <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </section>

        <section class="verify-step container" data-vstep="account">
            <h1 class="verify__title" data-vstep-title>Верификация ИП</h1>
            <h2 class="verify__subtitle">Реквизиты счёта</h2>
            <label class="field"><span class="field__label">Номер счёта</span><input class="field__input" name="account" placeholder="Введите номер своего счёта"></label>
            <label class="field"><span class="field__label">БИК</span><input class="field__input" name="bik" placeholder="Введите БИК"></label>
            <label class="field"><span class="field__label">Наименование банка</span><input class="field__input" name="bank_name" placeholder="Введите Наименование банка"></label>
            <label class="field"><span class="field__label">Корреспондентский счёт</span><input class="field__input" name="corr_account" placeholder="Введите Корреспондентский счёт"></label>
            <div class="verify__actions">
                <button class="btn btn--grey btn--icon-left" type="button" data-vback="legal"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                <button class="btn btn--primary" type="button" data-vnext="checking">Отправить <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </section>

        <section class="verify-step container" data-vstep="pdl">
            <h1 class="verify__title verify__title--sm">Небольшое уточнение</h1>
            <p class="verify__lead">Являетесь ли вы российским публичным должностным лицом, иностранным публичным должностным лицом или должностным лицом публичной международной организации?</p>
            <p style="text-align:center"><button class="btn btn--grey" type="button" data-open="pdl-info">Что это значит?</button></p>
            <div class="verify__actions" style="justify-content:center">
                <button class="btn btn--grey" type="button" data-vnext="checking" data-pdl-answer="no">Нет</button>
                <button class="btn btn--primary" type="button" data-vnext="checking" data-pdl-answer="yes">Да</button>
            </div>
        </section>

        <section class="verify-step container" data-vstep="checking">
            <h1 class="verify__title verify__title--sm">Проверка данных</h1>
            <p class="verify__lead">Мы одновременно проводим все проверки данных по вашей заявке</p>
            <div class="check-card">
                <h3 class="check-card__title">Выполняется проверка…</h3>
                <ul class="check-list">
                    <li class="check-list__item check-list__item--done"><span class="check-list__dot"></span>Проверка паспортных данных</li>
                    <li class="check-list__item check-list__item--done"><span class="check-list__dot"></span>Открытие номинального счёта в банке</li>
                    <li class="check-list__item"><span class="check-list__dot"></span>Проверка данных по 115-ФЗ <span class="check-list__timer">Осталось 20 ч</span></li>
                </ul>
            </div>
            <div class="verify__actions">
                <button class="btn btn--grey btn--icon-left" type="button" data-vback="pdl"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                <button class="btn btn--primary" type="button" data-vnext="almost">Продолжить <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </section>

        <?php
        $v_user_id = get_current_user_id();
        $v_wp_user = wp_get_current_user();
        $v_docs = cininvest_user_docs($v_user_id);
        $v_almost = [
            'series'      => get_user_meta($v_user_id, 'cininvest_v_passport_series', true),
            'last_name'   => $v_wp_user->last_name,
            'first_name'  => $v_wp_user->first_name,
            'middle_name' => get_user_meta($v_user_id, 'cininvest_middle_name', true),
            'birthdate'   => get_user_meta($v_user_id, 'cininvest_v_birthdate', true),
            'citizenship' => get_user_meta($v_user_id, 'cininvest_citizenship', true),
            'issue_date'  => get_user_meta($v_user_id, 'cininvest_v_issue_date', true),
            'dept_code'   => get_user_meta($v_user_id, 'cininvest_v_dept_code', true),
            'issued_by'   => get_user_meta($v_user_id, 'cininvest_v_issued_by', true),
            'birthplace'  => get_user_meta($v_user_id, 'cininvest_v_birthplace', true),
            'reg_address' => get_user_meta($v_user_id, 'cininvest_v_reg_address', true),
        ];
        $v_inn = $v_docs['inn'] ?: get_user_meta($v_user_id, 'cininvest_v_inn', true);
        $v_ogrn = get_user_meta($v_user_id, 'cininvest_v_ogrn', true);
        ?>
        <section class="verify-step container" data-vstep="almost">
            <h1 class="verify__title verify__title--sm">Почти готово</h1>
            <p class="verify__lead">Проверьте данные, которые мы распознали по вашим документам — если всё верно, подтвердите и продолжите</p>
            <div class="passport-card">
                <h3 class="passport-card__title">Паспортные данные</h3>
                <dl class="passport-grid">
                    <div class="passport-grid__cell"><dt>Серия и номер</dt><dd><?php echo esc_html($v_almost['series'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Фамилия</dt><dd><?php echo esc_html($v_almost['last_name'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Имя</dt><dd><?php echo esc_html($v_almost['first_name'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Отчество</dt><dd><?php echo esc_html($v_almost['middle_name'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Дата рождения</dt><dd><?php echo esc_html($v_almost['birthdate'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Гражданство</dt><dd><?php echo esc_html($v_almost['citizenship'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Дата выдачи</dt><dd><?php echo esc_html($v_almost['issue_date'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Код подразделения</dt><dd><?php echo esc_html($v_almost['dept_code'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Кем выдан</dt><dd><?php echo esc_html($v_almost['issued_by'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Место рождения</dt><dd><?php echo esc_html($v_almost['birthplace'] ?: '—'); ?></dd></div>
                    <div class="passport-grid__cell"><dt>Адрес регистрации</dt><dd><?php echo esc_html($v_almost['reg_address'] ?: '—'); ?></dd></div>
                </dl>
                <h3 class="passport-card__title">Другие документы</h3>
                <dl class="passport-grid">
                    <div class="passport-grid__cell">
                        <dt>ИНН</dt>
                        <dd><?php echo esc_html($v_inn ?: '—'); ?> <button class="copy-btn" type="button" data-copy="<?php echo esc_attr($v_inn); ?>" aria-label="Скопировать ИНН"></button></dd>
                    </div>
                    <div class="passport-grid__cell" data-ogrn-label data-not-fiz hidden>
                        <dt>ОГРНИП</dt>
                        <dd><?php echo esc_html($v_ogrn ?: '—'); ?> <button class="copy-btn" type="button" data-copy="<?php echo esc_attr($v_ogrn); ?>" aria-label="Скопировать ОГРН"></button></dd>
                    </div>
                </dl>
                <div class="passport-card__foot">
                    <label class="checkbox verify__agree"><input type="checkbox" required><span>Подтверждаю, что указанные данные верны</span></label>
                    <div class="verify__actions">
                        <button class="btn btn--grey btn--icon-left" type="button" data-vback="checking"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Назад</button>
                        <button class="btn btn--primary" type="submit">Подтвердить данные <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                    <p class="passport-card__support">Возникли вопросы по данным? Напишите в поддержку: <a href="mailto:support@test.ru">support@test.ru</a></p>
                </div>
            </div>
        </section>
    </form>

    <div class="modal" id="pdl-info" hidden>
        <div class="modal__overlay" data-close></div>
        <div class="modal__box">
            <button class="modal__close" type="button" data-close aria-label="Закрыть"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
            <h2 class="auth__title" style="margin-top:0">Что значит «публичное должностное лицо»?</h2>
            <p>Это человек, который занимает или занимал государственную должность — например, министр, депутат, судья или руководитель крупной государственной организации, — а также его близкие родственники. Для таких клиентов законом предусмотрена дополнительная проверка.</p>
        </div>
    </div>
</main>
<?php get_footer(); ?>
