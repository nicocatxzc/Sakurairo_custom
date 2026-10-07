<?php
global $iro_only_template;
if (post_password_required()) return;
require_once get_theme_file_path("/frontend/components/comment/list.php");
if (!$iro_only_template) {
    require_once get_theme_file_path("/frontend/components/comment/form.php");
}
