<?php /* Template Name: Верификация завершена */
if (is_user_logged_in()) cininvest_complete_verification(get_current_user_id());
get_header(); ?>
<main class="verify"><section class="verify-step is-active container"><div class="verify-done">
    <h1 class="verify-done__title">Верификация завершена!</h1>
    <p class="verify-done__text">Проверка данных пройдена успешно. Теперь вы можете инвестировать в кинопроекты</p>
    <a class="btn btn--primary verify-done__btn" href="<?php echo esc_url(get_post_type_archive_link('project')); ?>">Перейти к проектам <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
</div></section></main>
<?php get_footer(); ?>
