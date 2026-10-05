<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Github OAuth 的 admin-ajax 端点
 *
 * 登录页按钮、Github 回调、账户页绑定/解绑都是浏览器顶级跳转：
 * 前两者分别发生在未登录状态与 Github 站外，都拿不到 wp_rest nonce，
 * REST 那层的身份校验在这里没有意义，改用 ?action= 派发后按钮可以直接写成链接。
 */

function iro_github_oa_ajax_redirect(string $url, bool $external = false): void
{
    if ($external) {
        // Github 授权地址是站外地址，wp_safe_redirect 会把它拦下来
        wp_redirect($url);
    } else {
        wp_safe_redirect($url);
    }

    exit;
}

function iro_github_oa_ajax_authorize(): void
{
    if (!iro_github_oa_enabled()) {
        wp_die(esc_html__('Github OAuth 未启用。', 'sakurairo'), '', ['response' => 404]);
    }

    $intent = sanitize_key(wp_unslash($_GET['intent'] ?? '')) === 'bind' ? 'bind' : 'login';

    if ($intent === 'bind' && !is_user_logged_in()) {
        wp_die(esc_html__('请先登录后再绑定 Github 账号。', 'sakurairo'), '', ['response' => 403]);
    }

    iro_github_oa_ajax_redirect(
        iro_github_oa_begin($intent, $intent === 'login' ? (string) wp_unslash($_GET['redirect_to'] ?? '') : ''),
        true
    );
}

function iro_github_oa_ajax_callback(): void
{
    $payload = iro_github_oa_consume_state((string) wp_unslash($_GET['state'] ?? ''));
    $intent  = $payload['intent'] ?? 'login';

    if ($payload === null) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('state', 'login'));
    }

    if ('' !== (string) wp_unslash($_GET['error'] ?? '')) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('denied', $intent));
    }

    $code = (string) wp_unslash($_GET['code'] ?? '');

    if ('' === $code) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('state', $intent));
    }

    $token = iro_github_oa_access_token($code, (string) ($payload['verifier'] ?? ''));

    if (is_wp_error($token)) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('token', $intent));
    }

    $profile = iro_github_oa_profile($token);

    if (is_wp_error($profile)) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('profile', $intent));
    }

    if ($intent === 'bind') {
        $user_id = (int) ($payload['user_id'] ?? 0);

        iro_github_oa_ajax_redirect(
            iro_github_oa_notice_url(
                $user_id > 0 && !is_wp_error(iro_github_oa_bind($user_id, $profile)) ? 'bound' : 'taken',
                'bind'
            )
        );
    }

    $user_id = iro_github_oa_login($profile);

    if (is_wp_error($user_id)) {
        // 站点未开放注册
        if ('iro_github_oa_register_disabled' === $user_id->get_error_code()) {
            iro_github_oa_ajax_redirect(add_query_arg('registration', 'disabled', wp_login_url()));
        }

        iro_github_oa_ajax_redirect(
            iro_github_oa_notice_url(
                'iro_github_oa_email_missing' === $user_id->get_error_code() ? 'email' : 'login_failed',
                'login'
            )
        );
    }

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, is_ssl());

    iro_github_oa_ajax_redirect(wp_validate_redirect((string) ($payload['redirect_to'] ?? ''), home_url('/')));
}

function iro_github_oa_ajax_unbind(): void
{
    if (!wp_verify_nonce((string) wp_unslash($_GET['_wpnonce'] ?? ''), 'iro_github_oa_unbind')) {
        iro_github_oa_ajax_redirect(iro_github_oa_notice_url('unbind_failed', 'bind'));
    }

    iro_github_oa_unbind(get_current_user_id());

    iro_github_oa_ajax_redirect(iro_github_oa_notice_url('unbound', 'bind'));
}

function iro_github_oa_ajax_selfcheck(): void
{
    $app = iro_github_oa_app_info();

    if (is_wp_error($app)) {
        wp_send_json_error(['message' => $app->get_error_message()], 400);
    }

    wp_send_json_success(['app' => $app]);
}
