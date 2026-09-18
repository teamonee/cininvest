<?php
/* Главная (лендинг) */
get_header();
?>
<main class="home">
    <?php
    get_template_part('template-parts/home/hero');
    get_template_part('template-parts/home/directions');
    get_template_part('template-parts/home/projects-preview');
    // Отзывы отключены до появления формы добавления и модерации (template-parts/home/reviews.php).
    // get_template_part('template-parts/home/reviews');
    ?>
</main>
<?php get_footer(); ?>
