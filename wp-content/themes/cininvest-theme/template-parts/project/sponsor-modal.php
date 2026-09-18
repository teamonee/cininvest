<?php
$pid = $args['project_id'] ?? get_the_ID();
$perks_error = $args['perks_error'] ?? '';
$perks_ok = $args['perks_ok'] ?? '';
?>
<div class="modal" id="sponsor-modal" hidden>
    <div class="modal__overlay" data-close></div>
    <div class="modal__box modal__box--wide">
        <button class="modal__back" type="button" data-close aria-label="Назад"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>

        <h2 class="buy__title">Покупка привилегий</h2>
        <p class="buy__org"><?php the_title(); ?></p>
        <?php if ($perks_error) : ?>
        <p style="color:var(--c-primary);text-align:center"><?php echo esc_html($perks_error); ?></p>
        <?php elseif ($perks_ok) : ?>
        <p style="color:#1E8A2E;text-align:center"><?php echo esc_html($perks_ok); ?></p>
        <?php endif; ?>

        <form class="reward-block" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cininvest_buy_perks">
            <input type="hidden" name="project_id" value="<?php echo (int) $pid; ?>">
            <?php wp_nonce_field('cininvest_buy_perks', 'cininvest_buy_perks_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
            <div class="reward-block__head"><span class="reward-block__num">01</span><div><h3 class="reward-block__title">Поддержать на любую сумму</h3><p class="reward-block__sub">Спасибо, мне не нужно вознаграждение, я просто хочу поддержать проект.</p></div></div>
            <input class="field__input reward-block__input" type="number" name="total" min="1" step="1" placeholder="Введите сумму" required>
            <div class="reward-presets"><?php foreach (['200','500','750','1000'] as $p) echo '<button class="preset" type="button">' . esc_html($p) . ' ₽</button>'; ?></div>
            <button class="btn btn--primary reward-block__buy" type="submit">Оформить взнос</button>
        </form>

        <form class="reward-block" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cininvest_buy_perks">
            <input type="hidden" name="project_id" value="<?php echo (int) $pid; ?>">
            <input type="hidden" name="total" value="0" data-perks-total-input>
            <?php wp_nonce_field('cininvest_buy_perks', 'cininvest_buy_perks_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
            <div class="reward-block__head"><span class="reward-block__num">02</span><div><h3 class="reward-block__title">Вознаграждения</h3><p class="reward-block__sub">Выберите желаемое/ые вознаграждение/я.</p></div></div>
            <ul class="reward-options">
            <?php foreach (cininvest_project_rewards($pid) as $r) : ?>
                <li class="reward-option"><label class="checkbox"><input type="checkbox" name="perks[]" value="<?php echo esc_attr($r['title']); ?>" data-perk-price="<?php echo esc_attr($r['price']); ?>"><span><?php echo esc_html($r['title']); ?></span></label><b class="reward-option__price"><?php echo esc_html(cininvest_money($r['price'])); ?></b></li>
            <?php endforeach; ?>
            </ul>
            <b class="reward-block__total" data-rewards-total>Итого: 0 ₽</b>
            <button class="btn btn--primary reward-block__buy" type="submit">Оформить взнос</button>
        </form>
    </div>
</div>
