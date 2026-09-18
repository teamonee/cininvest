<?php
get_header();

$status = (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'completed' : 'active';

$base_query = ['post_type' => 'project', 'posts_per_page' => 9, 'paged' => max(1, get_query_var('paged'))];
if ($status === 'completed') {
    $base_query['meta_key'] = 'project_status';
    $base_query['meta_value'] = 'completed';
} else {
    $base_query['meta_query'] = [['key' => 'project_status', 'value' => 'completed', 'compare' => '!=']];
}
$projects = new WP_Query($base_query);

$count_completed = (new WP_Query(['post_type' => 'project', 'posts_per_page' => -1, 'meta_key' => 'project_status', 'meta_value' => 'completed']))->found_posts;
?>
<main class="showcase container">
    <header class="page-head">
        <span class="page-head__badge">20 000 инвесторов на платформе</span>
        <h1 class="page-head__title">Витрина проектов</h1>
        <p class="page-head__text">Дай старт перспективному проекту. Стань продюсером фильма</p>
    </header>
    <div class="showcase__filter" role="tablist">
        <a class="filter-pill <?php echo $status === 'active' ? 'filter-pill--active' : ''; ?>" href="<?php echo esc_url(remove_query_arg('status')); ?>" role="tab">В работе</a>
        <a class="filter-pill <?php echo $status === 'completed' ? 'filter-pill--active' : ''; ?>" href="<?php echo esc_url(add_query_arg('status', 'completed')); ?>" role="tab">Завершённые <span class="filter-pill__count"><?php echo (int) $count_completed; ?></span></a>
    </div>
    <div class="showcase__grid">
        <?php if ($projects->have_posts()) : while ($projects->have_posts()) : $projects->the_post(); $pid = get_the_ID(); ?>
        <article class="showcase-card">
            <a class="showcase-card__media" href="<?php the_permalink(); ?>"><img src="<?php echo esc_url(cininvest_poster($pid)); ?>" alt="<?php the_title_attribute(); ?>"><span class="showcase-card__ava"></span></a>
            <p class="showcase-card__badge">Одобрено «CININVEST»</p>
            <h3 class="showcase-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <p class="showcase-card__text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
            <div class="showcase-card__stats">
                <span><small>Необходимо собрать</small><b><?php echo esc_html(cininvest_money(cininvest_field('goal', $pid))); ?></b></span>
                <span><small>Уже собрано</small><b><?php echo esc_html(cininvest_money(cininvest_field('collected', $pid))); ?></b></span>
            </div>
            <div class="showcase-card__foot"><span><?php echo (int) cininvest_field('investors_count', $pid, 0); ?> инвестора</span><a href="<?php the_permalink(); ?>">Подробнее…</a></div>
        </article>
        <?php endwhile;
        echo '<nav class="pagination" aria-label="Страницы">' . paginate_links(['total' => $projects->max_num_pages, 'type' => 'list', 'prev_text' => '', 'next_text' => '›']) . '</nav>';
        wp_reset_postdata();
        else : ?>
            <p class="empty">Проекты появятся здесь.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
