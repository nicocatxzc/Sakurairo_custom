<?php

/**
 * Enqueue scripts and styles.
 */
function iro_enqueue_scripts()
{
    $dev_mode = iro_opt("dev_mode", false) &&
        (
            !iro_opt("dev_mode_admin_only", true) ||
            current_user_can('manage_options')
        );
    // 文章排版样式独立导出为 post-sakura.css，仅在设置为 Sakura 时按需引用。
    // page_style 选项值 "rating" 对应「Sakura」。
    $use_sakura_post_style = iro_opt("page_style", "sakura") === "sakura";
?>
    <?php if ($dev_mode): ?>
        <script type="module" src="<?= iro_opt("dev_mode_hmr_client") ?>"></script>
        <script type="module" src="<?= iro_opt("dev_mode_main_js") ?>"></script>
        <?php if ($use_sakura_post_style): ?>
            <script type="module" src="<?= rtrim(dirname(iro_opt("dev_mode_main_js")), '/\\') . '/components/post/post-sakura.scss' ?>"></script>
        <?php endif; ?>
    <?php else: ?>
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/app.js?ver=' . INT_VERSION ?>"></script>
        <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/style.css?ver=' . INT_VERSION ?>">
        <?php if ($use_sakura_post_style): ?>
            <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/post-sakura.css?ver=' . INT_VERSION ?>">
        <?php endif; ?>
        <link rel="stylesheet" media="print" onload="this.onload=null;this.media='all'" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/captcha.css?ver=' . INT_VERSION ?>">
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
