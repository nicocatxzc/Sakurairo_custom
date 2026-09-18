<?php
// Sakurairo古腾堡编辑器支持

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'iro_editor_vars');
function iro_editor_vars($hook)
{
    // 仅在编辑页注入
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    // 准备信息
    $data = [
        'siteTitle' => get_bloginfo('name'),
        'language' => get_locale(),
        'user' => wp_get_current_user()->user_login,
    ];

    // 传递变量
    wp_add_inline_script(
        'wp-blocks',
        'window.iroBlockEditor = ' . json_encode($data) . ';',
        'before'
    );
}

function iro_block_base_url()
{
    // 主题环境
    if (defined('IRO_VERSION')) {
        return get_theme_file_uri('/inc/blocks/');
    }

    // 插件环境
    return plugin_dir_url(__FILE__);
}

function iro_block_base_path()
{
    if (defined('IRO_VERSION')) {
        return get_theme_file_path('/inc/blocks/');
    }

    return plugin_dir_path(__FILE__);
}


add_action('enqueue_block_editor_assets', 'sakurairo_editor_styles');
function sakurairo_editor_styles()
{
    iro_register_editor_styles();

    wp_enqueue_style('fontawesome-icons');
    wp_enqueue_style('iro-codes');
}

/**
 * WP 7.1 起古腾堡画布改为渲染在 editor-canvas iframe 中。
 * 该 iframe 只消费 enqueue_block_assets 与区块注册时的 style / editorStyle 句柄，
 * 不会消费 enqueue_block_editor_assets；只挂后者会让编辑器内的区块完全没有主题样式。
 * 这里把同一份编辑器样式补进 iframe，并用 is_admin() 保证前台不会加载。
 */
add_action('enqueue_block_assets', 'sakurairo_iframe_editor_styles');
function sakurairo_iframe_editor_styles()
{
    if (! is_admin()) {
        return;
    }

    iro_register_editor_styles();

    wp_enqueue_style('fontawesome-icons');
    wp_enqueue_style('iro-codes');
}

// 注册编辑器样式句柄，供编辑器外层文档与编辑器 iframe 两处复用
function iro_register_editor_styles()
{
    if (wp_style_is('iro-codes', 'registered')) {
        return;
    }

    if (defined('IRO_VERSION')) {
        $fontawesome = iro_opt('fontawesome_source', 'https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css');
    } else {
        $fontawesome = 'https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css';
    }

    wp_register_style('fontawesome-icons', $fontawesome, array(), null);
    wp_register_style('iro-codes', iro_block_base_url() . 'build/style-index.css', array(), '3.0');
}

add_action('enqueue_block_editor_assets', 'iro_load_editor_block');
function iro_load_editor_block()
{
    $asset_file = include(iro_block_base_path() . 'build/index.asset.php');
    // 加载编辑器脚本
    wp_enqueue_script(
        'iroBlockEditor',
        iro_block_base_url() . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );
}

// 为代码块添加hljs语言标记language-支持
add_filter('render_block', function ($block_content, $block) {
    if ($block['blockName'] === 'core/code' && ! empty($block['attrs']['language'])) {
        $block_content = preg_replace(
            '/<code(.*?)>/',
            '<code$1 class="language-' . esc_attr($block['attrs']['language']) . '">',
            $block_content
        );
    }
    return $block_content;
}, 10, 2);
