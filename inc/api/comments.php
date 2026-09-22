<?php
require_once get_template_directory() . "/frontend/components/comment/card.php";
add_filter(
    'rest_allow_anonymous_comments',
    function ($allow, $request) {
        if ($request->get_route() !== '/wp/v2/comments') {
            return $allow;
        }

        return ! get_option('comment_registration');
    },
    10,
    2
);

// 评论验证码校验
add_filter(
    'rest_pre_insert_comment',
    'iro_rest_comment_captcha_check',
    10,
    2
);

function iro_rest_comment_captcha_check(
    $prepared_comment,
    WP_REST_Request $request
) {
    if (is_wp_error($prepared_comment)) {
        return $prepared_comment;
    }

    if (iro_opt("comment_captcha", "builtin") == "off") {
        return $prepared_comment;
    }

    // 已登陆，跳过验证
    if (is_user_logged_in()) {
        return $prepared_comment;
    }

    if (iro_opt("comment_captcha", "builtin") == "builtin") {
        $id = $request->get_param('captcha_id');
        $captchaCode = $request->get_param('captcha_text');

        if (!$id || !$captchaCode) {
            return new WP_Error(
                'captcha_required',
                __('Captcha verification required.', 'sakurairo'),
                [
                    'status' => 400,
                    'code'   => 5,
                    'data'   => '',
                    'msg'    => __('Captcha verification required.', 'sakurairo'),
                ]
            );
        }

        $captcha = new IroCaptcha();

        $result = $captcha->check_captcha(
            $captchaCode,
            $id
        );

        if (
            !is_array($result) ||
            $result["stat"] != true
        ) {
            return new WP_Error(
                'captcha_failed',
                $result['msg']
                    ?? __('验证码校验失败', 'sakurairo'),
                [
                    'status' => 400,
                    'stat' => false,
                    'data' => '',
                    'msg'  => $result['msg']
                        ?? __('验证码校验失败', 'sakurairo'),
                ]
            );
        }

        return $prepared_comment;
    }

    if (iro_opt("comment_captcha") == "turnstile") {

        $token = $request->get_param('turnstile_token');

        $result = iro_verify_turnstile($token);

        if ($result) {
            return $prepared_comment;
        } else {
            return new WP_Error(
                'captcha_failed',
                $result['msg']
                    ?? __('验证码校验失败', 'sakurairo'),
                [
                    'status' => 400,
                    'stat' => false,
                    'data' => '',
                    'msg'  => $result['msg']
                        ?? __('验证码校验失败', 'sakurairo'),
                ]
            );
        }
    }
}

// 登录态下先摘掉客户端提交的作者字段
add_filter(
    'rest_request_before_callbacks',
    'iro_rest_comment_strip_author_params',
    10,
    3
);

/**
 * 登录态下丢弃客户端提交的作者信息。
 *
 * 内核的 require_name_email 校验在 create_item() 里、早于 rest_pre_insert_comment，
 * 只带部分作者字段（例如只有 author_name）的请求会先撞 400；这里把三个字段清空，
 * 内核的 $missing_author 成立后就会用当前用户填充，再由下面的过滤器兜底覆盖。
 * 游客不受影响：他们的作者信息本来就是必需的。
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response 提前返回的响应。
 * @param array                                            $handler  命中的路由处理器。
 * @param WP_REST_Request                                  $request  当前请求。
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed
 */
function iro_rest_comment_strip_author_params($response, $handler, WP_REST_Request $request)
{
    if (!is_user_logged_in() || '/wp/v2/comments' !== $request->get_route()) {
        return $response;
    }

    foreach (['author_name', 'author_email', 'author_url'] as $key) {
        $request->set_param($key, '');
    }

    return $response;
}

// 登录用户的评论身份以服务端当前用户为准
add_filter(
    'rest_pre_insert_comment',
    'iro_rest_comment_force_current_user',
    10,
    2
);

/**
 * 覆盖登录用户的作者信息。
 * 内核 create_item() 只在 author/user_id 四个字段全空（$missing_author）时才用当前用户填充，
 * 客户端带上任意一个 author 字段就能伪造成别的昵称/邮箱；旧版 wp-comments-post.php 是
 * 无条件覆盖的（wp_handle_comment_submission()），这里把 REST 路径拉回同样的行为。
 *
 * @param array|WP_Error  $prepared_comment 待入库的评论数据。
 * @param WP_REST_Request $request          当前请求。
 * @return array|WP_Error
 */
function iro_rest_comment_force_current_user($prepared_comment, WP_REST_Request $request)
{
    if (!is_array($prepared_comment) || !is_user_logged_in() || '/wp/v2/comments' !== $request->get_route()) {
        return $prepared_comment;
    }

    $user = wp_get_current_user();

    $prepared_comment['user_id']              = $user->ID;
    $prepared_comment['comment_author']       = '' !== $user->display_name ? $user->display_name : $user->user_login;
    $prepared_comment['comment_author_email'] = $user->user_email;
    $prepared_comment['comment_author_url']   = $user->user_url;

    return $prepared_comment;
}

// ajax评论
add_filter(
    'rest_prepare_comment',
    'iro_rest_prepare_comment',
    10,
    3
);

function iro_rest_prepare_comment(
    WP_REST_Response $response,
    WP_Comment $comment,
    WP_REST_Request $request
) {
    ob_start();

    iro_comment_render($comment);

    $response->data['rendered'] = ob_get_clean();

    return $response;
}
