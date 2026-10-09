<?php
if (!function_exists('iro_get_custom_hitokoto')) {
    function iro_get_custom_hitokoto()
    {
        $arr = preg_split('/\r\n|\r|\n/', (string) iro_opt("footer_hitokoto_custom"), -1, PREG_SPLIT_NO_EMPTY);
        $arr = array_map('trim', $arr);
        $arr = array_values(array_filter($arr));

        if ($arr === []) {
            return '';
        }

        // 整组先过一遍：只译抽中的那条，其余句子要等随机抽到才登记，慢得没有意义
        $arr = array_map('iro__', $arr);

        return $arr[array_rand($arr)];
    }
}
?>
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