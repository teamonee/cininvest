<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if (has_custom_logo()) the_custom_logo(); else echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/img/logo.svg') . '" alt="CININVEST">'; ?>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Основная навигация">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'main-nav__list', 'fallback_cb' => false]); ?>
        </nav>

        <div class="site-header__actions">
            <?php if (is_user_logged_in()) :
                // CTA по роли cininvest_role: cinematographer — разместить проект,
                // investor — пройти квалификацию, sponsor (и прочее) — стать инвестором.
                switch (cininvest_user_role()) {
                    case 'cinematographer':
                        $header_cta_text = 'Разместить проект';
                        $header_cta_url  = home_url('/application/');
                        break;
                    case 'investor':
                        $header_cta_text = 'Пройти квалификацию';
                        $header_cta_url  = home_url('/qualification/');
                        break;
                    default: // sponsor
                        $header_cta_text = 'Стать инвестором';
                        $header_cta_url  = home_url('/for-investors/');
                }
            ?>
<?php
                // Уведомления: колокольчик + счётчик непрочитанных рядом с аватаркой
                $header_uid    = get_current_user_id();
                $header_notifs = cininvest_get_notifications($header_uid);
                $header_unread = 0;
                foreach ($header_notifs as $header_n) { if (empty($header_n['read'])) $header_unread++; }
            ?>
                <div class="notif" data-notif>
                    <button class="notif__btn" type="button" aria-label="Уведомления" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 3a6 6 0 0 0-6 6v3.5L4.5 16h15L18 12.5V9a6 6 0 0 0-6-6zM9.5 18a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php if ($header_unread) : ?><span class="notif__badge" data-notif-badge><?php echo $header_unread > 9 ? '9+' : (int) $header_unread; ?></span><?php endif; ?>
                    </button>
                    <div class="notif__panel" hidden>
                        <p class="notif__head">Уведомления</p>
                        <?php if ($header_notifs) : ?>
                        <ul class="notif__list">
                            <?php foreach (array_slice($header_notifs, 0, 20) as $header_n) : ?>
                            <li class="notif__item<?php echo empty($header_n['read']) ? ' notif__item--unread' : ''; ?>">
                                <?php if (!empty($header_n['url'])) : ?>
                                <a href="<?php echo esc_url($header_n['url']); ?>"><?php echo esc_html($header_n['text']); ?></a>
                                <?php else : ?>
                                <span><?php echo esc_html($header_n['text']); ?></span>
                                <?php endif; ?>
                                <time class="notif__date"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($header_n['date']))); ?></time>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php else : ?>
                        <p class="notif__empty">Пока нет уведомлений</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="acctmenu" data-acctmenu>
                    <button class="site-header__account" type="button" aria-label="Меню аккаунта" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="25" cy="25" r="25" fill="#333333"/><circle cx="25" cy="19" r="7" fill="#D9D9D9"/><path d="M25 29c-11.046 0-16 6-16 12.5V50h32v-8.5c0-6.5-4.954-12.5-16-12.5z" fill="#D9D9D9"/></svg>
                    </button>
                    <div class="acctmenu__panel" hidden>
                        <?php switch (cininvest_user_role()) :
                            case 'cinematographer': ?>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/profile-cinematographer/#projects')); ?>">Мои проекты</a>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/application/')); ?>">Подать заявку</a>
                        <?php break;
                            case 'sponsor': ?>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/profile-sponsor/#fees')); ?>">Мои вознаграждения</a>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/profile-sponsor/#wallet')); ?>">Кошелёк</a>
                        <?php break;
                            default: // investor ?>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/profile-investor/#invest')); ?>">Мои инвестиции</a>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/profile-investor/#wallet')); ?>">Кошелёк</a>
                        <?php endswitch; ?>
                        <a class="acctmenu__link" href="<?php echo esc_url(home_url('/settings/')); ?>">Настройки</a>
                        <a class="acctmenu__link acctmenu__item--logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/icons/exit.svg'); ?>" alt="" width="18" height="18">Выход
                        </a>
                    </div>
                </div>
                <a class="btn btn--primary site-header__cta" href="<?php echo esc_url($header_cta_url); ?>"><?php echo esc_html($header_cta_text); ?></a>
            <?php else : ?>
                <a class="site-header__auth" href="<?php echo esc_url(home_url('/login/')); ?>">Вход</a>
                <a class="btn btn--primary site-header__cta" href="<?php echo esc_url(home_url('/register/')); ?>">Регистрация</a>
            <?php endif; ?>
            <button class="burger" type="button" aria-label="Меню" aria-controls="main-nav" aria-expanded="false"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>
