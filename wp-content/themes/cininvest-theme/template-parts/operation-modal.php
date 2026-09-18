<?php
$titles = [
    'withdraw' => 'Выведение средств', 'deposit' => 'Пополнение счёта',
    'privileges' => 'Покупка привилегий', 'reward' => 'Вознаграждение',
    'sell' => 'Продажа долей', 'buy' => 'Покупка долей',
];
$op = $args['op'] ?? null;
if (!$op) return;
$type = $op->type;
$meta = $op->meta ? json_decode($op->meta, true) : [];
?>
<div class="modal" id="op-<?php echo esc_attr($op->id); ?>" hidden>
    <div class="modal__overlay" data-close></div>
    <div class="modal__box op-modal">
        <button class="modal__close" type="button" data-close aria-label="Закрыть">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <h2 class="op-modal__title"><?php echo esc_html($titles[$type] ?? 'Операция'); ?></h2>
        <?php if (!empty($meta['org'])) : ?><p class="op-modal__org"><?php echo esc_html($meta['org']); ?></p><?php endif; ?>
        <?php if (!empty($meta['items'])) : ?>
        <ul class="op-modal__items">
            <?php foreach ($meta['items'] as $it) : ?>
            <li class="op-modal__item"><span><?php echo esc_html($it['title']); ?></span><b><?php echo esc_html(cininvest_money($it['price'])); ?></b></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <dl class="op-modal__rows">
            <?php if ($op->shares) : ?><div class="op-modal__row"><dt>Количество долей</dt><dd><?php echo (int)$op->shares; ?></dd></div><?php endif; ?>
            <?php if (!empty($meta['share_price'])) : ?><div class="op-modal__row"><dt>Стоимость одной доли</dt><dd><?php echo esc_html(cininvest_money($meta['share_price'])); ?></dd></div><?php endif; ?>
            <div class="op-modal__row"><dt>Сумма операции</dt><dd><?php echo esc_html(cininvest_money($op->amount)); ?></dd></div>
            <?php if ($op->commission) : ?><div class="op-modal__row"><dt>Комиссия</dt><dd><?php echo esc_html(cininvest_money($op->commission)); ?></dd></div><?php endif; ?>
            <div class="op-modal__row"><dt>Дата</dt><dd><?php echo esc_html(date_i18n('d.m.Y', strtotime($op->created_at))); ?></dd></div>
            <div class="op-modal__row"><dt>Время</dt><dd><?php echo esc_html(date_i18n('H:i', strtotime($op->created_at))); ?></dd></div>
        </dl>
    </div>
</div>
