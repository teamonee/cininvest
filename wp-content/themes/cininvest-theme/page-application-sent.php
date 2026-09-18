<?php /* Template Name: Заявка отправлена */ get_header(); ?>
<main class="application-flow">
    <section class="app-step is-active"><div class="container app-done">
        <h1 class="app-done__title">Заявка отправлена!</h1>
        <p class="app-done__text">Ожидайте ответа</p>
        <a class="btn btn--primary app-done__btn" href="<?php echo esc_url(home_url('/profile-cinematographer/')); ?>">Перейти в мой профиль <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
    </div></section>
</main>
<?php get_footer(); ?>
