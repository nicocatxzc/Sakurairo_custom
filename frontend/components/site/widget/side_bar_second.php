<?php if (is_active_sidebar('iro_side_bar_second')) : ?>
    <aside class="site-side-bar side-bar-second <?= iro_opt("layout_side_bar_background", true) ? "with-background" : "" ?>">
        <?php dynamic_sidebar('iro_side_bar_second'); ?>
    </aside>
<?php endif; ?>
