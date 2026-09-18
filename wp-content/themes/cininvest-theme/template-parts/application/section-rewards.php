<?php
$d = $args['data'] ?? [];
$checked_rewards = (!empty($d['rewards']) && is_array($d['rewards'])) ? $d['rewards'] : [];
?>
<fieldset class="app-section" data-section="rewards">
    <legend class="app-section__title">Вознаграждения</legend>
    <p class="app-section__sub">Выберите желаемое/ые вознаграждение/я*</p>
    <?php foreach (['Ранний доступ к стримингу (онлайн просмотр)','Приглашение на один съёмочный день','Участие в массовке фильма','Актёр третьего плана','Офлайн/онлайн встреча с командой проекта','Приглашение на специальный показ','Реклама','PR-партнерство'] as $r) : ?>
    <label class="checkbox"><input type="checkbox" name="rewards[]" value="<?php echo esc_attr($r); ?>" <?php checked(in_array($r, $checked_rewards, true)); ?>><span><?php echo esc_html($r); ?></span></label>
    <?php endforeach; ?>
</fieldset>
