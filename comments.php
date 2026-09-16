<?php
global $iro_only_template;
if (post_password_required()) return;
require_once get_template_directory() . "/frontend/components/comment/list.php";
if (!$iro_only_template) {
    require_once get_template_directory() . "/frontend/components/comment/form.php";
}
