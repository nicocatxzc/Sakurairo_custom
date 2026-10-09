<?php

/**
 * 渲染文章列表
 */

function iro_llms_render_list(): void
{
    $GLOBALS['iro_only_template'] = true;

    require_once get_theme_file_path('/frontend/components/slots/pagination.php');

    header('Content-Type: text/markdown; charset=UTF-8');

    ob_start();
    require get_theme_file_path('/frontend/components/post/list.php');
    echo ob_get_clean();
}
