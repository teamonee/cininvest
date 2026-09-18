<fieldset class="app-section app-section--accent" data-section="requisites">
    <legend class="app-section__title">Реквизиты заявителя</legend>
    <?php
    $fiz = ['inn'=>'ИНН*','snils'=>'СНИЛС*','bank_name'=>'Наименование банка*','kpp'=>'КПП банка*','bik'=>'БИК банка*','corr_account'=>'Номер корреспондентского счёта*','settle_account'=>'Номер расчётного счёта*'];
    $ur  = ['legal_address'=>'Юридический адрес Заявителя*','ogrn'=>'ОГРН*','inn'=>'ИНН Заявителя*','kpp'=>'КПП Заявителя*','ogrn_date'=>'Дата присвоения ОГРН*','okved'=>'Основной ОКВЭД Заявителя*','bik'=>'БИК Банка Заявителя*','bank_name'=>'Наименование банка Заявителя*','corr_account'=>'Номер корреспондентского счёта банка Заявителя*','settle_account'=>'Номер расчётного счёта в банке Заявителя*'];
    $fields = !empty($args['is_ur']) ? $ur : $fiz;
    $d = $args['data'] ?? [];
    foreach ($fields as $key => $label) : ?>
    <label class="field"><span class="field__label"><?php echo esc_html($label); ?></span><input class="field__input" name="<?php echo esc_attr($key); ?>" placeholder="Текст" value="<?php echo cininvest_repop($d, $key); ?>"></label>
    <?php endforeach; ?>
</fieldset>
