<?php global $iro_only_template; ?>

<?php if (!$iro_only_template): ?>
    <div
        class="post-list"
        style="
            --post-card-border-radius: <?= iro_opt("post_card_design")["card_radius"] ?? 0.7 ?>rem;
            --post-card-meta-border-radius: <?= iro_opt("post_card_design")["meta_radius"] ?? 0.3 ?>rem;
            --post-card-title-border-radius: <?= iro_opt("post_card_design")["title_radius"] ?? 0.3 ?>rem;
            --post-card-title-font-size: <?= iro_opt("post_card_design")["title_font_size"] ?? 1.2 ?>rem;
        ">
    <?php endif; ?>
    <?php if (have_posts()): ?>
        <?php while (have_posts()) : the_post(); ?>
            <?php
            if (has_post_thumbnail()) :
                require get_template_directory() . '/frontend/components/post/card/with_image.php';
            else :
                require get_template_directory() . '/frontend/components/post/card/simple.php';
            endif;
            ?>
        <?php endwhile; ?>
        <?php iro_post_pagination(); ?>
    <?php else: ?>
        <div v-else class="empty-state">
            <i name="fa-solid fa-inbox"></i>
            <p>暂时还没有内容哦</p>
        </div>
    <?php endif; ?>
    <?php if (!$iro_only_template): ?>
    </div>
<?php endif; ?>