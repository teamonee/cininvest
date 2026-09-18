<section class="projects-preview container">
    <span class="projects-preview__glow projects-preview__glow--1" aria-hidden="true"></span>
    <span class="projects-preview__glow projects-preview__glow--2" aria-hidden="true"></span>
    <span class="projects-preview__glow projects-preview__glow--3" aria-hidden="true"></span>
    <span class="projects-preview__glow projects-preview__glow--4" aria-hidden="true"></span>
    <span class="projects-preview__glow projects-preview__glow--5" aria-hidden="true"></span>
    <h2 class="section-title">Проекты</h2>
    <div class="projects-preview__scroll">
        <?php
        $q = new WP_Query(['post_type' => 'project', 'posts_per_page' => 6, 'no_found_rows' => true]);
        if ($q->have_posts()) : while ($q->have_posts()) : $q->the_post(); $pid = get_the_ID(); ?>
        <article class="showcase-card">
            <a class="showcase-card__media" href="<?php the_permalink(); ?>">
                <img src="<?php echo esc_url(cininvest_poster($pid)); ?>" alt="<?php the_title_attribute(); ?>">
                <span class="showcase-card__ava"></span>
            </a>
            <p class="showcase-card__badge">Одобрено «CININVEST»</p>
            <h3 class="showcase-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <p class="showcase-card__text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
            <div class="showcase-card__stats">
                <span><small>Необходимо собрать</small><b><?php echo esc_html(cininvest_money(cininvest_field('goal', $pid))); ?></b></span>
                <span><small>Уже собрано</small><b><?php echo esc_html(cininvest_money(cininvest_field('collected', $pid))); ?></b></span>
            </div>
            <div class="showcase-card__foot"><span><?php echo (int) cininvest_field('investors_count', $pid, 0); ?> инвестора</span><a href="<?php the_permalink(); ?>">Подробнее…</a></div>
        </article>
        <?php endwhile; wp_reset_postdata(); else : ?>
            <p class="empty">Проекты появятся здесь.</p>
        <?php endif; ?>
    </div>
    <a class="btn btn--primary projects-preview__more" href="<?php echo esc_url(get_post_type_archive_link('project')); ?>">Смотреть все <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
</section>
