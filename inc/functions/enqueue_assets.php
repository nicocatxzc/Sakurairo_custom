<?php

/**
 * Enqueue scripts and styles.
 */
function iro_enqueue_scripts()
{
?>
    <?php if (iro_opt("dev_mode", false)): ?>
        <script type="module" src="https://wordpress:5173/@vite/client"></script>
        <script type="module" src="https://wordpress:5173/main.js"></script>
    <?php else: ?>
        <script type="module" src="<?= get_template_directory_uri().'/frontend/dist/app.js' ?>"></script>
        <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri().'/frontend/dist/style.css' ?>">
        <!-- 验证码样式独立于前台主样式（见 vite.config.js 的 assetFileNames），与 captcha.js 配套使用 -->
        <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri().'/frontend/dist/captcha.css' ?>">
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
