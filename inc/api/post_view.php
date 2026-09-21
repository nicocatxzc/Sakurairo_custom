<?php

/**
 * 校验请求指向的文章是否可被统计
 *
 * @return WP_Post|WP_Error
 */
function iro_rest_post_views_target(WP_REST_Request $request)
{
    $post = get_post((int) $request->get_param('post_id'));

    if (!$post || 'publish' !== $post->post_status) {
        return new WP_Error(
            'iro_rest_invalid_post',
            __('无效的文章 ID', 'sakurairo'),
            ['status' => 404]
        );
    }

    return $post;
}

function iro_rest_post_views_data($post_id)
{
    return [
        'post_id' => intval($post_id),
        'views'   => (int) get_post_meta($post_id, 'views', true),
        'display' => iro_get_post_views($post_id),
    ];
}

// GET 仅用于调试，不增加计数
function iro_rest_iro_get_post_views(WP_REST_Request $request)
{
    $post = iro_rest_post_views_target($request);

    if (is_wp_error($post)) {
        return $post;
    }

    return iro_rest_post_views_data($post->ID);
}

function iro_rest_iro_set_post_views(WP_REST_Request $request)
{
    $post = iro_rest_post_views_target($request);

    if (is_wp_error($post)) {
        return $post;
    }

    iro_set_post_views($post->ID);

    return iro_rest_post_views_data($post->ID);
}

