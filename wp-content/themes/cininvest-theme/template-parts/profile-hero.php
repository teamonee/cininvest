<?php
/* Шапка личного кабинета. Макет: имя + роль одной строкой (Montserrat Bold 35),
   ниже «lead» (25px), ниже «note» (16px) + опциональная ghost-кнопка. */
?>
<section class="profile-hero">
    <div class="container profile-hero__inner">
        <h1 class="profile-hero__name"><?php echo wp_kses_post(trim($args['name'] . ' ' . ($args['role'] ?? ''))); ?></h1>
        <?php if (!empty($args['lead'])) : ?>
            <p class="profile-hero__lead"><?php echo esc_html($args['lead']); ?></p>
        <?php endif; ?>
        <?php if (!empty($args['note']) || !empty($args['cta'])) : ?>
        <div class="profile-hero__foot">
            <?php if (!empty($args['note'])) : ?>
                <p class="profile-hero__note"><?php echo esc_html($args['note']); ?></p>
            <?php endif; ?>
            <?php if (!empty($args['cta'])) : ?>
                <a class="btn btn--grey profile-hero__cta" href="<?php echo esc_url($args['cta_href'] ?? '#'); ?>">
                    <?php echo esc_html($args['cta']); ?>
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
