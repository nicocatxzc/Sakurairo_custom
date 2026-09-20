<?php
/*
 * 后台登录页
 */
if (iro_opt('custom_login_switch', false)) {
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
                background: <?php echo iro_opt('theme_skin') ?: '#FF69B4'; ?>;
                border-color: transparent;
                border-radius: 6px;
                padding: 1px 18px !important;
                transition: all 0.3s ease;
            }

            .wp-core-ui .button-primary:hover,
            #wp-webauthn:hover {
                background: <?php echo iro_opt('theme_skin_matching') ?: '#FF69B4'; ?>;
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
                color: <?php echo iro_opt('theme_skin') ?: '#FF69B4'; ?>;
            }

            #language-switcher {
                color: <?php echo iro_opt('theme_skin') ?: '#FF69B4'; ?>;
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

if (!iro_opt('login_language_opt') == '1') {
    add_filter('login_display_language_dropdown', '__return_false');
}
