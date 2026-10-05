<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Github OAuth 集成
 *
 * 走 Github App 的用户授权 Web 流程：?action=iro_github_oa_authorize 生成 state 与
 * PKCE verifier 后跳转 Github，Github 带 code 回到 ?action=iro_github_oa_callback，
 * 服务端用 client_id + client_secret 换用户访问令牌，再读 /user 与 /user/emails
 * 拿到 github_id 与已验证主邮箱。
 *
 * 全部端点走 admin-ajax
 *
 * github_oa_pem 不参与登录流程：Github 的用户令牌只能由 client_secret 换取，
 * 私钥仅用于以 App 自身身份签 JWT 调 /app 做配置自检。
 */

const IRO_GITHUB_OA_META_KEY = 'github_id';
const IRO_GITHUB_OA_NOTICE_KEY = 'iro_github_oa';

function iro_github_oa_client_id(): string
{
    return trim((string) iro_opt('github_oa_client_id', ''));
}

function iro_github_oa_client_secret(): string
{
    return trim((string) iro_opt('github_oa_client_secret', ''));
}

function iro_github_oa_enabled(): bool
{
    return (bool) iro_opt('github_oa_switch', false)
        && iro_github_oa_client_id() !== ''
        && iro_github_oa_client_secret() !== '';
}

function iro_github_oa_callback_url(): string
{
    return iro_github_oa_ajax_url('iro_github_oa_callback');
}

function iro_github_oa_ajax_url(string $action): string
{
    return admin_url('admin-ajax.php?action=' . $action);
}

/**
 * 授权入口地址，登录/注册页按钮与个人资料页「绑定」共用
 */
function iro_github_oa_authorize_entry(string $intent, string $redirect_to = ''): string
{
    $url = add_query_arg('intent', $intent, iro_github_oa_ajax_url('iro_github_oa_authorize'));

    return $redirect_to === '' ? $url : add_query_arg('redirect_to', $redirect_to, $url);
}

/**
 * 解绑入口，链接里带 nonce 防跨站触发
 */
function iro_github_oa_unbind_entry(): string
{
    return wp_nonce_url(iro_github_oa_ajax_url('iro_github_oa_unbind'), 'iro_github_oa_unbind');
}

/**
 * 把一次授权所需的临时数据（state 对应的 PKCE verifier、意图、回跳目标）存进 transient，
 * 返回 Github 授权地址。浏览器只需带回 state，回调时取回并立即删除，state 因此一次性。
 */
function iro_github_oa_begin(string $intent, string $redirect_to = ''): string
{
    $state    = wp_generate_password(32, false, false);
    $verifier = wp_generate_password(64, false, false);

    set_transient(
        'iro_gh_oa_' . $state,
        [
            'intent'      => $intent,
            'verifier'    => $verifier,
            'redirect_to' => $redirect_to,
            'user_id'     => get_current_user_id(),
        ],
        600
    );

    return add_query_arg(
        [
            'client_id'             => iro_github_oa_client_id(),
            'redirect_uri'          => iro_github_oa_callback_url(),
            'state'                 => $state,
            'code_challenge'        => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ],
        'https://github.com/login/oauth/authorize'
    );
}

function iro_github_oa_consume_state(string $state): ?array
{
    if ($state === '') {
        return null;
    }

    $payload = get_transient('iro_gh_oa_' . $state);

    delete_transient('iro_gh_oa_' . $state);

    return is_array($payload) ? $payload : null;
}

/**
 * Github 的响应一律要求带 User-Agent，且令牌接口要靠 Accept: application/json 才回 JSON
 */
function iro_github_oa_http(string $url, array $args = [], array $headers = []): array|WP_Error
{
    $response = wp_remote_request(
        $url,
        array_merge(
            $args,
            [
                'timeout' => 15,
                'headers' => array_merge(
                    [
                        'Accept'     => 'application/json',
                        'User-Agent' => 'Sakurairo/' . IRO_VERSION,
                    ],
                    $headers
                ),
            ]
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($data)) {
        return new WP_Error('iro_github_oa_bad_response', __('Github 返回了无法解析的响应。', 'sakurairo'));
    }

    // 令牌接口的错误是 200 + error 字段，这里只管 4xx/5xx，
    // 否则 /app 的 401 会被当成正常响应、返回一堆空字段
    $status = (int) wp_remote_retrieve_response_code($response);

    if ($status >= 400) {
        return new WP_Error(
            'iro_github_oa_http_error',
            sprintf(
                /* translators: 1: HTTP 状态码 2: Github 返回的错误说明 */
                __('Github 接口返回 HTTP %1$d：%2$s', 'sakurairo'),
                $status,
                (string) ($data['message'] ?? $data['error_description'] ?? $data['error'] ?? '')
            )
        );
    }

    return $data;
}

function iro_github_oa_access_token(string $code, string $verifier): string|WP_Error
{
    $data = iro_github_oa_http(
        'https://github.com/login/oauth/access_token',
        [
            'method' => 'POST',
            'body'   => [
                'client_id'     => iro_github_oa_client_id(),
                'client_secret' => iro_github_oa_client_secret(),
                'code'          => $code,
                'redirect_uri'  => iro_github_oa_callback_url(),
                'code_verifier' => $verifier,
            ],
        ]
    );

    if (is_wp_error($data)) {
        return $data;
    }

    if (empty($data['access_token'])) {
        return new WP_Error(
            'iro_github_oa_token_failed',
            $data['error_description'] ?? $data['error'] ?? __('换取 Github 访问令牌失败。', 'sakurairo')
        );
    }

    return (string) $data['access_token'];
}

/**
 * 读取 Github 用户身份，邮箱只认 /user/emails 里 primary + verified 的那一条
 */
function iro_github_oa_profile(string $token): array|WP_Error
{
    $headers = [
        'Authorization'        => 'Bearer ' . $token,
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    $user = iro_github_oa_http('https://api.github.com/user', [], $headers);

    if (is_wp_error($user)) {
        return $user;
    }

    if (empty($user['id'])) {
        return new WP_Error('iro_github_oa_profile_failed', __('Github 未返回用户身份信息。', 'sakurairo'));
    }

    $emails = iro_github_oa_http('https://api.github.com/user/emails', [], $headers);
    $email  = '';

    if (is_array($emails)) {
        foreach ($emails as $item) {
            if (!empty($item['primary']) && !empty($item['verified']) && !empty($item['email'])) {
                $email = (string) $item['email'];
                break;
            }
        }
    }

    if ($email === '' && !empty($user['email'])) {
        $email = (string) $user['email'];
    }

    return [
        'id'    => (string) $user['id'],
        'login' => (string) ($user['login'] ?? ''),
        'name'  => (string) ($user['name'] ?? ''),
        'email' => $email,
    ];
}

function iro_github_oa_user_by_github_id(string $github_id): ?WP_User
{
    $users = get_users(
        [
            'meta_key'   => IRO_GITHUB_OA_META_KEY,
            'meta_value' => $github_id,
            'number'     => 1,
        ]
    );

    return $users === [] ? null : $users[0];
}

/**
 * 先按 github_id 认领已绑定账号，再按已验证主邮箱认领，都没有才考虑新建
 * 
 * 新建有邮箱可用和站点开放注册的前提
 */
function iro_github_oa_login(array $profile): int|WP_Error
{
    $user = iro_github_oa_user_by_github_id($profile['id']);

    if ($user) {
        return (int) $user->ID;
    }

    if ($profile['email'] !== '') {
        $user = get_user_by('email', $profile['email']);

        if ($user) {
            return (int) $user->ID;
        }
    }

    if (!get_option('users_can_register')) {
        return new WP_Error('iro_github_oa_register_disabled', __('站点未开放注册。', 'sakurairo'));
    }

    if ($profile['email'] === '') {
        return new WP_Error('iro_github_oa_email_missing', __('Github 未提供已验证邮箱，无法创建新账号。', 'sakurairo'));
    }

    return iro_github_oa_register($profile);
}

function iro_github_oa_register(array $profile): int|WP_Error
{
    $login    = sanitize_user($profile['login'], true) ?: 'github';
    $username = $login;
    $suffix   = 1;

    while (username_exists($username)) {
        $username = $login . '-' . $suffix++;
    }

    $user_id = wp_create_user($username, wp_generate_password(32), $profile['email']);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    if ($profile['name'] !== '' || $profile['login'] !== '') {
        wp_update_user(
            [
                'ID'           => $user_id,
                'display_name' => $profile['name'] !== '' ? $profile['name'] : $profile['login'],
            ]
        );
    }

    update_user_meta($user_id, IRO_GITHUB_OA_META_KEY, $profile['id']);

    return (int) $user_id;
}

function iro_github_oa_bind(int $user_id, array $profile): true|WP_Error
{
    $bound = iro_github_oa_user_by_github_id($profile['id']);

    if ($bound && (int) $bound->ID !== $user_id) {
        return new WP_Error('iro_github_oa_id_taken', __('该 Github 账号已绑定到其他用户。', 'sakurairo'));
    }

    update_user_meta($user_id, IRO_GITHUB_OA_META_KEY, $profile['id']);

    return true;
}

function iro_github_oa_unbind(int $user_id): void
{
    delete_user_meta($user_id, IRO_GITHUB_OA_META_KEY);
}

/**
 * 不管设置页存下来的是单行还是带多余换行的多行，
 * 一律按 -----BEGIN/END----- 剥出正文重新折成 64 字符一行，否则 openssl 解析不了
 */
function iro_github_oa_normalize_pem(string $pem): string
{
    if (!preg_match('/-----BEGIN ([A-Z ]+)-----(.*?)-----END \1-----/s', trim($pem), $matches)) {
        return '';
    }

    return sprintf(
        "-----BEGIN %s-----\n%s\n-----END %s-----\n",
        $matches[1],
        implode("\n", str_split((string) preg_replace('/\s+/', '', $matches[2]), 64)),
        $matches[1]
    );
}

function iro_github_oa_app_jwt(): string|WP_Error
{
    if (!function_exists('openssl_sign')) {
        return new WP_Error('iro_github_oa_no_openssl', __('服务器未启用 openssl 扩展。', 'sakurairo'));
    }

    // 私钥不合法时 openssl 会打 Warning，直接进 REST 响应体就破坏了 JSON
    $private = @openssl_pkey_get_private(iro_github_oa_normalize_pem((string) iro_opt('github_oa_pem', '')));

    if ($private === false) {
        return new WP_Error('iro_github_oa_bad_key', __('Github App 私钥无法解析。', 'sakurairo'));
    }

    $encode  = static fn(array $claim): string => rtrim(strtr(base64_encode((string) wp_json_encode($claim)), '+/', '-_'), '=');
    $now     = time();
    $header  = $encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $payload = $encode(
        [
            'iat' => $now - 60,
            'exp' => $now + 540,
            // iss 可以是 App ID 也可以是 Client ID，未填 App ID 时用后者
            'iss' => trim((string) iro_opt('github_oa_appid', '')) ?: iro_github_oa_client_id(),
        ]
    );

    if (!openssl_sign($header . '.' . $payload, $signature, $private, OPENSSL_ALGO_SHA256)) {
        return new WP_Error('iro_github_oa_sign_failed', __('Github App 私钥签名失败。', 'sakurairo'));
    }

    return $header . '.' . $payload . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
}

function iro_github_oa_app_info(): array|WP_Error
{
    $jwt = iro_github_oa_app_jwt();

    if (is_wp_error($jwt)) {
        return $jwt;
    }

    $app = iro_github_oa_http(
        'https://api.github.com/app',
        [],
        [
            'Authorization'        => 'Bearer ' . $jwt,
            'X-GitHub-Api-Version' => '2022-11-28',
        ]
    );

    if (is_wp_error($app)) {
        return $app;
    }

    return [
        'id'        => (string) ($app['id'] ?? ''),
        'name'      => (string) ($app['name'] ?? ''),
        'slug'      => (string) ($app['slug'] ?? ''),
        'client_id' => (string) ($app['client_id'] ?? ''),
        'html_url'  => (string) ($app['html_url'] ?? ''),
    ];
}

/**
 * 探一次令牌端点确认 Client ID / Secret 可用
 *
 * Github 先校验凭证再校验 code，凭证不对才回 incorrect_client_credentials，
 * 返回别的错误码说明这对凭证本身已被接受。这样不需要通过真实的用户授权就能验证配置。
 */
function iro_github_oa_verify_client(): array|WP_Error
{
    if (iro_github_oa_client_id() === '' || iro_github_oa_client_secret() === '') {
        return new WP_Error('iro_github_oa_client_missing', __('Client ID 或 Client Secret 未填写。', 'sakurairo'));
    }

    $data = iro_github_oa_http(
        'https://github.com/login/oauth/access_token',
        [
            'method' => 'POST',
            'body'   => [
                'client_id'     => iro_github_oa_client_id(),
                'client_secret' => iro_github_oa_client_secret(),
                'code'          => 'iro-selfcheck',
            ],
        ]
    );

    if (is_wp_error($data)) {
        return $data;
    }

    if ('incorrect_client_credentials' === ($data['error'] ?? '')) {
        return new WP_Error('iro_github_oa_client_mismatch', __('Client ID 与 Client Secret 不匹配。', 'sakurairo'));
    }

    return [
        'error'       => (string) ($data['error'] ?? ''),
        'description' => (string) ($data['error_description'] ?? ''),
    ];
}

/**
 * 设置页自检项
 *
 * - App 身份：用 github_oa_pem 签 RS256 JWT（iss 取 github_oa_appid，未填则回落
 *   github_oa_client_id）请求 GET /app，验的是私钥与 App 是否配对。
 * - OAuth 凭证：拿无效 code 打一次令牌端点。Github 先校验 client_id/client_secret、
 *   再校验 code，凭证不对才回 incorrect_client_credentials，所以「不是这个错误」
 *   就说明凭证本身可用。
 *
 * @return array<int, array{label: string, ok: bool, detail: string}>
 */
function iro_github_oa_selfchecks(): array
{
    $app    = iro_github_oa_app_info();
    $client = iro_github_oa_verify_client();

    $checks = [
        [
            'label'  => __('App 身份（github_oa_appid / github_oa_pem）', 'sakurairo'),
            'ok'     => !is_wp_error($app),
            'detail' => is_wp_error($app) ? $app->get_error_message() : '',
        ],
        [
            'label'  => __('OAuth 凭证（github_oa_client_id / github_oa_client_secret）', 'sakurairo'),
            'ok'     => !is_wp_error($client),
            'detail' => is_wp_error($client) ? $client->get_error_message() : '',
        ],
    ];

    if (!is_wp_error($app)) {
        $checks[] = [
            'label'  => __('Client ID 比对（github_oa_client_id）', 'sakurairo'),
            'ok'     => iro_github_oa_client_id() === $app['client_id'],
            'detail' => iro_github_oa_client_id() === ''
                ? __('未填写', 'sakurairo')
                : sprintf(__('与 Github 返回的 %s 不一致', 'sakurairo'), $app['client_id']),
        ];
    }

    return $checks;
}

/**
 * 提示码 -> 文案，回调失败时只把码放进 query，避免把服务端信息回显到地址栏
 */
function iro_github_oa_notices(): array
{
    return [
        'bound'         => ['success', __('已绑定 Github 账号。', 'sakurairo')],
        'unbound'       => ['success', __('已解除 Github 账号绑定。', 'sakurairo')],
        'denied'        => ['error', __('你取消了 Github 授权。', 'sakurairo')],
        'state'         => ['error', __('授权会话已失效，请重新发起。', 'sakurairo')],
        'token'         => ['error', __('换取 Github 访问令牌失败，请检查 Client ID 与 Client Secret。', 'sakurairo')],
        'profile'       => ['error', __('读取 Github 用户信息失败。', 'sakurairo')],
        'taken'         => ['error', __('该 Github 账号已绑定到其他用户。', 'sakurairo')],
        'email'         => ['error', __('Github 未提供已验证邮箱，无法创建新账号。', 'sakurairo')],
        'login_failed'  => ['error', __('Github 登录失败，请稍后重试。', 'sakurairo')],
        'unbind_failed' => ['error', __('解除绑定失败，请重试。', 'sakurairo')],
    ];
}

function iro_github_oa_notice(): ?array
{
    $code = isset($_GET[IRO_GITHUB_OA_NOTICE_KEY])
        ? sanitize_key(wp_unslash($_GET[IRO_GITHUB_OA_NOTICE_KEY]))
        : '';

    $notices = iro_github_oa_notices();

    return $code === '' || !isset($notices[$code]) ? null : $notices[$code];
}

function iro_github_oa_notice_url(string $code, string $intent): string
{
    return add_query_arg(
        IRO_GITHUB_OA_NOTICE_KEY,
        $code,
        $intent === 'bind' ? admin_url('profile.php') : wp_login_url()
    );
}

function iro_github_oa_login_message(string $message): string
{
    $notice = iro_github_oa_notice();

    if ($notice === null) {
        return $message;
    }

    [$type, $text] = $notice;

    return $type === 'success'
        ? '<p class="message iro-github-oa-notice">' . esc_html($text) . '</p>' . $message
        : '<div id="login_error" class="iro-github-oa-notice">' . esc_html($text) . '</div>' . $message;
}
add_filter('login_message', 'iro_github_oa_login_message');

function iro_render_login_github_oa(): void
{
    if (!iro_github_oa_enabled()) {
        return;
    }

    $redirect_to = isset($_REQUEST['redirect_to']) ? wp_unslash((string) $_REQUEST['redirect_to']) : '';
?>
    <p class="iro-github-oa">
        <a class="iro-github-oa-button" href="<?= esc_url(iro_github_oa_authorize_entry('login', $redirect_to)) ?>">
            <i class="fa-icon-solid fa-github" aria-hidden="true"></i>
            <?= esc_html('register_form' === current_filter() ? __('使用 Github 注册', 'sakurairo') : __('使用 Github 登录', 'sakurairo')) ?>
        </a>
    </p>
<?php
}
add_action('login_form', 'iro_render_login_github_oa');
add_action('register_form', 'iro_render_login_github_oa');

function iro_github_oa_profile_fields(WP_User $user): void
{
    if (!iro_github_oa_enabled()) {
        return;
    }

    $github_id = (string) get_user_meta($user->ID, IRO_GITHUB_OA_META_KEY, true);
    $is_self   = (int) $user->ID === get_current_user_id();
?>
    <h2><?= esc_html__('Github OAuth', 'sakurairo') ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?= esc_html__('Github 账号', 'sakurairo') ?></th>
            <td>
                <?php if ($is_self && $github_id !== ''): ?>
                    <a class="button" href="<?= esc_url(iro_github_oa_unbind_entry()) ?>"><?= esc_html__('解除绑定', 'sakurairo') . ' ' . esc_html($github_id) ?></a>
                <?php elseif ($is_self): ?>
                    <a class="button" href="<?= esc_url(iro_github_oa_authorize_entry('bind')) ?>"><?= esc_html__('绑定 Github 账号', 'sakurairo') ?></a>
                <?php elseif ($github_id !== ''): ?>
                    <?= esc_html__('已绑定', 'sakurairo') . ' ' . esc_html($github_id) ?>
                <?php else: ?>
                    <?= esc_html__('未绑定', 'sakurairo') ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'iro_github_oa_profile_fields');
add_action('edit_user_profile', 'iro_github_oa_profile_fields');

function iro_github_oa_admin_notice(): void
{
    if (($GLOBALS['pagenow'] ?? '') !== 'profile.php') {
        return;
    }

    $notice = iro_github_oa_notice();

    if ($notice === null) {
        return;
    }

    [$type, $text] = $notice;

    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        esc_attr($type === 'success' ? 'success' : 'error'),
        esc_html($text)
    );
}
add_action('admin_notices', 'iro_github_oa_admin_notice');
