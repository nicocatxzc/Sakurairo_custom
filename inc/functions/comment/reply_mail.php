<?php
//评论回复
function sakura_comment_notify($comment_id)
{
    if (!isset($_POST['mail-notify'])) {
        update_comment_meta($comment_id, 'mail_notify', 'false');
    }
}
add_action('comment_post', 'sakura_comment_notify');
/*
 * 评论邮件回复
 */
function comment_mail_notify($comment_id)
{
    $mail_user_name = iro_opt('mail_user_name') ? iro_opt('mail_user_name') : 'no-reply';
    $comment = get_comment($comment_id);
    $parent_id = $comment->comment_parent ?: '';

    // 获取评论的审核状态，如果评论无需审核则直接发送
    $comment_approved = $comment->comment_approved;
    $admin_notify = (isset(get_comment($parent_id)->comment_author_email) && get_comment($parent_id)->comment_author_email) != get_bloginfo('admin_email') ? '1' : '0';

    if (($parent_id != '') && ($comment_approved === '1' || $comment_approved === 1) && ($admin_notify != '0')) {
        $wp_email = $mail_user_name . '@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
        $to = trim(get_comment($parent_id)->comment_author_email);

        // 主题主色调
        $theme_color = iro_opt('active_color') ?: '#FE9600';

        // 获取用户语言环境
        $comment_author_locale = get_comment_meta($parent_id, 'comment_author_locale', true);
        $locale = $comment_author_locale ?: get_locale();

        // 多语言支持
        switch ($locale) {
            case 'zh_TW':
                $subject = '你在 [' . get_option("blogname") . '] 的留言有了回應';
                $notification_title = '評論回覆通知';
                $dear = '親愛的';
                $new_reply = '您有一條來自';
                $new_reply_2 = '的回覆';
                $your_comment = '您在文章《';
                $your_comment_2 = '》上發表的評論：';
                $reply_to_you = '給您的回覆：';
                $view_complete = '查看完整對話';
                $auto_notify = '此郵件由系統自動發送，請勿直接回覆';
                break;
            case 'ja':
            case 'ja_JP':
                $subject = '[' . get_option("blogname") . '] のコメントに返信がありました';
                $notification_title = 'コメント返信通知';
                $dear = '尊敬する';
                $new_reply = 'からの新しい返信があります';
                $new_reply_2 = '';
                $your_comment = '記事「';
                $your_comment_2 = '」へのあなたのコメント：';
                $reply_to_you = 'さんからの返信：';
                $view_complete = '完全な会話を見る';
                $auto_notify = 'このメールはシステムによって自動的に送信されたものです。直接返信しないでください';
                break;
            case 'en_US':
            case 'en_GB':
                $subject = 'New Reply to Your Comment on [' . get_option("blogname") . ']';
                $notification_title = 'Comment Reply Notification';
                $dear = 'Dear';
                $new_reply = 'You have a new reply from';
                $new_reply_2 = '';
                $your_comment = 'Your comment on the article "';
                $your_comment_2 = '":';
                $reply_to_you = '\'s reply to you:';
                $view_complete = 'View Complete Conversation';
                $auto_notify = 'This email was automatically sent by the system, please do not reply directly';
                break;
            default: // 默认中文
                $subject = '你在 [' . get_option("blogname") . '] 的留言有了回应';
                $notification_title = '评论回复通知';
                $dear = '尊敬的';
                $new_reply = '您有一条来自';
                $new_reply_2 = '的回复';
                $your_comment = '您在文章《';
                $your_comment_2 = '》上发表的评论：';
                $reply_to_you = '给您的回复：';
                $view_complete = '查看完整对话';
                $auto_notify = '此邮件由系统自动发送，请勿直接回复';
                break;
        }

        // 现代化邮件模板
        $message = '
        <!DOCTYPE html>
        <html lang="' . str_replace('_', '-', $locale) . '">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $notification_title . '</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; color: #333; background-color: #f5f5f5;">
            <div style="max-width: 600px; margin: 20px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                <!-- 头部 -->
                <div style="background-color: ' . $theme_color . '; padding: 20px; text-align: center;">
                    <h1 style="color: #fff; margin: 0; font-size: 22px;">' . $notification_title . '</h1>
                </div>
                
                <!-- 内容区 -->
                <div style="padding: 20px;">
                    <p style="font-size: 16px; margin-top: 0;">' . $dear . ' <strong>' . trim(get_comment($parent_id)->comment_author) . '</strong>：</p>
                    <p style="font-size: 16px;">' . $new_reply . ' <a href="' . home_url() . '" style="color: ' . $theme_color . '; text-decoration: none; font-weight: bold;">' . get_option("blogname") . '</a> ' . $new_reply_2 . '</p>
                    
                    <!-- 您的评论 -->
                    <p style="font-size: 14px;">' . $your_comment . '<a href="' . get_permalink($comment->comment_post_ID) . '" style="color: ' . $theme_color . '; text-decoration: none; font-weight: bold;">' . get_the_title($comment->comment_post_ID) . '</a>' . $your_comment_2 . '</p>
                    <div style="margin: 15px 0; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #ddd; border-radius: 4px;">
                        <p style="margin: 0; font-size: 15px; color: #666;">' . trim(get_comment($parent_id)->comment_content) . '</p>
                    </div>
                    
                    <!-- 回复评论 -->
                    <p style="font-size: 14px;"><strong style="color: ' . $theme_color . ';">' . trim($comment->comment_author) . '</strong>' . $reply_to_you . '</p>
                    <div style="margin: 15px 0; padding: 15px; background-color: #f0f8ff; border-left: 4px solid ' . $theme_color . '; border-radius: 4px;">
                        <p style="margin: 0; font-size: 15px;">' . trim($comment->comment_content) . '</p>
                    </div>
                    
                    <!-- 查看回复按钮 -->
                    <div style="text-align: center; margin: 25px 0 15px;">
                        <a href="' . htmlspecialchars(get_comment_link($parent_id)) . '" style="background-color: ' . $theme_color . '; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; display: inline-block;">' . $view_complete . '</a>
                    </div>
                </div>
                
                <!-- 页脚 -->
                <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 13px; color: #999;">
                    <p style="margin: 5px 0;">' . $auto_notify . '</p>
                    <p style="margin: 5px 0;">&copy; ' . date('Y') . ' <a href="' . home_url() . '" style="color: ' . $theme_color . '; text-decoration: none;">' . get_option("blogname") . '</a></p>
                </div>
            </div>
        </body>
        </html>';

        // 处理表情符号和特殊格式
        $message = convert_smilies($message);
        $message = str_replace('{{', '<img src="' . iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . '/smilies/bilipng/emoji_', $message);
        $message = str_replace('}}', '.png" alt="emoji" style="height: 1.5em; max-height: 1.5em; vertical-align: middle;">', $message);

        // 处理图片
        $message = str_replace('{UPLOAD}', 'https://i.loli.net/', $message);
        $message = str_replace('[/img][img]', '[/img^img]', $message);
        $message = str_replace('[img]', '<img src="', $message);
        $message = str_replace('[/img]', '" style="max-width: 100%; height: auto; margin: 10px 0; border-radius: 4px;">', $message);
        $message = str_replace('[/img^img]', '" style="max-width: 100%; height: auto; margin: 10px 0; border-radius: 4px;"><img src="', $message);

        $from = 'From: "' . get_option('blogname') . "\" <$wp_email>";
        $headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
        wp_mail($to, $subject, $message, $headers);
    }
}
add_action('comment_post', 'comment_mail_notify');

/*
 * 评论通过审核时发送通知
 */
add_action('wp_set_comment_status', 'comment_status_changed_notify', 10, 2);

function comment_status_changed_notify($comment_id, $comment_status)
{
    if ($comment_status === 'approve') {
        comment_mail_notify($comment_id);
    }
}
