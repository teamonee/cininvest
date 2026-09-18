<?php /* Template Name: Для инвесторов */ get_header(); ?>
<main class="home">
    <?php
    get_template_part('template-parts/home/lp-hero', null, ['type' => 'investor']);
    get_template_part('template-parts/home/coauthor');
    get_template_part('template-parts/home/path', null, ['type' => 'investor']);
    ?>
</main>
<?php get_footer(); ?>
