<?php
if (post_password_required()) return;
require_once get_template_directory() . "/frontend/components/comment/list.php";
require_once get_template_directory() . "/frontend/components/comment/form.php";
