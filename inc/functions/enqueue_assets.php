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
?>
    <?php if ($dev_mode): ?>
        <script type="module" src="<?= iro_opt("dev_mode_hmr_client") ?>"></script>
        <script type="module" src="<?= iro_opt("dev_mode_main_js") ?>"></script>
    <?php else: ?>
        <link rel="stylesheet" href="<?= get_template_directory_uri() . '/frontend/dist/style.css?ver=' . INT_VERSION ?>">
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/app.js?ver=' . INT_VERSION ?>"></script>
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
