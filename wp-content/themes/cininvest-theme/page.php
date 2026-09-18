<?php
/* page.php — используется для любой WP-страницы, которой не назначен специальный шаблон */
get_header();
?>
<main class="container" style="padding-block:40px">
    <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class(); ?>>
        <h1><?php the_title(); ?></h1>
        <div><?php the_content(); ?></div>
        <?php if (!trim((string) get_the_content())) : ?>
        <p class="empty">У этой страницы пока нет содержимого. Добавьте текст в редакторе или назначьте один из готовых шаблонов в блоке «Атрибуты страницы» → «Шаблон».</p>
        <?php endif; ?>
    </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
