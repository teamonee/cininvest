<?php /* Template Name: Профиль — Спонсор */
cininvest_require_account_role('sponsor');
get_header();
$uid = get_current_user_id(); $u = wp_get_current_user(); $w = cininvest_wallet($uid);
?>
<main class="profile profile--wide profile--sponsor">
    <?php
    get_template_part('template-parts/profile-hero', null, [
        'name' => esc_html($u->display_name) . ',',
        'role' => 'Вы зарегистрированы как СПОНСОР',
        'lead' => 'Теперь вы можете помочь в реализации понравившейся идеи',
        'note' => 'Если хотите получать долю от прибыли пропорционально вложенным средствам — станьте инвестором',
        'cta'  => 'Стать инвестором', 'cta_href' => home_url('/register/'),
    ]);
    get_template_part('template-parts/wallet', null, [
        'rows' => [
            ['label' => 'Общая сумма взносов', 'value' => cininvest_money($w['total'])],
            ['label' => 'Зарезервировано', 'value' => cininvest_money($w['reserved'])],
        ],
        'free' => cininvest_money($w['free']),
    ]);
    ?>
    <section class="tabs" data-tabs>
        <nav class="container tabs__nav tabs__nav--scroll" role="tablist">
            <button class="tabs__link tabs__link--active" data-tab="profile" role="tab">Мой профиль</button>
            <button class="tabs__link" data-tab="fees" role="tab">Мои взносы</button>
            <button class="tabs__link" data-tab="history" role="tab">История операций</button>
        </nav>
        <div class="container tabs__panel is-active" data-panel="profile">
            <h2 class="tabs__title">Мои данные <button class="icon-edit" type="button" data-profile-edit aria-label="Редактировать"></button></h2>
            <?php if ($profile_msg = get_transient('cininvest_profile_msg_' . $uid)) { delete_transient('cininvest_profile_msg_' . $uid); echo '<p class="profile-form__msg">' . esc_html($profile_msg) . '</p>'; } ?>
            <form class="profile-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" data-profile-form>
                <input type="hidden" name="action" value="cininvest_profile_save">
                <?php wp_nonce_field('cininvest_profile_save', 'cininvest_profile_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <div class="photo-picker" data-photo-picker><span>Выбрать фото</span><input type="file" name="avatar" accept="image/*" hidden></div>
                <label class="field"><span class="field__label">ФИО</span><input class="field__input" name="fio" value="<?php echo esc_attr($u->display_name); ?>" readonly></label>
                <label class="field"><span class="field__label">Номер телефона</span><input class="field__input" name="phone" value="<?php echo esc_attr(get_user_meta($uid,'cininvest_phone',true)); ?>" readonly></label>
                <label class="field"><span class="field__label">Email</span><input class="field__input" type="email" name="email" value="<?php echo esc_attr($u->user_email); ?>" readonly></label>
                <label class="field"><span class="field__label">Пароль</span><input class="field__input" type="password" name="password" placeholder="Оставьте пустым, чтобы не менять" readonly></label>
            </form>
        </div>
        <div class="container tabs__panel" data-panel="fees">
            <?php
            $ops = cininvest_get_operations($uid); $has = false;
            foreach ($ops as $op) { if ($op->type !== 'privileges') continue; $has = true; ?>
            <article class="reward">
                <span class="reward__icon"></span>
                <div class="reward__body">
                    <h4 class="reward__title"><?php echo esc_html($op->project_id ? get_the_title($op->project_id) : 'Проект'); ?></h4>
                    <p class="reward__org"><?php echo esc_html(cininvest_field('org_name',$op->project_id,'')); ?> <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6"/></svg></p>
                </div>
                <div class="reward__side">
                    <span class="reward__amount"><?php echo esc_html(cininvest_money($op->amount)); ?></span>
                    <span class="reward__tag">Вознаграждение</span>
                </div>
            </article>
            <?php }
            if (!$has) echo '<p class="empty">У вас пока нет взносов.</p>';
            ?>
        </div>
        <div class="container tabs__panel" data-panel="history">
            <?php
            $ops = cininvest_get_operations($uid);
            if ($ops) foreach ($ops as $op) {
                $sign = in_array($op->type, ['deposit','sell','reward']) ? '+ ' : '- ';
                $titles = ['withdraw'=>'Выведение средств','deposit'=>'Пополнение счёта','privileges'=>'Покупка привилегий','reward'=>'Вознаграждение','sell'=>'Продажа долей','buy'=>'Покупка долей'];
                ?>
                <article class="operation">
                    <span class="operation__icon operation__icon--<?php echo esc_attr($op->type); ?>"></span>
                    <div class="operation__body"><h4 class="operation__title"><?php echo esc_html($titles[$op->type] ?? 'Операция'); ?></h4><time class="operation__date"><?php echo esc_html(date_i18n('d.m.Y, H:i', strtotime($op->created_at))); ?></time></div>
                    <div class="operation__side"><span class="operation__amount"><?php echo esc_html($sign . cininvest_money($op->amount)); ?></span><?php if ($op->shares) : ?><span class="operation__shares">Долей: <span class="num"><?php echo (int)$op->shares; ?></span></span><?php endif; ?></div>
                    <a class="operation__more" href="#" data-open="op-<?php echo esc_attr($op->id); ?>">Подробнее…</a>
                </article>
            <?php }
            else echo '<p class="empty">История операций пуста.</p>';
            foreach ($ops as $op) get_template_part('template-parts/operation-modal', null, ['op' => $op]);
            ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
