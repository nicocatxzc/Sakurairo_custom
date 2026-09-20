<?php
function iro_verify_turnstile(string $token)
{
    $secret_key = iro_opt('turnstile_secret_key');
    $response = wp_safe_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 15,
        'body' => [
            'secret' => $secret_key,
            'response' => $token,
            'remoteip' => '183.213.72.249',
            // 'remoteip' => iro_get_user_ip(),
        ],
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // 检查success字段
    return isset($body['success']) && $body['success'] === true;
}
