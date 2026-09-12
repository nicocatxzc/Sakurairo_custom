<?php
add_action("after_setup_theme", function () {
    if (iro_opt("pjax", true) == true) {
        // 禁用wp6.9按需加载
        add_filter('wp_should_load_separate_core_block_assets', '__return_false');
        add_filter('should_load_separate_core_block_assets', '__return_false', 1);
        add_filter('should_load_block_assets_on_demand', '__return_false', 1);
        add_filter('enqueue_empty_block_content_assets', '__return_true');
    }
});

add_action("wp_enqueue_scripts", function () {
    if (iro_opt("pjax", true) == true) {
        // 全量加载wordpress区块和原生组件样式
        wp_enqueue_style('wp-block-library');
        wp_enqueue_style('wp-block-library-theme');
        wp_enqueue_style('wp-block-library-comments');
        wp_enqueue_style('wp-block-library-widgets');
    }
});

/**
 * 修复 WordPress 搜索结果为空，返回为 200 的问题。
 * @author ivampiresp <im@ivampiresp.com>
 */
function search_404_fix_template_redirect()
{
    if (is_search()) {
        global $wp_query;

        if ($wp_query->found_posts == 0) {
            status_header(404);
        }
    }
}

add_action('template_redirect', 'search_404_fix_template_redirect');

// 主动resize触发wp_scripts后台排版修正，防止左侧导航栏飞出
add_action('admin_footer', function () {
?><script>
        document.addEventListener('DOMContentLoaded', function() {
            window.dispatchEvent(new Event("resize"));
        })
    </script>
<?php
});

/*
 * 阻止站内文章互相Pingback
 */
function theme_noself_ping(&$links)
{
    $home = get_option('home');
    foreach ($links as $l => $link) {
        if (0 === strpos($link, $home)) {
            unset($links[$l]);
        }
    }
}
add_action('pre_ping', 'theme_noself_ping');

// 给上传图片增加时间戳
add_filter('wp_handle_upload_prefilter', function ($file) {
    $file['name'] = time() . '-' . $file['name'];
    return $file;
});

/*
 * 删除后台某些版权和链接
 * @wpdx
 */
add_filter('admin_title', 'wpdx_custom_admin_title', 10, 2);
function wpdx_custom_admin_title($admin_title, $title)
{
    return $title . ' &lsaquo; ' . get_bloginfo('name');
}
//去掉Wordpress LOGO
function remove_logo($wp_toolbar)
{
    $wp_toolbar->remove_node('wp-logo');
}
add_action('admin_bar_menu', 'remove_logo', 999);

//去掉Wordpress 底部版权
function change_footer_admin()
{
    return '';
}
add_filter('admin_footer_text', 'change_footer_admin', 9999);
function change_footer_version()
{
    return '';
}
add_filter('update_footer', 'change_footer_version', 9999);

//去掉Wordpres挂件
function disable_dashboard_widgets()
{
    //remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');//近期评论 
    //remove_meta_box('dashboard_recent_drafts', 'dashboard', 'normal');//近期草稿
    // remove_meta_box('dashboard_primary', 'dashboard', 'core');//wordpress博客  
    // remove_meta_box('dashboard_secondary', 'dashboard', 'core');//wordpress其它新闻  
    // remove_meta_box('dashboard_right_now', 'dashboard', 'core');//wordpress概况  
    //remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');//wordresss链入链接  
    //remove_meta_box('dashboard_plugins', 'dashboard', 'core');//wordpress链入插件  
    //remove_meta_box('dashboard_quick_press', 'dashboard', 'core');//wordpress快速发布   
}
add_action('admin_menu', 'disable_dashboard_widgets');

/**
 * 文章摘要
 */
function changes_post_excerpt_more($more)
{
    return ' ...';
}
function changes_post_excerpt_length($length)
{
    return 65;
}
add_filter('excerpt_more', 'changes_post_excerpt_more');
add_filter('excerpt_length', 'changes_post_excerpt_length', 999);

/**
 * 更改作者页链接为昵称显示
 */
// Replace the user name using the nickname, query by user ID
add_filter('request', 'siren_request');
function siren_request($query_vars)
{
    if (array_key_exists('author_name', $query_vars)) {
        global $wpdb;
        $author_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='nickname' AND meta_value = %s", $query_vars['author_name']));
        if ($author_id) {
            $query_vars['author'] = $author_id;
            unset($query_vars['author_name']);
        }
    }
    return $query_vars;
}
