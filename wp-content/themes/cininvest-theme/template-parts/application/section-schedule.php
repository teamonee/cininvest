<?php
$d = $args['data'] ?? [];
$periods_data = (!empty($d['periods']) && is_array($d['periods'])) ? $d['periods'] : [];
?>
<fieldset class="app-section app-section--accent" data-section="schedule">
    <legend class="app-section__title">Календарный план</legend>
    <p class="app-section__sub">Укажите, на каком этапе реализации проекта вы сейчас находитесь, и предоставьте информацию о последующих этапах</p>
    <?php $periods = ['prep'=>'Подготовительный период','shoot'=>'Съемочный/производственный период','post'=>'Монтажно-тонировочный период','release'=>'Прокат'];
    $i = 0; foreach ($periods as $key => $label) : $pd = $periods_data[$i] ?? []; ?>
    <div class="period">
        <label class="checkbox period__toggle"><input type="checkbox" name="periods[<?php echo $i; ?>][active]" <?php checked(!empty($pd['active'])); ?>><span><?php echo esc_html($label); ?></span><input type="hidden" name="periods[<?php echo $i; ?>][name]" value="<?php echo esc_attr($label); ?>"></label>
        <div class="period__dates">
            <label class="field"><span class="field__label">Дата начала</span><input class="field__input" name="periods[<?php echo $i; ?>][start]" placeholder="01.01.2026" value="<?php echo esc_attr($pd['start'] ?? ''); ?>"></label>
            <label class="field"><span class="field__label">Дата окончания</span><input class="field__input" name="periods[<?php echo $i; ?>][end]" placeholder="01.01.2026" value="<?php echo esc_attr($pd['end'] ?? ''); ?>"></label>
        </div>
    </div>
    <?php $i++; endforeach; ?>
    <label class="field"><span class="field__label">Общий срок производства фильма/сериала (в месяцах)*</span><input class="field__input" name="total_duration" placeholder="Текст" value="<?php echo cininvest_repop($d, 'total_duration'); ?>"></label>
</fieldset>
