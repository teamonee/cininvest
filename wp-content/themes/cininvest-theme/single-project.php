<?php
/* Single: проект */
get_header();
$pid = get_the_ID(); the_post();
$is_guest = !is_user_logged_in();
// Роль платформы (investor|sponsor|cinematographer) — из inc/user-profile.php, user-meta cininvest_role.
$is_sponsor_role = !$is_guest && function_exists('cininvest_user_role') && cininvest_user_role() === 'sponsor';
$collected = cininvest_field('collected', $pid, 0);
$goal = cininvest_field('goal', $pid, 0);
$progress = cininvest_progress($collected, $goal);
$perks_error = '';
$perks_ok = '';
if (!$is_guest) {
    $perks_uid = get_current_user_id();
    $perks_error = get_transient('cininvest_perks_error_' . $perks_uid);
    $perks_ok = get_transient('cininvest_perks_ok_' . $perks_uid);
    if ($perks_error) delete_transient('cininvest_perks_error_' . $perks_uid);
    if ($perks_ok) delete_transient('cininvest_perks_ok_' . $perks_uid);
}
?>
<main class="project">
    <section class="project-hero">
        <div class="project-hero__media">
            <img src="<?php echo esc_url(cininvest_poster($pid)); ?>" alt="<?php the_title_attribute(); ?>">
            <a class="project-hero__back" href="javascript:history.back()" aria-label="Назад"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            <?php $rounds = cininvest_field('rounds', $pid, []); ?>
            <?php if (!empty($rounds)) : ?>
            <div class="project-hero__rounds container">
                <?php $i = 0; foreach ($rounds as $r) { $i++; ?>
                <button class="round-tab <?php echo !empty($r['active']) ? 'round-tab--active' : ''; ?>" type="button"><?php echo esc_html($r['name'] ?: $i . ' раунд'); ?></button>
                <?php } ?>
            </div>
            <?php endif; ?>
            <div class="project-hero__caption container">
                <h1 class="project-hero__title"><?php the_title(); ?></h1>
                <p class="project-hero__desc"><?php echo esc_html(wp_trim_words(get_the_content(), 40)); ?></p>
            </div>
        </div>
    </section>
    <div class="project-body container">
        <aside class="fund-card<?php echo $is_sponsor_role ? ' fund-card--sponsor' : ''; ?>">
            <span class="fund-card__glow fund-card__glow--1"></span>
            <span class="fund-card__glow fund-card__glow--2"></span>
            <div class="fund-card__progress">
                <div class="fund-card__progress-main">
                    <span class="fund-card__collected">Проект уже собрал <?php echo esc_html(cininvest_money($collected)); ?></span>
                    <div class="fund-card__bar"><span class="fund-card__bar-fill" style="width:<?php echo (int)$progress; ?>%"></span><span class="fund-card__bar-thumb" style="left:<?php echo (int)$progress; ?>%"></span></div>
                </div>
                <div class="fund-card__goal"><small>Цель раунда</small><b><?php echo esc_html(cininvest_money($goal)); ?></b></div>
            </div>
            <div class="fund-card__meta"><div><b><?php echo esc_html(cininvest_money(cininvest_field('share_price',$pid,5000))); ?></b><span>Цена за 1 долю</span></div><div><b><?php echo esc_html(cininvest_field('min_package',$pid,'1 доля')); ?></b><span>Минимальный пакет</span></div></div>
            <?php
            // Sponsor-role members (and guests) see "Спонсировать" as the primary red CTA;
            // everyone else (default/investor accounts) sees "Инвестировать" as primary.
            $sponsor_is_primary = $is_guest || $is_sponsor_role;
            $invest_class = $sponsor_is_primary ? 'btn--grey fund-card__btn--ghost' : 'btn--primary';
            $sponsor_class = $sponsor_is_primary ? 'btn--primary' : 'btn--grey fund-card__btn--ghost';
            // гость: модалок покупки/спонсорства нет (рендерятся только для авторизованных) —
            // кнопки ведут на вход, чтобы клик не был «мёртвым»
            $login_url = esc_url(add_query_arg('redirect_to', urlencode(get_permalink($pid)), home_url('/login/')));
            $invest_ctl  = $is_guest
                ? '<a class="btn ' . $invest_class . '" href="' . $login_url . '">Инвестировать</a>'
                : '<button class="btn ' . $invest_class . '" type="button" data-open="buy-shares">Инвестировать</button>';
            $sponsor_ctl = $is_guest
                ? '<a class="btn ' . $sponsor_class . '" href="' . $login_url . '">Спонсировать</a>'
                : '<button class="btn ' . $sponsor_class . '" type="button" data-open="sponsor-modal">Спонсировать</button>';
            $invest_btn = '<div class="fund-card__action">' . $invest_ctl . '<div class="fund-card__action-meta"><span class="fund-card__action-count">' . (int)cininvest_field('investors_count',$pid,0) . ' инвесторов</span><span class="fund-card__action-text">Вы можете получать долю от прибыли кинопроекта.</span></div></div>';
            $sponsor_btn = '<div class="fund-card__action">' . $sponsor_ctl . '<div class="fund-card__action-meta"><span class="fund-card__action-count">' . (int)cininvest_field('sponsors_count',$pid,0) . ' спонсоров</span><span class="fund-card__action-text">Вы можете стать частью проекта и помочь в его реализации.</span></div></div>';
            echo $sponsor_is_primary ? $sponsor_btn . $invest_btn : $invest_btn . $sponsor_btn;
            ?>
        </aside>
        <div class="project-body__content" data-tabs>
        <nav class="tabs__nav tabs__nav--project" role="tablist">
            <button class="tabs__link tabs__link--active" data-tab="about" role="tab">О проекте</button>
            <button class="tabs__link" data-tab="team" role="tab">Команда</button>
            <button class="tabs__link" data-tab="materials" role="tab">Творческие материалы</button>
            <button class="tabs__link" data-tab="docs" role="tab">Документы</button>
            <button class="tabs__link" data-tab="rewards" role="tab">Вознаграждения</button>
        </nav>
        <section class="tabs__panel is-active" data-panel="about">
            <h2 class="about-title"><?php the_title(); ?></h2>
            <p class="about-desc"><?php echo esc_html(wp_trim_words(get_the_content(), 40)); ?></p>
            <?php
            $about = [
                'Жанр' => ['tags' => array_filter(array_map('trim', explode(',', cininvest_field('genre',$pid,''))))],
                'Хронометраж' => ['text' => cininvest_field('duration',$pid,'')],
                'Тип' => ['tags' => array_filter([cininvest_field('ptype',$pid,'')])],
                'Вид' => ['tags' => array_filter([cininvest_field('kind',$pid,'')])],
                'Логлайн' => ['text' => cininvest_field('logline',$pid,'')],
                'Оригинальная идея / Хай-концепт' => ['text' => cininvest_field('idea',$pid,'')],
                'Сеттинг' => ['text' => cininvest_field('setting',$pid,'')],
                'Аннотация' => ['text' => cininvest_field('annotation',$pid,'')],
                'Синопсис' => ['text' => wp_strip_all_tags(cininvest_field('synopsis',$pid,''))],
                'Локации' => ['text' => cininvest_field('locations',$pid,'')],
            ];
            foreach ($about as $label => $data) {
                if (empty($data['text']) && empty($data['tags'])) continue; ?>
            <div class="about-row">
                <h3 class="about-row__label"><?php echo esc_html($label); ?></h3>
                <?php if (!empty($data['tags'])) : ?><div class="about-row__tags"><?php foreach ($data['tags'] as $t) echo '<span class="tag">' . esc_html($t) . '</span>'; ?></div>
                <?php else : ?><p class="about-row__text"><?php echo esc_html($data['text']); ?></p><?php endif; ?>
            </div>
            <?php } ?>
            <?php /* Отзывы отключены до появления формы добавления и модерации — вернуть блок позже. */ ?>
        </section>
        <section class="tabs__panel" data-panel="team">
            <?php $video = cininvest_field('video_url', $pid, ''); ?>
            <h2 class="project-panel__title">Видеообращение</h2>
            <div class="video-placeholder"><?php if ($video) : ?><iframe src="<?php echo esc_url($video); ?>" width="100%" height="100%" frameborder="0" allowfullscreen title="Видеообращение"></iframe><?php else : ?><span>Видеообращение</span><?php endif; ?></div>
            <h2 class="project-panel__title">Команда</h2>
            <?php if (function_exists('have_rows') && have_rows('team', $pid)) : while (have_rows('team', $pid)) { the_row(); ?>
            <article class="member">
                <header class="member__head"><img class="member__ava" src="<?php echo esc_url(get_sub_field('photo') ?: get_template_directory_uri().'/assets/img/poster.jpg'); ?>" alt=""><div><b class="member__name"><?php echo esc_html((string) get_sub_field('name')); ?></b><span class="member__role"><?php echo esc_html((string) get_sub_field('role')); ?></span></div></header>
                <p class="member__label">Обо мне</p><p class="member__text"><?php echo wp_kses_post((string) get_sub_field('about')); ?></p>
                <?php $member_kp = get_sub_field('kinopoisk'); ?>
                <p class="member__label">Ссылка на Кинопоиск</p><a class="member__link" href="<?php echo esc_url($member_kp); ?>"><?php echo esc_html($member_kp); ?></a>
            </article>
            <?php } else : echo '<p class="empty">Команда пока не указана.</p>'; endif; ?>
        </section>
        <section class="tabs__panel" data-panel="materials">
            <?php if (function_exists('have_rows') && have_rows('materials', $pid)) :
                while (have_rows('materials', $pid)) { the_row(); $f = get_sub_field('file');
                $file_meta = '';
                if ($f) {
                    $size_ru = str_replace(['.', 'KB', 'MB', 'GB'], [',', 'КБ', 'МБ', 'ГБ'], size_format($f['filesize'], 1));
                    $date_ru = !empty($f['date']) ? mysql2date('d.m.Y', $f['date']) : (!empty($f['ID']) ? get_the_date('d.m.Y', $f['ID']) : '');
                    $file_meta = $size_ru . ($date_ru ? ', загружен ' . $date_ru : '');
                }
                ?>
            <article class="file-row"><span class="file-row__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2h9l5 5v15H6V2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M15 2v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span><div class="file-row__body"><h3><?php echo esc_html((string) get_sub_field('title')); ?></h3><p><?php echo esc_html($file_meta); ?></p></div><a class="file-row__btn" href="<?php echo esc_url($f['url'] ?? '#'); ?>" target="_blank">Посмотреть</a></article>
                <?php }
            else : ?>
                <p class="empty">Материалы пока не загружены.</p>
            <?php endif; ?>
        </section>
        <section class="tabs__panel" data-panel="docs">
            <?php if (function_exists('have_rows') && have_rows('docs', $pid)) :
                while (have_rows('docs', $pid)) { the_row(); $f = get_sub_field('file');
                $file_meta = '';
                if ($f) {
                    $size_ru = str_replace(['.', 'KB', 'MB', 'GB'], [',', 'КБ', 'МБ', 'ГБ'], size_format($f['filesize'], 1));
                    $date_ru = !empty($f['date']) ? mysql2date('d.m.Y', $f['date']) : (!empty($f['ID']) ? get_the_date('d.m.Y', $f['ID']) : '');
                    $file_meta = $size_ru . ($date_ru ? ', загружен ' . $date_ru : '');
                }
                ?>
            <article class="file-row"><span class="file-row__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 2h9l5 5v15H6V2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M15 2v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span><div class="file-row__body"><h3><?php echo esc_html((string) get_sub_field('title')); ?></h3><p><?php echo esc_html($file_meta); ?></p></div><a class="file-row__btn" href="<?php echo esc_url($f['url'] ?? '#'); ?>" target="_blank">Посмотреть</a></article>
                <?php }
            else : ?>
                <p class="empty">Документы пока не загружены.</p>
            <?php endif; ?>
        </section>
        <section class="tabs__panel" data-panel="rewards" id="rewards">
            <?php if ($perks_error) : ?>
            <p style="color:var(--c-primary);text-align:center;margin:0 0 16px"><?php echo esc_html($perks_error); ?></p>
            <?php elseif ($perks_ok) : ?>
            <p style="color:#1E8A2E;text-align:center;margin:0 0 16px"><?php echo esc_html($perks_ok); ?></p>
            <?php endif; ?>
            <form class="reward-block" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cininvest_buy_perks">
                <input type="hidden" name="project_id" value="<?php echo (int) $pid; ?>">
                <?php wp_nonce_field('cininvest_buy_perks', 'cininvest_buy_perks_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <div class="reward-block__head"><span class="reward-block__num">01</span><div><h3 class="reward-block__title">Поддержать на любую сумму</h3><p class="reward-block__sub">- Спасибо, мне не нужно вознаграждение, я просто хочу поддержать проект.</p></div></div>
                <input class="field__input reward-block__input" type="number" name="total" min="1" step="1" placeholder="Введите сумму" required>
                <div class="reward-presets"><?php foreach (['200','500','750','1000'] as $p) echo '<button class="preset" type="button">' . esc_html($p) . ' ₽</button>'; ?></div>
                <button class="btn btn--primary reward-block__buy" type="submit">Купить</button>
            </form>
            <form class="reward-block" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cininvest_buy_perks">
                <input type="hidden" name="project_id" value="<?php echo (int) $pid; ?>">
                <input type="hidden" name="total" value="0" data-perks-total-input>
                <?php wp_nonce_field('cininvest_buy_perks', 'cininvest_buy_perks_nonce'); ?>
                <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
                <div class="reward-block__head"><span class="reward-block__num">02</span><div><h3 class="reward-block__title">Вознаграждения</h3><p class="reward-block__sub">Выберите желаемое/ые вознаграждение/я.</p></div></div>
                <ul class="reward-options">
                <?php foreach (cininvest_project_rewards($pid) as $r) : ?>
                    <li class="reward-option"><label class="checkbox"><input type="checkbox" name="perks[]" value="<?php echo esc_attr($r['title']); ?>" data-perk-price="<?php echo esc_attr($r['price']); ?>"><span><?php echo esc_html($r['title']); ?></span></label><b class="reward-option__price"><?php echo esc_html(cininvest_money($r['price'])); ?></b></li>
                <?php endforeach; ?>
                </ul>
                <button class="btn btn--primary reward-block__buy" type="submit">Купить <span data-rewards-total data-rewards-amount></span></button>
            </form>
        </section>
        </div>
    </div>
    <?php
    if (!$is_guest) {
        $buy_uid = get_current_user_id();
        $buy_error = get_transient('cininvest_buy_error_' . $buy_uid);
        $buy_ok = get_transient('cininvest_buy_ok_' . $buy_uid);
        if ($buy_error) delete_transient('cininvest_buy_error_' . $buy_uid);
        if ($buy_ok) delete_transient('cininvest_buy_ok_' . $buy_uid);
        get_template_part('template-parts/project/buy-shares-modal', null, [
            'project_id' => $pid, 'buy_error' => $buy_error, 'buy_ok' => $buy_ok,
        ]);
        get_template_part('template-parts/project/sponsor-modal', null, [
            'project_id' => $pid, 'perks_error' => $perks_error, 'perks_ok' => $perks_ok,
        ]);
    }
    ?>
</main>
<?php get_footer(); ?>
