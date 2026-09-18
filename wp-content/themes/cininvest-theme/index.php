<?php get_header(); ?>
<main class="container" style="padding-block:40px">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div><?php the_content(); ?></div>
        </article>
    <?php endwhile; else : ?>
        <p>Записи не найдены.</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
