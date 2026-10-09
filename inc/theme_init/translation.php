<?php
// 载入翻译文件
load_theme_textdomain('sakurairo', get_template_directory() . '/languages');

add_action('init', 'set_user_locale');
function set_user_locale()
{
    // 前台语言由多语言模块按「URL 前缀 / 访客 cookie」决定并在请求内临时切换，
    // 这里再按登录用户切一次会把那个决定覆盖掉；后台仍沿用用户语言
    if (iro_i18n_enabled() && !is_admin()) {
        return;
    }

    if (is_user_logged_in()) {
        $user_locale = get_user_locale();
        switch_to_locale($user_locale);
    }
}
