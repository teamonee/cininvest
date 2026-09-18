<?php $d = $args['data'] ?? []; ?>
<fieldset class="app-section" data-section="financing">
    <legend class="app-section__title">Источники финансирования производства</legend>
    <?php foreach (['budget'=>'Сметная стоимость производства*','own_funds'=>'Собственные средства*','requested'=>'Запрашиваемая сумма*','spent'=>'Уже потрачено на проект*'] as $key => $label) : ?>
    <label class="field"><span class="field__label"><?php echo esc_html($label); ?></span><input class="field__input" name="<?php echo esc_attr($key); ?>" placeholder="Текст" value="<?php echo cininvest_repop($d, $key); ?>"></label>
    <?php endforeach; ?>
    <div class="field"><span class="field__label">Смета*</span><label class="dropzone"><input type="file" name="estimate" hidden><span>Перенесите файл с устройства или нажмите на данное поле для добавления файла<br><small>Расширение файла: Excel, Word</small></span></label></div>
</fieldset>
