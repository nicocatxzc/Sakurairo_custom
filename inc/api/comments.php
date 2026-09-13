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

    $captcha = new Captcha();

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

                /**
                 * 保留你的协议
                 */
                'stat' => false,
                'data' => '',
                'msg'  => $result['msg']
                    ?? __('验证码校验失败', 'sakurairo'),
            ]
        );
    }

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
