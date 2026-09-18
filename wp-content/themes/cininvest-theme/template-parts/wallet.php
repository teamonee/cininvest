<section class="wallet" id="wallet">
    <div class="container wallet__inner">
        <dl class="wallet__rows">
            <?php foreach ($args['rows'] as $row) : ?>
                <div class="wallet__row">
                    <dt class="wallet__label"><?php echo esc_html($row['label']); ?></dt>
                    <dd class="wallet__value"><?php echo esc_html($row['value']); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <div class="wallet__free">
            <span class="wallet__label">Свободные средства</span>
            <span class="wallet__value"><?php echo esc_html($args['free']); ?></span>
        </div>
        <?php
        $wallet_uid = get_current_user_id();
        $wallet_error = get_transient('cininvest_wallet_error_' . $wallet_uid);
        if ($wallet_error) delete_transient('cininvest_wallet_error_' . $wallet_uid);
        ?>
        <?php if ($wallet_error) : ?>
        <p style="color:var(--c-primary);text-align:center"><?php echo esc_html($wallet_error); ?></p>
        <?php endif; ?>
        <form class="wallet__actions" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cininvest_wallet">
            <?php wp_nonce_field('cininvest_wallet', 'cininvest_wallet_nonce'); ?>
            <input type="hidden" name="cininvest_token" value="<?php echo esc_attr(cininvest_form_token()); ?>">
            <label class="field"><span class="field__label">Сумма</span><input class="field__input" type="number" name="amount" min="1" placeholder="Введите сумму" required></label>
            <button class="btn btn--primary" type="submit" name="wallet_action" value="deposit">Пополнить баланс</button>
            <button class="btn btn--grey" type="submit" name="wallet_action" value="withdraw">Вывести средства</button>
        </form>
    </div>
</section>
