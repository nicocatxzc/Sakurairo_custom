<?php
// 评论区等级
require_once get_template_directory() . '/inc/functions/comment/vip.php';
// 评论渲染回调
require_once get_template_directory() . '/inc/functions/comment/render.php';
// 评论区过滤
require_once get_template_directory() . '/inc/functions/comment/filter.php';
// 评论表情实现
require_once get_template_directory() . '/inc/functions/comment/smiles.php';
// 私密评论
require_once get_template_directory() . '/inc/functions/comment/private.php';
// 回复邮件
require_once get_template_directory() . '/inc/functions/comment/reply_mail.php';

/*
 * Ajax评论
 */
if (version_compare($GLOBALS['wp_version'], '4.4-alpha', '<')) {
    wp_die(__('Please upgrade wordpress to version 4.4+', 'sakurairo'));
}/*请升级到4.4以上版本*/
// 提示
if (!function_exists('siren_ajax_comment_err')) {
    function siren_ajax_comment_err($t)
    {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain;charset=UTF-8');
        echo $t;
        exit;
    }
}
// 机器评论验证
function comment_captcha()
{
    if (empty($_POST)) {
        return siren_ajax_comment_err(__('You may post nothing', 'sakurairo'));
    }
    if (iro_opt('comment_captcha_select') == "off") {
        return true;
    }
    if (is_user_logged_in()) { //登录后不需要验证
        return true;
    }
    if (iro_opt('comment_captcha_select') == "iro_captcha") {
        if (!(isset($_POST['captcha']) && !empty(trim($_POST['captcha'])))) {
            return siren_ajax_comment_err(__('Please fill in the captcha answer', 'sakurairo'));
        }
        if (!isset($_POST['timestamp']) || !isset($_POST['id']) || !preg_match('/^[\w$.\/]+$/', $_POST['id']) || !ctype_digit($_POST['timestamp'])) {
            return siren_ajax_comment_err(__('Have you modified the captcha code data? Or refresh the captcha and try again?', 'sakurairo'));
        }
        include_once(get_template_directory() . '/inc/classes/Captcha.php');
        $img = new Sakura\API\Captcha;
        $check = $img->check_captcha($_POST['captcha'], $_POST['timestamp'], $_POST['id']);
        if ($check['code'] == 5) {
            return true;
        }
        return siren_ajax_comment_err(__('Please fill in the correct captcha answer', 'sakurairo'));
    } else if (iro_opt('comment_captcha_select') == "turnstile") {
        if (!(isset($_POST['cf-turnstile-response']) && !empty(trim($_POST['cf-turnstile-response'])))) {
            return siren_ajax_comment_err(__('Please wait for cloudflare turnstile checking...', 'sakurairo'));
        }

        $token = sanitize_text_field($_POST['cf-turnstile-response']);
        $ip = get_the_user_ip();

        include_once(get_template_directory() . '/inc/classes/Turnstile.php');
        $turnstile = new Sakura\API\Turnstile;
        $response = $turnstile->verify($token, $ip);

        if ($response['success'] === false) {
            return siren_ajax_comment_err(__('Captcha verification failed', 'sakurairo'));
        }

        if (!$response['success']) {
            return siren_ajax_comment_err(__('Captcha verification failed', 'sakurairo'));
        }

        return true;
    } else {
        return siren_ajax_comment_err(__('Have you modified the captcha code data? Or refresh the captcha and try again?', 'sakurairo'));
    }
}
add_action('pre_comment_on_post', 'comment_captcha');

// 评论提交
if (!function_exists('siren_ajax_comment_callback')) {
    function siren_ajax_comment_callback()
    {
        $comment = wp_handle_comment_submission(wp_unslash($_POST));
        if (is_wp_error($comment)) {
            $data = $comment->get_error_data();
            if (!empty($data)) {
                siren_ajax_comment_err($comment->get_error_message());
            } else {
                if (count($_POST) <= 1) {
                    siren_ajax_comment_err("你好像提交了一个空表单。");
                } else {
                    siren_ajax_comment_err(join(',', array_keys($comment->errors)));
                }
            }
        }
        $user = wp_get_current_user();
        do_action('set_comment_cookies', $comment, $user);
        $GLOBALS['comment'] = $comment; //根据你的评论结构自行修改，如使用默认主题则无需修改
?>
        <li <?php comment_class(); ?> id="comment-<?php echo esc_attr(comment_ID()); ?>">
            <div class="contents">
                <div class="comment-arrow">
                    <div class="main shadow">
                        <div class="profile">
                            <a href="<?php comment_author_url(); ?>"><?php echo get_avatar($comment->comment_author_email, 80, '', get_comment_author()); ?></a>
                        </div>
                        <div class="commentinfo">
                            <section class="commeta">
                                <div class="left">
                                    <h4 class="author"><a href="<?php comment_author_url(); ?>"><?php echo get_avatar($comment->comment_author_email, 80, '', get_comment_author()); ?><?php comment_author(); ?> <span class="isauthor" title="<?php esc_attr_e('Author', 'sakurairo'); ?>"></span></a></h4>
                                </div>
                                <div class="right">
                                    <div class="info"><time datetime="<?php comment_date('Y-m-d'); ?>"><?php echo poi_time_since(strtotime($comment->comment_date), true); //comment_date(get_option('date_format')); 
                                                                                                        ?></time></div>
                                </div>
                            </section>
                        </div>
                        <div class="body">
                            <?php comment_text(); ?>
                        </div>
                    </div>
                    <div class="arrow-left"></div>
                </div>
            </div>
        </li>
<?php die();
    }
}
add_action('wp_ajax_nopriv_ajax_comment', 'siren_ajax_comment_callback');
add_action('wp_ajax_ajax_comment', 'siren_ajax_comment_callback');
