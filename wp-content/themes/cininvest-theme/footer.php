<footer class="site-footer">
    <div class="container site-footer__grid">
        <div class="site-footer__col">
            <a class="site-footer__logo" href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-white.svg'); ?>" alt="CININVEST">
            </a>
        </div>
        <nav class="site-footer__col" aria-label="Навигация подвала">
            <h3 class="site-footer__title">Навигация</h3>
            <p class="site-footer__nav-head"><a href="<?php echo esc_url(home_url('/')); ?>">Наша платформа</a></p>
            <ul class="site-footer__list">
                <li><a href="<?php echo esc_url(home_url('/for-cinematographers/')); ?>">Для кинематографистов</a></li>
                <li><a href="<?php echo esc_url(home_url('/for-investors/')); ?>">Для инвесторов</a></li>
            </ul>
            <ul class="site-footer__list site-footer__list--plain">
                <li><a href="<?php echo esc_url(get_post_type_archive_link('project')); ?>">Проекты</a></li><li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li><li><a href="<?php echo esc_url(home_url('/docs/')); ?>">Документация</a></li>
            </ul>
        </nav>
        <div class="site-footer__col">
            <h3 class="site-footer__title">Режим работы</h3>
            <p>с 9:00 до 19:00<br>пн–пт</p>
        </div>
        <div class="site-footer__col">
            <h3 class="site-footer__title">Контактные данные</h3>
            <p>+7 (800) 555-35-55<br>+7 (800) 555-35-55</p>
            <div class="site-footer__socials">
                <a href="#" aria-label="WhatsApp"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/icons/whatsapp.svg'); ?>" alt=""></a>
                <a href="#" aria-label="Telegram"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/icons/telegram.svg'); ?>" alt=""></a>
            </div>
        </div>
    </div>
    <p class="site-footer__copy">Сайт создан в 2025 году</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
