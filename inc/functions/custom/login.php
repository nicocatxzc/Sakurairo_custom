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

if (iro_opt('captcha_select') === 'iro_captcha') {
    function login_CAPTCHA()
    {
        include_once('inc/classes/Captcha.php');
        $img = new Sakura\API\Captcha;
        $test = $img->create_captcha_img();
        echo '<p><label for="captcha" class="captcha"><img id="captchaimg" width="120" height="40" style="border-radius: 8px;" src="', $test['data'], '"><input type="text" name="yzm" id="yzm" class="input" value="" size="20" tabindex="4" placeholder="请输入验证码"><input type="hidden" name="timestamp" value="', $test['time'], '"><input type="hidden" name="id" value="', $test['id'], '">'
            . "</label></p>";
    }
    add_action('login_form', 'login_CAPTCHA');
    add_action('register_form', 'login_CAPTCHA');
    add_action('lostpassword_form', 'login_CAPTCHA');

    /**
     * 登录界面验证码验证
     */
    function CAPTCHA_CHECK($user, $username, $password)
    {
        // Skip captcha check if it's a passwordless login
        if (isset($_POST['skip_captcha_check']) && $_POST['skip_captcha_check'] == '1') {
            return $user;
        }

        if (empty($_POST)) {
            return new WP_Error();
        }
        if (!(isset($_POST['yzm']) && !empty(trim($_POST['yzm'])))) {
            return new WP_Error('prooffail', '<strong>错误</strong>：验证码为空！');
        }
        if (!isset($_POST['timestamp']) || !isset($_POST['id']) || !preg_match('/^[\w$.\/]+$/', $_POST['id']) || !ctype_digit($_POST['timestamp'])) {
            return new WP_Error('prooffail', '<strong>错误</strong>：非法数据');
        }
        include_once('inc/classes/Captcha.php');
        $img = new Sakura\API\Captcha;
        $check = $img->check_captcha($_POST['yzm'], $_POST['timestamp'], $_POST['id']);
        if ($check['code'] == 5) {
            return $user;
        }
        return new WP_Error('prooffail', '<strong>错误</strong>：' . $check['msg']);
    }
    add_filter('authenticate', 'CAPTCHA_CHECK', 20, 3);

    // Add JavaScript to check for password field and toggle captcha visibility
    function add_captcha_check_script()
    {
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var loginForm = document.getElementById('loginform');
                if (!loginForm) return;

                // Add hidden field for skipping captcha check
                var hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'skip_captcha_check';
                hiddenField.id = 'skip_captcha_check';
                hiddenField.value = '0';
                loginForm.appendChild(hiddenField);

                // Get elements once at initialization
                var passwordField = document.getElementById('user_pass');
                var captchaImg = document.getElementById('captchaimg');
                var yzmField = document.getElementById('yzm');

                // Find the captcha container (the parent element that contains the captcha)
                var captchaContainer = null;
                if (yzmField) {
                    // Try to find the parent paragraph or label
                    captchaContainer = yzmField.closest('p') || yzmField.closest('label');
                    if (!captchaContainer && yzmField.parentNode) {
                        captchaContainer = yzmField.parentNode;
                    }
                }

                function checkPasswordField() {
                    // Check if password field is hidden or not present
                    var isPasswordVisible = passwordField &&
                        passwordField.style.display !== 'none' &&
                        passwordField.offsetParent !== null;

                    if (!isPasswordVisible) {
                        // Hide captcha elements
                        if (captchaContainer) {
                            captchaContainer.style.display = 'none';
                        }

                        hiddenField.value = '1';
                    } else {
                        // Show captcha elements
                        if (captchaContainer) {
                            captchaContainer.style.display = '';
                        }

                        hiddenField.value = '0';
                    }
                }

                // Initial check
                checkPasswordField();

                // Set up a less frequent interval to reduce performance impact
                var checkInterval = setInterval(checkPasswordField, 500);

                // Use MutationObserver for efficiency
                if (typeof MutationObserver !== 'undefined') {
                    var observer = new MutationObserver(checkPasswordField);

                    observer.observe(loginForm, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['style', 'class', 'display']
                    });
                }

                // Add event listener for form submission
                loginForm.addEventListener('submit', checkPasswordField);
            });
        </script>
    <?php
    }
    add_action('login_footer', 'add_captcha_check_script');
    /**
     * 忘记密码界面验证码验证
     */
    function lostpassword_CHECK($errors)
    {
        if (empty($_POST)) {
            return false;
        }
        if (isset($_POST['yzm']) && !empty(trim($_POST['yzm']))) {
            if (!isset($_POST['timestamp']) || !isset($_POST['id']) || !preg_match('/^[\w$.\/]+$/', $_POST['id']) || !ctype_digit($_POST['timestamp'])) {
                return new WP_Error('prooffail', '<strong>错误</strong>：非法数据');
            }
            include_once('inc/classes/Captcha.php');
            $img = new Sakura\API\Captcha;
            $check = $img->check_captcha($_POST['yzm'], $_POST['timestamp'], $_POST['id']);
            if ($check['code'] != 5) {
                return $errors->add('invalid_department ', '<strong>错误</strong>：' . $check['msg']);
            }
        } else {
            return $errors->add('invalid_department', '<strong>错误</strong>：验证码为空！');
        }
    }

    add_action('lostpassword_post', 'lostpassword_CHECK');
    /** 
     *   注册界面验证码验证
     */
    function registration_CAPTCHA_CHECK($errors, $sanitized_user_login, $user_email)
    {
        if (empty($_POST)) {
            return new WP_Error();
        }
        if (!(isset($_POST['yzm']) && !empty(trim($_POST['yzm'])))) {
            return new WP_Error('prooffail', '<strong>错误</strong>：验证码为空！');
        }
        if (!isset($_POST['timestamp']) || !isset($_POST['id']) || !preg_match('/^[\w$.\/]+$/', $_POST['id']) || !ctype_digit($_POST['timestamp'])) {
            return new WP_Error('prooffail', '<strong>错误</strong>：非法数据');
        }
        include_once('inc/classes/Captcha.php');
        $img = new Sakura\API\Captcha;
        $check = $img->check_captcha($_POST['yzm'], $_POST['timestamp'], $_POST['id']);
        if ($check['code'] == 5)
            return $errors;

        return new WP_Error('prooffail', '<strong>错误</strong>：' . $check['msg']);
    }
    add_filter('registration_errors', 'registration_CAPTCHA_CHECK', 2, 3);
} elseif ((iro_opt('captcha_select') === 'vaptcha') && (!empty(iro_opt("vaptcha_vid")) && !empty(iro_opt("vaptcha_key")))) {
    function vaptchaInit()
    {
        include_once('inc/classes/Vaptcha.php');
        $vaptcha = new Sakura\API\Vaptcha;
        echo $vaptcha->html();
        echo $vaptcha->script();
    }
    add_action('login_form', 'vaptchaInit');

    function checkVaptchaAction($user)
    {
        if (empty($_POST)) {
            return new WP_Error();
        }
        if (!(isset($_POST['vaptcha_server']) && isset($_POST['vaptcha_token']))) {
            return new WP_Error('prooffail', '<strong>错误</strong>：请先进行人机验证');
        }
        if (!preg_match('/^https:\/\/([\w-]+\.)+[\w-]*([^<>=?\"\'])*$/', $_POST['vaptcha_server']) || !preg_match('/^[\w\-\$]+$/', $_POST['vaptcha_token'])) {
            return new WP_Error('prooffail', '<strong>错误</strong>：非法数据');
        }
        include_once('inc/classes/Vaptcha.php');
        $url = $_POST['vaptcha_server'];
        $token = $_POST['vaptcha_token'];
        $ip = get_the_user_ip();
        $vaptcha = new Sakura\API\Vaptcha;
        $response = $vaptcha->checkVaptcha($url, $token, $ip);
        if ($response->msg && $response->success && $response->score) {
            if ($response->success === 1 && $response->score >= 70) {
                return $user;
            }
            if ($response->success === 0) {
                $errorcode = $response->msg;
                return new WP_Error('prooffail', '<strong>错误</strong>：' . $errorcode);
            }
            return new WP_Error('prooffail', '<strong>错误</strong>：人机验证失败');
        } else if (is_string($response)) {
            return new WP_Error('prooffail', '<strong>错误</strong>：' . $response);
        }
        return new WP_Error('prooffail', '<strong>错误</strong>：未知错误');
    }
    add_filter('authenticate', 'checkVaptchaAction', 20, 3);
} else if ((iro_opt('captcha_select') === 'turnstile') && (!empty(iro_opt("turnstile_site_key")) && !empty(iro_opt("turnstile_secret_key")))) {
    function turnstile_init()
    {
        include_once('inc/classes/Turnstile.php');
        $turnstile = new Sakura\API\Turnstile;
        echo $turnstile->html();
        echo $turnstile->script();
    }
    add_action('login_form', 'turnstile_init');
    add_action('register_form', 'turnstile_init');
    add_action('lostpassword_form', 'turnstile_init');

    function verify_turnstile($user, $username = '', $password = '')
    {
        // Skip captcha check if it's a passwordless login
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $user;
        }
        if (isset($_POST['skip_captcha_check']) && $_POST['skip_captcha_check'] == '1') {
            return $user;
        }

        if (empty($_POST['cf-turnstile-response'])) {
            return new WP_Error('invalid_turnstile', '<strong>错误</strong>: 请完成人机验证', 'sakurairo');
        }

        $secret_key = iro_opt('turnstile_secret_key');
        $token = sanitize_text_field($_POST['cf-turnstile-response']);
        $ip = get_the_user_ip();
        include_once('inc/classes/Turnstile.php');
        $turnstile = new Sakura\API\Turnstile;

        $response = $turnstile->verify($token, $ip);
        if ($response['success'] === false) {
            return new WP_Error('turnstile_error', '<strong>错误</strong>: 无法验证人机验证，请稍后再试', 'sakurairo');
        }

        if (!$response['success']) {
            return new WP_Error('invalid_turnstile', '<strong>错误</strong>: 人机验证失败', 'sakurairo');
        }

        return $user;
    }
    add_filter('authenticate', 'verify_turnstile', 20, 3);

    function turnstile_lostpassword_check($errors)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $errors;
        }
        if (empty($_POST['cf-turnstile-response'])) {
            $errors->add('invalid_turnstile', '<strong>错误</strong>: 请完成人机验证', 'sakurairo');
            return $errors;
        }

        $secret_key = iro_opt('turnstile_secret_key');
        $token = sanitize_text_field($_POST['cf-turnstile-response']);
        $ip = get_the_user_ip();

        include_once('inc/classes/Turnstile.php');
        $turnstile = new Sakura\API\Turnstile;
        $response = $turnstile->verify($token, $ip);

        if ($response['success'] === false) {
            $errors->add('turnstile_error', '<strong>错误</strong>: 无法验证人机验证，请稍后再试', 'sakurairo');
            return $errors;
        }

        if (!$response['success']) {
            $errors->add('invalid_turnstile', '<strong>错误</strong>: 人机验证失败', 'sakurairo');
        }

        return $errors;
    }
    add_action('lostpassword_post', 'turnstile_lostpassword_check');

    function turnstile_registration_check($errors, $sanitized_user_login, $user_email)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $errors;
        }
        if (empty($_POST['cf-turnstile-response'])) {
            $errors->add('invalid_turnstile', '<strong>错误</strong>: 请完成人机验证', 'sakurairo');
            return $errors;
        }

        include_once('inc/classes/Turnstile.php');
        $turnstile = new Sakura\API\Turnstile;
        $secret_key = iro_opt('turnstile_secret_key');
        $token = sanitize_text_field($_POST['cf-turnstile-response']);
        $ip = get_the_user_ip();

        $response = $turnstile->verify($token, $ip);

        if ($response['success'] === false) {
            $errors->add('turnstile_error', '<strong>错误</strong>: 无法验证人机验证，请稍后再试', 'sakurairo');
            return $errors;
        }

        if (!$response['success']) {
            $errors->add('invalid_turnstile', '<strong>错误</strong>: 人机验证失败', 'sakurairo');
        }

        return $errors;
    }
    add_filter('registration_errors', 'turnstile_registration_check', 10, 3);

    function add_captcha_check_script()
    {
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var loginForm = document.getElementById('loginform');
                if (!loginForm) return;

                // Add hidden field for skipping captcha check
                var hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'skip_captcha_check';
                hiddenField.id = 'skip_captcha_check';
                hiddenField.value = '0';
                loginForm.appendChild(hiddenField);

                // Get elements once at initialization
                var passwordField = document.getElementById('user_pass');
                var captchaImg = document.getElementById('captchaimg');
                var yzmField = document.getElementById('yzm');
                var turnstileWidget = document.querySelector('.cf-turnstile');

                // Find the captcha container (the parent element that contains the captcha)
                var captchaContainer = null;
                if (yzmField) {
                    // Try to find the parent paragraph or label
                    captchaContainer = yzmField.closest('p') || yzmField.closest('label');
                    if (!captchaContainer && yzmField.parentNode) {
                        captchaContainer = yzmField.parentNode;
                    }
                } else if (turnstileWidget) {
                    captchaContainer = turnstileWidget.parentNode;
                }

                function checkPasswordField() {
                    // Check if password field is hidden or not present
                    var isPasswordVisible = passwordField &&
                        passwordField.style.display !== 'none' &&
                        passwordField.offsetParent !== null;

                    if (!isPasswordVisible) {
                        // Hide captcha elements
                        if (captchaContainer) {
                            captchaContainer.style.display = 'none';
                        }

                        hiddenField.value = '1';
                    } else {
                        // Show captcha elements
                        if (captchaContainer) {
                            captchaContainer.style.display = '';
                        }

                        hiddenField.value = '0';
                    }
                }

                // Initial check
                checkPasswordField();

                // Set up a less frequent interval to reduce performance impact
                var checkInterval = setInterval(checkPasswordField, 500);

                // Use MutationObserver for efficiency
                if (typeof MutationObserver !== 'undefined') {
                    var observer = new MutationObserver(checkPasswordField);

                    observer.observe(loginForm, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['style', 'class', 'display']
                    });
                }

                // Add event listener for form submission
                loginForm.addEventListener('submit', checkPasswordField);
            });
        </script>
<?php
    }
}
