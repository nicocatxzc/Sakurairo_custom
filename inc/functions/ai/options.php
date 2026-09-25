<?php

if (!defined('ABSPATH')) {
    exit;
}

// WordPress 7.0 以下没有内置 AI Client，不注册用不了的后台入口
if (!function_exists('iro_ai_generate')) {
    return;
}

function iro_ai_build_url(string $file): string
{
    return get_template_directory_uri() . '/inc/ai/build/' . $file;
}

// 资源加载
add_action('admin_enqueue_scripts', 'iro_ai_enqueue_assets');
function iro_ai_enqueue_assets(string $hook_suffix = ''): void
{
    // 编辑器页：post.php / post-new.php，且 post type 支持 editor
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_editor_screen = $screen
        && $screen->base === 'post'
        && post_type_supports($screen->post_type, 'editor');

    // 设置页：add_management_page 生成的 hook 是 tools_page_iro-ai
    $is_settings_screen = $hook_suffix === 'tools_page_iro-ai';

    if (!$is_editor_screen && !$is_settings_screen) {
        return;
    }

    echo '<script type="module" src="https://wordpress:5174/@vite/client"></script>';
    echo '<script type="module" src="https://wordpress:5174/src/main.js"></script>';

    // wp_enqueue_script('iro-ai', iro_ai_build_url('ai.js'), [], IRO_VERSION, true);

    // wp_enqueue_style(
    //     'iro-ai',
    //     iro_ai_build_url('ai.css'),
    //     [],
    //     IRO_VERSION
    // );

    // wp_add_inline_script(
    //     'iro-ai',
    //     'window.iroAiOptions = ' . wp_json_encode([
    //         'provider'   => iro_ai_provider_id(),
    //         'model'      => iro_ai_default_model(),
    //         'apiBase'    => iro_ai_api_base(),
    //         'configured' => iro_ai_api_key() !== '',
    //         // 面板工具项：接入工具时用该过滤器追加 ['id' => ..., 'label' => ...]
    //         'tools'      => apply_filters('iro_ai_panel_tools', []),
    //     ]) . ';',
    //     'before'
    // );
}

// 编辑器菜单
add_action('add_meta_boxes', 'iro_ai_register_meta_box');
function iro_ai_register_meta_box(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    foreach (get_post_types_by_support('editor') as $post_type) {
        add_meta_box(
            'iro_ai_tools',
            __('AI 工具', 'sakurairo'),
            'iro_ai_meta_box_render',
            $post_type,
            'side',
            'high'
        );
    }
}

function iro_ai_meta_box_render(): void
{
    echo '<div id="iro-ai-editor"></div>';
}

// 工具菜单
add_action('admin_menu', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }

    add_management_page(
        __('AI工具', 'sakurairo'),
        __('AI工具', 'sakurairo'),
        'edit_posts',
        'iro-ai',
        function () {
            echo '<div id="iro-ai-config"></div>';
        }
    );
});
