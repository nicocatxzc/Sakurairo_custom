<footer
    class="site-footer flex-center"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <?php if (iro_opt('footer_sakura')): ?>
        <div
            class="sakura-icon flex-center">
            <?= file_get_contents(get_template_directory() . '/frontend/components/icons/sakura.svg') ?>
        </div>
    <?php endif; ?>
    <div class="site-info">
        <?= iro_opt("footer_html") ?>
    </div>
    <div class="theme-info">
        <a
            href="https://github.com/mirai-mamori/Sakurairo"
            rel="noopener"
            target="_blank">
            Theme Sakurairo
        </a>
        <a href="https://docs.fuukei.org/" rel="noopener" target="_blank">
            by Fuukei
        </a>
    </div>
</footer>