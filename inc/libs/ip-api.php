<?php

/**
 * 单个接口允许的最大并发请求数。
 *
 * 计数借 IroGlobalState 落在对象缓存里，跨请求共享。
 */
const IRO_IP_API_MAX_CONCURRENCY = 10;

/**
 * 查询 IPv4 的地理位置。
 *
 * 按 ip_api_select 中启用的接口顺序依次尝试，某个接口失败时回退到下一个。
 * 返回值按「国家 省级 市级」拼接，缺失的层级直接省略；全部失败返回空字符串。
 *
 * 查询语言跟随站点语言（见 iro_ip_api_language()），接口不支持的语种回退英文。
 *
 * @param string $ipv4 待查询的 IPv4 地址。
 * @return string
 */
function iro_get_ip_address(string $ipv4): string
{
    $ipv4 = trim($ipv4);

    if (!filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }

    $enabled = iro_opt('ip_api_select', [
        'ip_api' => true,
        'ip_data' => true,
        'ip_whois' => true,
        'ip_sb' => true,
        'pconline' => true,
    ]);

    foreach (iro_ip_api_providers() as $key => $resolver) {
        if (empty($enabled[$key])) {
            continue;
        }

        $location = iro_ip_api_locate($key, $resolver, $ipv4);

        if ($location !== '') {
            return $location;
        }
    }

    return '';
}

/**
 * 站点语言对应的接口语言代码。
 *
 * ip-api.com 只提供 en / de / es / pt-BR / fr / ja / zh-CN / ru，其余语言回退英文，
 * 避免无条件按中文请求。
 *
 * @return string
 */
function iro_ip_api_language(): string
{
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

    $languages = [
        'de' => 'de',
        'es' => 'es',
        'fr' => 'fr',
        'ja' => 'ja',
        'pt' => 'pt-BR',
        'ru' => 'ru',
        'zh' => 'zh-CN',
    ];

    return $languages[strtolower(substr($locale, 0, 2))] ?? 'en';
}

/**
 * 接口名与解析函数的对应关系，数组顺序即回退顺序。
 *
 * @return array<string, callable>
 */
function iro_ip_api_providers(): array
{
    return [
        'ip_api' => 'iro_ip_api_lookup_ipapi',
        'ip_data' => 'iro_ip_api_lookup_ipdata',
        'ip_whois' => 'iro_ip_api_lookup_ipwhois',
        'ip_sb' => 'iro_ip_api_lookup_ipsb',
        'pconline' => 'iro_ip_api_lookup_pconline',
    ];
}

/**
 * 在并发配额内调用单个接口。
 *
 * 配额已满时不排队，直接视为该接口本次不可用，交由上层回退到其他接口。
 *
 * @param string   $key      接口名，用于区分各接口的并发计数。
 * @param callable $resolver 解析函数。
 * @param string   $ipv4     待查询的 IPv4 地址。
 * @return string
 */
function iro_ip_api_locate(string $key, callable $resolver, string $ipv4): string
{
    $state = iroGlobalState('ip_api_' . $key);

    if ($state->current() >= IRO_IP_API_MAX_CONCURRENCY) {
        return '';
    }

    $state->add();

    try {
        return iro_ip_api_format($resolver($ipv4));
    } finally {
        $state->remove();
    }
}

/**
 * 把「国家 / 省级 / 市级」拼成单行字符串。
 *
 * @param array $parts
 * @return string
 */
function iro_ip_api_format(array $parts): string
{
    $parts = array_map(static fn($part) => trim((string) $part), $parts);
    $parts = array_filter($parts, static fn($part) => $part !== '');

    return implode(' ', $parts);
}

/**
 * 请求接口并解析 JSON。
 *
 * @param string $url      接口地址。
 * @param string $encoding 响应编码，非空时先转成 UTF-8（pconline 返回 GBK）。
 * @return array
 */
function iro_ip_api_request(string $url, string $encoding = ''): array
{
    $response = wp_remote_get($url, [
        'timeout' => 5,
        'redirection' => 2,
        'user-agent' => 'Sakurairo/' . IRO_VERSION . '; ' . home_url('/'),
    ]);

    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return [];
    }

    $body = trim(wp_remote_retrieve_body($response));

    if ($encoding !== '') {
        $body = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($body, 'UTF-8', $encoding)
            : iconv($encoding, 'UTF-8//IGNORE', $body);
    }

    $data = json_decode($body, true);

    return is_array($data) ? $data : [];
}

/**
 * ip-api.com
 *
 * 文档：https://ip-api.com/docs/api:json
 *
 * @param string $ipv4
 * @return array
 */
function iro_ip_api_lookup_ipapi(string $ipv4): array
{
    $data = iro_ip_api_request('http://ip-api.com/json/' . $ipv4 . '?lang=' . rawurlencode(iro_ip_api_language()) . '&fields=status,message,country,regionName,city');

    if (($data['status'] ?? '') !== 'success') {
        return [];
    }

    return [$data['country'] ?? '', $data['regionName'] ?? '', $data['city'] ?? ''];
}

/**
 * ipdata.info
 *
 * 文档：https://github.com/ihmily/ip-info-api/blob/main/README.md#address-1.20
 *
 * @param string $ipv4
 * @return array
 */
function iro_ip_api_lookup_ipdata(string $ipv4): array
{
    $data = iro_ip_api_request('https://ipdata.info/json/' . $ipv4);

    if (empty($data['success'])) {
        return [];
    }

    return [$data['country'] ?? '', $data['region'] ?? '', $data['city'] ?? ''];
}

/**
 * ipwhois.app
 *
 * 文档：https://github.com/ihmily/ip-info-api/blob/main/README.md#address-1.18
 *
 * @param string $ipv4
 * @return array
 */
function iro_ip_api_lookup_ipwhois(string $ipv4): array
{
    $data = iro_ip_api_request('https://ipwhois.app/json/' . $ipv4 . '?format=json');

    if (empty($data['success'])) {
        return [];
    }

    return [$data['country'] ?? '', $data['region'] ?? '', $data['city'] ?? ''];
}

/**
 * api.ip.sb
 *
 * 文档：https://github.com/ihmily/ip-info-api/blob/main/README.md#address-1.5
 *
 * @param string $ipv4
 * @return array
 */
function iro_ip_api_lookup_ipsb(string $ipv4): array
{
    $data = iro_ip_api_request('https://api.ip.sb/geoip/' . $ipv4);

    if (empty($data) || !empty($data['error'])) {
        return [];
    }

    return [$data['country'] ?? '', $data['region'] ?? '', $data['city'] ?? ''];
}

/**
 * whois.pconline.com.cn
 *
 * 文档：https://github.com/ihmily/ip-info-api/blob/main/README.md#address-1.3
 *
 * 接口只返回中文地名、且只覆盖国内数据：站点语言不是中文时直接跳过，避免输出与其他接口语言不一致。
 * 国内 IP 的响应没有国家字段，只取省市，不臆造国家名。
 *
 * @param string $ipv4
 * @return array
 */
function iro_ip_api_lookup_pconline(string $ipv4): array
{
    if (iro_ip_api_language() !== 'zh-CN') {
        return [];
    }

    $data = iro_ip_api_request('https://whois.pconline.com.cn/ipJson.jsp?ip=' . $ipv4 . '&json=true', 'GBK');

    if (empty($data)) {
        return [];
    }

    $province = trim((string) ($data['pro'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));

    if ($province !== '' || $city !== '') {
        return [$province, $city];
    }

    return [trim((string) ($data['addr'] ?? '')), '', ''];
}
