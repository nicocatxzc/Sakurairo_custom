<?php

/**
 * post views
 */
function restyle_text($input)
{
    // 类型修复
    if (is_numeric($input)) {
        $number = (float)$input;
    } elseif (is_string($input)) {
        if (preg_match('/[-+]?[0-9]*\.?[0-9]+/', $input, $matches)) {
            $number = (float)$matches[0];
        } else {
            $number = 0;
        }
    } else {
        $number = 0;
    }

    switch (iro_opt('statistics_format')) {
        case "type_2": //23,333 次访问
            return number_format($number);
        case "type_3": //23 333 次访问
            return number_format($number, 0, '.', ' ');
        case "type_4": //23k 次访问
            if ($number >= 1000) {
                return round($number / 1000, 2) . 'k';
            }
            return $number;
        default:
            return $number;
    }
}

function set_post_views()
{
    if (!is_singular())
        return;

    global $post;
    $post_id = intval($post->ID);
    if (!$post_id)
        return;
    $views = (int) get_post_meta($post_id, 'views', true);
    if (!update_post_meta($post_id, 'views', ($views + 1))) {
        add_post_meta($post_id, 'views', 1, true);
    }
}

add_action('get_header', 'set_post_views');

function get_post_views($post_id)
{
    // 检查传入的参数是否有效
    if (empty($post_id) || !is_numeric($post_id)) {
        return 'Error: Invalid post ID.';
    }
    // 检查 WP-Statistics 插件是否安装
    if ((function_exists('wp_statistics_pages')) && (iro_opt('statistics_api') == 'wp_statistics')) {
        // 使用 WP-Statistics 插件获取浏览量
        $views = wp_statistics_pages('total', 'uri', $post_id);
        return empty($views) ? 0 : intval($views);
    } else {
        // 使用文章自定义字段获取浏览量
        $views = get_post_meta($post_id, 'views', true);
        if (empty($views)) {
            return 0;
        }
        // 格式化浏览量
        return restyle_text(intval($views));
    }
}
