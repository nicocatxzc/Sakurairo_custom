<?php

/**
 * Enqueue scripts and styles.
 */
function iro_enqueue_scripts()
{
?>
    <?php if (
        iro_opt("dev_mode", false) &&
        (
            !iro_opt("dev_mode_admin_only", true) ||
            current_user_can('manage_options')
        )
    ): ?>
        <script type="module" src="<?= iro_opt("dev_mode_hmr_client") ?>"></script>
        <script type="module" src="<?= iro_opt("dev_mode_main_js") ?>"></script>
    <?php else: ?>
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/app.js?ver=' . INT_VERSION ?>"></script>
        <link rel="stylesheet" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/style.css?ver=' . INT_VERSION ?>">
        <link rel="stylesheet" media="print" onload="this.onload=null;this.media='all'" crossorigin="" href="<?= get_template_directory_uri() . '/frontend/dist/captcha.css?ver=' . INT_VERSION ?>">
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
