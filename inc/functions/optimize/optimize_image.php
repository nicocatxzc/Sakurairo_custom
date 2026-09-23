<?php
add_filter('the_content', 'iro_media_optimize_content_images', 99);

/**
 * 相对 URL：
 *
 *     /wp-content/uploads/a.jpg，
 *     wp-content/uploads/a.jpg，
 *     //example.com/a.jpg，
 *
 * 以及当前 domain：
 *
 *     https://example.com/a.jpg
 *
 * 均可视为同源。
 */
function iro_media_is_same_origin(
    string $url
): bool {

    $url = trim(
        html_entity_decode(
            $url,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        )
    );

    if ($url === '') {
        return false;
    }

    // 特殊协议
    if (preg_match(
        '/^(?:data|blob|javascript|mailto):/i',
        $url
    )) {
        return false;
    }

    $parts = wp_parse_url($url);

    if ($parts === false) {
        return false;
    }

    // 没有域名的相对链接
    if (empty($parts['host'])) {
        return true;
    }

    $current_domain = strtolower(
        iro_get_current_domain()
    );

    $host = strtolower(
        (string) $parts['host']
    );

    $port = isset($parts['port'])
        ? ':' . (int) $parts['port']
        : '';

    return ($host . $port) === $current_domain;
}

function iro_media_public_base_url(): string
{
    $home_path = iro_media_home_path();

    if ($home_path === '/') {
        $home_path = '';
    }

    return
        iro_media_current_scheme()
        . '://'
        . iro_get_current_domain()
        . $home_path
        . '/static/media/';
}


/**
 * 把一个同源图片 URL 转为优化图片：
 * 
 * /static/media/...
 *
 * $args 支持：
 *
 * [
 *     'quality' => 80,
 *     'format'  => 'webp',
 *     'width'   => 1536,
 *     'height'  => 1024,
 * ]
 *
 * 同时支持：
 *
 * q/f/w/h
 *
 * 当 $args 为空：
 *
 * /static/media/wp-content/uploads/...
 *
 * 服务端默认输出无损 WebP，
 * 当$force为true时强制进行优化
 */
function iro_media_optimize_image_url(
    string $url,
    array $args = [],
    bool $force = false,
): string {

    $original = $url;

    // 读取选项
    $optimize_enabled = (bool) iro_opt("iro_image_optimize");
    $cdn_domain       = trim((string) iro_opt("iro_image_cdn"));

    if ($force == true) {
        $optimize_enabled = true;
    }

    // 两个条件都不满足，直接返回原 URL
    if (!$optimize_enabled && $cdn_domain === '') {
        return $original;
    }

    $url = trim(
        html_entity_decode(
            $url,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        )
    );

    // 只处理同源图片
    if (!iro_media_is_same_origin($url)) {
        return $original;
    }

    $parts = wp_parse_url($url);

    if (
        $parts === false
        || empty($parts['path'])
    ) {
        return $original;
    }

    $path = (string) $parts['path'];

    // 如果开启了优化，则走原有的优化路径逻辑
    if ($optimize_enabled) {

        /*
         * 去掉 WordPress 安装子目录。
         */
        $home_path = iro_media_home_path();

        if (
            $home_path !== '/'
            && str_starts_with(
                $path,
                $home_path . '/'
            )
        ) {
            $path_for_route = substr(
                $path,
                strlen($home_path)
            );
        } else {
            $path_for_route = $path;
        }

        /*
         * 已经是优化路由则不重复改写路径。
         */
        if (
            str_starts_with(
                $path_for_route,
                '/static/media/'
            )
        ) {
            // 已是优化路由，保留原始 URL，后续可能还要替换 CDN 域名
            $route = $original;
        } else {
            // 构造优化路由
            $options = [
                'quality' => null,
                'format'  => 'webp',
                'width'   => null,
                'height'  => null,
            ];

            $aliases = [
                'q' => 'quality',
                'f' => 'format',
                'w' => 'width',
                'h' => 'height',
            ];

            foreach ($args as $key => $value) {
                $key = strtolower((string) $key);
                if (isset($aliases[$key])) {
                    $key = $aliases[$key];
                }
                if (array_key_exists($key, $options)) {
                    $options[$key] = $value;
                }
            }

            $modifier_segment = iro_media_build_modifier_segment($options);

            if (is_wp_error($modifier_segment)) {
                // 参数错误时退回原 URL
                $route = $original;
            } else {
                $route = iro_media_public_base_url();

                if ($modifier_segment !== '') {
                    $route .= $modifier_segment . '/';
                }

                $route .= ltrim($path_for_route, '/');

                if (!empty($parts['query'])) {
                    $route .= '?' . $parts['query'];
                }

                if (!empty($parts['fragment'])) {
                    $route .= '#' . $parts['fragment'];
                }
            }
        }
    } else {
        // 未开启优化，保持原始路径，仅后续可能替换域名
        $route = $original;
    }

    // 如果配置了 CDN 域名，则替换最终 URL 的 scheme + host
    if ($cdn_domain !== '') {

        // 补全 scheme
        if (!preg_match('#^https?://#i', $cdn_domain)) {
            $cdn_domain = 'https://' . $cdn_domain;
        }

        $cdn_parts = wp_parse_url($cdn_domain);

        if (
            $cdn_parts
            && !empty($cdn_parts['scheme'])
            && !empty($cdn_parts['host'])
        ) {
            $route_parts = wp_parse_url($route);

            if ($route_parts) {
                $new_url = $cdn_parts['scheme'] . '://' . $cdn_parts['host'];

                if (!empty($cdn_parts['port'])) {
                    $new_url .= ':' . $cdn_parts['port'];
                }

                if (!empty($route_parts['path'])) {
                    $new_url .= $route_parts['path'];
                }

                if (!empty($route_parts['query'])) {
                    $new_url .= '?' . $route_parts['query'];
                }

                if (!empty($route_parts['fragment'])) {
                    $new_url .= '#' . $route_parts['fragment'];
                }

                $route = $new_url;
            }
        }
    }

    return esc_url_raw($route);
}

/**
 * @return string|WP_Error
 */
function iro_media_build_modifier_segment(
    array $options
): string|WP_Error {

    $items = [];

    /*
     * q
     */
    if ($options['quality'] !== null) {

        if (
            !is_int($options['quality'])
            && !ctype_digit(
                (string) $options['quality']
            )
        ) {
            return new WP_Error(
                'invalid_quality',
                'Quality must be an integer.'
            );
        }

        $quality = (int) $options['quality'];

        if (
            $quality < 0
            || $quality > 100
        ) {
            return new WP_Error(
                'invalid_quality',
                'Quality must be 0-100.'
            );
        }

        $items[] = 'q_' . $quality;
    }

    /*
     * f
     */
    if ($options['format'] !== null) {

        $format = strtolower(
            (string) $options['format']
        );

        $aliases = [
            'jpg'  => 'jpeg',
            'jfif' => 'jpeg',
        ];

        $format = $aliases[$format] ?? $format;

        if (!in_array(
            $format,
            ['webp', 'jpeg', 'png', 'avif'],
            true
        )) {
            return new WP_Error(
                'invalid_format',
                'Supported formats: webp, jpeg, png, avif.'
            );
        }

        if (
            $format === 'avif'
            && !function_exists('imageavif')
        ) {
            return new WP_Error(
                'avif_not_supported',
                'This GD build does not support AVIF.'
            );
        }

        $items[] = 'f_' . $format;
    }

    /*
     * w / h
     */
    foreach (
        [
            'width'  => 'w',
            'height' => 'h',
        ] as $key => $prefix
    ) {

        if ($options[$key] === null) {
            continue;
        }

        if (
            !is_int($options[$key])
            && !ctype_digit(
                (string) $options[$key]
            )
        ) {
            return new WP_Error(
                'invalid_dimension',
                'Image dimensions must be integers.'
            );
        }

        $value = (int) $options[$key];

        if (
            $value < 1
            || $value > IRO_MEDIA_MAX_DIMENSION
        ) {
            return new WP_Error(
                'invalid_dimension',
                'Image dimensions are out of range.'
            );
        }

        $items[] =
            $prefix
            . '_'
            . $value;
    }

    /*
     * f_webp 本身就等价于默认模式，
     * 所以不需要把它放进 URL。
     */
    if ($items === ['f_webp']) {
        return '';
    }

    return implode(
        '&',
        $items
    );
}

/**
 * 替换 the_content() 中：
 *
 * <img src="">
 * <img srcset="">
 * <img data-src="">
 * <img data-srcset="">
 *
 * <source srcset="">
 *
 * 中的同源图片。
 */
function iro_media_optimize_content_images(
    string $content
): string {

    if ($content === '') {
        return $content;
    }

    if (!preg_match(
        '/<(?:img|source)\b/i',
        $content
    )) {
        return $content;
    }

    return preg_replace_callback(
        '/<(img|source)\b([^>]*?)>/is',
        static function (
            array $tag_match
        ): string {

            $tag = $tag_match[0];

            /*
             * src / data-src
             */
            $tag = preg_replace_callback(
                '/(\b(?:src|data-src)\s*=\s*)(["\'])(.*?)(\2)/is',
                static function (
                    array $m
                ): string {

                    $optimized =
                        iro_media_optimize_image_url(
                            $m[3]
                        );

                    return
                        $m[1]
                        . $m[2]
                        . esc_attr($optimized)
                        . $m[4];
                },
                $tag
            );

            /*
             * srcset / data-srcset
             */
            $tag = preg_replace_callback(
                '/(\b(?:srcset|data-srcset)\s*=\s*)(["\'])(.*?)(\2)/is',
                static function (
                    array $m
                ): string {

                    $srcset =
                        preg_replace_callback(
                            '/(^|,)\s*([^,\s]+)(\s+[^,]+)?/i',
                            static function (
                                array $candidate
                            ): string {

                                $url =
                                    $candidate[2];

                                $optimized =
                                    iro_media_optimize_image_url(
                                        $url
                                    );

                                return
                                    $candidate[1]
                                    . ' '
                                    . $optimized
                                    . ($candidate[3] ?? '');
                            },
                            $m[3]
                        );

                    return
                        $m[1]
                        . $m[2]
                        . esc_attr($srcset)
                        . $m[4];
                },
                $tag
            );

            return $tag;
        },
        $content
    ) ?? $content;
}
