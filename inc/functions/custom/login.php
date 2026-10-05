<?php
// 后台登录页
if (iro_opt('login_custom_switch', false)) {
    function custom_login(): void
    {
?>
        <style type="text/css">
            body.login.iro-login-custom {
                --login-logo-image: url('<?= iro_opt('login_logo_img') ?: get_site_icon_url() ?>');
                --login-accent: <?= iro_opt('word_color_first') ?: '#FF69B4' ?>;
                --login-accent-hover: <?= iro_opt('active_color') ?: '#FF69B4' ?>;
            }

            <?php if (iro_opt("login_background_select", "off") == "with_cover"): ?>body.login.iro-login-custom {
                --login-background-image: var(--cover-background-img-pc, --cover-background-img-mb);
            }

            @media (max-width: 860px) {
                body.login.iro-login-custom {
                    --login-background-image: var(--cover-background-img-mb, --cover-background-img-pc);
                }
            }

            <?php endif; ?><?php if (iro_opt("login_background_select", "off") == "custom"): ?>body.login.iro-login-custom {
                --login-background-image: url('<?= iro_opt('login_background_image_url') ?>');
            }

            <?php endif; ?>
        </style>
    <?php
    }
    add_action('login_head', 'custom_login');

    /**
     * 给登录页 body 打上定制皮肤标记，login.scss 的规则挂在它上面；
     * 关闭定制时不加，登录页就能退回核心与 theme_style_vars.php 的默认外观。
     */
    function iro_login_custom_body_class(array $classes): array
    {
        $classes[] = 'iro-login-custom';

        return $classes;
    }
    add_filter('login_body_class', 'iro_login_custom_body_class');

    // Login Page Title
    function custom_headertitle($title)
    {
        return get_bloginfo('name');
    }
    add_filter('login_headertext', 'custom_headertitle');

    // Login Page Link
    function custom_loginlogo_url($url)
    {
        return esc_url(home_url('/'));
    }
    add_filter('login_headerurl', 'custom_loginlogo_url');
}

// 登录界面语言选项
if (iro_opt('login_language_opt') != true) {
    add_filter('login_display_language_dropdown', '__return_false');
}

// 登录页样式
function iro_login_style(): void
{
    $captcha_on = iro_opt("login_captcha_select", "builtin") != "off";
    $custom_skin = iro_opt('login_custom_switch', false);
    $github_oa_on = iro_github_oa_enabled();

    if (!$captcha_on && !$custom_skin && !$github_oa_on) {
        return;
    }

    if ($captcha_on) {
        // 验证码组件的配色取自主题的全局变量
        require get_template_directory() . '/frontend/theme_style_vars.php';
    }
    ?>
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/frontend/dist/login.css?ver=' . INT_VERSION ?>">
<?php
}

add_action('login_head', 'iro_login_style');

function iro_render_login_captcha(): void
{
?>
    <?php if (iro_opt("login_captcha_select", "builtin") != "off"): ?>
        <div class="captcha <?= iro_opt("login_captcha_select", "builtin") ?>"></div>
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/login.js?ver=' . INT_VERSION ?>"></script>
    <?php endif; ?>
<?php
}

add_action('login_form',        'iro_render_login_captcha');
add_action('register_form',     'iro_render_login_captcha');
add_action('lostpassword_form', 'iro_render_login_captcha');

/**
 * 登录/注册/找回密码验证码错误
 */
function iro_login_captcha_error($message = null)
{
    $message = $message ?: __('验证码校验失败', 'sakurairo');

    return new WP_Error(
        'captcha_failed',
        $message,
        [
            'status' => 400,
            'stat'   => false,
            'data'   => '',
            'msg'    => $message,
        ]
    );
}

/**
 * 校验登录相关表单验证码
 *
 * 返回：
 * - true       验证通过
 * - WP_Error   验证失败
 */
function iro_login_captcha_validate()
{
    $captchaType = iro_opt('login_captcha_select', 'builtin');

    // 关闭验证码
    if ($captchaType === 'off') {
        return true;
    }

    // 内置验证码
    if ($captchaType === 'builtin') {
        $id   = iro_get_post_key('captcha_id');
        $code = iro_get_post_key('captcha_text');

        if (!$id || !$code) {
            return iro_login_captcha_error(
                __('请填写验证码。', 'sakurairo')
            );
        }

        $captcha = new IroCaptcha();

        $result = $captcha->check_captcha(
            $code,
            $id
        );

        if (
            !is_array($result) ||
            empty($result['stat'])
        ) {
            return iro_login_captcha_error(
                is_array($result) && !empty($result['msg'])
                    ? $result['msg']
                    : __('验证码校验失败', 'sakurairo')
            );
        }

        return true;
    }

    // Cloudflare Turnstile
    if ($captchaType === 'turnstile') {
        $token = iro_get_post_key('turnstile_token');

        if (!$token) {
            return iro_login_captcha_error(
                __('请填写验证码。', 'sakurairo')
            );
        }

        $result = iro_verify_turnstile($token);
        if (is_array($result)) {
            if (isset($result['stat'])) {
                $success = (bool) $result['stat'];
            } else {
                $success = !empty($result['success']);
            }

            $message =
                $result['msg']
                ?? $result['message']
                ?? __('验证码校验失败', 'sakurairo');
        } else {
            $success = (bool) $result;
            $message = __('验证码校验失败', 'sakurairo');
        }

        if (!$success) {
            return iro_login_captcha_error($message);
        }

        return true;
    }

    // 未知验证码类型，默认拒绝
    return iro_login_captcha_error(
        __('验证码配置错误，请联系管理员。', 'sakurairo')
    );
}


/**
 * 登录验证码
 *
 * WP 登录认证入口：
 * authenticate
 *
 * @param WP_User|WP_Error|null $user
 * @param string               $username
 * @param string               $password
 */
function iro_login_captcha_check($user, $username, $password)
{
    // 只处理正常登录 POST
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
        !isset($_POST['log'])
    ) {
        return $user;
    }

    $result = iro_login_captcha_validate();

    if (is_wp_error($result)) {
        return $result;
    }

    return $user;
}

add_filter(
    'authenticate',
    'iro_login_captcha_check',
    99,
    3
);


/**
 * 注册验证码
 *
 * @param WP_Error $errors
 * @param string   $sanitized_user_login
 * @param string   $user_email
 */
function iro_register_captcha_check(
    $errors,
    $sanitized_user_login,
    $user_email
) {
    $result = iro_login_captcha_validate();

    if (is_wp_error($result)) {
        $errors->add(
            $result->get_error_code(),
            $result->get_error_message()
        );
    }

    return $errors;
}

add_filter(
    'registration_errors',
    'iro_register_captcha_check',
    10,
    3
);


/**
 * 找回密码验证码
 *
 * @param WP_Error $errors
 * @param WP_User|false $user_data
 */
function iro_lostpassword_captcha_check(
    $errors,
    $user_data
) {
    $result = iro_login_captcha_validate();

    if (is_wp_error($result)) {
        $errors->add(
            $result->get_error_code(),
            $result->get_error_message()
        );
    }

    return $errors;
}

add_filter(
    'lostpassword_errors',
    'iro_lostpassword_captcha_check',
    10,
    2
);
