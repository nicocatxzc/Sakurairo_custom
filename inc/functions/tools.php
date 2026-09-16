<?php
//生成随机链接，防止浏览器缓存策略
function get_random_url(string $url): string
{
    $array = parse_url($url);
    if (!isset($array['query'])) {
        // 无参数
        $url .= '?';
    } else {
        // 有参数
        $url .= '&';
    }
    return $url . random_int(1, 100);
}

// default feature image
function DEFAULT_FEATURE_IMAGE()
{
    //使用独立外部api
    if (iro_opt('post_cover_options') == 'type_2') {
        $url = iro_opt('post_cover');
        return $url ? get_random_url($url) : '';
    }
    //使用内建
    if (iro_opt('random_graphs_options') == 'gallery') {
        $url = rest_url('sakura/v1/gallery') . '?img=w';
        return get_random_url($url);
    }
    //使用封面外部
    if (iro_opt('random_graphs_options') == 'external_api') {
        $url = iro_opt('random_graphs_link');
        return $url ? get_random_url($url) : '';
    }
    //意外情况
    $url = iro_opt('random_graphs_link');
    return $url ? get_random_url($url) : '';
}

// 获取访客 IP
function get_the_user_ip()
{
    // if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    //     //check ip from share internet
    //     $ip = $_SERVER['HTTP_CLIENT_IP'];
    // } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    //     //to check ip is pass from proxy
    //     $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    // } else {
    //     $ip = $_SERVER['REMOTE_ADDR'];
    // }
    // 简略版
    // $ip = $_SERVER['HTTP_CLIENT_IP'] ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR']);
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    return apply_filters('wpb_get_ip', $ip);
}

function iterator_to_string(Iterator $iterator): string
{
    $content = '';
    foreach ($iterator as $item) {
        $content .= $item;
    }
    return $content;
}

function check_title_tags($content)
{
    if (!empty($content)) {
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $headings = $dom->getElementsByTagName('h1');
        for ($i = 1; $i <= 6; $i++) {
            $headings = $dom->getElementsByTagName('h' . $i);
            foreach ($headings as $heading) {
                if (trim($heading->nodeValue) != '') {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * 返回是否应当显示文章标题。
 * 
 */
function should_show_title(): bool
{
    $id = get_the_ID();
    $use_as_thumb = get_post_meta($id, 'use_as_thumb', true); //'true','only',(default)
    return !iro_opt('patternimg')
        || !get_post_thumbnail_id($id)
        && $use_as_thumb != 'true' && !get_post_meta($id, 'video_cover', true);
}

/**
 * 获取用户UA信息
 */
// 浏览器信息
function siren_get_browsers(string $ua): array
{
    $title = 'Unknow';
    $icon = 'unknown';
    if (strpos($ua, 'Chrome')) {
        if (strpos($ua, 'Edg') && preg_match('#Edg/([0-9]+)#i', $ua, $matches)) {
            $title = 'Edge ' . $matches[1];
            $icon = 'edge';
        } elseif (strpos($ua, '360EE')) {
            $title = '360 Browser ';
            $icon = '360se';
        } elseif (strpos($ua, 'OPR') && preg_match('#OPR/([0-9]+)#i', $ua, $matches)) {
            $title = 'Opera ' . $matches[1];
            $icon = 'opera';
        } elseif (preg_match('#Chrome/([0-9]+)#i', $ua, $matches)) {
            $title = 'Chrome ' . $matches[1];
            $icon = 'chrome';
        }
    } elseif (strpos($ua, 'Firefox') && preg_match('#Firefox/([0-9]+)#i', $ua, $matches)) {
        $title = 'Firefox ' . $matches[1];
        $icon = 'firefox';
    } elseif (strpos($ua, 'Safari') && preg_match('#Safari/([0-9]+)#i', $ua, $matches)) {
        $title = 'Safari ' . $matches[1];
        $icon = 'safari';
    }

    return [
        'title' => $title,
        'icon' => $icon
    ];
}

// 操作系统信息
function siren_get_os(string $ua): array
{
    $title = 'Unknow';
    $icon = 'unknown';
    // UA样式决定strpos返回值不可能为0 所以不需要考虑为0的情况
    if (strpos($ua, 'Win')) {
        if (strpos($ua, 'Windows NT 10.0')) {
            $title = "Windows 10/11";
            $icon = "win10-11";
        } elseif (strpos($ua, 'Windows NT 6.1')) {
            $title = "Windows 7";
            $icon = "win7";
        } elseif (strpos($ua, 'Windows NT 6.2')) {
            $title = "Windows 8";
            $icon = "win8";
        } elseif (strpos($ua, 'Windows NT 6.3')) {
            $title = "Windows 8.1";
            $icon = "win8";
        }
    } elseif (strpos($ua, 'iPhone OS') && preg_match('#iPhone OS ([0-9]+)#i', $ua, $matches)) { // 1.2 修改成 iphone os 来判断 
        $title = "iOS " . $matches[1];
        $icon = "apple";
    } elseif (strpos($ua, 'Android') && preg_match('/Android.([0-9. _]+)/i', $ua, $matches)) {
        if (count(explode(7, $matches[1])) > 1) $matches[1] = 'Lion ' . $matches[1];
        elseif (count(explode(8, $matches[1])) > 1) $matches[1] = 'Mountain Lion ' . $matches[1];
        $title = $matches[0];
        $icon = "android";
    } elseif (strpos($ua, 'Mac OS') && preg_match('/Mac OS X[ _]?([0-9]+(?:[._][0-9]+)?)/i', $ua, $m)) {
        $ver = str_replace('_', '.', $m[1]);
        list($M, $mnr) = array_map('intval', array_pad(explode('.', $ver), 2, 0));
        $map = ['11' => 'Big Sur', '12' => 'Monterey', '13' => 'Ventura', '14' => 'Sonoma', '15' => 'Sequoia'];
        $key = "$M.$mnr";
        $maj = (string)$M;
        $code = $M < 10 || ($M === 10 && $mnr < 15) ? 'Catalina or Older' : ($M === 10 && $mnr === 15 ? 'Catalina' : ($map[$key] ?? $map[$maj] ?? ($M > 15 ? 'Sequoia or Higher' : '')));
        $title = 'macOS ' . ($M === 10 ? 'X ' : '') . "$code $ver";
        $icon = 'apple';
    } elseif (strpos($ua, 'Macintosh')) {
        $title = 'macOS';
        $icon  = 'apple';
    } elseif (strpos($ua, 'Linux')) {
        $title = 'Linux';
        $icon = 'linux';
    }
    return [
        'title' => $title,
        'icon' => $icon
    ];
}

function iro_check_bool($value)
{
    if (is_null($value)) return false;
    if (is_bool($value)) return $value;
    if (is_numeric($value) && !is_string($value)) {
        return !($value === 0 || $value === 0.0 || is_nan($value));
    }
    if (is_string($value)) return $value !== '';
    if (is_array($value)) return !empty($value);
    return true;
}

// 编码
function hachimi_encode_data($data)
{
    return esc_attr(
        wp_json_encode(
            $data,
            JSON_HEX_TAG |
                JSON_HEX_APOS |
                JSON_HEX_QUOT |
                JSON_HEX_AMP |
                JSON_UNESCAPED_UNICODE
        )
    );
}