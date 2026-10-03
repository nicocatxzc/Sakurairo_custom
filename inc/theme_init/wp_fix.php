<?php

/**
 * WP 6.9 起，经典主题的区块样式默认按需加载：样式要到 render_block 时才入队，只能在页脚打印，
 * 再由核心的模板增强输出缓冲（wp_hoist_late_printed_styles）吊回 head。
 * 现在把「吊回 head 的那批区块样式」合并成一个
 * <style id="iro_block_styles">， pjax 可以整体替换。
 */
const IRO_BLOCK_STYLES_MARKER = '/*iro-block-styles*/';

// 容器输出、收集、替换三处必须同条件：pjax 关掉就没有容器可放，ajax 翻页（X-Template-Part）
// 走的是同一批列表、容器里已经有了，都不接管。WP 6.9 以下没有模板增强缓冲，同样不接管。
function iro_block_styles_available(): bool
{
    if (!iro_opt("pjax", true) || !empty($_SERVER['HTTP_X_TEMPLATE_PART'])) {
        return false;
    }

    return function_exists('wp_should_output_buffer_template_for_enhancement')
        && wp_should_output_buffer_template_for_enhancement();
}

/**
 * 本页用到的区块样式 CSS。
 *
 * 收集时把这些句柄出队，改由 <style id="iro_block_styles"> 承载；
 * 前提不成立就返回空串，一个句柄都不动，让核心照常打印样式。
 */
function iro_block_styles_css(): string
{
    static $css = null;

    if ($css !== null) {
        return $css;
    }

    $css = '';

    global $wp_styles;

    if (!iro_block_styles_available() || !$wp_styles instanceof WP_Styles) {
        return $css;
    }

    // 本页所有已注册区块的样式句柄（core/paragraph → wp-block-paragraph）
    $handles = [];
    foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type) {
        foreach ((array) $block_type->style_handles as $style_handle) {
            $handles[$style_handle] = true;
        }
    }

    $collected = '';

    foreach ($wp_styles->queue as $handle) {
        if (!isset($handles[$handle])) {
            continue;
        }

        $style = $wp_styles->registered[$handle] ?? null;
        if (!$style) {
            continue;
        }

        // wp_add_inline_style 追加的行内片段
        $inline = '';
        foreach (['before', 'after'] as $position) {
            if (!empty($style->extra[$position])) {
                $inline .= implode("\n", (array) $style->extra[$position]) . "\n";
            }
        }

        $files = '';
        if ($wp_styles->get_data($handle, 'inlined_src')) {
            // 核心已把文件内容内联进 extra；真为空就原样保留，别把样式丢掉
            if ($inline === '') {
                continue;
            }
        } else {
            // 按注册时记下的 path 读盘，避免额外 HTTP 往返；读不到（CDN 资源等）就保留 <link>
            $path = (string) ($style->extra['path'] ?? '');
            if ($path === '' || !is_readable($path)) {
                continue;
            }

            $files = (string) file_get_contents($path);

            // 内联后相对 url() 会以文档为基准失效，按样式表所在目录补成绝对地址
            if (str_contains($files, 'url(')) {
                $src_path = (string) preg_replace('/[?#].*$/', '', (string) $style->src);
                $slash = strrpos($src_path, '/');
                $dir = $slash === false ? '' : substr($src_path, 0, $slash + 1);

                $files = (string) preg_replace_callback(
                    '/url\(\s*([\'"]?)(?!data:|https?:|\/\/|\/|#)([^\'")]+)\1\s*\)/i',
                    static fn(array $matches): string => 'url(' . $matches[1] . $dir . $matches[2] . $matches[1] . ')',
                    $files
                );
            }

            $files .= "\n";
        }

        $collected .= $inline . $files;
        unset($style->extra['before'], $style->extra['after']);
        wp_dequeue_style($handle);
    }

    $css = $collected;

    return $css;
}

// 页脚打印前先收集并出队（核心的吊装逻辑此时会看到空队列，不会重复打印）
add_action('wp_print_footer_scripts', 'iro_block_styles_css', 5);

// 容器固定打在已入队样式的后面（wp_print_styles 在 wp_head 优先级 8）
add_action('wp_head', function (): void {
    if (!iro_block_styles_available()) {
        return;
    }
    echo '<style id="iro_block_styles">' . IRO_BLOCK_STYLES_MARKER . '</style>' . "\n";
}, 9);

// 请求结束时把实际 CSS 填进容器
add_filter('wp_template_enhancement_output_buffer', function ($buffer) {
    if (!is_string($buffer) || !str_contains($buffer, IRO_BLOCK_STYLES_MARKER)) {
        return $buffer;
    }

    return str_replace(IRO_BLOCK_STYLES_MARKER, iro_block_styles_css(), $buffer);
}, 20);

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
            const csf = document.querySelector(".csf-nav")
            if (csf) {
                csf.addEventListener("click", () => {
                    window.dispatchEvent(new Event("resize"));
                })
            }
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
