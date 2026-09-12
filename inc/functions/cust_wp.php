<?php
/*
 * 删除自带小工具
 */
function unregister_default_widgets()
{
    unregister_widget('WP_Widget_Pages');
    unregister_widget('WP_Widget_Calendar');
    unregister_widget('WP_Widget_Archives');
    unregister_widget('WP_Widget_Links');
    unregister_widget('WP_Widget_Meta');
    unregister_widget('WP_Widget_Search');
    unregister_widget('WP_Widget_Categories');
    unregister_widget('WP_Widget_Recent_Posts');
    unregister_widget('WP_Nav_Menu_Widget');
}
add_action("widgets_init", "unregister_default_widgets", 11);

/*
 * 隐藏 Dashboard
 */
/* Remove the "Dashboard" from the admin menu for non-admin users */
function remove_dashboard()
{
    global $current_user, $menu, $submenu;
    wp_get_current_user();

    if (!in_array('administrator', $current_user->roles)) {
        reset($menu);
        $page = key($menu);
        while ((__('Dashboard') != $menu[$page][0]) && next($menu)) {
            $page = key($menu);
        }
        if (__('Dashboard') == $menu[$page][0]) {
            unset($menu[$page]);
        }
        reset($menu);
        $page = key($menu);
        while (!$current_user->has_cap($menu[$page][1]) && next($menu)) {
            $page = key($menu);
        }
        if (
            preg_match('#wp-admin/?(index.php)?$#', $_SERVER['REQUEST_URI']) &&
            ('index.php' != $menu[$page][2])
        ) {
            wp_redirect(get_option('siteurl') . '/wp-admin/profile.php');
        }
    }
}
add_action('admin_menu', 'remove_dashboard');
