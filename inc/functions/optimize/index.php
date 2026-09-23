<?php
defined('ABSPATH') || exit;

const IRO_MEDIA_ROUTE_VAR = 'iro_media_route';
const IRO_MEDIA_MAX_DIMENSION = 8192;
const IRO_MEDIA_MAX_PIXELS    = 50000000;

require_once get_template_directory() . '/inc/functions/optimize/image_server.php';
require_once get_template_directory() . '/inc/functions/optimize/optimize_image.php';