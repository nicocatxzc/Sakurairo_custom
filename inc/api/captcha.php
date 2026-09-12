<?php
require_once get_template_directory() . '/inc/libs/Captcha.php';

function iro_create_captcha(WP_REST_Request $request): WP_REST_Response
{
    $captcha = new Captcha();

    $result = $captcha->create_captcha_img();

    return new WP_REST_Response($result, 200);
}

function iro_verify_captcha(WP_REST_Request $request): WP_REST_Response
{
    $id = $request->get_param('captcha_id');
    $captchaCode = $request->get_param('captcha_text');

    $captcha = new Captcha();

    $result = $captcha->check_captcha(
        $captchaCode,
        $id
    );

    return new WP_REST_Response($result, 200);
}
