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

                /*
                 * 这里不按后端能力拦格式：
                 * 能不能交付由 iro_media_build() 现试（Imagick → GD），
                 * 实在不行还有 302 到原图兜底。
                 */
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

/**
 * 编码链的第一环：Imagick 能处理多帧动图，写不出来时才轮到 GD。
 */
function iro_media_has_imagick(): bool
{
    return class_exists('Imagick');
}

/**
 * 校验文件头与目标格式是否一致。
 *
 * Imagick 写不出目标格式时会安静地退回源格式（PNG 源就写出 PNG），
 * 只看 writeImage() 的返回值会把这种文件当成功缓存出去。
 */
function iro_media_file_matches_format(
    string $format,
    string $path
): bool {

    $head = (string) @file_get_contents(
        $path,
        false,
        null,
        0,
        16
    );

    if (strlen($head) < 12) {
        return false;
    }

    return match (strtolower($format)) {

        'avif' => substr($head, 4, 4) === 'ftyp'
            && in_array(
                substr($head, 8, 4),
                ['avif', 'avis', 'mif1', 'msf1'],
                true
            ),

        'webp' => str_starts_with($head, 'RIFF')
            && substr($head, 8, 4) === 'WEBP',

        'jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),

        'png' => str_starts_with($head, "\x89PNG\r\n\x1A\n"),

        default => true,
    };
}

/**
 * 真编一张最小的图，验证 Imagick 写不写得出来。
 *
 * 写法必须与 iro_media_encode_imagick() 一致，探的才是同一条路。
 */
function iro_media_probe_imagick_writer(string $format): bool
{
    $probe = @tempnam(
        get_temp_dir(),
        'iro-writer-'
    );

    if ($probe === false) {
        return false;
    }

    try {

        $image = new Imagick();

        $image->newImage(
            16,
            16,
            new ImagickPixel('white')
        );

        $image->setFormat($format);

        $image->writeImage($format . ':' . $probe);

        $image->clear();
    } catch (Throwable $e) {

        @unlink($probe);

        return false;
    }

    $ok = iro_media_file_matches_format($format, $probe);

    @unlink($probe);

    return $ok;
}

/**
 * Imagick 能不能写出这个格式。
 *
 * queryFormats() 只回答「coder 在不在册」：镜像里缺 libheif 编码插件时
 * AVIF 依然在列，实际写出去却会报 no encode delegate，或者退回源格式。
 * 所以这里真的编码一次来验证，结果按 Imagick 版本缓存一天
 */
function iro_media_imagick_writes(string $format): bool
{
    static $probed = [];

    $format = strtolower($format);

    if (!iro_media_has_imagick()) {
        return false;
    }

    if (array_key_exists($format, $probed)) {
        return $probed[$format];
    }

    $probed[$format] = false;

    if (Imagick::queryFormats(strtoupper($format)) === []) {
        return false;
    }

    $cache_key = 'iro_media_writer_' . md5(
        $format
        . (Imagick::getVersion()['versionString'] ?? '')
        . (string) phpversion('imagick')
    );

    $cached = get_transient($cache_key);

    if ($cached === 'yes' || $cached === 'no') {

        $probed[$format] = $cached === 'yes';

        return $probed[$format];
    }

    $probed[$format] = iro_media_probe_imagick_writer($format);

    set_transient(
        $cache_key,
        $probed[$format] ? 'yes' : 'no',
        DAY_IN_SECONDS
    );

    return $probed[$format];
}

/**
 * GD 有没有这个格式的编码函数。
 */
function iro_media_gd_can_encode(string $format): bool
{
    return match (strtolower($format)) {

        'webp' => function_exists('imagewebp'),

        'jpeg' => function_exists('imagejpeg'),

        'png' => function_exists('imagepng'),

        'avif' => function_exists('imageavif'),

        default => false,
    };
}

/**
 * 能不能「推荐」这个格式：只看首选后端。
 *
 * 这只决定前台要不要发这个格式的 URL，所以要求的是首选后端的效率：
 * GD 的 AVIF 在 q99 下比 WebP 还大，拿它去投 avif 分支是负收益。
 * 真正交付时写不出来还会退 GD，那是另一回事。
 */
function iro_media_webp_supported(): bool
{
    return iro_media_has_imagick()
        ? iro_media_imagick_writes('webp')
        : iro_media_gd_can_encode('webp');
}

function iro_media_avif_supported(): bool
{
    return iro_media_has_imagick()
        ? iro_media_imagick_writes('avif')
        : iro_media_gd_can_encode('avif');
}

/**
 * 源图是不是多帧动图。
 *
 * 只看文件头：GIF 看 NETSCAPE 循环块与图形控制块，
 * WebP 看 ANIM 块，不解码整图。
 */
function iro_media_source_is_animated(string $path): bool
{
    $head = (string) @file_get_contents(
        $path,
        false,
        null,
        0,
        4096
    );

    if (str_starts_with($head, 'GIF')) {
        return str_contains($head, 'NETSCAPE2.0')
            || substr_count($head, "\x21\xF9\x04") > 1;
    }

    if (
        str_starts_with($head, 'RIFF')
        && str_contains(substr($head, 0, 16), 'WEBP')
    ) {
        return str_contains($head, 'ANIM');
    }

    return false;
}

function iro_media_destroy(Imagick|GdImage $image): void
{
    if ($image instanceof Imagick) {
        $image->clear();

        return;
    }

    @imagedestroy($image);
}

function iro_media_load_imagick(string $path): ?Imagick
{
    try {
        return new Imagick($path);
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * GD 只认静态图：动图会只剩第一帧，所以动图不走这条路。
 */
function iro_media_load_gd(string $path): ?GdImage
{
    $type = @exif_imagetype($path);

    if ($type === false) {
        return null;
    }

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
        return null;
    }

    $image = @$function($path);

    return $image instanceof GdImage
        ? $image
        : null;
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
 *
 * 任何一边都不放大：
 *     目标超过原图的边按原图截断，
 *     只把大于参数的那一边居中裁剪（对边各裁一半）
 *
 * 两个后端共用这一份几何计算，避免规则跑偏。
 *
 * @param array<string,mixed> $options
 * @return array{width:int,height:int,x:int,y:int,crop_width:int,crop_height:int}|null
 */
function iro_media_resize_geometry(
    int $src_w,
    int $src_h,
    array $options
): ?array {

    $target_w = $options['width'] ?? null;
    $target_h = $options['height'] ?? null;

    /*
     * 不需要 resize。
     */
    if (
        $target_w === null
        && $target_h === null
    ) {
        return null;
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

    /*
     * 不放大：
     *
     * 目标尺寸超过原图时按原图截断，
     * 后面的 cover 逻辑便只会裁掉大于参数的那一边，
     * 另一边保持原始像素，且从中心对半裁。
     */
    $target_w = min($target_w, $src_w);
    $target_h = min($target_h, $src_h);

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

    return [
        'width'       => (int) $target_w,
        'height'      => (int) $target_h,
        'x'           => $src_x,
        'y'           => $src_y,
        'crop_width'  => $crop_w,
        'crop_height' => $crop_h,
    ];
}

function iro_media_resize(
    Imagick|GdImage $source,
    array $options
): Imagick|GdImage {

    $imagick = $source instanceof Imagick;

    $geometry = iro_media_resize_geometry(
        $imagick
            ? $source->getImageWidth()
            : imagesx($source),
        $imagick
            ? $source->getImageHeight()
            : imagesy($source),
        $options
    );

    if ($geometry === null) {
        return $source;
    }

    if (
        $geometry['width'] * $geometry['height']
        > IRO_MEDIA_MAX_PIXELS
    ) {
        throw new RuntimeException(
            'Target image is too large.'
        );
    }

    if ($imagick) {
        return iro_media_resize_imagick($source, $geometry);
    }

    $canvas = iro_media_prepare_canvas(
        $geometry['width'],
        $geometry['height'],
        (string) $options['format']
    );

    imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        $geometry['x'],
        $geometry['y'],
        $geometry['width'],
        $geometry['height'],
        $geometry['crop_width'],
        $geometry['crop_height']
    );

    return $canvas;
}

/**
 * Imagick 分支：动图先合并出完整画布，再逐帧裁剪缩放，
 * 帧延时与循环次数原样保留。
 *
 * @param array{width:int,height:int,x:int,y:int,crop_width:int,crop_height:int} $geometry
 */
function iro_media_resize_imagick(
    Imagick $source,
    array $geometry
): Imagick {

    $animated = $source->getNumberImages() > 1;

    $frames = $animated
        ? $source->coalesceImages()
        : $source;

    foreach ($frames as $frame) {

        $frame->cropImage(
            $geometry['crop_width'],
            $geometry['crop_height'],
            $geometry['x'],
            $geometry['y']
        );

        /*
         * 裁剪后画布原点必须归零，否则动图每帧都会带着偏移。
         */
        $frame->setImagePage(0, 0, 0, 0);

        $frame->resizeImage(
            $geometry['width'],
            $geometry['height'],
            Imagick::FILTER_LANCZOS,
            1
        );
    }

    if ($animated) {
        $source->clear();

        $frames->setIteratorIndex(0);
    }

    return $frames;
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
    Imagick|GdImage $image,
    string $cache_path,
    array $options
): array|WP_Error {

    if ($image instanceof Imagick) {
        return iro_media_encode_imagick(
            $image,
            $cache_path,
            $options
        );
    }

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
 * Imagick 编码：WebP 保留动图（逐帧写入），其余格式只能写第一帧。
 *
 * @return array{mime:string,extension:string}|WP_Error
 */
function iro_media_encode_imagick(
    Imagick $image,
    string $cache_path,
    array $options
): array|WP_Error {

    $format  = (string) $options['format'];
    $quality = $options['quality'];

    $extensions = [
        'webp' => 'webp',
        'jpeg' => 'jpg',
        'png'  => 'png',
        'avif' => 'avif',
    ];

    if (!isset($extensions[$format])) {
        return new WP_Error(
            'invalid_format',
            'Unsupported output format.'
        );
    }

    if (!iro_media_imagick_writes($format)) {
        return new WP_Error(
            $format . '_not_supported',
            'Imagick cannot write this format.'
        );
    }

    $animated = $format === 'webp'
        && $image->getNumberImages() > 1;

    try {

        $image->setFormat($format);

        switch ($format) {

            case 'webp':

                if ($animated) {

                    /*
                     * 动图源（GIF 之类）本身就是有损调色板，
                     * 无损重编码只会比原图还大，统一走有损最高档。
                     */
                    $image->setIteratorIndex(0);

                    $image->setImageCompressionQuality(
                        (int) min(99, $quality ?? 100)
                    );

                    break;
                }

                /*
                 * 与 GD 分支保持一致：
                 *
                 * quality = 100 或未指定 => 无损，
                 * 其余 => 有损。
                 */
                if ($quality === null || $quality === 100) {
                    $image->setOption(
                        'webp:lossless',
                        'true'
                    );
                } else {
                    $image->setImageCompressionQuality(
                        (int) $quality
                    );
                }

                break;

            case 'jpeg':

                /*
                 * JPEG 没有 alpha，统一压到白底，与 GD 分支一致。
                 */
                $image->setIteratorIndex(0);

                $image->setImageBackgroundColor('#ffffff');

                $image->setImageAlphaChannel(
                    Imagick::ALPHACHANNEL_REMOVE
                );

                $image->setImageCompression(
                    Imagick::COMPRESSION_JPEG
                );

                $image->setImageCompressionQuality(
                    (int) ($quality ?? 85)
                );

                break;

            case 'png':

                /*
                 * GD 的 0-9 压缩级换算方式照搬过来，
                 * 保证换后端时体积量级一致。
                 */
                $image->setIteratorIndex(0);

                $image->setOption(
                    'png:compression-level',
                    (string) max(
                        0,
                        min(
                            9,
                            $quality === null
                                ? 6
                                : 9 - (int) round(
                                    $quality * 9 / 100
                                )
                        )
                    )
                );

                break;

            case 'avif':

                $image->setIteratorIndex(0);

                $image->setImageCompressionQuality(
                    (int) ($quality ?? 85)
                );

                break;
        }

        /*
         * 必须写 coder 前缀：缓存临时文件没有后缀，
         * 不带前缀时 Imagick 认不出目标格式，会按源格式（GIF 之类）写出去，
         * 质量设置也会一起失效。
         */
        $target = $format . ':' . $cache_path;

        $ok = $animated
            ? $image->writeImages($target, true)
            : $image->writeImage($target);
    } catch (Throwable $e) {

        return new WP_Error(
            'encode_failed',
            $e->getMessage()
        );
    }

    if (!$ok) {
        return new WP_Error(
            'encode_failed',
            'Imagick failed to encode image.'
        );
    }

    if (!iro_media_file_matches_format($format, $cache_path)) {
        return new WP_Error(
            'encode_failed',
            'Imagick wrote an unexpected image format.'
        );
    }

    return [
        'mime'      => 'image/'
            . ($format === 'jpeg' ? 'jpeg' : $format),
        'extension' => $extensions[$format],
    ];
}

function iro_media_cache_dir(): string
{
    return trailingslashit(WP_CONTENT_DIR)
        . 'cache/theme-gd-media';
}

/**
 * 临时文件就放在缓存目录里：交付完原地改名，不跨文件系统。
 */
function iro_media_temp_path(): ?string
{
    $dir = iro_media_cache_dir();

    if (!wp_mkdir_p($dir)) {
        return null;
    }

    $path = @tempnam($dir, 'img-');

    return $path === false ? null : $path;
}

/**
 * 缓存命中才算数：meta 是提交点，它存在、且指向的文件还在。
 *
 * @return array{path:string,mime:string}|null
 */
function iro_media_cache_read(string $cache_key): ?array
{
    $meta_path = iro_media_cache_dir()
        . '/'
        . $cache_key
        . '.meta.php';

    if (!is_file($meta_path)) {
        return null;
    }

    $meta = @include $meta_path;

    if (
        !is_array($meta)
        || !isset($meta['path'], $meta['mime'])
        || !is_file((string) $meta['path'])
    ) {
        return null;
    }

    return [
        'path' => (string) $meta['path'],
        'mime' => (string) $meta['mime'],
    ];
}

/**
 * 图已经交付给客户端了，这里只把它挪进缓存目录。
 *
 * 不加锁：并发下两个请求写同一个键也无所谓，
 * rename 是原子的，而且两边写出来的内容一致。
 */
function iro_media_cache_write(
    string $cache_key,
    array $built
): void {

    $final = iro_media_cache_dir()
        . '/'
        . $cache_key
        . '.'
        . $built['extension'];

    if (!@rename($built['path'], $final)) {
        @unlink($built['path']);

        return;
    }

    /*
     * 先落图再写 meta：meta 出现就代表这份图可用。
     */
    @file_put_contents(
        iro_media_cache_dir() . '/' . $cache_key . '.meta.php',
        '<?php return '
            . var_export(
                [
                    'path' => $final,
                    'mime' => $built['mime'],
                ],
                true
            )
            . ';'
    );
}

/*
|--------------------------------------------------------------------------
| Pipeline
|--------------------------------------------------------------------------
*/

/**
 * 生成一份优化图。
 *
 * Imagick 优先；它写不出来或失败时，静态图再交给 GD 重试一次；
 * 两条路都不行就返回 null，由调用方 302 到原图。
 *
 * 动图只走 Imagick：GD 只解第一帧，会把动画静帧化。
 *
 * @return array{path:string,mime:string,extension:string}|null
 */
function iro_media_build(
    string $source_path,
    array $options
): ?array {

    $format = (string) $options['format'];

    $animated = iro_media_source_is_animated($source_path);

    /*
     * 动图只有「Imagick + WebP」这一条路保得住动画，
     * 其余组合一律不处理，让浏览器直接取原图。
     */
    if ($animated && $format !== 'webp') {
        return null;
    }

    $size = @getimagesize($source_path);

    if (
        $size === false
        || $size[0] * $size[1] > IRO_MEDIA_MAX_PIXELS
    ) {
        return null;
    }

    $built = iro_media_build_imagick($source_path, $options);

    $fallback = false;

    /*
     * 回退前先看收益：GD 写不了这个格式、或者解不了这张图，就不必白跑一趟。
     */
    if (
        $built === null
        && !$animated
        && iro_media_gd_can_encode($format)
    ) {
        $fallback = true;

        $built = iro_media_build_gd($source_path, $options);
    }

    /*
     * 收益校验：
     * 动图转码不划算会让页面变重，回退本身也是额外成本，
     * 编出来不比原图小就把原图交出去。
     */
    if ($built !== null && ($animated || $fallback)) {

        $produced = (int) @filesize($built['path']);
        $original = (int) @filesize($source_path);

        if ($original > 0 && $produced >= $original) {
            @unlink($built['path']);

            return null;
        }
    }

    return $built;
}

/**
 * Imagick 分支：解码 → 缩放 → 编码。
 *
 * @return array{path:string,mime:string,extension:string}|null
 */
function iro_media_build_imagick(
    string $source_path,
    array $options
): ?array {

    $format = (string) $options['format'];

    /*
     * 先问能力再动手：写不出来的格式不必白解码一张大图。
     */
    if (!iro_media_imagick_writes($format)) {
        return null;
    }

    $source = iro_media_load_imagick($source_path);

    if ($source === null) {
        return null;
    }

    $frames = $source->getNumberImages();

    /*
     * 头部看不出多帧的格式（动图 AVIF 之类）在这里补一刀：
     * 非 WebP 静帧化会毁动画，超限的动图不碰。
     */
    if (
        $frames > 1
        && (
            $format !== 'webp'
            || $frames
                * $source->getImageWidth()
                * $source->getImageHeight()
                > IRO_MEDIA_MAX_PIXELS
        )
    ) {
        iro_media_destroy($source);

        return null;
    }

    try {
        $output = iro_media_resize($source, $options);
    } catch (Throwable $e) {

        iro_media_destroy($source);

        return null;
    }

    if ($output !== $source) {
        iro_media_destroy($source);
    }

    $built = iro_media_encode_to_temp($output, $options);

    iro_media_destroy($output);

    return $built;
}

/**
 * GD 分支：只跑第一帧，格式能不能写由调用方先问过。
 *
 * @return array{path:string,mime:string,extension:string}|null
 */
function iro_media_build_gd(
    string $source_path,
    array $options
): ?array {

    $source = iro_media_load_gd($source_path);

    if ($source === null) {
        return null;
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
        $output = iro_media_resize($source, $options);
    } catch (Throwable $e) {

        iro_media_destroy($source);

        return null;
    }

    if ($output !== $source) {
        iro_media_destroy($source);
    }

    $built = iro_media_encode_to_temp($output, $options);

    iro_media_destroy($output);

    return $built;
}

/**
 * 编到缓存目录里的临时文件，失败返回 null。
 *
 * @return array{path:string,mime:string,extension:string}|null
 */
function iro_media_encode_to_temp(
    Imagick|GdImage $image,
    array $options
): ?array {

    $tmp_path = iro_media_temp_path();

    if ($tmp_path === null) {
        return null;
    }

    $encoded = iro_media_encode(
        $image,
        $tmp_path,
        $options
    );

    if (is_wp_error($encoded)) {
        @unlink($tmp_path);

        return null;
    }

    return [
        'path'      => $tmp_path,
        'mime'      => $encoded['mime'],
        'extension' => $encoded['extension'],
    ];
}

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

/**
 * 一切都是客户端缓存，服务端不留结论：
 * 命中就 30 天内不再来，客户端清了缓存打回来就重新算。
 */
function iro_media_cache_control(): string
{
    return 'public, max-age=' . MONTH_IN_SECONDS . ', immutable';
}

/**
 * 交付文件。
 *
 * 命中缓存时这就是全部工作；未命中时调用方在它之后写缓存，
 * 客户端不必等落盘。
 */
function iro_media_send_file(
    string $path,
    string $mime,
    string $etag
): void {

    $size = @filesize($path);
    $mtime = @filemtime($path);

    header('ETag: ' . $etag);
    header('Cache-Control: ' . iro_media_cache_control());
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');

    if ($size !== false) {
        header('Content-Length: ' . $size);
    }

    if ($mtime !== false) {
        header(
            'Last-Modified: '
                . gmdate('D, d M Y H:i:s', $mtime)
                . ' GMT'
        );
    }

    @readfile($path);

    iro_media_finish_response();
}

function iro_media_send_not_modified(string $etag): void
{
    header('ETag: ' . $etag);
    header('Cache-Control: ' . iro_media_cache_control());

    status_header(304);

    exit;
}

/**
 * 客户端带来的 If-None-Match。
 *
 * WP 对 $_SERVER 做了 add_magic_quotes()，不还原转义斜杠的话
 * 带引号的 ETag 永远比不上，304 就成了死代码。
 */
function iro_media_client_etag(): string
{
    $etag = trim(
        stripslashes(
            (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')
        )
    );

    if (str_starts_with($etag, 'W/')) {
        $etag = substr($etag, 2);
    }

    return $etag;
}

/**
 * 响应推完就断开：后面的写缓存不占用客户端的等待时间。
 */
function iro_media_finish_response(): void
{
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

/**
 * 交不出优化图就交原图：显示正确优先，宁可多一跳也不出错。
 *
 * 缓存和图片用同一套：服务端不落盘、不留负缓存，
 * 结论只跟着响应走，客户端清了缓存再打回来就重新算。
 */
function iro_media_redirect_source(string $image_path): void
{
    status_header(302);

    header('Cache-Control: ' . iro_media_cache_control());

    header(
        'Location: '
            . home_url('/' . ltrim($image_path, '/'))
    );

    exit;
}

/**
 * 请求入口：命中就打缓存，没命中就现做一份，做不出来就 302 到原图。
 *
 * 这条路只有两种结局：交付一张图，或者交回原图，不会回错误页。
 */
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

    $image_path = $request['image_path'];

    /*
     * 源图不存在就没有可交付的东西：
     * 302 过去也只是把 404 挪个位置。
     */
    $source_path = iro_media_resolve_source($image_path);

    if (is_wp_error($source_path)) {
        status_header(404);
        exit;
    }

    /*
     * 参数不认识就把原图交出去，不再回错误页。
     */
    $options = iro_media_parse_modifiers(
        $request['modifiers']
    );

    if (is_wp_error($options)) {
        iro_media_redirect_source($image_path);
    }

    $cache_key = iro_media_cache_key(
        $source_path,
        $options
    );

    $etag = '"' . $cache_key . '"';

    /*
     * 客户端手里那份就是这个 ETag：连构建都省掉。
     */
    if (iro_media_client_etag() === $etag) {
        iro_media_send_not_modified($etag);
    }

    /*
     * 1. 有缓存直接发。
     */
    $cached = iro_media_cache_read($cache_key);

    if ($cached !== null) {

        iro_media_send_file(
            $cached['path'],
            $cached['mime'],
            $etag
        );

        exit;
    }

    /*
     * 2. 现做一份：Imagick 优先，退 GD，都不行就交原图。
     */
    $built = iro_media_build($source_path, $options);

    if ($built === null) {
        iro_media_redirect_source($image_path);
    }

    /*
     * 3. 先把图交出去，再落盘。
     */
    iro_media_send_file(
        $built['path'],
        $built['mime'],
        $etag
    );

    iro_media_cache_write($cache_key, $built);

    exit;
}
