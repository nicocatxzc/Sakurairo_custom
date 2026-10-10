<?php
/**
 * 注入给前台的界面语言
 *
 * 前台直接问多语言模块要「本次请求的语言」，而不是 `get_locale()`：本文件在 `functions.php`
 * 载入期就被求值，语言判定是模块自己的事，这样不依赖此刻已注册了哪些 locale 过滤器。
 * 后台与登录页仍走 `get_locale()`（那里的界面语言由用户设置决定）。
 */
function iro_get_theme_config_language(): string
{
    if (function_exists('iro_i18n_current_language') && iro_is_frontend()) {
        return iro_i18n_locale(iro_i18n_current_language());
    }

    return get_locale();
}

function iro_get_basic_theme_config(): array
{
    return [
        'language' => esc_js(str_replace('-', '_', iro_get_theme_config_language())),
        'api'      => esc_url_raw(rest_url()),
        'ajaxurl'  => admin_url('admin-ajax.php'),
        'iro_api'  => esc_url_raw(rest_url('sakura/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
    ];
}

function iro_basic_theme_config(): void
{
?>
    <script id="iro_theme_config" type="application/json">
        <?= json_encode(iro_get_basic_theme_config(), JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
<?php
}
add_action('admin_head', 'iro_basic_theme_config');
add_action('login_head', 'iro_basic_theme_config');

function iro_front_theme_config()
{
    $iro_theme_config = array_merge(iro_get_basic_theme_config(), [
        'typed_config' => iro_opt("cover_typedjs_config"),
        'particle' => [
            'select' => iro_opt("frontend_particle"),
            'builtin' => iro_opt("frontend_particle_builtin"),
            'config' => iro_opt("frontend_particle") == 'custom' ? iro_opt("particle_config") : [] // 省流
        ],
        'extract_theme_skin_from_cover' => iro_opt("extract_theme_skin_from_cover", true),
        'extract_article_highlight_from_feature' => iro_opt("extract_article_highlight_from_feature", true),
        'post_cover_as_background' => iro_opt("post_cover_as_background", false),
        'cover_random_pic_url_pc' => iro_opt("cover_random_pic_url_pc"),
        'cover_random_pic_url_mb' => iro_opt("cover_random_pic_url_mb"),
        'pagination_mode' => iro_opt("pagination_mode", "pagination"),
        'pagination_ajax_wait' => iro_opt("pagination_ajax_wait", 3),
        'missing_images' => iro_opt('missing_images_placeholder'),
        'missing_avatars' => iro_opt("missing_avatars_placeholder"),
        'bangumi_source' => iro_opt("bangumi_source"),
        'lightbox' => iro_opt("lightbox", "medium_zoom"),
        'code_highlight' => iro_opt("code_highlight_method", "hljs"),
        'code_katex' => iro_opt('code_katex', true),
        'turnstile_site_key' => iro_opt("turnstile_site_key"),
        'hitokoto_apis' => json_decode((string) iro_opt("footer_hitokoto_api"), true) ?: ['https://v1.hitokoto.cn/'],
        'player' => footer_player_frontend_config(),
        'slow_net_optimize' => iro_opt("iro_slow_net_optimize", false)
    ]);
?>

    <?php
    $post = get_post();
    $iro_page_config = [
        "post_id" => get_the_ID(),
        "post_image" => iro_media_optimize_image_url(get_the_post_thumbnail_url($post, 'full')),
        "is_home" => is_home(),
        "is_singular" => is_singular(),
        // 导航栏在 pjax 容器之外，语言列表得随每次页面加载一起换，所以挂在页面配置里
        "langs" => iro_i18n_enabled() ? iro_i18n_language_links() : [],
    ];
    ?>

    <?php
    $user = wp_get_current_user();
    $iro_user_config = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'roles' => $user->roles,
        'description' => get_the_author_meta('description', $user->ID),
        'slug' => $user->user_nicename,
        'avatar' => [
            'url_96' => get_avatar_url($user->ID, ['size' => 96]),
            'url_150' => get_avatar_url($user->ID, ['size' => 150]),
            'url_300' => get_avatar_url($user->ID, ['size' => 300]),
        ]
    ];
    ?>
    <script id="iro_theme_config" type="application/json">
        <?= json_encode($iro_theme_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script id="iro_page_config" type="application/json">
        <?= json_encode($iro_page_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script id="iro_user_config" type="application/json">
        <?= json_encode($iro_user_config, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>
    </script>
<?php
}
add_action('wp_enqueue_scripts', 'iro_front_theme_config', 1);
