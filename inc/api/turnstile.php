<?php
function iro_verify_turnstile(string $token)
{
    $secret_key = iro_opt('turnstile_secret_key');
    $response = wp_safe_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 15,
        'body' => [
            'secret' => $secret_key,
            'response' => $token,
            'remoteip' => iro_get_user_ip(),
        ],
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // 检查success字段
    return isset($body['success']) && $body['success'] === true;
}

function iro_verify_turnstile_api(WP_REST_Request $request): WP_REST_Response
{
    $token = $request->get_param('turnstile_token');

    $result = iro_verify_turnstile($token);

    return new WP_REST_Response($result, 200);
}
