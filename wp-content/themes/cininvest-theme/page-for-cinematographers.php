<?php /* Template Name: Для кинематографистов */ get_header(); ?>
<main class="home">
    <?php
    get_template_part('template-parts/home/lp-hero', null, ['type' => 'cinema']);
    get_template_part('template-parts/home/path', null, ['type' => 'project']);
    ?>
</main>
<?php get_footer(); ?>
