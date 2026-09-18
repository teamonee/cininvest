<?php
/* Хиро для страниц направлений.
   Макет Figma: кинематографисты (944:5171) и инвесторы (957:4184) — одинаковая структура
   на всех разрешениях: фото + белый заголовок + 3 стеклянные карточки статистики.
   Текст и цифры статистики инвесторов подтверждены в Figma (957:4184). */
$is_investor = ($args['type'] ?? 'investor') === 'investor';
if ($is_investor) {
    $title = 'Инвестируйте в перспективные кинопроекты';
    $bg = 'hero-investor.jpg';
} else {
    $title = 'Привлеки инвестиции<br>в свой проект';
    $bg = 'hero-cinema.jpg';
}
?>
<section class="lp-hero <?php echo $is_investor ? 'lp-hero--investor' : 'lp-hero--cinema'; ?>">
    <div class="lp-hero__photo" style="background-image:url('<?php echo esc_url(get_template_directory_uri() . '/assets/img/' . $bg); ?>')">
        <h1 class="lp-hero__title"><?php echo wp_kses($title, ['br' => []]); ?></h1>
    </div>
    <ul class="stats lp-hero__stats">
        <li class="stats__item"><b class="stats__num"><small>от</small>&nbsp;100&nbsp;<small>₽</small></b><span class="stats__label">минимальная сумма инвестиций</span></li>
        <li class="stats__item"><b class="stats__num">2%</b><span class="stats__label">проектов проходят отбор и публикуются на платформе</span></li>
        <li class="stats__item"><b class="stats__num">30&nbsp;<small class="stats__num-caps">проектов</small></b><span class="stats__label">одобренных «CININVEST»</span></li>
    </ul>
</section>
