<?php if (is_active_sidebar('iro_side_bar_first')) : ?>
    <aside
        style="--side_bar_radius:<?= iro_opt("layout_side_bar_radius", 0.6) ?>rem;
    --side_bar_item_radius:<?= iro_opt("layout_side_bar_item_radius", 0.6) ?>rem"
        class="site-side-bar side-bar-first <?= iro_opt("layout_side_bar_background", true) ? "with-background" : "" ?> <?= iro_opt("layout_side_bar_items_background", true) ? "with-items-bg" : "" ?>">
        <?php dynamic_sidebar('iro_side_bar_first'); ?>
    </aside>
<?php endif; ?>