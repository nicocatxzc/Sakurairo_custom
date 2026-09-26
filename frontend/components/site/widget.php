<div class="site-widget"
    style="
--widget_button_radius:<?= iro_opt("widget_button_radius", 0.6) ?>rem;
--widget_panel_radius:<?= iro_opt("widget_panel_radius") ?>rem;
font-family:<?= iro_opt("widget_font") ?>;
">
    <div class="control">
        <button id="goToTop" title="<?= __("回到顶部",'sakurairo') ?>">
            <i class="icon fa-icon-solid fa-caret-up fa-lg flex-center"></i>
        </button>
        <button
            id="widgetToggle"
            title="小工具"
            @click="isPanelShow = !isPanelShow">
            <i class="icon fa-icon-solid fa-compass-drafting fa-lg fa-flip flex-center"></i>
        </button>
    </div>
    <div class="panel hide">
        <?php if (is_active_sidebar('iro_widget')&&iro_opt("widget_wordpress_widget",false)) : ?>
            <aside class="wp-widget">
                <?php dynamic_sidebar('iro_widget'); ?>
            </aside>
        <?php endif; ?>
        <div class="theme-controls widget-groups">
            <div class="darkmode group">
                <button
                    class="darkmode-toggle"
                    aria-label="<?= __("切换主题深色模式状态",'sakurairo') ?>"
                    title="<?= __("切换主题深色模式状态",'sakurairo') ?>">
                </button>
            </div>

            <?php if (iro_opt("widget_font_switch", false) && iro_opt("widget_font_choice")): ?>
                <div
                    class="font-controls group">
                    <?php foreach (iro_opt("widget_font_choice", []) as $font): ?>
                        <button
                            type="button"
                            aria-label="<?= __("切换到字体",'sakurairo') ?><?= $font["name"] ?>"
                            title="<?= __("切换到字体",'sakurairo') ?><?= $font["name"] ?>"
                            data-name="<?= $font["name"] ?>">
                            <?= $font["name"] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>