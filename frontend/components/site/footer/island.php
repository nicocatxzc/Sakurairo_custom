<footer
    class="site-footer island"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <div class="site-info">
        <div class="footer-content <?= iro_opt("footer_island_style", "center") == "center" ? "just-center" : "" ?>">
            <?php require_once get_theme_file_path('/frontend/components/site/footer/hitokoto.php'); ?>
            <?= iro__((string) iro_opt("footer_html_content")) ?>
            <?= iro_opt("footer_html") ?>
        </div>
        <?php if (iro_opt('footer_sakura')): ?>
            <div
                class="sakura-icon flex-center">
                <i class="sakura"></i>
            </div>
        <?php endif; ?>
        <?php require_once get_theme_file_path('/frontend/components/site/footer/theme_info.php'); ?>
    </div>
</footer>