<?php
// 载入翻译文件
load_theme_textdomain('sakurairo', get_template_directory() . '/languages');

add_action('init', 'set_user_locale');
function set_user_locale()
{
    if (is_user_logged_in()) {
        $user_locale = get_user_locale();
        switch_to_locale($user_locale);
    }
}
