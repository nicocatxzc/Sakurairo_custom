<?php
if (!defined('ABSPATH')) {
    exit;
}

// 安全响应头
//
// 只做两个不会破坏功能的开关：
// - 点击劫持用 X-Frame-Options 而不是 CSP 的 frame-ancestors：后者会与站长自己的
//   Content-Security-Policy 合并生效，一旦写死就很难回退；
// - Referrer-Policy 用 same-origin 而不是 no-referrer：同源请求仍会带 referer，
//   后台与统计插件不受影响，只挡住外站看到本站来源。
// 其余如 HSTS、X-XSS-Protection、Permissions-Policy 会与站点环境或第三方资源冲突，不做成开关。
add_action('send_headers', function () {
    if (!is_admin() && iro_opt('iro_header_no_iframe', false)) {
        header('X-Frame-Options: SAMEORIGIN');
    }

    if (!is_admin() && iro_opt('iro_header_no_referrer', false)) {
        header('Referrer-Policy: same-origin');
    }
});
