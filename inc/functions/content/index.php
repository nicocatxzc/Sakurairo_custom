<?php
/*
 * 订制body类
 */
function akina_body_classes($classes)
{
    // Adds a class of group-blog to blogs with more than 1 published author.
    if (is_multi_author()) {
        $classes[] = 'group-blog';
    }
    // Adds a class of hfeed to non-singular pages.
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }
    // 定制中文字体class
    $classes[] = 'chinese-font';
    /*if(!wp_is_mobile()) {
    $classes[] = 'serif';
    }*/
    if (isset($_COOKIE['dark' . iro_opt('cookie_version', '')])) {
        $classes[] = $_COOKIE['dark' . iro_opt('cookie_version', '')] == '1' ? 'dark' : ' ';
    } else {
        $classes[] = ' ';
    }
    return $classes;
}
add_filter('body_class', 'akina_body_classes');

/*
 * 图片CDN
 */
add_filter('upload_dir', 'wpjam_custom_upload_dir');
function wpjam_custom_upload_dir($uploads)
{
    /*     $upload_path = '';
     */
    $upload_url_path = iro_opt('image_cdn');

    $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];

    if ($upload_url_path) {
        $uploads['baseurl'] = $upload_url_path;
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
    }
    return $uploads;
}

function toc_support($content)
{
    $content = str_replace('[toc]', '<div class="has-toc have-toc"></div>', $content); // TOC 支持
    $content = str_replace('[begin]', '<span class="begin">', $content); // 首字格式支持
    $content = str_replace('[/begin]', '</span>', $content); // 首字格式支持
    return $content;
}
add_filter('the_content', 'toc_support');
add_filter('the_excerpt_rss', 'toc_support');
add_filter('the_content_feed', 'toc_support');

// 友情链接渲染
require_once get_template_directory() . '/inc/functions/content/link_render.php';
// 优化相关
require_once get_template_directory() . '/inc/functions/content/optimize.php';
// 阅读数量
require_once get_template_directory() . '/inc/functions/content/post_views.php';
// wp_query干预
require_once get_template_directory() . '/inc/functions/content/query.php';
// 统计信息
require_once get_template_directory() . '/inc/functions/content/statistical.php';