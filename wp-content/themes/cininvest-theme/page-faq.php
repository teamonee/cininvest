<?php /* Template Name: FAQ */ get_header(); ?>
<main class="page-faq container">
    <header class="page-head"><h1 class="page-head__title">FAQ</h1><p class="page-head__text">Приходите к нам с идеей или готовым проектом. Вера в идею и готовность вложить в неё время и силы — основные критерии выбора кандидатов.</p></header>
    <section class="faq">
        <h2 class="faq__group">О платформе CININVEST</h2>
        <?php
        $faq = [
            'Что такое CININVEST?' => 'Ответ на вопрос.',
            'Что такое краудфандинг и краудинвестинг в кино?' => 'Ответ на вопрос.',
            'Чем ваша платформа отличается от других краудфандинговых сайтов?' => 'Ответ на вопрос.',
            'Как регулируется деятельность платформы?' => 'Ответ на вопрос.',
            'По какой модели работает платформа?' => 'Ответ на вопрос.',
            'Какие риски при инвестировании в кино?' => 'Ответ на вопрос.',
            'Какой проект я могу разместить на платформе?' => 'Ответ на вопрос.',
        ];
        foreach ($faq as $q => $a) : ?>
        <details class="faq__item">
            <summary class="faq__q"><span><?php echo esc_html($q); ?></span><svg class="faq__chevron" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M8 8h8v8M16 8L8 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
            <div class="faq__a"><?php echo esc_html($a); ?></div>
        </details>
        <?php endforeach; ?>
    </section>
    <section class="faq-cta"><h2 class="section-title">Остались вопросы<span class="num">?</span></h2><p class="faq-cta__text">Свяжитесь с нашей службой поддержки по адресу support@test.ru или напишите нам в чате. Мы всегда рады помочь вам стать частью большого кино!</p><a class="btn btn--dark faq-cta__btn" href="mailto:support@test.ru">Написать в чат <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></section>
</main>
<?php get_footer(); ?>
