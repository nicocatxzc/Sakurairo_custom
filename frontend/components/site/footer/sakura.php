<footer
    class="site-footer-sakura flex-center"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <?php if (iro_opt('footer_sakura')): ?>
        <?= iro_render_decorate('sakura-icon') ?>
    <?php endif; ?>
    <?= iro_render_decorate('hitokoto') ?>
    <div class="site-info">
        <?= iro__((string) iro_opt("footer_html_content")) ?>
        <?= iro_opt("footer_html") ?>
    </div>
    <?php require_once get_theme_file_path('/frontend/components/site/footer/theme_info.php'); ?>
</footer>
