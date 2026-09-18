<?php $d = $args['data'] ?? []; ?>
<fieldset class="app-section" data-section="creators">
    <legend class="app-section__title">Создатели фильма</legend>
    <?php if (!empty($args['is_ur'])) : ?><label class="field"><span class="field__label">Кинокомпания*</span><input class="field__input" name="company" placeholder="Текст" value="<?php echo cininvest_repop($d, 'company'); ?>"></label><?php endif; ?>
    <label class="field"><span class="field__label">ФИО Руководителя*</span><input class="field__input" name="head_fio" placeholder="Текст" value="<?php echo cininvest_repop($d, 'head_fio'); ?>"></label>
    <?php foreach (['producer'=>'Продюсер*','director'=>'Режиссер*','writer'=>'Авторы сценария*','operator'=>'Оператор*','artist'=>'Художник','composer'=>'Композитор'] as $key => $role) : ?>
    <div class="field creator">
        <span class="field__label"><?php echo esc_html($role); ?></span>
        <div class="creator__row">
            <label class="creator__col"><small class="field__hint">ФИО</small><input class="field__input" name="<?php echo esc_attr($key); ?>_fio" placeholder="Текст" value="<?php echo cininvest_repop($d, $key . '_fio'); ?>"></label>
            <label class="creator__col"><small class="field__hint">Ссылка на Кинопоиск</small><input class="field__input" name="<?php echo esc_attr($key); ?>_kp" placeholder="Текст" value="<?php echo cininvest_repop($d, $key . '_kp'); ?>"></label>
        </div>
    </div>
    <?php endforeach; ?>
</fieldset>
