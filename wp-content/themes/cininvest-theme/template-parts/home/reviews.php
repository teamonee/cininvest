<section class="reviews container">
    <h2 class="section-title">Отзывы</h2>
    <div class="reviews__scroll">
        <?php
        // Отзывы с последнего проекта (демо-источник); при отсутствии — заглушка
        $rq = new WP_Query(['post_type' => 'project', 'posts_per_page' => 1, 'no_found_rows' => true]);
        $printed = false;
        if ($rq->have_posts()) { $rq->the_post();
            if (function_exists('have_rows') && have_rows('reviews')) {
                while (have_rows('reviews')) { the_row(); $printed = true; ?>
                <figure class="review">
                    <header class="review__head">
                        <span class="review__ava"></span>
                        <figcaption><b class="review__name"><?php echo esc_html((string) get_sub_field('name')); ?></b><span class="review__role"><?php echo esc_html((string) get_sub_field('role')); ?></span></figcaption>
                        <span class="review__quote"><svg width="14" height="30" viewBox="0 0 27.9478 60.5756" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.0195 33.8626V19.6286L0 23.6583V52.5162L7.0195 56.546V39.5822H3.11978L7.0195 33.8626Z" fill="currentColor"/><path d="M17.4836 23.5933V9.03433L10.4641 13.129V33.8626L14.3638 39.5822H10.4641V58.5608L13.9739 60.5757L17.4836 58.5608V29.3129H13.5839L17.4836 23.5933Z" fill="currentColor"/><path d="M20.9282 4.02971V23.5933L24.8279 29.3129H20.9282V56.546L27.9477 52.5162V0L20.9282 4.02971Z" fill="currentColor"/></svg></span>
                    </header>
                    <blockquote class="review__text"><?php echo wp_kses_post((string) get_sub_field('text')); ?></blockquote>
                </figure>
                <?php }
            }
            wp_reset_postdata();
        }
        if (!$printed) echo '<p class="empty">Отзывы появятся здесь.</p>';
        ?>
    </div>
</section>
