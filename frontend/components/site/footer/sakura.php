<footer
    class="site-footer-sakura flex-center"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <?php if (iro_opt('footer_sakura')): ?>
        <div
            class="sakura-icon flex-center">
            <i class="sakura"></i>
        </div>
    <?php endif; ?>
    <?php require_once get_theme_file_path('/frontend/components/site/footer/hitokoto.php'); ?>
    <div class="site-info">
        <?= iro_opt("footer_html") ?>
    </div>
    <?php require_once get_theme_file_path('/frontend/components/site/footer/theme_info.php'); ?>
</footer>