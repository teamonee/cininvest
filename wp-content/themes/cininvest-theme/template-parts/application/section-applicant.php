<?php $d = $args['data'] ?? []; ?>
<fieldset class="app-section" data-section="applicant">
    <legend class="app-section__title">Заявитель</legend>
    <label class="field"><span class="field__label">Полное наименование Заявителя*</span><input class="field__input" name="org_name" placeholder="Текст" value="<?php echo cininvest_repop($d, 'org_name'); ?>"></label>
    <div class="field"><span class="field__label">Руководитель организации*</span><div class="field-row3"><input class="field__input" name="ceo_last" placeholder="Фамилия" value="<?php echo cininvest_repop($d, 'ceo_last'); ?>"><input class="field__input" name="ceo_first" placeholder="Имя" value="<?php echo cininvest_repop($d, 'ceo_first'); ?>"><input class="field__input" name="ceo_middle" placeholder="Отчество" value="<?php echo cininvest_repop($d, 'ceo_middle'); ?>"></div></div>
    <label class="field"><span class="field__label">Должность*</span><input class="field__input" name="position" placeholder="Текст" value="<?php echo cininvest_repop($d, 'position'); ?>"></label>
    <label class="field"><span class="field__label">Контактные данные*</span><input class="field__input" name="applicant_phone" type="tel" placeholder="+7 (800) 555-35-55" value="<?php echo cininvest_repop($d, 'applicant_phone'); ?>"></label>
</fieldset>
