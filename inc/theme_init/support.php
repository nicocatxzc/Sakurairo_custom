<?php
if (!function_exists('akina_setup')) {
    function akina_setup()
    {
        // 启用文章封面
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(150, 150, true);

        // 注册导航菜单
        register_nav_menus(
            array(
                'primary' => __('Nav Menus', 'sakurairo'), //导航菜单
            )
        );

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );

        /*
         * Enable support for Post Formats.
         * See https://developer.wordpress.org/themes/functionality/post-formats/
         */
        add_theme_support(
            'post-formats',
            array(
                'aside',
                'image',
                'status',
            )
        );

        // 注册小工具支持
        add_theme_support('widgets');

        /**
         * 废弃过时的wp_title
         * @seealso https://make.wordpress.org/core/2015/10/20/document-title-in-4-4/
         */
        add_theme_support('title-tag');

        add_filter('pre_option_link_manager_enabled', '__return_true');

        // 优化代码
        //去除头部冗余代码
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'start_post_rel_link', 10);
        remove_action('wp_head', 'wp_generator');
        remove_filter('the_content', 'wptexturize');
        remove_action('template_redirect', 'rest_output_link_header', 11);

        function coolwp_remove_open_sans_from_wp_core()
        {
            wp_deregister_style('open-sans');
            wp_register_style('open-sans', false);
            wp_enqueue_style('open-sans', '');
        }
        add_action('init', 'coolwp_remove_open_sans_from_wp_core');

        if (!function_exists('disable_emojis')) {
            /**
             * Disable the emoji's
             * @see https://wordpress.org/plugins/disable-emojis/
             */
            function disable_emojis()
            {
                remove_action('wp_head', 'print_emoji_detection_script', 7);
                remove_action('admin_print_scripts', 'print_emoji_detection_script');
                remove_action('wp_print_styles', 'print_emoji_styles');
                remove_action('admin_print_styles', 'print_emoji_styles');
                remove_filter('the_content_feed', 'wp_staticize_emoji');
                remove_filter('comment_text_rss', 'wp_staticize_emoji');
                remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
                add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
            }
            add_action('init', 'disable_emojis');
        }
        if (!function_exists('disable_emojis_tinymce')) {
            /**
             * Filter function used to remove the tinymce emoji plugin.
             *
             * @param    array  $plugins
             * @return   array             Difference betwen the two arrays
             */
            function disable_emojis_tinymce($plugins)
            {
                if (is_array($plugins)) {
                    return array_diff($plugins, array('wpemoji'));
                } else {
                    return array();
                }
            }
        }
        // 移除菜单冗余代码
        add_filter('nav_menu_css_class', 'my_css_attributes_filter', 100, 1);
        add_filter('nav_menu_item_id', 'my_css_attributes_filter', 100, 1);
        add_filter('page_css_class', 'my_css_attributes_filter', 100, 1);
        function my_css_attributes_filter($var)
        {
            return is_array($var) ? array_intersect($var, array('current-menu-item', 'current-post-ancestor', 'current-menu-ancestor', 'current-menu-parent')) : '';
        }
    }
};
add_action('after_setup_theme', 'akina_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function akina_content_width()
{
    $GLOBALS['content_width'] = apply_filters('akina_content_width', 640);
}
add_action('after_setup_theme', 'akina_content_width', 0);

/**
 * Jetpack setup function.
 *
 * See: https://jetpack.com/support/infinite-scroll/
 * See: https://jetpack.com/support/responsive-videos/
 */
function akina_jetpack_setup()
{
    // Add theme support for Infinite Scroll.
    add_theme_support(
        'infinite-scroll',
        array(
            'container' => 'main',
            'render' => 'akina_infinite_scroll_render',
            'footer' => 'page',
        )
    );

    // Add theme support for Responsive Videos.
    add_theme_support('jetpack-responsive-videos');
}
add_action('after_setup_theme', 'akina_jetpack_setup');

/**
 * Custom render function for Infinite Scroll.
 */
function akina_infinite_scroll_render()
{
    while (have_posts()) {
        the_post();
        get_template_part('tpl/content', is_search() ? 'search' : get_post_format());
    }
}

//侧栏小工具
add_action('widgets_init', function () {
    register_sidebar([
        'name'          => __('Sakurairo工具栏', 'textdomain'),
        'id'            => 'iro_widget',
        'description'   => __('显示在页脚工具栏的内容', 'textdomain'),
        'before_widget' => '<section id="%1$s" class="iro_widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="iro_widget_title">',
        'after_title'   => '</h2>',
    ]);
});

//WEBP支持?
function mimvp_filter_mime_types($array)
{
    $array['webp'] = 'image/webp';
    return $array;
}
add_filter('mime_types', 'mimvp_filter_mime_types', 10, 1);
function mimvp_file_is_displayable_image($result, $path)
{
    $info = @getimagesize($path);
    // if ($info['mime'] == 'image/webp') {
    //     $result = true;
    // }
    // return $result;
    return (bool) ($info); // 根据文档这里需要返回一个bool
}
add_filter('file_is_displayable_image', 'mimvp_file_is_displayable_image', 10, 2);

// 允许分类、标签描述添加html代码
remove_filter('pre_term_description', 'wp_filter_kses');
remove_filter('term_description', 'wp_kses_data');
// 去除顶部工具栏
show_admin_bar(false);
