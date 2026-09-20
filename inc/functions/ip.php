<?php

/**
 * 获取客户端 IP
 * @return string
 */
function iro_get_user_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',      // Cloudflare
        'HTTP_TRUE_CLIENT_IP',        // Akamai / 部分 CDN
        'HTTP_ALI_CDN_REAL_IP',       // 阿里云 CDN
        'HTTP_CDN_REAL_IP',           // 部分 CDN
        'HTTP_CDN_SRC_IP',            // 部分 CDN
        'HTTP_X_REAL_IP',              // Nginx / 反向代理
        'HTTP_X_CLUSTER_CLIENT_IP',   // 部分负载均衡
        'HTTP_WL_PROXY_CLIENT_IP',    // WebLogic
        'HTTP_PROXY_CLIENT_IP',       // 部分代理
        'HTTP_CLIENT_IP',             // 部分代理
        'HTTP_FORWARDED_FOR',         // 非标准
        'HTTP_X_FORWARDED',            // 非标准
        'HTTP_X_FORWARDED_FOR',       // 最常见代理 Header
        'HTTP_FORWARDED',              // RFC 7239
        'REMOTE_ADDR',                 // 最终兜底
    ];

    foreach ($headers as $server_key) {
        if (empty($_SERVER[$server_key])) {
            continue;
        }

        $value = trim((string) $_SERVER[$server_key]);

        if ($value === '') {
            continue;
        }

        /*
         * 某些 Header 可能包含多个 IP：
         *
         * X-Forwarded-For:
         * 1.2.3.4, 10.0.0.1, 10.0.0.2
         *
         * 从左向右寻找合法 IP。
         */
        $candidates = preg_split('/\s*,\s*/', $value);

        if (!$candidates) {
            continue;
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            /*
             * RFC 7239 Forwarded 可能是：
             *
             * Forwarded: for=1.2.3.4;proto=https
             * Forwarded: for="[2001:db8::1]"
             *
             * 提取 for= 后面的地址。
             */
            if ($server_key === 'HTTP_FORWARDED') {
                if (preg_match(
                    '/(?:^|[;,])\s*for\s*=\s*(?:"?\[?)([^"\];,\s]+)\]?/i',
                    $candidate,
                    $matches
                )) {
                    $candidate = $matches[1];
                } else {
                    continue;
                }
            }

            /*
             * IPv4 可能带端口：
             *
             * 1.2.3.4:12345
             *
             * IPv6 则通常是：
             *
             * [2001:db8::1]:12345
             */
            $candidate = iro_normalize_client_ip($candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    return '';
}


/**
 * 标准化并验证 IP。
 *
 * @param string $ip
 * @return string
 */
function iro_normalize_client_ip(string $ip): string
{
    $ip = trim($ip);

    if ($ip === '') {
        return '';
    }

    /*
     * RFC 7239 / 代理 Header 中可能出现：
     *
     * [2001:db8::1]
     * [2001:db8::1]:443
     */
    if (preg_match('/^\[([0-9a-fA-F:]+)\](?::\d+)?$/', $ip, $matches)) {
        $ip = $matches[1];
    }

    /*
     * IPv4:port
     *
     * 只处理明确的 IPv4:port，
     * 避免误伤 IPv6。
     */
    if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $matches)) {
        $ip = $matches[1];
    }

    /*
     * 去掉可能存在的引号。
     */
    $ip = trim($ip, "\"'");

    /*
     * FILTER_VALIDATE_IP 同时支持 IPv4 / IPv6。
     */
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '';
}
