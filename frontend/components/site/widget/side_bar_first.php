<?php if (is_active_sidebar('iro_side_bar_first')) : ?>
    <aside class="site-side-bar side-bar-first <?= iro_opt("layout_side_bar_background", true) ? "with-background" : "" ?>">
        <?php dynamic_sidebar('iro_side_bar_first'); ?>
    </aside>
<?php endif; ?>
