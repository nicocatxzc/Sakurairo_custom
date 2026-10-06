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
 * 尺寸参数支持 px 与 rem：
 *
 * 96 / '96' / '96px' / '6rem'
 *
 * rem 按 iro_opt('global_font_size') 换算，CSS 里写多少这里就传多少。
 */
function iro_media_parse_dimension(mixed $value): ?int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_float($value)) {
        return (int) round($value);
    }

    if (!is_string($value)) {
        return null;
    }

    $value = strtolower(trim($value));

    if (str_ends_with($value, 'rem')) {

        $number = trim(substr($value, 0, -3));

        if (!is_numeric($number)) {
            return null;
        }

        // 后台的滑块存的是纯数字，这里容忍 '16px' 之类的写法
        $font_size = (float) iro_opt('global_font_size', '16');

        if ($font_size <= 0) {
            $font_size = 16.0;
        }

        return (int) round((float) $number * $font_size);
    }

    if (str_ends_with($value, 'px')) {
        $value = trim(substr($value, 0, -2));
    }

    return is_numeric($value)
        ? (int) round((float) $value)
        : null;
}

function iro_media_normalize_dimensions(array $args): array
{
    foreach (['width', 'w', 'height', 'h'] as $key) {

        if (!array_key_exists($key, $args)) {
            continue;
        }

        $parsed = iro_media_parse_dimension($args[$key]);

        // 解析不了就留下原值，交给参数校验照旧报错
        if ($parsed !== null) {
            $args[$key] = $parsed;
        }
    }

    return $args;
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
    ?string $url = null,
    array $args = [],
    bool $force = false,
): string {

    // 未配置的主题选项读出来是 null（如导航栏 logo），模板照样会把它当 URL 传进来
    if ($url === null) {
        return '';
    }

    $original = $url;

    $args = iro_media_normalize_dimensions($args);

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
         * 已经是优化路由则不重复改写路径；
         * 没有 Imagick 时动图只能静帧化（GD 只解第一帧），
         * 所以 GIF 保持原始 URL，宁可不优化也不毁动画。
         */
        if (
            str_starts_with(
                $path_for_route,
                '/static/media/'
            )
            || (
                !iro_media_has_imagick()
                && strtolower(
                    pathinfo(
                        $path_for_route,
                        PATHINFO_EXTENSION
                    )
                ) === 'gif'
            )
        ) {
            // 保留原始 URL，后续可能还要替换 CDN 域名
            $route = $original;
        } else {
            // 构造优化路由
            // 默认质量取主题选项，兜底 100（即无损 WebP）
            $default_quality = iro_opt('iro_image_quality', 100);

            if (!is_numeric($default_quality)) {
                $default_quality = 100;
            }

            $default_quality = max(
                0,
                min(100, (int) $default_quality)
            );

            $options = [
                'quality' => $default_quality,
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
            && !iro_media_avif_supported()
        ) {
            return new WP_Error(
                'avif_not_supported',
                'Neither Imagick nor GD can write AVIF here.'
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
     * q_100 + f_webp 与服务端默认的无损 WebP 完全等价，
     * 先去重，避免默认质量额外产生一份缓存。
     */
    if (in_array('f_webp', $items, true)) {
        $items = array_values(
            array_diff($items, ['q_100'])
        );
    }

    /*
     * f_webp 本身就等价于默认模式，
     * 所以不需要把它放进 URL。
     */
    if ($items === [] || $items === ['f_webp']) {
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

/**
 * 响应式图片生成。
 *
 * width/height 视为开发时该图在最大档（>860px 视口）下的实际显示像素；
 * 图片在任何视口下都不会宽过视口，于是三档显示宽度是：
 *
 * ≤480px 视口 -> min(width, 480)
 * ≤860px 视口 -> min(width, 860)
 * 更大视口    -> width
 *
 * 每档再按 1 / 2 / 3 倍密度补候选，交给浏览器按自身 DPR 取；
 * 没有 width 时退化成单档。
 *
 * 例如头像传 96x96：
 *
 * srcset  96w, 192w, 288w
 * sizes   96px
 *
 * @return array{sizes:string,width:int|null,height:int|null,candidates:array<int,array{width:int|null,height:int|null}>}
 */
function iro_media_responsive_plan(array $args): array
{
    $args = iro_media_normalize_dimensions($args);

    $width  = $args['width'] ?? $args['w'] ?? null;
    $height = $args['height'] ?? $args['h'] ?? null;

    $width  = is_int($width) && $width > 0 ? $width : null;
    $height = is_int($height) && $height > 0 ? $height : null;

    if ($width === null) {
        return [
            'sizes'      => '',
            'width'      => null,
            'height'     => $height,
            'candidates' => [
                ['width' => null, 'height' => null],
            ],
        ];
    }

    $tiers = [
        min($width, IRO_MEDIA_BREAKPOINT_MOBILE),
        min($width, IRO_MEDIA_BREAKPOINT_TABLET),
        $width,
    ];

    /*
     * 与最大档同宽的媒体条件恒真，去掉。
     */
    $conditions = [
        '(max-width: ' . IRO_MEDIA_BREAKPOINT_MOBILE . 'px)',
        '(max-width: ' . IRO_MEDIA_BREAKPOINT_TABLET . 'px)',
    ];

    $sizes = [];

    foreach ($conditions as $index => $condition) {
        if ($tiers[$index] !== $width) {
            $sizes[] = $condition . ' ' . $tiers[$index] . 'px';
        }
    }

    $sizes[] = $width . 'px';

    /*
     * 1 / 2 / 3 倍密度，按宽度去重升序。
     */
    $widths = [];

    foreach ($tiers as $tier) {
        foreach ([1, 2, 3] as $density) {
            $widths[$tier * $density] = true;
        }
    }

    ksort($widths);

    $candidates = [];

    foreach (array_keys($widths) as $candidate_width) {

        $candidate_width = min(
            (int) $candidate_width,
            IRO_MEDIA_MAX_DIMENSION
        );

        $candidate_height = $height === null
            ? null
            : min(
                IRO_MEDIA_MAX_DIMENSION,
                max(
                    1,
                    (int) round(
                        $height * $candidate_width / $width
                    )
                )
            );

        /*
         * 超过图床像素上限的候选会被拒绝，不要递给浏览器。
         */
        if (
            $candidate_height !== null
            && $candidate_width * $candidate_height
            > IRO_MEDIA_MAX_PIXELS
        ) {
            continue;
        }

        $candidates[$candidate_width] = [
            'width'  => $candidate_width,
            'height' => $candidate_height,
        ];
    }

    if ($candidates === []) {
        $candidates[$width] = [
            'width'  => $width,
            'height' => $height,
        ];
    }

    return [
        'sizes'      => is_string($args['sizes'] ?? null)
            && ($args['sizes'] ?? '') !== ''
            ? (string) $args['sizes']
            : implode(', ', $sizes),
        'width'      => $width,
        'height'     => $height,
        'candidates' => array_values($candidates),
    ];
}

/**
 * 按档位计划生成一整套 src / srcset / sizes。
 *
 * @param array{sizes:string,width:int|null,height:int|null,candidates:array<int,array{width:int|null,height:int|null}>} $plan
 * @return array{src:string,srcset:string,sizes:string}
 */
function iro_media_responsive_srcset(
    ?string $url,
    array $args,
    array $plan,
    bool $force = false
): array {

    $urls = [];

    foreach ($plan['candidates'] as $candidate) {

        $candidate_args = $args;

        if ($candidate['width'] !== null) {
            unset($candidate_args['w']);

            $candidate_args['width'] = $candidate['width'];
        }

        if ($candidate['height'] !== null) {
            unset($candidate_args['h']);

            $candidate_args['height'] = $candidate['height'];
        }

        $candidate_url = iro_media_optimize_image_url(
            $url,
            $candidate_args,
            $force
        );

        if ($candidate_url === '') {
            continue;
        }

        /*
         * 档位升序插入，最后一个即最大档；
         * 图床路由没生效时各档返回同一个 URL，会自然塌缩成单张。
         */
        $urls[$candidate_url] = $candidate['width'];
    }

    if ($urls === []) {
        return ['src' => '', 'srcset' => '', 'sizes' => ''];
    }

    /*
     * src 取最大档的 1 倍，也就是显示尺寸本身：
     * 支持 srcset 的浏览器不会用它，不支持时也不会去拉一张远超渲染尺寸的图。
     */
    $src = (string) array_key_last($urls);

    foreach ($urls as $candidate_url => $candidate_width) {
        if (
            $plan['width'] !== null
            && $candidate_width === $plan['width']
        ) {
            $src = (string) $candidate_url;
        }
    }

    $items = [];

    foreach ($urls as $candidate_url => $candidate_width) {
        if ($candidate_width !== null) {
            $items[] = $candidate_url . ' ' . $candidate_width . 'w';
        }
    }

    if (count($items) < 2) {
        return ['src' => $src, 'srcset' => '', 'sizes' => ''];
    }

    return [
        'src'    => $src,
        'srcset' => implode(', ', $items),
        'sizes'  => (string) $plan['sizes'],
    ];
}

/**
 * <img> 的优化属性：src + srcset + sizes + width/height。
 *
 * 只生成属性，标签与其余属性（alt / class / loading / ...）由调用处自己写：
 *
 * <img <?= iro_media_optimize_image_sizes($url, ['width' => '6rem', 'height' => '6rem']) ?>
 *     alt="<?= esc_attr($alt) ?>" loading="lazy">
 *
 * $args 透传给 iro_media_optimize_image_url()，
 * 其中 width/height 视为实际显示像素（px 与 rem 写法等价），
 * 既用于推导档位，也用于输出宽高属性；
 * 额外的 sizes 键可以覆盖自动生成的 sizes。
 *
 * @param array<string,mixed> $args
 */
function iro_media_optimize_image_sizes(
    ?string $url = null,
    array $args = [],
    bool $force = false
): string {

    $plan = iro_media_responsive_plan($args);

    $responsive = iro_media_responsive_srcset(
        $url,
        $args,
        $plan,
        $force
    );

    if ($responsive['src'] === '') {
        return '';
    }

    $attributes = ['src' => $responsive['src']];

    if ($responsive['srcset'] !== '') {
        $attributes['srcset'] = $responsive['srcset'];
        $attributes['sizes']  = $responsive['sizes'];
    }

    if ($plan['width'] !== null) {
        $attributes['width'] = $plan['width'];
    }

    if ($plan['height'] !== null) {
        $attributes['height'] = $plan['height'];
    }

    $html = [];

    foreach ($attributes as $name => $value) {
        $html[] = $name . '="' . esc_attr((string) $value) . '"';
    }

    return implode(' ', $html);
}

/**
 * <picture>
 *     <?= iro_media_optimize_image_formats($url, ['width' => '6rem', 'height' => '6rem'], ['alt' => '']) ?>
 * </picture>
 *
 * $args 与 iro_media_optimize_image_sizes() 一致，每个 <source> 用各自的 format；
 * GD 不支持该格式、或图床路由没生效时自动跳过格式分支。
 *
 * $attributes 收 <img> 的其余属性（alt / class / loading / decoding / ...）。
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $attributes
 */
function iro_media_optimize_image_formats(
    ?string $url = null,
    array $args = [],
    array $attributes = [],
    bool $force = false
): string {

    $image = iro_media_optimize_image_sizes(
        $url,
        $args,
        $force
    );

    if ($image === '') {
        return '';
    }

    /*
     * 要看生成后的地址：传入的多半是 /wp-content/uploads/...，
     * 非同源、或优化未开启时不会改写成图床路由，这时声明 image/avif 就是撒谎。
     */
    $routed = str_contains(
        (string) wp_parse_url(
            iro_media_optimize_image_url($url, $args, $force),
            PHP_URL_PATH
        ),
        '/static/media/'
    );

    $sources = [];

    if ($routed) {

        $plan = iro_media_responsive_plan($args);

        /*
         * AVIF 的 100 是无损档，体积会大过无损 WebP；
         * 取有损最高档（WebP 的 100 减一），保证它始终更小。
         */
        $quality = $args['quality'] ?? $args['q']
            ?? iro_opt('iro_image_quality', 100);

        $quality = is_numeric($quality)
            ? min(99, max(0, (int) $quality))
            : 99;

        /*
         * 有损 AVIF 并非永远更小，也不是永远安全：
         *
         * 动图换 AVIF 会丢帧（只有 WebP 分支能保住动画），
         * PNG 则可以直接看密度——PNG 无损，字节/像素越小说明越扁平
         * （图标、线条图），那种图有损 AVIF 反而大过无损 WebP。
         */
        $path = (string) wp_parse_url((string) $url, PHP_URL_PATH);

        $home_path = iro_media_home_path();

        if (
            $home_path !== '/'
            && str_starts_with($path, $home_path . '/')
        ) {
            $path = substr($path, strlen($home_path));
        }

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        $avif = true;

        if (in_array(
            $extension,
            ['gif', 'webp', 'png', 'avif'],
            true
        )) {

            $source = iro_media_resolve_source(
                ltrim($path, '/')
            );

            if (!is_wp_error($source)) {
                $avif = !iro_media_source_is_animated($source);
            }

            if (
                $avif
                && $extension === 'png'
                && !is_wp_error($source)
            ) {
                $info = @getimagesize($source);

                if (
                    $info !== false
                    && $info[0] * $info[1] > 0
                ) {
                    $avif =
                        (@filesize($source) ?: 0)
                        / ($info[0] * $info[1])
                        >= IRO_MEDIA_AVIF_MIN_PNG_DENSITY;
                }
            }
        }

        foreach ($avif ? ['avif', 'webp'] : ['webp'] as $format) {

            $supported = $format === 'avif'
                ? iro_media_avif_supported()
                : iro_media_webp_supported();

            if (!$supported) {
                continue;
            }

            $format_args = $args;

            unset($format_args['f']);

            $format_args['format'] = $format;

            if ($format === 'avif') {
                $format_args['quality'] = $quality;
            }

            $responsive = iro_media_responsive_srcset(
                $url,
                $format_args,
                $plan,
                $force
            );

            if ($responsive['src'] === '') {
                continue;
            }

            /*
             * 单档（例如只给了 height）时没有 w 描述符，
             * 但换个格式仍然省流量。
             */
            $sources[$format] = [
                'srcset' => $responsive['srcset'] === ''
                    ? $responsive['src']
                    : $responsive['srcset'],
                'sizes'  => $responsive['srcset'] === ''
                    ? ''
                    : ' sizes="' . esc_attr($responsive['sizes']) . '"',
            ];
        }
    }

    $extra = '';

    foreach (['alt' => ''] + $attributes as $name => $value) {

        if (
            $value === null
            || $value === false
            || preg_match(
                '/^[a-zA-Z][a-zA-Z0-9_:.-]*$/',
                (string) $name
            ) !== 1
        ) {
            continue;
        }

        $extra .= $value === true
            ? ' ' . $name
            : ' ' . $name . '="' . esc_attr((string) $value) . '"';
    }

    ob_start();
?><?php foreach ($sources as $type => $source) : ?>
<source
    type="image/<?= $type ?>"
    srcset="<?= esc_attr($source['srcset']) ?>" <?= $source['sizes'] ?>>
<?php endforeach; ?>
<img <?= $image ?><?= $extra ?>>
<?php
    return (string) ob_get_clean();
}
