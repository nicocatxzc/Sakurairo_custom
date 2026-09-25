<?php
//rest api支持
function permalink_tip()
{
    if (!get_option('permalink_structure')) {
        $msg = __('<b>为了更好的使用体验，请不要将<a href="/wp-admin/options-permalink.php">固定链接</a>设置为朴素。为此，您可能需要配置<a href="https://www.wpdaxue.com/wordpress-rewriterule.html" target="_blank">伪静态</a>。</b>', 'sakurairo'); /**/
        echo '<div class="notice notice-success is-dismissible" id="scheme-tip"><p><b>' . $msg . '</b></p></div>';
    }
}
add_action('admin_notices', 'permalink_tip');

// 载入通用验证码方法
require_once get_template_directory() . '/inc/api/captcha.php';
require_once get_template_directory() . '/inc/api/turnstile.php';

/**
 * REST nonce 校验
 * nonce 由 frontend/theme_config.php 的 nonce 字段注入前端，经 X-WP-Nonce 头或 _wpnonce / nonce 参数传入
 * action 固定为 wp_rest：登录态请求的内核校验也用该 action，自定义 action 会在内核层被拒
 * 用法：'permission_callback' => 'iro_rest_check_nonce'
 *
 * @return true|WP_Error
 */
function iro_rest_check_nonce(WP_REST_Request $request)
{
    // 头给前端调用；_wpnonce 是内核也会读取的参数名，登录态用地址栏手动调试时不会被降级为 uid 0
    $nonce = $request->get_header('X-WP-Nonce')
        ?: $request->get_param('_wpnonce')
        ?: $request->get_param('nonce');

    if (empty($nonce)) {
        return new WP_Error(
            'iro_rest_nonce_missing',
            __('缺少 nonce 校验参数', 'sakurairo'),
            ['status' => 403]
        );
    }

    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error(
            'iro_rest_nonce_invalid',
            __('nonce 校验失败', 'sakurairo'),
            ['status' => 403]
        );
    }

    return true;
}

function iro_rest_check_permission(WP_REST_Request $request)
{
    if (!current_user_can('manage_options')) {
        return new WP_Error(
            'iro_ai_forbidden',
            __('仅管理员可使用 AI 接口。', 'sakurairo'),
            ['status' => 403]
        );
    }

    return iro_rest_check_nonce($request);
}

/**
 * 自定义api接口
 */
add_action('rest_api_init', function () {

    // 评论区api自定义
    require_once get_template_directory() . '/inc/api/comments.php';

    // 评论表情面板
    require_once get_template_directory() . '/inc/api/smiles.php';
    register_rest_route(
        'sakura/v1',
        '/comment/smiles',
        array(
            'methods' => 'GET',
            'callback' => 'iro_rest_get_smiley_packs',
            'permission_callback' => '__return_true'
        )
    );

    // 验证码接口
    register_rest_route(
        'sakura/v1',
        '/captcha',
        array(
            'methods' => 'GET',
            'callback' => 'iro_create_captcha',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/captcha',
        array(
            'methods' => 'POST',
            'callback' => 'iro_verify_captcha',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/captcha/turnstile',
        array(
            'methods' => 'POST',
            'callback' => 'iro_verify_turnstile_api',
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/search_index.php';
    register_rest_route(
        'sakura/v1',
        '/search_index',
        array(
            'methods' => 'GET',
            'callback' => 'iro_get_search_index',
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/bangumi.php';
    register_rest_route(
        'sakura/v1',
        '/bangumi/bangumi',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getBangumiList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 12
                );
            },
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/bangumi/bilibili',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getBilibiliList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 15,
                    (string) ($req->get_param('type') ?? 'bangumi')
                );
            },
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/bangumi/mal',
        array(
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                return IroAnimeList::getMyAnimeList(
                    (int) $req->get_param('page') ?: 1,
                    (int) $req->get_param('per_page') ?: 12
                );
            },
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/bilibili_favlist.php';
    register_rest_route(
        'sakura/v1',
        '/favlist/all',
        array(
            'methods' => 'GET',
            'callback' => function () {
                return iro_get_bilibili_favlist();
            },
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'sakura/v1',
        '/favlist/detail',
        array(
            'methods' => 'GET',
            'callback' => function (WP_REST_Request $req) {
                $fav_id = (int) $req->get_param('favId');
                $page   = (int) ($req->get_param('page') ?: 1);

                if (!$fav_id || $page < 1) {
                    return new WP_Error('invalid_params', '缺少必要参数', ['status' => 400]);
                }

                $data = iro_get_bilibili_fav_detail($fav_id, $page);

                if ($data === null) {
                    return new WP_Error('not_found', '不存在该收藏夹', ['status' => 404]);
                }

                return $data;
            },
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/steam.php';
    register_rest_route(
        'sakura/v1',
        '/steam',
        array(
            'methods' => 'GET',
            'callback' => function (WP_REST_Request $req) {
                return IroSteam::getSteamList(
                    (int) ($req->get_param('page') ?: 1),
                    (int) ($req->get_param('per_page') ?: 20)
                );
            },
            'permission_callback' => '__return_true'
        )
    );

    require_once get_template_directory() . '/inc/api/post_view.php';
    register_rest_route(
        'sakura/v1',
        '/post/views',
        array(
            'methods' => 'GET',
            'callback' => 'iro_rest_iro_get_post_views',
            'permission_callback' => 'iro_rest_check_nonce',
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                    'minimum'  => 1,
                ),
            ),
        )
    );
    register_rest_route(
        'sakura/v1',
        '/post/views',
        array(
            'methods' => 'POST',
            'callback' => 'iro_rest_iro_set_post_views',
            'permission_callback' => 'iro_rest_check_nonce',
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                    'minimum'  => 1,
                ),
            ),
        )
    );

    // AI 自检与测试对话（仅管理员，实现见 inc/api/ai.php）
    require_once get_template_directory() . '/inc/api/ai.php';
    register_rest_route(
        'sakura/v1',
        '/ai/selftest',
        array(
            'methods' => 'GET',
            'callback' => 'iro_ai_rest_selftest',
            'permission_callback' => 'iro_rest_check_permission',
        )
    );
    register_rest_route(
        'sakura/v1',
        '/ai/models',
        array(
            'methods' => 'GET',
            'callback' => 'iro_ai_rest_models',
            'permission_callback' => 'iro_rest_check_permission',
        )
    );
    register_rest_route(
        'sakura/v1',
        '/ai/chat',
        array(
            'methods' => 'POST',
            'callback' => 'iro_ai_rest_chat',
            'permission_callback' => 'iro_rest_check_permission',
        )
    );
});
