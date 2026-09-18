<?php
/* Модалка «Продажа долей» (профиль инвестора, вкладка «Мои инвестиции»).
   URL Telegram-чата модератора настраивается фильтром / константой. */
$tg = defined('CININVEST_MODERATOR_TG') ? CININVEST_MODERATOR_TG : 'https://t.me/cininvest_moderator';
$tg = apply_filters('cininvest_moderator_telegram', $tg);
?>
<div class="modal" id="sell-shares" hidden>
    <div class="modal__overlay" data-close></div>
    <div class="modal__box sell-modal">
        <button class="modal__close" type="button" data-close aria-label="Закрыть"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        <h2 class="sell-modal__title">Продажа долей</h2>
        <p class="sell-modal__text">Для оформления сделки по продаже вашей доли обратитесь к модератору в специальном Telegram-чате.</p>
        <a class="btn btn--primary sell-modal__btn" href="<?php echo esc_url($tg); ?>" target="_blank" rel="noopener">Перейти в Telegram-чат с модератором
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</div>
