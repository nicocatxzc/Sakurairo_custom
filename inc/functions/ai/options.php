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
    return get_template_directory_uri() . '/inc/ai/dist/' . $file;
}

// inc/ai 面板需要的接口地址与校验信息
function iro_ai_panel_config(): array
{
    return [
        'restUrl' => rest_url('sakura/v1'),
        'nonce' => wp_create_nonce('wp_rest'),
        'provider' => iro_ai_provider_id(),
        'model' => iro_ai_default_model(),
        'apiBase' => iro_ai_api_base(),
        'configured' => iro_ai_api_key() !== '',
        // 面板工具项：接入工具时用该过滤器追加 ['id' => ..., 'label' => ...]
        'tools' => apply_filters('iro_ai_panel_tools', []),
    ];
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

    // 配置以 JSON 注入，dev 与本地产物两种加载方式共用同一份
    // id 不能与挂载点 #iro-ai-config 同名，否则 Vue 会挂到该 <script> 上
    echo '<script id="iro_ai_config" type="application/json">'
        . wp_json_encode(iro_ai_panel_config(), JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        . '</script>';

    // 端口与 host 跟 inc/ai/vite.config.js 保持一致；dev 资源同样只给管理员
    if (iro_opt('dev_mode', false) && current_user_can('manage_options')) {
        echo '<script type="module" src="https://wordpress:5174/@vite/client"></script>';
        echo '<script type="module" src="https://wordpress:5174/src/main.js"></script>';
        return;
    }

    // Vite 产物是 ESM（含 import.meta / 动态 import），必须按模块脚本加载
    if (function_exists('wp_enqueue_script_module')) {
        wp_enqueue_script_module('iro-ai', iro_ai_build_url('main.js'), [], IRO_VERSION);
    } else {
        echo '<script type="module" src="' . esc_url(iro_ai_build_url('main.js') . '?ver=' . IRO_VERSION) . '"></script>';
    }

    wp_enqueue_style('iro-ai', iro_ai_build_url('style.css'), [], IRO_VERSION);
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

add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    add_management_page(
        __('AI工具', 'sakurairo'),
        __('AI工具', 'sakurairo'),
        'manage_options',
        'iro-ai',
        function () {
            echo '<div id="iro-ai-config"></div>';
        }
    );
});
