<?php

/**
 * Theme Image Server
 *
 * URL examples:
 *
 * 默认无损 WebP：
 * /static/media/wp-content/uploads/2024/11/image.png
 *
 * 完整参数：
 * /static/media/q_100&f_webp&w_1536&h_1024/wp-content/uploads/2024/11/image.png
 */

add_action('init', 'iro_media_register_routes', 10);
add_filter('query_vars', 'iro_media_query_vars');
add_action('template_redirect', 'iro_media_dispatch', 0);
add_action('after_switch_theme', function () {
    flush_rewrite_rules();
});
add_action('update_option_iro_options', function () {
    flush_rewrite_rules();
});


/**
 * 注册：
 *
 * /static/media/...
 *
 * 注意：
 * 不把 modifiers/path 放进 WP query string，
 * 因为 modifiers 使用 & 作为分隔符。
 */
function iro_media_register_routes(): void
{
    add_rewrite_rule(
        '^static/media(?:/.*)?/?$',
        'index.php?' . IRO_MEDIA_ROUTE_VAR . '=1',
        'top'
    );
}
function iro_media_query_vars(array $vars): array
{
    $vars[] = IRO_MEDIA_ROUTE_VAR;

    return $vars;
}

function iro_get_current_domain(): string
{
    $domain = $name = (
        $_SERVER['HTTP_X_FORWARDED_HOST']
        ?? $_SERVER['HTTP_HOST']
        ?? $_SERVER['SERVER_NAME']
        ?? ''
    );

    return (string) $domain;
}

function iro_media_current_scheme(): string
{
    $forwarded_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

    if ($forwarded_proto !== '') {
        $forwarded_proto = strtolower(
            trim(explode(',', $forwarded_proto)[0])
        );

        if (in_array($forwarded_proto, ['http', 'https'], true)) {
            return $forwarded_proto;
        }
    }

    return is_ssl() ? 'https' : 'http';
}


/**
 * WordPress 如果安装在：
 * https://example.com/blog/
 * 那么这里返回：
 * /blog
 */
function iro_media_home_path(): string
{
    $path = (string) wp_parse_url(
        home_url('/'),
        PHP_URL_PATH
    );

    return '/' . trim($path, '/');
}

/**
 * 从 REQUEST_URI 中拿：
 *
 * /static/media/q_80&f_webp&w_1536/wp-content/uploads/a.jpg
 *
 * 返回：
 *
 * [
 *     'modifiers'  => 'q_80&f_webp&w_1536',
 *     'image_path' => 'wp-content/uploads/a.jpg',
 * ]
 *
 * @return array{modifiers:string,image_path:string}|null
 */
function iro_media_parse_request_uri(): ?array
{

    $raw = $_SERVER['REQUEST_URI'] ?? '/';

    // 处理可能的转义
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $raw = urldecode($raw);

    $request_path = (string) wp_parse_url($raw, PHP_URL_PATH);

    $home_path = iro_media_home_path();

    if ($home_path !== '/' && str_starts_with($request_path, $home_path)) {
        $request_path = substr(
            $request_path,
            strlen($home_path)
        );
    }

    $request_path = ltrim($request_path, '/');

    if (!str_starts_with($request_path, 'static/media/')) {
        return null;
    }

    $tail = trim(
        substr($request_path, strlen('static/media/')),
        '/'
    );

    if ($tail === '') {
        return null;
    }

    $parts = explode('/', $tail);

    $first = array_shift($parts);

    /*
     * 防止：
     *
     * /static/media/wp-content/uploads/...
     *
     * 被错误识别成：
     *
     * modifier = wp-content
     */
    $modifier_pattern =
        '/^
        (?:
            q_\d{1,3}
            |
            f_[a-z0-9-]+
            |
            w_\d+
            |
            h_\d+
        )
        (?:
            &
            (?:
                q_\d{1,3}
                |
                f_[a-z0-9-]+
                |
                w_\d+
                |
                h_\d+
            )
        )*
        $/ix';

    if (
        preg_match($modifier_pattern, $first) === 1
        && $parts !== []
    ) {
        return [
            'modifiers'  => $first,
            'image_path' => implode('/', $parts),
        ];
    }

    return [
        'modifiers'  => '',
        'image_path' => implode(
            '/',
            array_merge([$first], $parts)
        ),
    ];
}

/**
 * @return array<string,int|string|null>|WP_Error
 */
function iro_media_parse_modifiers(string $modifiers): array|WP_Error
{

    $modifiers = html_entity_decode($modifiers, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $modifiers = urldecode($modifiers);

    $result = [
        'quality' => null,
        'format'  => 'webp',
        'width'   => null,
        'height'  => null,
    ];

    /*
     * 没有 modifier：
     *
     * 默认 WebP
     * 默认 lossless
     */
    if ($modifiers === '') {
        return $result;
    }

    $seen = [];

    foreach (explode('&', $modifiers) as $modifier) {

        if (!preg_match(
            '/^(q|f|w|h)_(.+)$/i',
            $modifier,
            $m
        )) {
            return new WP_Error(
                'invalid_modifier',
                'Invalid image modifier.'
            );
        }

        $key = strtolower($m[1]);

        if (isset($seen[$key])) {
            return new WP_Error(
                'duplicate_modifier',
                'Duplicate image modifier.'
            );
        }

        $seen[$key] = true;

        switch ($key) {

            case 'q':

                if (!ctype_digit($m[2])) {
                    return new WP_Error(
                        'invalid_quality',
                        'Invalid quality.'
                    );
                }

                $quality = (int) $m[2];

                if ($quality < 0 || $quality > 100) {
                    return new WP_Error(
                        'invalid_quality',
                        'Quality must be 0-100.'
                    );
                }

                $result['quality'] = $quality;

                break;


            case 'f':

                $format = strtolower($m[2]);

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

                $result['format'] = $format;

                break;


            case 'w':
            case 'h':

                if (!ctype_digit($m[2])) {
                    return new WP_Error(
                        'invalid_dimension',
                        'Invalid image dimension.'
                    );
                }

                $dimension = (int) $m[2];

                if (
                    $dimension < 1
                    || $dimension > IRO_MEDIA_MAX_DIMENSION
                ) {
                    return new WP_Error(
                        'invalid_dimension',
                        'Image dimension is out of range.'
                    );
                }

                $result[$key === 'w'
                    ? 'width'
                    : 'height'] = $dimension;

                break;
        }
    }

    return $result;
}

/**
 * 将 URL path 变成本地真实路径。
 *
 * 只允许 ABSPATH 内的真实文件，
 * 并且最终通过 getimagesize() 验证确实是图片。
 */
function iro_media_resolve_source(
    string $image_path
): string|WP_Error {

    $image_path = rawurldecode($image_path);

    $image_path = str_replace(
        '\\',
        '/',
        $image_path
    );

    $image_path = '/' . ltrim(
        $image_path,
        '/'
    );

    if (str_contains($image_path, "\0")) {
        return new WP_Error(
            'invalid_path',
            'Invalid image path.'
        );
    }

    $segments = explode(
        '/',
        trim($image_path, '/')
    );

    if (in_array('..', $segments, true)) {
        return new WP_Error(
            'invalid_path',
            'Path traversal is not allowed.'
        );
    }

    $root = realpath(ABSPATH);

    $candidate = realpath(
        ABSPATH . ltrim(
            $image_path,
            '/'
        )
    );

    if (
        $root === false
        || $candidate === false
        || !is_file($candidate)
        || !is_readable($candidate)
    ) {
        return new WP_Error(
            'not_found',
            'Source image not found.'
        );
    }

    /*
     * 防止 symlink 指向站点目录之外。
     */
    $root_prefix = trailingslashit($root);

    if (
        $candidate !== $root
        && !str_starts_with(
            $candidate,
            $root_prefix
        )
    ) {
        return new WP_Error(
            'invalid_path',
            'Source path is outside the site root.'
        );
    }

    /*
     * 必须是真正的图片。
     */
    $info = @getimagesize($candidate);

    if ($info === false) {
        return new WP_Error(
            'not_image',
            'Source file is not a supported image.'
        );
    }

    return $candidate;
}

function iro_media_create_from_file(
    string $path
): GdImage|WP_Error {

    $type = @exif_imagetype($path);

    $map = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_GIF  => 'imagecreatefromgif',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];

    if (defined('IMAGETYPE_AVIF')) {
        $map[IMAGETYPE_AVIF] = 'imagecreatefromavif';
    }

    $function = $map[$type] ?? null;

    if (
        $function === null
        || !function_exists($function)
    ) {
        return new WP_Error(
            'unsupported_source',
            'GD cannot decode this image format.'
        );
    }

    $image = @$function($path);

    if (!$image instanceof GdImage) {
        return new WP_Error(
            'decode_failed',
            'GD failed to decode source image.'
        );
    }

    return $image;
}

function iro_media_prepare_canvas(
    int $width,
    int $height,
    string $format
): GdImage {

    $canvas = imagecreatetruecolor(
        $width,
        $height
    );

    /*
     * PNG / WebP / AVIF 保留 alpha。
     */
    if (in_array(
        $format,
        ['webp', 'png', 'avif'],
        true
    )) {
        imagealphablending(
            $canvas,
            false
        );

        imagesavealpha(
            $canvas,
            true
        );

        $transparent = imagecolorallocatealpha(
            $canvas,
            0,
            0,
            0,
            127
        );

        imagefilledrectangle(
            $canvas,
            0,
            0,
            $width - 1,
            $height - 1,
            $transparent
        );
    } else {

        /*
         * JPEG 不支持 alpha，默认用白色背景。
         */
        $white = imagecolorallocate(
            $canvas,
            255,
            255,
            255
        );

        imagefill(
            $canvas,
            0,
            0,
            $white
        );
    }

    return $canvas;
}

/**
 * 规则：
 *
 * 只有 width：
 *     等比例缩放
 *
 * 只有 height：
 *     等比例缩放
 *
 * width + height：
 *     cover，居中裁剪到精确尺寸
 */
function iro_media_resize(
    GdImage $source,
    array $options
): GdImage {

    $src_w = imagesx($source);
    $src_h = imagesy($source);

    $target_w = $options['width'] ?? null;
    $target_h = $options['height'] ?? null;

    /*
     * 不需要 resize。
     */
    if (
        $target_w === null
        && $target_h === null
    ) {
        return $source;
    }

    /*
     * 只有 height。
     */
    if ($target_w === null) {
        $target_w = max(
            1,
            (int) round(
                $src_w * ($target_h / $src_h)
            )
        );
    }

    /*
     * 只有 width。
     */ elseif ($target_h === null) {
        $target_h = max(
            1,
            (int) round(
                $src_h * ($target_w / $src_w)
            )
        );
    }

    if (
        $target_w * $target_h
        > IRO_MEDIA_MAX_PIXELS
    ) {
        imagedestroy($source);

        throw new RuntimeException(
            'Target image is too large.'
        );
    }

    $canvas = iro_media_prepare_canvas(
        (int) $target_w,
        (int) $target_h,
        (string) $options['format']
    );

    $src_x = 0;
    $src_y = 0;

    $crop_w = $src_w;
    $crop_h = $src_h;

    /*
     * width + height = cover + center crop
     */
    if (
        $options['width'] !== null
        && $options['height'] !== null
    ) {
        $source_ratio = $src_w / $src_h;
        $target_ratio = $target_w / $target_h;

        if ($source_ratio > $target_ratio) {

            /*
             * 原图过宽：
             * 左右裁剪
             */
            $crop_w = (int) round(
                $src_h * $target_ratio
            );

            $src_x = (int) floor(
                ($src_w - $crop_w) / 2
            );
        } elseif ($source_ratio < $target_ratio) {

            /*
             * 原图过高：
             * 上下裁剪
             */
            $crop_h = (int) round(
                $src_w / $target_ratio
            );

            $src_y = (int) floor(
                ($src_h - $crop_h) / 2
            );
        }
    }

    imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        $src_x,
        $src_y,
        $target_w,
        $target_h,
        $crop_w,
        $crop_h
    );

    imagedestroy($source);

    return $canvas;
}


/*
|--------------------------------------------------------------------------
| Cache
|--------------------------------------------------------------------------
*/

function iro_media_cache_key(
    string $source_path,
    array $options
): string {

    $stat = @stat($source_path);

    $version =
        ($stat['mtime'] ?? 0)
        . ':'
        . ($stat['size'] ?? 0);

    return hash(
        'sha256',
        $source_path
            . '|'
            . $version
            . '|'
            . serialize($options)
    );
}


/*
|--------------------------------------------------------------------------
| Encoder
|--------------------------------------------------------------------------
*/

/**
 * @return array{mime:string,extension:string}|WP_Error
 */
function iro_media_encode(
    GdImage $image,
    string $cache_path,
    array $options
): array|WP_Error {

    $format  = $options['format'];
    $quality = $options['quality'];

    switch ($format) {

        /*
         * ----------------------------------------------------------
         * WebP
         * ----------------------------------------------------------
         */
        case 'webp':

            if (!function_exists('imagewebp')) {
                return new WP_Error(
                    'webp_not_supported',
                    'GD WebP support is unavailable.'
                );
            }

            /*
             * PHP 8.1+：
             *
             * quality = 100
             * 或者未指定 quality
             *
             * => 真正的 WebP lossless 参数。
             *
             * quality < 100
             * => lossy WebP。
             */
            $webp_quality =
                (
                    ($quality === null || $quality === 100)
                    && defined('IMG_WEBP_LOSSLESS')
                )
                ? IMG_WEBP_LOSSLESS
                : ($quality ?? 85);

            imagealphablending(
                $image,
                false
            );

            imagesavealpha(
                $image,
                true
            );

            $ok = @imagewebp(
                $image,
                $cache_path,
                $webp_quality
            );

            if (!$ok) {
                return new WP_Error(
                    'encode_failed',
                    'GD failed to encode WebP.'
                );
            }

            return [
                'mime'      => 'image/webp',
                'extension' => 'webp',
            ];


            /*
         * ----------------------------------------------------------
         * JPEG
         * ----------------------------------------------------------
         */
        case 'jpeg':

            if (!function_exists('imagejpeg')) {
                return new WP_Error(
                    'jpeg_not_supported',
                    'GD JPEG support is unavailable.'
                );
            }

            $ok = @imagejpeg(
                $image,
                $cache_path,
                $quality ?? 85
            );

            if (!$ok) {
                return new WP_Error(
                    'encode_failed',
                    'GD failed to encode JPEG.'
                );
            }

            return [
                'mime'      => 'image/jpeg',
                'extension' => 'jpg',
            ];


            /*
         * ----------------------------------------------------------
         * PNG
         * ----------------------------------------------------------
         */
        case 'png':

            if (!function_exists('imagepng')) {
                return new WP_Error(
                    'png_not_supported',
                    'GD PNG support is unavailable.'
                );
            }

            /*
             * PNG 本质始终无损。
             *
             * 这里把统一的 0-100 quality
             * 映射到 GD 的 0-9 compression。
             */
            $compression =
                $quality === null
                ? 6
                : 9 - (int) round(
                    $quality * 9 / 100
                );

            $compression = max(
                0,
                min(9, $compression)
            );

            $ok = @imagepng(
                $image,
                $cache_path,
                $compression
            );

            if (!$ok) {
                return new WP_Error(
                    'encode_failed',
                    'GD failed to encode PNG.'
                );
            }

            return [
                'mime'      => 'image/png',
                'extension' => 'png',
            ];


            /*
         * ----------------------------------------------------------
         * AVIF
         * ----------------------------------------------------------
         */
        case 'avif':

            if (!function_exists('imageavif')) {
                return new WP_Error(
                    'avif_not_supported',
                    'This GD build does not support AVIF.'
                );
            }

            $ok = @imageavif(
                $image,
                $cache_path,
                $quality ?? 85
            );

            if (!$ok) {
                return new WP_Error(
                    'encode_failed',
                    'GD failed to encode AVIF.'
                );
            }

            return [
                'mime'      => 'image/avif',
                'extension' => 'avif',
            ];
    }

    return new WP_Error(
        'invalid_format',
        'Unsupported output format.'
    );
}

/**
 * @return array{path:string,mime:string,etag:string}|WP_Error
 */
function iro_media_generate(
    string $source_path,
    array $options
): array|WP_Error {

    $cache_dir =
        trailingslashit(WP_CONTENT_DIR)
        . 'cache/theme-gd-media';

    if (!wp_mkdir_p($cache_dir)) {
        return new WP_Error(
            'cache_dir_failed',
            'Unable to create image cache directory.'
        );
    }

    $cache_key = iro_media_cache_key(
        $source_path,
        $options
    );

    $meta_path =
        trailingslashit($cache_dir)
        . $cache_key
        . '.meta.php';

    /*
     * 已有缓存。
     */
    if (is_file($meta_path)) {

        $meta = @include $meta_path;

        if (
            is_array($meta)
            && isset(
                $meta['path'],
                $meta['mime'],
                $meta['etag']
            )
            && is_file($meta['path'])
        ) {
            return $meta;
        }
    }

    /*
     * 读取原图。
     */
    $source = iro_media_create_from_file(
        $source_path
    );

    if (is_wp_error($source)) {
        return $source;
    }

    $src_w = imagesx($source);
    $src_h = imagesy($source);

    if (
        $src_w * $src_h
        > IRO_MEDIA_MAX_PIXELS
    ) {
        imagedestroy($source);

        return new WP_Error(
            'source_too_large',
            'Source image is too large.'
        );
    }

    /*
     * GD 某些输入可能是 palette image，
     * 输出 WebP/PNG/AVIF 前转换到 truecolor。
     */
    if (
        function_exists('imageistruecolor')
        && function_exists('imagepalettetotruecolor')
        && !imageistruecolor($source)
    ) {
        @imagepalettetotruecolor($source);
    }

    try {

        $output = iro_media_resize(
            $source,
            $options
        );
    } catch (Throwable $e) {

        /*
         * 如果 resize 没接管 source，则安全释放。
         */
        if ($source instanceof GdImage) {
            @imagedestroy($source);
        }

        return new WP_Error(
            'resize_failed',
            $e->getMessage()
        );
    }

    $tmp_path = tempnam(
        $cache_dir,
        'img-'
    );

    if ($tmp_path === false) {
        imagedestroy($output);

        return new WP_Error(
            'cache_temp_failed',
            'Unable to create image cache file.'
        );
    }

    /*
     * 编码。
     */
    $encoded = iro_media_encode(
        $output,
        $tmp_path,
        $options
    );

    imagedestroy($output);

    if (is_wp_error($encoded)) {
        @unlink($tmp_path);

        return $encoded;
    }

    $final_path =
        trailingslashit($cache_dir)
        . $cache_key
        . '.'
        . $encoded['extension'];

    if (!@rename($tmp_path, $final_path)) {
        @unlink($tmp_path);

        return new WP_Error(
            'cache_write_failed',
            'Unable to move encoded image into cache.'
        );
    }

    $etag = '"' . $cache_key . '"';

    $meta = [
        'path' => $final_path,
        'mime' => $encoded['mime'],
        'etag' => $etag,
    ];

    /*
     * PHP array cache。
     */
    $meta_php =
        '<?php return '
        . var_export($meta, true)
        . ';';

    if (
        @file_put_contents(
            $meta_path,
            $meta_php,
            LOCK_EX
        ) === false
    ) {
        @unlink($final_path);

        return new WP_Error(
            'cache_meta_failed',
            'Unable to write image cache metadata.'
        );
    }

    return $meta;
}

function iro_media_dispatch(): void
{
    /*
     * 解析当前 URL。
     */
    $request = iro_media_parse_request_uri();

    if ($request === null) {
        if ((int) get_query_var(IRO_MEDIA_ROUTE_VAR) === 1) {
            status_header(404);
            exit;
        }

        return;
    }

    /*
     * modifiers。
     */
    $options = iro_media_parse_modifiers(
        $request['modifiers']
    );

    if (is_wp_error($options)) {
        status_header(400);

        header(
            'Content-Type: text/plain; charset=UTF-8'
        );

        echo $options->get_error_message();

        exit;
    }

    /*
     * 原图。
     */
    $source_path = iro_media_resolve_source(
        $request['image_path']
    );

    if (is_wp_error($source_path)) {

        $status =
            $source_path->get_error_code()
            === 'not_found'
            ? 404
            : 400;

        status_header($status);

        header(
            'Content-Type: text/plain; charset=UTF-8'
        );

        echo $source_path->get_error_message();

        exit;
    }

    /*
     * 生成/读取缓存。
     */
    $generated = iro_media_generate(
        $source_path,
        $options
    );

    if (is_wp_error($generated)) {
        status_header(500);

        header(
            'Content-Type: text/plain; charset=UTF-8'
        );

        echo $generated->get_error_message();

        exit;
    }

    /*
     * HTTP cache。
     */
    $etag = $generated['etag'];

    header('ETag: ' . $etag);

    header(
        'Cache-Control: public, max-age=31536000, immutable'
    );

    header(
        'Content-Type: ' . $generated['mime']
    );

    header(
        'X-Content-Type-Options: nosniff'
    );

    /*
     * ETag。
     */
    if (
        isset($_SERVER['HTTP_IF_NONE_MATCH'])
        && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag
    ) {
        status_header(304);
        exit;
    }

    /*
     * Last-Modified。
     */
    $last_modified = @filemtime(
        $generated['path']
    );

    if ($last_modified !== false) {
        header(
            'Last-Modified: '
                . gmdate(
                    'D, d M Y H:i:s',
                    $last_modified
                )
                . ' GMT'
        );
    }

    /*
     * Content-Length。
     */
    $size = @filesize(
        $generated['path']
    );

    if ($size !== false) {
        header(
            'Content-Length: ' . $size
        );
    }

    @readfile(
        $generated['path']
    );

    exit;
}
