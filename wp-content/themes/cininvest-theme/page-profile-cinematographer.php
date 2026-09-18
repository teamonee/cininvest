<?php /* Template Name: Профиль — Кинематографист */
cininvest_require_account_role('cinematographer');
get_header();
$uid = get_current_user_id(); $u = wp_get_current_user();
?>
<main class="profile profile--cinema">
    <?php get_template_part('template-parts/profile-hero', null, [
        'name' => esc_html($u->display_name) . ',',
        'role' => 'Вы зарегистрированы как КИНЕМАТОГРАФИСТ',
        'lead' => 'Теперь вы можете подать свой проект и найти спонсоров и инвесторов для его реализации',
    ]); ?>
    <section class="tabs" data-tabs>
        <nav class="container tabs__nav tabs__nav--scroll" role="tablist">
            <button class="tabs__link tabs__link--active" data-tab="profile" role="tab">Мой профиль</button>
            <button class="tabs__link" data-tab="apps" role="tab">Мои заявки</button>
            <button class="tabs__link" data-tab="projects" role="tab">Мои проекты</button>
            <button class="tabs__link" data-tab="stats" role="tab">Статистика</button>
        </nav>
        <div class="container tabs__panel is-active" data-panel="profile">
            <h2 class="tabs__title">Мои данные <button class="icon-edit" type="button" data-profile-edit aria-label="Редактировать"></button></h2>
            <?php
            if ($profile_msg = get_transient('cininvest_profile_msg_' . $uid)) { delete_transient('cininvest_profile_msg_' . $uid); echo '<p class="profile-form__msg">' . esc_html($profile_msg) . '</p>'; }
            $c_edu   = get_user_meta($uid, 'cininvest_education', true);
            $c_orgs  = (array) get_user_meta($uid, 'cininvest_edu_org', true) ?: [''];
            $c_exp   = (array) get_user_meta($uid, 'cininvest_experience', true) ?: [''];
            $c_soc   = (array) get_user_meta($uid, 'cininvest_social', true) ?: [''];
            ?>
            <form class="profile-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" data-profile-form>
                <input type="hidden" name="action" value="cininvest_profile_save">
                <?php wp_nonce_field('cininvest_profile_save', 'cininvest_profile_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <div class="photo-picker" data-photo-picker><span>Выбрать фото</span><input type="file" name="avatar" accept="image/*" hidden></div>
                <label class="field"><span class="field__label">ФИО</span><input class="field__input" name="fio" value="<?php echo esc_attr($u->display_name); ?>" readonly></label>
                <label class="field"><span class="field__label">Номер телефона</span><input class="field__input" name="phone" value="<?php echo esc_attr(get_user_meta($uid,'cininvest_phone',true)); ?>" readonly></label>
                <label class="field"><span class="field__label">Email</span><input class="field__input" type="email" name="email" value="<?php echo esc_attr($u->user_email); ?>" readonly></label>
                <label class="field"><span class="field__label">Пароль</span><input class="field__input" type="password" name="password" placeholder="Оставьте пустым, чтобы не менять" readonly></label>
                <label class="field field--full"><span class="field__label">Образование</span><select class="field__input" name="education" disabled>
                    <option value="">Выберите вид</option>
                    <?php foreach (['Высшее','Неоконченное высшее','Среднее профессиональное','Среднее общее','Учёная степень'] as $o) : ?>
                    <option<?php selected($c_edu, $o); ?>><?php echo esc_html($o); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <?php foreach ($c_orgs as $c_v) : ?>
                <label class="field field--full"><span class="field__label">Образовательные организации и специальности</span><input class="field__input" name="edu_org[]" placeholder="Текст" value="<?php echo esc_attr($c_v); ?>" readonly></label>
                <?php endforeach; ?>
                <button class="add-more" type="button" data-clone>Добавить ещё</button>
                <?php foreach ($c_exp as $c_v) : ?>
                <label class="field field--full"><span class="field__label">Опыт работы</span><input class="field__input" name="experience[]" placeholder="Текст" value="<?php echo esc_attr($c_v); ?>" readonly></label>
                <?php endforeach; ?>
                <button class="add-more" type="button" data-clone>Добавить ещё</button>
                <?php foreach ($c_soc as $c_v) : ?>
                <label class="field field--full"><span class="field__label">Социальные сети</span><input class="field__input" name="social[]" placeholder="Текст" value="<?php echo esc_attr($c_v); ?>" readonly></label>
                <?php endforeach; ?>
                <button class="add-more" type="button" data-clone>Добавить ещё</button>
                <label class="field field--full"><span class="field__label">О себе</span><input class="field__input" name="about" value="<?php echo esc_attr(get_user_meta($uid,'cininvest_about',true)); ?>" placeholder="Текст" readonly></label>
                <label class="field field--full"><span class="field__label">Я на Кинопоиске</span><input class="field__input" name="kinopoisk" value="<?php echo esc_attr(get_user_meta($uid,'cininvest_kinopoisk',true)); ?>" placeholder="Ссылка" readonly></label>
            </form>
        </div>
        <div class="container tabs__panel" data-panel="apps">
            <a class="new-app" href="<?php echo esc_url(home_url('/application/')); ?>">Подать заявку <span>+</span></a>
            <?php
            /* Только у неопубликованного черновика есть ribbon-плашка на постере
               (application__status без модификатора = красный) + подпись-заглушка.
               У остальных статусов — только текстовая подпись .application__note
               над заголовком. Соответствие меняй под свои реальные термины
               application_status. */
            $apps = new WP_Query(['post_type' => 'application', 'author' => $uid, 'posts_per_page' => -1]);
            if ($apps->have_posts()) : while ($apps->have_posts()) : $apps->the_post(); $aid = get_the_ID();
                $st = wp_get_object_terms($aid, 'application_status');
                $st_slug = $st ? $st[0]->slug : 'draft'; $st_name = $st ? $st[0]->name : 'Заявка подана';
                $is_draft = $st_slug === 'draft';
                $is_rejected = $st_slug === 'rejected';
                // Заявку одобрили → плагин cininvest-admin создаёт из неё project и кладёт
                // сюда его ID. Пока админ не опубликует черновик проекта — ссылку давать
                // некуда (смотреть там нечего), поэтому просто показываем статус текстом.
                $created_project_id = (int) get_post_meta($aid, '_cininvest_created_project', true);
                $project_published = $created_project_id && get_post_status($created_project_id) === 'publish';
                ?>
            <article class="application">
                <div class="application__media">
                    <img src="<?php echo esc_url(cininvest_poster()); ?>" alt="">
                    <span class="application__status application__status--<?php echo esc_attr($st_slug); ?>"><?php echo $is_draft ? 'Заявка не опубликована' : esc_html($st_name); ?></span>
                    <span class="application__ava"></span>
                </div>
                <p class="application__note"><?php echo $is_draft ? 'Черновая заявка «CININVEST»' : esc_html($st_name); ?></p>
                <h3 class="application__title"><?php the_title(); ?></h3>
                <p class="application__text"><?php echo esc_html(wp_trim_words(get_post_meta($aid,'logline',true), 18)); ?></p>
                <div class="application__foot">
                    <div class="application__sum"><small>Необходимо собрать</small><b><?php echo esc_html(cininvest_money(get_post_meta($aid,'requested',true))); ?></b></div>
                    <?php if ($is_draft) : ?>
                    <a class="application__action" href="<?php echo esc_url(home_url('/application/?edit=' . $aid)); ?>">Продолжить заполнение →</a>
                    <?php elseif ($is_rejected) : ?>
                    <a class="application__action application__action--red" href="#">Узнать причину отказа →</a>
                    <?php elseif ($project_published) : ?>
                    <a class="application__action" href="<?php echo esc_url(get_permalink($created_project_id)); ?>">Открыть проект →</a>
                    <?php elseif ($created_project_id) : ?>
                    <span class="application__action" style="color:#888;cursor:default">Проект одобрен, готовится к публикации</span>
                    <?php else : ?>
                    <span class="application__action" style="color:#888;cursor:default">Заявка на рассмотрении</span>
                    <?php endif; ?>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); else : ?>
                <p class="empty">У вас пока нет заявок.</p>
            <?php endif; ?>
        </div>
        <div class="container tabs__panel" data-panel="projects">
            <?php
            $projs = new WP_Query(['post_type' => 'project', 'author' => $uid, 'posts_per_page' => -1]);
            if ($projs->have_posts()) : while ($projs->have_posts()) : $projs->the_post(); $pid = get_the_ID(); ?>
            <article class="application application--approved">
                <div class="application__media"><img src="<?php echo esc_url(cininvest_poster($pid)); ?>" alt=""><span class="application__ava"></span></div>
                <p class="application__badge">Одобрено «CININVEST»</p>
                <h3 class="application__title"><?php the_title(); ?></h3>
                <p class="application__text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                <div class="application__stats"><span><small>Необходимо собрать</small><b><?php echo esc_html(cininvest_money(cininvest_field('goal',$pid))); ?></b></span><span><small>Уже собрано</small><b><?php echo esc_html(cininvest_money(cininvest_field('collected',$pid))); ?></b></span></div>
                <div class="application__foot application__foot--plain"><span><?php echo (int)cininvest_field('investors_count',$pid,0); ?> инвестора</span><a href="<?php the_permalink(); ?>">Подробнее…</a></div>
            </article>
            <?php endwhile; wp_reset_postdata(); else : ?>
                <p class="empty">У вас пока нет проектов.</p>
            <?php endif; ?>
        </div>
        <div class="container tabs__panel" data-panel="stats">
            <?php
            /* ВНИМАНИЕ: номер раунда и «осталось дней» — полей для них в теме пока нет,
               подставь свои реальные meta-ключи вместо cininvest_field('round', $pid, 1)
               и заглушки $days_left. Проценты считаются из goal/collected. */
            $stats = new WP_Query(['post_type' => 'project', 'author' => $uid, 'posts_per_page' => -1]);
            if ($stats->have_posts()) : while ($stats->have_posts()) : $stats->the_post(); $pid = get_the_ID();
                $goal = (float) cininvest_field('goal', $pid, 0);
                $collected = (float) cininvest_field('collected', $pid, 0);
                $percent = $goal > 0 ? min(100, round($collected / $goal * 100)) : 0;
                $round = (int) cininvest_field('round', $pid, 1);
                $days_left = (int) cininvest_field('days_left', $pid, 0);
                ?>
            <article class="round-card">
                <div class="round-card__head">
                    <img class="round-card__poster" src="<?php echo esc_url(cininvest_poster($pid)); ?>" alt="">
                    <div>
                        <h3 class="round-card__title"><?php the_title(); ?> (раунд <span class="num"><?php echo esc_html($round); ?></span>)</h3>
                        <p class="round-card__org"><?php echo esc_html(get_post_meta($pid, 'org_name', true)); ?></p>
                    </div>
                </div>
                <div class="round-card__body">
                    <div class="round-card__progress-meta"><span>Собрано <?php echo esc_html($percent); ?>%</span><span>Осталось <?php echo esc_html($days_left); ?> дней</span></div>
                    <div class="progress"><span class="progress__bar" style="width:<?php echo esc_attr($percent); ?>%"></span></div>
                </div>
                <div class="round-card__amounts">
                    <div class="round-card__amount"><small>Уже собрано</small><b><?php echo esc_html(cininvest_money($collected)); ?></b></div>
                    <div class="round-card__amount round-card__amount--goal"><small>Запрашиваемая сумма</small><b><?php echo esc_html(cininvest_money($goal)); ?></b></div>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); else : ?>
                <p class="empty">Статистика будет здесь.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
