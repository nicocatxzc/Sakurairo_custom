<?php

/**
 * 主题内置 SMTP 邮件支持。
 *
 * 集成思路来自 mariokernich/wordpress-smtp-plugin（WP Simple SMTP，GPLv2）：
 * 在此基础上补充了：
 * - 修正原插件 wp_mail_from 与 wp_mail_from_name 取值颠倒的问题
 * - 兼容主题选项里的 none/TLS/SSL 加密方式
 * - 将发信过程与失败原因写入 temp/smtp.log.php
 * - 将每次发信内容按时间戳留存到 temp/smtp/*.php
 * - 所有留存文件均以 .php 结尾，并在头部写入 <?php exit; ?>，防止被直接读取
 *
 * @link https://github.com/mariokernich/wordpress-smtp-plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 主题 temp 目录。
 */
function iro_smtp_temp_dir(): string
{
    return trailingslashit(get_template_directory()) . 'temp';
}

/**
 * 邮件内容留存目录。
 */
function iro_smtp_store_dir(): string
{
    return iro_smtp_temp_dir() . '/smtp';
}

/**
 * 留存文件的防读取头。
 */
function iro_smtp_guard(): string
{
    return "<?php exit; ?>\n";
}

/**
 * 写入一条 SMTP 日志，便于排查发信问题。
 *
 * @param string $message 日志内容
 * @param string $level   日志级别（INFO/WARN/ERROR）
 * @return bool
 */
function iro_smtp_log(string $message, string $level = 'INFO'): bool
{
    $dir = iro_smtp_temp_dir();
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        error_log('Sakurairo SMTP: ' . $message);
        return false;
    }

    $prefix = sprintf('[%s] [%s] ', current_time('Y-m-d H:i:s'), strtoupper($level));
    $output = '';
    foreach (preg_split('/\r\n|\r|\n/', $message) as $line) {
        $output .= $prefix . $line . "\n";
    }

    $log_file = $dir . '/smtp.log.php';
    if (!file_exists($log_file)) {
        $output = iro_smtp_guard() . $output;
    }

    if (false === @file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX)) {
        error_log('Sakurairo SMTP: ' . $message);
        return false;
    }
    return true;
}

/**
 * 确保留存目录存在，并写入防目录浏览的占位文件。
 */
function iro_smtp_ensure_store_dir(): bool
{
    $dir = iro_smtp_store_dir();
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return false;
    }

    $guard = $dir . '/index.php';
    if (!file_exists($guard)) {
        @file_put_contents($guard, "<?php\n// Silence is golden.\n");
    }

    return true;
}

/**
 * 生成以时间戳为基准且不冲突的文件名。
 */
function iro_smtp_store_filename(string $dir): string
{
    $base = current_time('Y-m-d_H-i-s');
    $name = $base . '.php';
    $suffix = 1;
    while (file_exists($dir . '/' . $name)) {
        $name = $base . '-' . $suffix . '.php';
        $suffix++;
    }
    return $name;
}

/**
 * 留存一封邮件的内容，返回文件名；失败返回空字符串。
 */
function iro_smtp_store_mail(array $mail): string
{
    if (!iro_smtp_ensure_store_dir()) {
        iro_smtp_log('无法创建邮件留存目录：' . iro_smtp_store_dir(), 'ERROR');
        return '';
    }

    $dir = iro_smtp_store_dir();
    $name = iro_smtp_store_filename($dir);

    $to = $mail['to'] ?? '';
    if (is_array($to)) {
        $to = implode(', ', $to);
    }
    $meta = sprintf(
        "<!--\n发送时间: %s\n收件人: %s\n主题: %s\n-->\n",
        current_time('Y-m-d H:i:s'),
        str_replace('--', '-', (string) $to),
        str_replace('--', '-', (string) ($mail['subject'] ?? ''))
    );

    $content = iro_smtp_guard() . $meta . (string) ($mail['message'] ?? '');
    if (false === @file_put_contents($dir . '/' . $name, $content, LOCK_EX)) {
        iro_smtp_log('邮件内容留存失败：' . $dir . '/' . $name, 'ERROR');
        return '';
    }
    return $name;
}

/**
 * 汇总当前发送周期的 SMTP 会话记录。
 */
function iro_smtp_debug_transcript(): string
{
    $debug = $GLOBALS['iro_smtp_debug'] ?? [];
    if (empty($debug) || !is_array($debug)) {
        return '';
    }
    return implode("\n", $debug);
}

/**
 * 生成排版精美的测试邮件 HTML。
 */
function iro_smtp_test_mail_html(): string
{
    $site_name = esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
    $site_url = esc_url(home_url());
    $from_name = esc_html((string) iro_opt('smtp_from_name'));
    $from_address = esc_html((string) iro_opt('smtp_from_address'));
    $host = esc_html((string) iro_opt('smtp_host'));
    $port = esc_html((string) iro_opt('smtp_port'));
    $time = esc_html(current_time('Y-m-d H:i:s'));
    $wp_version = esc_html(get_bloginfo('version'));
    $php_version = esc_html(PHP_VERSION);

    $crypt = strtolower((string) iro_opt('smtp_crypt'));
    if ($crypt === 'ssl') {
        $crypt_label = 'SSL';
    } elseif ($crypt === 'tls') {
        $crypt_label = 'TLS';
    } else {
        $crypt_label = __('无', 'sakurairo');
    }

    $rows = [
        __('SMTP 服务器', 'sakurairo') => $host . ':' . $port,
        __('加密方式', 'sakurairo') => $crypt_label,
        __('发件人', 'sakurairo') => sprintf('%s <%s>', $from_name, $from_address),
        __('站点', 'sakurairo') => sprintf('%s（%s）', $site_name, $site_url),
        __('运行环境', 'sakurairo') => sprintf('WordPress %s · PHP %s', $wp_version, $php_version),
        __('发送时间', 'sakurairo') => $time,
    ];

    $rows_html = '';
    $last = count($rows) - 1;
    $index = 0;
    foreach ($rows as $label => $value) {
        $background = ($index % 2 === 0) ? '#f8fafc' : '#ffffff';
        $border = ($index === $last) ? 'none' : '1px solid #e2e8f0';
        $rows_html .= sprintf(
            '<tr style="background-color:%s;"><td style="padding:12px 16px;font-size:14px;color:#64748b;white-space:nowrap;border-bottom:%s;">%s</td><td style="padding:12px 16px;font-size:14px;color:#1e293b;word-break:break-all;border-bottom:%s;">%s</td></tr>',
            $background,
            $border,
            esc_html($label),
            $border,
            $value
        );
        $index++;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMTP 测试邮件</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif,'PingFang SC','Hiragino Sans GB','Microsoft YaHei';color:rgba(0,0,0,0.88);-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #f0f0f0;border-radius:8px;overflow:hidden;box-shadow:0 1px 2px 0 rgba(0,0,0,0.03),0 1px 6px -1px rgba(0,0,0,0.02),0 2px 4px 0 rgba(0,0,0,0.02);">
                    <!-- 头部 -->
                    <tr>
                        <td style="padding:40px 40px 24px;text-align:center;border-bottom:1px solid #f0f0f0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td width="56" height="56" align="center" valign="middle" style="width:56px;height:56px;background-color:#e6f4ff;border-radius:50%;font-size:26px;line-height:56px;text-align:center;">&#128238;</td>
                                </tr>
                            </table>
                            <h1 style="margin:20px 0 8px;font-size:20px;font-weight:600;line-height:1.4;color:rgba(0,0,0,0.88);">SMTP 测试邮件</h1>
                            <p style="margin:0;font-size:14px;line-height:1.5715;color:rgba(0,0,0,0.45);">{$site_name} · 邮件通道连通性验证</p>
                        </td>
                    </tr>

                    <!-- 正文 -->
                    <tr>
                        <td style="padding:24px 40px 0;">
                            <p style="margin:0 0 8px;font-size:14px;line-height:1.5715;color:rgba(0,0,0,0.88);">你好，</p>
                            <p style="margin:0;font-size:14px;line-height:1.5715;color:rgba(0,0,0,0.65);">如果你正在阅读这封邮件，说明主题的 SMTP 邮件通道已经可以正常工作。以下是本次发送使用的配置摘要：</p>
                        </td>
                    </tr>

                    <!-- 配置摘要 -->
                    <tr>
                        <td style="padding:20px 40px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fafafa;border:1px solid #f0f0f0;border-radius:8px;overflow:hidden;font-size:14px;line-height:1.5715;color:rgba(0,0,0,0.88);">
{$rows_html}
                            </table>
                        </td>
                    </tr>

                    <!-- 说明与按钮 -->
                    <tr>
                        <td style="padding:24px 40px 0;">
                            <p style="margin:0 0 20px;font-size:13px;line-height:1.5715;color:rgba(0,0,0,0.45);">本邮件由主题在发送测试时自动生成，无需回复。若你并未发起此次测试，可忽略本邮件。</p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:6px;background-color:#1677ff;">
                                        <a href="{$site_url}" style="display:inline-block;padding:8px 20px;font-size:14px;font-weight:400;line-height:1.5715;color:#ffffff;text-decoration:none;border-radius:6px;">访问站点</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 页脚 -->
                    <tr>
                        <td style="padding:32px 40px 40px;">
                            <p style="margin:0;padding-top:20px;border-top:1px solid #f0f0f0;font-size:12px;line-height:1.6667;color:rgba(0,0,0,0.45);">来自 {$site_name} · {$site_url}</p>
                            <p style="margin:4px 0 0;font-size:12px;line-height:1.6667;color:rgba(0,0,0,0.25);">发送时间：{$time}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * 发送一封测试邮件。
 *
 * @param string $to 收件地址
 * @return array{success:bool,error:string,to:string}
 */
function iro_smtp_test(string $to): array
{
    $result = ['success' => false, 'error' => '', 'to' => $to];

    if (!is_email($to)) {
        $result['error'] = __('收件地址不是有效的邮箱地址。', 'sakurairo');
        iro_smtp_log('测试邮件失败：非法收件地址 ' . $to, 'ERROR');
        return $result;
    }

    $GLOBALS['iro_smtp_last_error'] = '';
    $GLOBALS['iro_smtp_debug'] = [];

    $subject = sprintf(
        /* translators: %s: 站点名称 */
        __('【%s】SMTP 测试邮件', 'sakurairo'),
        wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
    );
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    iro_smtp_log('开始发送测试邮件 → ' . $to, 'INFO');
    $sent = wp_mail($to, $subject, iro_smtp_test_mail_html(), $headers);

    if ($sent) {
        $result['success'] = true;
        return $result;
    }

    $error = (string) ($GLOBALS['iro_smtp_last_error'] ?? '');
    $result['error'] = $error !== '' ? $error : __('邮件发送失败，请查看 temp/smtp.log.php 中的详细原因。', 'sakurairo');
    return $result;
}

// 仅在主题 SMTP 开关开启时接管 WordPress 的发信流程。
if (!iro_opt('smtp_switch')) {
    return;
}

/**
 * 校正发件人地址
 */
add_filter('wp_mail_from', static function ($from) {
    $configured = (string) iro_opt('smtp_from_address');
    return $configured !== '' ? $configured : $from;
});

/**
 * 校正发件人名称。
 */
add_filter('wp_mail_from_name', static function ($name) {
    $configured = (string) iro_opt('smtp_from_name');
    return $configured !== '' ? $configured : $name;
});

/**
 * 接管 PHPMailer 的 SMTP 配置。
 */
add_action('phpmailer_init', static function ($phpmailer) {
    $username = (string) iro_opt('smtp_username');
    $crypt = strtolower((string) iro_opt('smtp_crypt', 'tls'));
    if ($crypt === 'none') {
        $crypt = '';
    }

    $GLOBALS['iro_smtp_debug'] = [];

    $phpmailer->isSMTP();
    $phpmailer->Host = (string) iro_opt('smtp_host');
    $phpmailer->Port = (int) iro_opt('smtp_port');
    $phpmailer->SMTPAuth = $username !== '';
    $phpmailer->Username = $username;
    $phpmailer->Password = (string) iro_opt('smtp_key');
    $phpmailer->SMTPSecure = $crypt;
    $phpmailer->SMTPAutoTLS = $crypt !== '';
    $phpmailer->Mailer = 'smtp';
    $phpmailer->From = (string) iro_opt('smtp_from_address');
    $phpmailer->FromName = (string) iro_opt('smtp_from_name');
    $phpmailer->ClearReplyTos();
    $phpmailer->addReplyTo(
        (string) iro_opt('smtp_from_address'),
        (string) iro_opt('smtp_from_name')
    );

    // 收集 SMTP 会话用于失败排查；回调会吞掉原生输出，避免污染页面。
    $phpmailer->SMTPDebug = 2;
    $phpmailer->Debugoutput = static function ($str, $level) {
        $GLOBALS['iro_smtp_debug'][] = sprintf('[%s] %s', $level, trim((string) $str));
    };
});

/**
 * 发信前记录日志并留存邮件内容。
 */
add_filter('wp_mail', static function ($args) {
    if (!is_array($args)) {
        return $args;
    }
    $to = $args['to'] ?? '';
    if (is_array($to)) {
        $to = implode(', ', $to);
    }
    $subject = (string) ($args['subject'] ?? '');

    $file = iro_smtp_store_mail($args);
    iro_smtp_log(sprintf('尝试发送邮件 → %s | 主题：%s', (string) $to, $subject), 'INFO');
    if ($file !== '') {
        iro_smtp_log('邮件内容已留存：temp/smtp/' . $file, 'INFO');
    }
    return $args;
});

/**
 * 记录发送成功。
 */
add_action('wp_mail_succeeded', static function ($mail_data) {
    $to = $mail_data['to'] ?? '';
    if (is_array($to)) {
        $to = implode(', ', $to);
    }
    iro_smtp_log(sprintf('发送成功 → %s | 主题：%s', (string) $to, (string) ($mail_data['subject'] ?? '')), 'INFO');
});

/**
 * 记录发送失败与 SMTP 会话详情。
 */
add_action('wp_mail_failed', static function ($error) {
    $message = ($error instanceof WP_Error) ? $error->get_error_message() : (string) $error;
    $GLOBALS['iro_smtp_last_error'] = $message;
    iro_smtp_log('发送失败：' . ($message !== '' ? $message : '未知错误'), 'ERROR');

    $debug = iro_smtp_debug_transcript();
    if ($debug !== '') {
        iro_smtp_log("SMTP 会话记录：\n" . $debug, 'ERROR');
    }
});
