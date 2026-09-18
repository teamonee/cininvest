<?php $d = $args['data'] ?? []; ?>
<fieldset class="app-section app-section--accent" data-section="project">
    <legend class="app-section__title">Проект</legend>
    <label class="field"><span class="field__label">Название проекта*</span><input class="field__input" name="project_name" placeholder="Текст" required value="<?php echo cininvest_repop($d, 'project_name'); ?>"></label>
    <?php $genre = $d['genre'] ?? ''; ?>
    <label class="field"><span class="field__label">Жанр*</span><select class="field__input" name="genre">
        <option value="">Выберите жанр</option>
        <?php foreach (['Драма','Комедия','Мелодрама','Триллер','Боевик','Детектив','Фантастика','Фэнтези','Ужасы','Приключения','Исторический','Военный','Биография','Спорт','Мюзикл','Документальное','Анимация'] as $o) : ?>
        <option<?php selected($genre, $o); ?>><?php echo esc_html($o); ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="field"><span class="field__label">Хронометраж*</span><input class="field__input" name="duration" placeholder="Текст" value="<?php echo cininvest_repop($d, 'duration'); ?>"></label>
    <?php $ptype = $d['ptype'] ?? ''; ?>
    <label class="field"><span class="field__label">Тип*</span><select class="field__input" name="ptype">
        <option value="">Выберите тип</option>
        <?php foreach (['Полнометражный фильм','Короткометражный фильм','Сериал','Мини-сериал','Веб-сериал','Альманах'] as $o) : ?>
        <option<?php selected($ptype, $o); ?>><?php echo esc_html($o); ?></option>
        <?php endforeach; ?>
    </select></label>
    <?php $kind = $d['kind'] ?? ''; ?>
    <label class="field"><span class="field__label">Вид*</span><select class="field__input" name="kind">
        <option value="">Выберите вид</option>
        <?php foreach (['Игровое','Документальное','Анимационное','Научно-популярное','Экспериментальное'] as $o) : ?>
        <option<?php selected($kind, $o); ?>><?php echo esc_html($o); ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="field"><span class="field__label">Логлайн*</span><input class="field__input" name="logline" placeholder="Текст" value="<?php echo cininvest_repop($d, 'logline'); ?>"></label>
    <label class="field"><span class="field__label">Оригинальная идея / Хай-концепт*</span><input class="field__input" name="idea" placeholder="Текст" value="<?php echo cininvest_repop($d, 'idea'); ?>"></label>
    <label class="field"><span class="field__label">Сеттинг*</span><input class="field__input" name="setting" placeholder="Текст" value="<?php echo cininvest_repop($d, 'setting'); ?>"></label>
    <label class="field"><span class="field__label">Аннотация*</span><input class="field__input" name="annotation" placeholder="Текст" value="<?php echo cininvest_repop($d, 'annotation'); ?>"></label>
    <label class="field"><span class="field__label">Синопсис* (до 3000 символов)</span><input class="field__input" name="synopsis" placeholder="Текст" value="<?php echo cininvest_repop($d, 'synopsis'); ?>"></label>
    <label class="field"><span class="field__label">Локации*</span><input class="field__input" name="locations" placeholder="Текст" value="<?php echo cininvest_repop($d, 'locations'); ?>"></label>
    <?php
    $is_ur = !empty($args['is_ur']);
    $files = $is_ur ? ['script'=>'Сценарий*','presentation'=>'Презентация*'] : ['script'=>'Сценарий*','presentation'=>'Презентация*','sizzle'=>'Сиззл*'];
    foreach ($files as $name => $label) : ?>
    <div class="field"><span class="field__label"><?php echo esc_html($label); ?></span><div class="file-attach"><span class="file-attach__name">Прикрепить файл</span><label class="file-attach__btn">Выбрать файл<input type="file" name="<?php echo esc_attr($name); ?>" hidden></label></div></div>
    <?php endforeach; ?>
</fieldset>
