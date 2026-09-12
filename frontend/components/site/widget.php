<div class="site-widget"
    style="
--widget_button_radius:<?= iro_opt("widget_button_radius", 0.6) ?>rem;
--widget_panel_radius:<?= iro_opt("widget_panel_radius") ?>rem;
font-family:<?= iro_opt("widget_font") ?>;
">
    <div class="control">
        <button id="goToTop" title="回到顶部">
            <i class="icon fa-solid fa-caret-up fa-lg flex-center"></i>
        </button>
        <button
            id="widgetToggle"
            title="小工具"
            @click="isPanelShow = !isPanelShow">
            <i class="icon fa-solid fa-compass-drafting fa-lg fa-flip flex-center"></i>
        </button>
    </div>
    <div class="panel hide">
        <div class="theme-controls widget-groups">
            <div class="darkmode group">
                <button
                    class="darkmode-toggle"
                    aria-label="切换主题深色模式状态"
                    :title="`切换主题深色模式状态`">
                </button>
            </div>

            <?php if (iro_opt("widget_font_switch", false) && iro_opt("widget_font_choice")): ?>
                <div
                    class="font-controls group">
                    <?php foreach (iro_opt("widget_font_choice", []) as $font): ?>
                        <button
                            type="button"
                            aria-label="切换到字体<?= $font["name"] ?>"
                            title="切换到字体<?= $font["name"] ?>"
                            data-name="<?= $font["name"] ?>">
                            <?= $font["name"] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>