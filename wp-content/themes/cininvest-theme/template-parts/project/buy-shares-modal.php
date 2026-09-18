<?php
$pid = $args['project_id'] ?? get_the_ID();
$buy_error = $args['buy_error'] ?? '';
$buy_ok = $args['buy_ok'] ?? '';
$topup_url = cininvest_account_url_for_role(cininvest_user_role());
?>
<div class="modal" id="buy-shares" <?php echo ($buy_error || $buy_ok) ? '' : 'hidden'; ?>>
    <?php if ($buy_error || $buy_ok) : ?>
    <script>document.body.style.overflow = 'hidden';</script>
    <?php endif; ?>
    <div class="modal__overlay" data-close></div>
    <div class="modal__box modal__box--wide">
        <button class="modal__back" type="button" data-close aria-label="Назад"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-buy-form data-project="<?php echo (int)$pid; ?>">
            <input type="hidden" name="action" value="cininvest_buy_shares">
            <input type="hidden" name="project_id" value="<?php echo (int)$pid; ?>">
            <?php wp_nonce_field('cininvest_buy_shares', 'cininvest_buy_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">

            <h2 class="buy__title">Вы покупаете доли проекта <?php the_title(); ?></h2>
            <p class="buy__org">(<?php echo esc_html(cininvest_field('org_name', $pid, '')); ?>)</p>
            <?php if ($buy_error) : ?>
            <p style="color:var(--c-primary);text-align:center"><?php echo esc_html($buy_error); ?></p>
            <?php elseif ($buy_ok) : ?>
            <p style="color:#1E8A2E;text-align:center"><?php echo esc_html($buy_ok); ?></p>
            <?php endif; ?>

            <div class="buy__avail">
                <b class="buy__avail-num">Доступно <?php echo esc_html(cininvest_field('shares_available', $pid, 0)); ?> долей</b>
                <span class="buy__avail-min">Минимум: 1 шт · Одна доля: <?php echo esc_html(cininvest_money(cininvest_field('share_price', $pid, 5000))); ?></span>
                <p class="buy__avail-text">Напишите, сколько долей вы хотите купить, и мы посчитаем точную сумму вложений.</p>
                <div class="buy__balance">
                    <div><b><?php echo esc_html(cininvest_money(cininvest_get_balance())); ?></b><span><?php echo cininvest_get_balance() > 0 ? 'Доступно к использованию' : 'Средств недостаточно'; ?></span></div>
                    <a class="buy__topup" href="<?php echo esc_url($topup_url); ?>">Пополнить</a>
                </div>
            </div>

            <label class="field"><span class="field__label">Сумма инвестирования</span><input class="field__input field__input--accent" name="amount_display" data-calc-amount></label>
            <label class="field"><span class="field__label">Количество долей</span><input class="field__input field__input--accent" name="shares" data-calc-shares value="1"></label>

            <div class="buy__stats">
                <div class="buy__stat"><span class="buy__stat-label">Количество долей</span><span class="buy__stat-val" data-out="shares">1</span></div>
                <div class="buy__stat"><span class="buy__stat-label">Цена за долю</span><span class="buy__stat-val" data-out="share_price">—</span></div>
                <div class="buy__stat"><span class="buy__stat-label">Стоимость долей</span><span class="buy__stat-val" data-out="base">—</span></div>
                <div class="buy__stat"><span class="buy__stat-label">Комиссия <?php echo esc_html(cininvest_field('commission',$pid,4)); ?>% <a href="#" class="buy__more">Подробнее</a></span><span class="buy__stat-val" data-out="commission">—</span></div>
            </div>

            <button class="btn buy__total" type="submit">Купить <span data-out="total">—</span></button>

            <label class="checkbox buy__agree"><input type="checkbox" name="agree_limit" required><span>Я не превысил <a href="#">лимит инвестиций для неквалифицированного инвестора</a></span></label>
            <label class="checkbox buy__agree"><input type="checkbox" name="agree_docs" required><span>Нажимая на кнопку «Купить», я соглашаюсь со следующими документами:</span></label>
            <ol class="buy__docs">
                <li><a href="#">Договор инвестирования</a></li>
                <li><a href="#">Инвестиционное предложение #1</a></li>
                <li><a href="#">Акцепт инвестиционного предложения</a></li>
                <li><a href="#">Декларация о рисках</a></li>
            </ol>
        </form>
    </div>
</div>
