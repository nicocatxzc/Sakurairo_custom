<?php
defined('ABSPATH') || exit;

const IRO_MEDIA_ROUTE_VAR = 'iro_media_route';
const IRO_MEDIA_MAX_DIMENSION = 8192;
const IRO_MEDIA_MAX_PIXELS    = 50000000;

/*
 * 响应式图片的三档视口分界。
 */
const IRO_MEDIA_BREAKPOINT_MOBILE = 480;
const IRO_MEDIA_BREAKPOINT_TABLET = 860;

/*
 * PNG 源图的“字节/像素”低于此值视为扁平图，
 * 这类图有损 AVIF 反而大过无损 WebP，不出 AVIF 分支。
 */
const IRO_MEDIA_AVIF_MIN_PNG_DENSITY = 0.5;

require_once get_template_directory() . '/inc/functions/optimize/slow_net.php';
require_once get_template_directory() . '/inc/functions/optimize/image_server.php';
require_once get_template_directory() . '/inc/functions/optimize/optimize_image.php';
