<?php /* Template Name: Профиль — Инвестор */
cininvest_require_account_role('investor');
get_header();
$uid = get_current_user_id();
$u = wp_get_current_user();
$w = cininvest_wallet($uid);
$docs = cininvest_user_docs($uid);
?>
<main class="profile profile--wide profile--investor">
    <?php
    get_template_part('template-parts/profile-hero', null, [
        'name' => esc_html($u->display_name) . ',',
        'role' => 'Вы зарегистрированы как ИНВЕСТОР',
        'lead' => 'Теперь вы можете получать долю от прибыли кинопроекта',
        'note' => 'Сейчас Вы можете инвестировать в проекты до 600 000 тыс., если хотите увеличить свой лимит, необходимо стать квалифицированным инвестором',
        'cta'  => 'Пройти квалификацию', 'cta_href' => home_url('/qualification/'),
    ]);
    get_template_part('template-parts/wallet', null, [
        'rows' => [
            ['label' => 'Общая сумма взносов', 'value' => cininvest_money($w['total'])],
            ['label' => 'Доля ЦФА', 'value' => cininvest_money($w['shares_value'])],
            ['label' => 'Зарезервировано', 'value' => cininvest_money($w['reserved'])],
        ],
        'free' => cininvest_money($w['free']),
    ]);
    ?>
    <section class="tabs" data-tabs>
        <nav class="container tabs__nav" role="tablist">
            <button class="tabs__link tabs__link--active" data-tab="profile" role="tab">Мой профиль</button>
            <button class="tabs__link" data-tab="invest" role="tab">Мои инвестиции</button>
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
            <div class="docs-block">
                <h3 class="tabs__subtitle">Мои Документы и реквизиты</h3>
                <ul class="docs">
                    <li class="docs__row"><span>Паспорт <?php echo esc_html($docs['passport'] ?: '**** **** ****'); ?></span><a href="#">Посмотреть</a></li>
                    <li class="docs__row"><span>ИНН <?php echo esc_html($docs['inn'] ?: '**** **** ****'); ?></span><a href="#">Посмотреть</a></li>
                </ul>
            </div>
            <div class="docs-block">
                <h3 class="tabs__subtitle">Счета</h3>
                <ul class="docs">
                    <li class="docs__row"><span>Реквизиты счёта</span><a href="#">Посмотреть</a></li>
                </ul>
            </div>
            <div class="docs-block">
                <h3 class="tabs__subtitle">Договоры и отчёты</h3>
                <ul class="docs">
                    <li class="docs__row docs__row--file">
                        <img class="docs__row-ic" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/icons/pdf.svg'); ?>" alt="" width="20" height="20">
                        <span class="docs__row-name">Заявление о присоединении к договору по содействию в инвестировании</span>
                        <span class="docs__row-meta">PDF, 408 КБ, 14.10.2025</span>
                        <span class="docs__row-actions"><a href="#">Скачать</a><a href="#">Посмотреть</a></span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="container tabs__panel" data-panel="invest">
            <?php
            $ops = cininvest_get_operations($uid);
            $has = false;
            foreach ($ops as $op) { if ($op->type !== 'buy') continue; $has = true;
                $proj = $op->project_id ? get_the_title($op->project_id) : 'Проект';
                $p_goal = (float) cininvest_field('goal', $op->project_id, 0);
                $p_coll = (float) cininvest_field('collected', $op->project_id, 0);
                $p_pct  = $p_goal > 0 ? min(100, round($p_coll / $p_goal * 100)) : 0;
                $p_round = (int) cininvest_field('round', $op->project_id, 0);
                $p_days  = (int) cininvest_field('days_left', $op->project_id, 0);
                ?>
            <article class="project-card">
                <img class="project-card__poster" src="<?php echo esc_url(cininvest_poster($op->project_id)); ?>" alt="">
                <div class="project-card__info">
                    <h3 class="project-card__title"><?php echo esc_html($proj); ?><?php if ($p_round) : ?> (раунд <span class="num"><?php echo $p_round; ?></span>)<?php endif; ?></h3>
                    <p class="project-card__org"><?php echo esc_html(cininvest_field('org_name', $op->project_id, '')); ?>
                        <button class="project-card__copy" type="button" data-copy="<?php echo esc_attr(get_permalink($op->project_id)); ?>" aria-label="Скопировать ссылку"><svg width="14" height="16" viewBox="0 0 14 16" fill="none"><rect x="3.8" y="0.5" width="9.7" height="11.2" rx="1.3" stroke="currentColor"/><rect x="0.5" y="3.8" width="9.7" height="11.2" rx="1.3" fill="#fff" stroke="currentColor"/></svg></button>
                    </p>
                </div>
                <div class="project-card__sum"><small>Запрашиваемая сумма</small><b><?php echo esc_html(cininvest_money($p_goal)); ?></b></div>
                <div class="project-card__progress">
                    <div class="project-card__progress-meta"><span>Собрано <span class="num"><?php echo $p_pct; ?></span>%</span><span class="project-card__days">Осталось <span class="num"><?php echo $p_days; ?></span> дней</span></div>
                    <div class="fund-card__bar"><span class="fund-card__bar-fill" style="width:<?php echo (int)$p_pct; ?>%"></span><span class="fund-card__bar-thumb" style="left:<?php echo (int)$p_pct; ?>%"></span></div>
                </div>
                <div class="project-card__meta"><b class="num"><?php echo (int)$op->shares; ?> долей</b><span><?php echo esc_html(cininvest_money($op->amount)); ?></span></div>
                <a class="project-card__action" href="#" data-open="sell-shares">Продать доли проекта</a>
            </article>
            <?php }
            if (!$has) echo '<p class="empty">У вас пока нет инвестиций.</p>';
            get_template_part('template-parts/project/sell-shares-modal');
            ?>
        </div>
        <div class="container tabs__panel" data-panel="history">
            <?php
            $ops = cininvest_get_operations($uid);
            if ($ops) foreach ($ops as $op) {
                $sign = in_array($op->type, ['deposit','sell','reward']) ? '+ ' : '- ';
                $titles = ['withdraw'=>'Выведение средств','deposit'=>'Пополнение счёта','sell'=>'Продажа долей','buy'=>'Покупка долей','privileges'=>'Покупка привилегий','reward'=>'Вознаграждение'];
                ?>
                <article class="operation">
                    <span class="operation__icon operation__icon--<?php echo esc_attr($op->type); ?>"></span>
                    <div class="operation__body"><h4 class="operation__title"><?php echo esc_html($titles[$op->type] ?? 'Операция'); ?></h4><time class="operation__date"><?php echo esc_html(date_i18n('d.m.Y, H:i', strtotime($op->created_at))); ?></time></div>
                    <div class="operation__side"><span class="operation__amount"><?php echo esc_html($sign . cininvest_money($op->amount)); ?></span><?php if ($op->shares) : ?><span class="operation__shares">Долей: <span class="num"><?php echo (int)$op->shares; ?></span></span><?php endif; ?></div>
                    <a class="operation__more" href="#" data-open="op-<?php echo esc_attr($op->id); ?>">Подробнее…</a>
                </article>
                <?php }
            else echo '<p class="empty">История операций пуста.</p>';
            // модалки операций
            foreach ($ops as $op) get_template_part('template-parts/operation-modal', null, ['op' => $op]);
            ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
