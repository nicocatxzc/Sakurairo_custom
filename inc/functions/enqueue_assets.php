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
    <?php endif; ?>
<?php
}
add_action('wp_enqueue_scripts', 'iro_enqueue_scripts', 999);
