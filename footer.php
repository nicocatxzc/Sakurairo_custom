<?php
if(iro_opt("footer_style_select","sakura")=="sakura") {
    require_once get_theme_file_path('/frontend/components/site/footer/sakura.php');
} else {
    require_once get_theme_file_path('/frontend/components/site/footer/island.php');
}
wp_footer();
