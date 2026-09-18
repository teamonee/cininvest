<?php
$d = $args['data'] ?? [];
$edu_orgs = (!empty($d['edu_org']) && is_array($d['edu_org'])) ? $d['edu_org'] : [''];
$experiences = (!empty($d['experience']) && is_array($d['experience'])) ? $d['experience'] : [''];
$socials = (!empty($d['social']) && is_array($d['social'])) ? $d['social'] : [''];
?>
<fieldset class="app-section" data-section="data">
    <legend class="app-section__title">Мои данные</legend>
    <?php $education = $d['education'] ?? ''; ?>
    <label class="field"><span class="field__label">Образование</span><select class="field__input" name="education">
        <option value="">Выберите вид образования</option>
        <?php foreach (['Высшее','Неоконченное высшее','Среднее профессиональное','Среднее общее','Учёная степень'] as $o) : ?>
        <option<?php selected($education, $o); ?>><?php echo esc_html($o); ?></option>
        <?php endforeach; ?>
    </select></label>
    <?php foreach ($edu_orgs as $v) : ?>
    <label class="field"><span class="field__label">Образовательные организации и специальности</span><input class="field__input" name="edu_org[]" placeholder="Текст" value="<?php echo esc_attr($v); ?>"></label>
    <?php endforeach; ?>
    <button class="add-more" type="button" data-clone>Добавить ещё</button>
    <?php foreach ($experiences as $v) : ?>
    <label class="field"><span class="field__label">Опыт работы</span><input class="field__input" name="experience[]" placeholder="Текст" value="<?php echo esc_attr($v); ?>"></label>
    <?php endforeach; ?>
    <button class="add-more" type="button" data-clone>Добавить ещё</button>
    <?php foreach ($socials as $v) : ?>
    <label class="field"><span class="field__label">Социальные сети</span><input class="field__input" name="social[]" placeholder="Текст" value="<?php echo esc_attr($v); ?>"></label>
    <?php endforeach; ?>
    <button class="add-more" type="button" data-clone>Добавить ещё</button>
    <label class="field"><span class="field__label">Дополнительно</span><input class="field__input" name="additional" placeholder="Текст" value="<?php echo cininvest_repop($d, 'additional'); ?>"></label>
    <div class="field">
        <span class="field__label">Рекомендации, письма, отзывы, характеристики</span>
        <span class="field__hint">Можно загрузить до 5 документов и(или) файлов</span>
        <label class="dropzone"><input type="file" name="recommendations" hidden><span>Перенесите файл с устройства или нажмите на данное поле для добавления файла<br><small>Размер файла: не больше 10 Мбайт · Расширение: pdf, jpeg, jpg, png, tiff</small></span></label>
    </div>
    <label class="field"><span class="field__label">Контактные данные*</span><input class="field__input" name="applicant_phone" type="tel" placeholder="+7 (800) 555-35-55" value="<?php echo cininvest_repop($d, 'applicant_phone'); ?>"></label>
</fieldset>
