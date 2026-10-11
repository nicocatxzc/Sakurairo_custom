<?php
switch (iro_opt("footer_style_select", "sakura")) {
    case 'island':
        require_once get_theme_file_path('/frontend/components/site/footer/island.php');
        break;
    case 'custom':
        block_template_part('site-footer');
        break;

    default:
        require_once get_theme_file_path('/frontend/components/site/footer/sakura.php');
        break;
}
wp_footer();
