<?php

/**
 * 渲染文章标题和正文
 */
function iro_llms_render_markdown(int $post_id): void
{
    $post = get_post($post_id);

    if (!$post || $post->post_status !== 'publish' || post_password_required($post)) {
        status_header(404);

        return;
    }

    // setup_postdata() 只补 $id / $pages 等全局量，不负责 $post，
    // 而模板里的 get_the_ID()、the_content() 都读 $post，必须一并赋值
    $GLOBALS['post'] = $post;
    setup_postdata($post);

    header('Content-Type: text/markdown; charset=UTF-8');

    ob_start();

    if (has_post_thumbnail($post)) {
        require get_theme_file_path('/frontend/components/post/header_with_image.php');
    } else {
        require get_theme_file_path('/frontend/components/post/header.php');
    }

    require get_theme_file_path('/frontend/components/post/render.php');

    echo ob_get_clean();

    wp_reset_postdata();
}
