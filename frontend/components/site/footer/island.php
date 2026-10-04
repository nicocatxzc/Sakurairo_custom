<footer
    class="site-footer island"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <div class="site-info">
        <div class="footer-content">
            <?php require_once get_template_directory() . '/frontend/components/site/footer/hitokoto.php'; ?>
            <?= iro_opt("footer_html") ?>
        </div>
        <?php if (iro_opt('footer_sakura')): ?>
            <div
                class="sakura-icon flex-center">
                <i class="sakura"></i>
            </div>
        <?php endif; ?>
        <?php require_once get_template_directory() . '/frontend/components/site/footer/theme_info.php'; ?>
    </div>
</footer>
