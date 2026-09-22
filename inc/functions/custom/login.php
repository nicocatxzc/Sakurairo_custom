<?php
/*
 * 后台登录页
 */
if (iro_opt('login_custom_switch', false)) {
    // Add custom login styles
    function custom_login()
    {
?>
        <style type="text/css">
            body.login {
                background-image: url('<?php echo DEFAULT_FEATURE_IMAGE(); ?>');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed;
            }

            .login h1 a {
                background-image: url('<?php echo iro_opt('login_logo_img') ?: get_site_icon_url(); ?>') !important;
                background-size: contain;
                width: 100%;
                max-height: 100px;
            }

            .login form {
                box-shadow: 0 1px 30px -4px #e8e8e880;
                border: 1px solid #FFFFFF;
                background: rgba(255, 255, 255, 0.8);
                -webkit-backdrop-filter: saturate(180%) blur(10px);
                backdrop-filter: saturate(180%) blur(10px);
                border-radius: 10px;
            }

            .login form input[type=checkbox],
            .login input[type=password],
            .login input[type=text],
            .login input[type=email] {
                background: rgba(255, 255, 255, 0.7);
                box-shadow: 0 1px 30px -4px #e8e8e880;
                border: 1px solid #FFFFFF;
                -webkit-backdrop-filter: saturate(180%) blur(10px);
                backdrop-filter: saturate(180%) blur(10px);
                font-size: 15px;
                padding: 0.6rem;
                border-radius: 8px;
            }

            .wp-core-ui .button-primary,
            #wp-webauthn {
                background: <?php echo iro_opt('word_color_first') ?: '#FF69B4'; ?>;
                border-color: transparent;
                border-radius: 6px;
                padding: 1px 18px !important;
                transition: all 0.3s ease;
            }

            .wp-core-ui .button-primary:hover,
            #wp-webauthn:hover {
                background: <?php echo iro_opt('active_color') ?: '#FF69B4'; ?>;
                border-color: transparent;
                transition: all 0.3s ease;
            }

            .vaptchaContainer {
                margin: 5px 0 20px;
            }

            .login form .forgetmenot {
                margin-top: 6px;
            }

            .login .button.wp-hide-pw .dashicons {
                color: <?php echo iro_opt('word_color_first') ?: '#FF69B4'; ?>;
            }

            #language-switcher {
                color: <?php echo iro_opt('word_color_first') ?: '#FF69B4'; ?>;
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }

            .login #nav {
                font-size: 12px;
                padding: 8px 12px;
                background: rgba(255, 255, 255, 0.7);
                box-shadow: 0 1px 30px -4px #e8e8e8;
                border: 1px solid #FFFFFF;
                -webkit-backdrop-filter: saturate(180%) blur(10px);
                backdrop-filter: saturate(180%) blur(10px);
                width: fit-content;
                border-radius: 8px;
                margin: auto;
                margin-top: -13%;
            }

            .login #backtoblog {
                display: none;
            }

            .captcha {
                display: flex !important;
                align-items: center;
                margin-bottom: 20px !important;
                margin-top: 10px;
                gap: 10px;
            }

            .login form input[name=yzm] {
                margin: 0;
            }

            .login label {
                margin-bottom: 5px;
            }

            .wp-webauthn-notice {
                height: 40px !important;
                margin-bottom: 15px;
            }

            #wp-webauthn span {
                color: #fff;
            }

            .vp-dark-btn.vp-basic-btn {
                border-radius: 8px !important;
            }
        </style>
    <?php
    }
    add_action('login_head', 'custom_login');

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

if (iro_opt('login_language_opt') == true) {
    add_filter('login_display_language_dropdown', '__return_false');
}

// 验证码样式
function iro_login_captcha_style(): void
{
    if (iro_opt("login_captcha_select", "builtin") == "off") {
        return;
    }
    require get_template_directory() . '/frontend/theme_style_vars.php';
    ?>
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/frontend/dist/captcha.css?ver=' . INT_VERSION ?>">
<?php
}

add_action('login_head', 'iro_login_captcha_style');

function iro_render_login_captcha(): void
{
?>
    <?php if (iro_opt("login_captcha_select", "builtin") != "off"): ?>
        <div class="captcha <?= iro_opt("login_captcha_select", "builtin") ?>"></div>
        <script type="module" src="<?= get_template_directory_uri() . '/frontend/dist/captcha.js?ver=' . INT_VERSION ?>"></script>
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
                __('Captcha verification required.', 'sakurairo')
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
                __('Captcha verification required.', 'sakurairo')
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
