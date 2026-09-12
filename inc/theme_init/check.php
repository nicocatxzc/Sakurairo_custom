<?php

/**
 * 检查父主题文件夹名称是否正确
 * 如果名称不正确，尝试重命名或显示管理员警告信息
 */
function theme_folder_check_on_admin_init()
{
    // 获取当前父主题文件夹名称及路径
    $current_theme_path = get_template_directory();
    $theme_folder_name = basename($current_theme_path);
    $correct_theme_folder = 'Sakurairo';
    $user_locale = get_user_locale();

    // 仅管理员用户处理
    if (!current_user_can('manage_options')) {
        return;
    }

    // 当主题文件夹名称不正确时
    if ($theme_folder_name !== $correct_theme_folder) {
        $correct_theme_path = trailingslashit(dirname($current_theme_path)) . $correct_theme_folder;

        // 如果目标路径已存在
        if (file_exists($correct_theme_path)) {
            if (is_writable($correct_theme_path)) {
                $is_writable = true;
            } else {
                $is_writable = false;
            }
            add_action('admin_notices', function () use ($theme_folder_name, $correct_theme_folder, $user_locale, $is_writable) {
                switch ($user_locale) {
                    case 'zh_CN':
?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 当前父主题文件夹名称为 <code><?php echo esc_html($theme_folder_name); ?></code>，但目标名称 <code><?php echo esc_html($correct_theme_folder); ?></code> 已存在。请手动检查主题文件夹。</p>
                            <?php if ($is_writable) { ?> <br><a href="/wp-admin/admin.php?iro_act=del_exist_theme" class="page-title-action">点击此处立即删除重名主题</a> <?php } ?>
                        </div>
                    <?php
                        break;
                    case 'zh_TW':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 目前父主題資料夾名稱為 <code><?php echo esc_html($theme_folder_name); ?></code>，但目標名稱 <code><?php echo esc_html($correct_theme_folder); ?></code> 已存在。請手動檢查主題資料夾。</p>
                            <?php if ($is_writable) { ?> <br><a href="/wp-admin/admin.php?iro_act=del_exist_theme" class="page-title-action">點擊此處立即刪除重名的主題</a> <?php } ?>
                        </div>
                    <?php
                        break;
                    case 'ja':
                    case 'ja_JP':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 現在の親テーマフォルダ名は <code><?php echo esc_html($theme_folder_name); ?></code> ですが、対象の名前 <code><?php echo esc_html($correct_theme_folder); ?></code> は既に存在します。テーマフォルダを手動で確認してください。</p>
                            <?php if ($is_writable) { ?> <br><a href="/wp-admin/admin.php?iro_act=del_exist_theme" class="page-title-action">ここをクリックして、重複するテーマをすぐに削除します</a> <?php } ?>
                        </div>
                    <?php
                        break;
                    default:
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>Warning:</strong> The current parent theme folder name is <code><?php echo esc_html($theme_folder_name); ?></code>, but the target name <code><?php echo esc_html($correct_theme_folder); ?></code> already exists. Please manually check the theme folder.</p>
                            <?php if ($is_writable) { ?> <br><a href="/wp-admin/admin.php?iro_act=del_exist_theme" class="page-title-action">Click here to immediately delete the duplicate theme</a> <?php } ?>
                        </div>
                    <?php
                        break;
                }
            });
            return;
        }

        // 尝试重命名文件夹
        if (rename($current_theme_path, $correct_theme_path)) {
            switch_theme($correct_theme_folder);
        } else {
            add_action('admin_notices', function () use ($theme_folder_name, $correct_theme_folder, $user_locale) {
                switch ($user_locale) {
                    case 'zh_CN':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 当前父主题文件夹名称为 <code><?php echo esc_html($theme_folder_name); ?></code>，无法重命名为 <code><?php echo esc_html($correct_theme_folder); ?></code>。请检查文件系统权限。</p>
                        </div>
                    <?php
                        break;
                    case 'zh_TW':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 目前父主題資料夾名稱為 <code><?php echo esc_html($theme_folder_name); ?></code>，無法重新命名為 <code><?php echo esc_html($correct_theme_folder); ?></code>。請檢查檔案系統權限。</p>
                        </div>
                    <?php
                        break;
                    case 'ja':
                    case 'ja_JP':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 現在の親テーマフォルダ名は <code><?php echo esc_html($theme_folder_name); ?></code> ですが、<code><?php echo esc_html($correct_theme_folder); ?></code> にリネームできませんでした。ファイルシステムの権限を確認してください。</p>
                        </div>
                    <?php
                        break;
                    default:
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>Warning:</strong> The current parent theme folder name is <code><?php echo esc_html($theme_folder_name); ?></code>, and it cannot be renamed to <code><?php echo esc_html($correct_theme_folder); ?></code>. Please check the file system permissions.</p>
                        </div>
                    <?php
                        break;
                }
            });
        }
    }
    // 当主题文件夹名称正确时，检查目录权限
    else {
        if (!is_writable($current_theme_path)) {
            add_action('admin_notices', function () use ($current_theme_path, $user_locale) {
                switch ($user_locale) {
                    case 'zh_CN':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 当前主题目录 <code><?php echo esc_html($current_theme_path); ?></code> 不可写。请检查文件系统权限。</p>
                        </div>
                    <?php
                        break;
                    case 'zh_TW':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 目前主題目錄 <code><?php echo esc_html($current_theme_path); ?></code> 不可寫。請檢查檔案系統權限。</p>
                        </div>
                    <?php
                        break;
                    case 'ja':
                    case 'ja_JP':
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>警告：</strong> 現在のテーマディレクトリ <code><?php echo esc_html($current_theme_path); ?></code> は書き込み不可です。ファイルシステムの権限を確認してください。</p>
                        </div>
                    <?php
                        break;
                    default:
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong>Warning:</strong> The current theme directory <code><?php echo esc_html($current_theme_path); ?></code> is not writable. Please check the file system permissions.</p>
                        </div>
    <?php
                        break;
                }
            });
        }
    }
}

// 在后台初始化时执行检查
add_action('admin_init', 'theme_folder_check_on_admin_init');

/*
 * 后台通知
 */
/**
 * 在提供权限的情况下，为管理员用户显示通知并更新 meta 值
 */
function theme_admin_notice_callback()
{
    // 判断当前用户是否为管理员
    if (!current_user_can('manage_options')) {
        return;
    }

    // 读取 meta 值
    $meta_value = get_user_meta(get_current_user_id(), 'theme_admin_notice', true);

    // 判断 meta 值是否存在
    if ($meta_value) {
        return; // 如果存在，退出函数，避免重复加载通知
    }

    // 显示通知
    $theme_name = 'Sakurairo';
    switch (get_user_locale()) {
        case 'zh_CN':
            $thankyou = '感谢你使用 ' . $theme_name . ' 主题！这里有一些需要你的许可的东西(*/ω＼*)';
            $dislike = '不，谢谢';
            $allow_send = '允许发送你的主题版本数据以便官方统计';
            break;

        case 'zh_TW':
            $thankyou = '感謝你使用 ' . $theme_name . ' 主題！以下是一些需要你許可的內容。';
            $dislike = '謝謝，不用了';
            $allow_send = '允許出於統計目的發送主題版本数据';
            break;

        case 'ja':
        case 'ja_JP':
            $thankyou = 'ご使用いただきありがとうございます！以下は、あなたの許可が必要なコンテンツです。';
            $dislike = 'いいえ、結構です';
            $allow_send = '統計目的のためにあなたのテーマバージョンを送信することを許可する';
            break;

        default:
            $thankyou = 'Thank you for using the ' . $theme_name . ' theme! There is something that needs your approval.';
            $dislike = 'No, thanks';
            $allow_send = 'Allow sending your theme version for statistical purposes';
            break;
    }
    $theme_notice_nonce = wp_create_nonce('theme_admin_notice_action');
    ?>
    <div class="notice notice-success" id="send-ver-tip">
        <p><?php echo $thankyou; ?></p>
        <button class="button" onclick="dismiss_notice()"><?php echo $dislike; ?></button>
        <button class="button" onclick="update_option()"><?php echo $allow_send; ?></button>
    </div>
    <script>
        function dismiss_notice() {
            // 隐藏通知
            document.getElementById("send-ver-tip").style.display = "none";
            // 写入 1 到 meta
            var data = new FormData();
            data.append('action', 'update_theme_admin_notice_meta');
            data.append('user_id', '<?php echo get_current_user_id(); ?>');
            data.append('meta_key', 'theme_admin_notice');
            data.append('meta_value', '1');
            data.append('nonce', '<?php echo esc_js($theme_notice_nonce); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: data
            });
        }

        function update_option() {
            // 隐藏通知
            document.getElementById("send-ver-tip").style.display = "none";
            // 发送 AJAX 请求
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo admin_url('admin-ajax.php'); ?>", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("action=update_theme_option&option=send_theme_version&value=1&nonce=<?php echo esc_js($theme_notice_nonce); ?>");

            // 写入 1 到 meta
            var data = new FormData();
            data.append('action', 'update_theme_admin_notice_meta');
            data.append('user_id', '<?php echo get_current_user_id(); ?>');
            data.append('meta_key', 'theme_admin_notice');
            data.append('meta_value', '1');
            data.append('nonce', '<?php echo esc_js($theme_notice_nonce); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: data
            });
        }
    </script>
<?php
}
add_action('admin_notices', 'theme_admin_notice_callback');

// AJAX 处理函数 - 更新主题选项
add_action('wp_ajax_update_theme_option', 'update_theme_option');
function update_theme_option()
{
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden', '', array('response' => 403));
    }

    check_ajax_referer('theme_admin_notice_action', 'nonce');

    if (!isset($_POST['option']) || !isset($_POST['value'])) {
        wp_die('Missing required parameters');
    }

    $option = sanitize_key(wp_unslash($_POST['option']));
    $value = sanitize_text_field(wp_unslash($_POST['value']));

    if ($option !== 'send_theme_version') {
        wp_die('Invalid option', '', array('response' => 400));
    }

    $value = in_array($value, array('1', 'true', 'on'), true) ? '1' : '0';

    iro_opt_update($option, $value);
    wp_die();
}

// AJAX 处理函数 - 写入 theme_admin_notice 元值
add_action('wp_ajax_update_theme_admin_notice_meta', 'update_theme_admin_notice_meta');
function update_theme_admin_notice_meta()
{
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden', '', array('response' => 403));
    }

    check_ajax_referer('theme_admin_notice_action', 'nonce');

    if (!isset($_POST['user_id']) || !isset($_POST['meta_key']) || !isset($_POST['meta_value'])) {
        wp_die('Missing required parameters');
    }

    $user_id = absint(wp_unslash($_POST['user_id']));
    $meta_key = sanitize_key(wp_unslash($_POST['meta_key']));
    $meta_value = sanitize_text_field(wp_unslash($_POST['meta_value']));

    if ($user_id !== get_current_user_id() || $meta_key !== 'theme_admin_notice') {
        wp_die('Invalid target', '', array('response' => 400));
    }

    update_user_meta($user_id, $meta_key, $meta_value);
    wp_die();
}

//发送主题版本号 
function send_theme_version()
{
    $theme = wp_get_theme();
    $version = $theme->get('Version');
    $data = array(
        'date' => date('Y-m-d H:i:s'),
        'version' => $version
    );
    $args = array(
        'body' => $data,
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'cookies' => array()
    );
    wp_remote_post('https://api.fuukei.org/version-stat/index.php', $args);
}

if (iro_opt('send_theme_version') == '1') {
    if (!wp_next_scheduled('daily_event')) {
        wp_schedule_event(time(), 'daily', 'daily_event');
    }
    add_action('daily_event', 'send_theme_version');
}

/*
 * 检查主题版本号，并在更新主题后执行设置选项值的更新
 */
function visual_resource_updates($specified_version, $option_name, $new_value)
{
    $theme = wp_get_theme();
    $current_version = $theme->get('Version');

    // Check if the function has already been triggered
    $function_triggered = get_transient('visual_resource_updates_triggered20');
    if ($function_triggered) {
        return; // Function has already been triggered, do nothing
    }

    if (version_compare($current_version, $specified_version, '>')) {
        $option_value = iro_opt($option_name);
        if (empty($option_value)) {
            $option_value = "https://s.nmxc.ltd/sakurairo_vision/@3.0/";
        } else if (strpos($option_value, '@') === false || substr($option_value, strpos($option_value, '@') + 1) !== $new_value) {
            $option_value = preg_replace('/@.*/', '@' . $new_value, $option_value);
        }
        iro_opt_update($option_name, $option_value);

        // Set transient to indicate that the function has been triggered
        set_transient('visual_resource_updates_triggered20', true);
    }
}

visual_resource_updates('2.5.6', 'vision_resource_basepath', '3.0/');
