<?php
if (!function_exists('iro_get_custom_hitokoto')) {
    function iro_get_custom_hitokoto()
    {
        $arr = preg_split('/\r\n|\r|\n/', iro_opt("footer_hitokoto_custom"), -1, PREG_SPLIT_NO_EMPTY);
        $arr = array_map('trim', $arr);
        $arr = array_values(array_filter($arr));
        $random = $arr[array_rand($arr)];
        return $random;
    }
}
?>

<footer
    class="site-footer flex-center"
    style=" font-family: <?= iro_opt("footer_font") ?> ">
    <?php if (iro_opt('footer_sakura')): ?>
        <div
            class="sakura-icon flex-center">
            <?= file_get_contents(get_template_directory() . '/frontend/components/icons/sakura.svg') ?>
        </div>
    <?php endif; ?>
    <?php if (iro_opt('footer_hitokoto_select', 'off') != "off"): ?>
        <?php if (iro_opt('footer_hitokoto_select') == "api"): ?>
            <p id="footer_hitokoto" class="hitokoto"></p>
        <?php endif; ?>
        <?php if (iro_opt('footer_hitokoto_select') == "custom"): ?>
            <p id="footer_hitokoto" class="hitokoto"><?= iro_get_custom_hitokoto() ?></p>
        <?php endif; ?>
        <?php if (iro_opt('footer_hitokoto_select') == "both"): ?>
            <p id="footer_hitokoto" class="hitokoto"><?= mt_rand(0, 1) ? iro_get_custom_hitokoto() : "" ?></p>
        <?php endif; ?>
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