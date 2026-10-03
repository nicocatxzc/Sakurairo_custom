<?php
/**
 * 预取外链随机图接口的真实图片地址
 *
 * @param string $url 远端随机图接口地址
 * @return string 解析后的固定地址，失败时返回原地址
 */
function iro_random_img_fixed_url(string $url): string
{
    static $cache = [];

    if ($url === '' || isset($cache[$url])) {
        return $cache[$url] ?? $url;
    }

    $cache[$url] = $url;

    $key = 'iro_cover_url_' . md5($url);
    $hit = get_transient($key);

    if (is_string($hit) && $hit !== '') {
        return $cache[$url] = $hit;
    }

    // 只取跳转头
    $response = wp_remote_head($url, [
        'timeout'   => 3,
        'sslverify' => false,
    ]);

    if (is_wp_error($response)) {
        return $cache[$url];
    }

    $code  = (int) wp_remote_retrieve_response_code($response);
    $final = '';

    if ($code >= 300 && $code < 400) {
        // 远端可能返回相对路径（如 img/s15.webp），要按接口地址补成绝对地址。
        // 这里不能用 dirname()：接口地址常以斜杠结尾（.../imgs/mb/），dirname 会
        // 把最后一段吃掉变成 .../imgs，拼出 .../imgs/img/... 这种不存在的路径。
        $final = (string) wp_remote_retrieve_header($response, 'location');

        if ($final !== '' && !preg_match('#^https?://#i', $final)) {
            $final = rtrim($url, '/\\') . '/' . ltrim($final, '/');
        }
    } elseif ($code === 200) {
        $final = $url;
    }

    $final = $final === '' ? '' : esc_url_raw($final, ['http', 'https']);

    if ($final === '') {
        return $cache[$url];
    }

    set_transient($key, $final, 30 * DAY_IN_SECONDS);

    return $cache[$url] = $final;
}

if (iro_opt("iro_slow_net_optimize", true)) {
    header('Accept-CH: RTT, Save-Data, ECT, Downlink');
    $iro_is_slow_net = (function () {
        $rtt      = $_SERVER['HTTP_RTT'] ?? null;          // 延迟
        $saveData = $_SERVER['HTTP_SAVE_DATA'] ?? null;    // 节流模式
        $ect      = $_SERVER['HTTP_ECT'] ?? null;          // 网络环境
        $downlink = $_SERVER['HTTP_DOWNLINK'] ?? null;     // 下载速度

        if ($saveData === 'on') {
            return true;
        } elseif ($ect !== null && in_array($ect, ['slow-2g', '2g'], true)) {
            return true;
        } elseif ($rtt !== null && (int)$rtt > 200) {
            return true;
        } elseif ($downlink !== null && (float)$downlink < 1.5) {
            return true;
        }
        return false;
    })();

    global $iro_is_slow_net;

    if ($iro_is_slow_net) {
        global $iro_options;
        $iro_options["extra_fonts"] = [];
        $iro_options["frontend_particle"] = "off";
        $iro_options["particle_config"] = '';
        $iro_options["footer_hitokoto_select"] = 'off';
        $iro_options["footer_player_mode"] = 'off';
        $iro_options["post_card_image"] = 'only_feather_image';
        $iro_options["iro_image_quality"] = 70;
    }
}
