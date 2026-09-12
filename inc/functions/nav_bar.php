<?php
require_once get_template_directory() . '/inc/libs/wp-api-menus/wp-api-menus.php';
function iro_get_menu($location)
{

    // 所有已注册菜单
    $menus = wp_get_nav_menus();

    // 没有任何菜单，新建一个
    if (empty($menus)) {

        $menu_id = wp_create_nav_menu(__('导航栏', 'iro'));

        // 添加首页链接
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'  => get_bloginfo('name'),
            'menu-item-url'    => home_url('/'),
            'menu-item-status' => 'publish',
        ]);

        $menus = wp_get_nav_menus();
    }

    // 当前菜单位置绑定情况
    $locations = get_nav_menu_locations();

    // 如果菜单为空则自动分配
    if (empty($locations[$location])) {

        // 自动使用第一个可用菜单
        $menu = reset($menus);

        $locations[$location] = $menu->term_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    return (int) $locations[$location];
}

function iro_get_navigation()
{

    // 获取菜单
    $menu_id = iro_get_menu('primary');

    if (!$menu_id) {
        return [];
    }

    // 使用wp_rest_menu获取结构
    if (!class_exists('WP_REST_Menus')) {
        return new WP_Error(
            'missing_dependency',
            'WP_API_Menus plugin is required.',
            ['status' => 500]
        );
    }

    $menus_api = new WP_REST_Menus();

    $request = new WP_REST_Request('GET');
    $request->set_param('location', 'primary');

    return $menus_api->get_menu_location($request);
}
