<?php
require_once get_template_directory() . '/inc/libs/ip-api.php';

function iro_get_comment_ip_address(int $id): string
{
    if (metadata_exists('comment', $id, 'iro_ip_address')) {
        return (string) get_comment_meta($id, 'iro_ip_address', true);
    }
    // 查不到也写入空值，避免每次渲染都重复发起请求
    add_action('shutdown', function () use ($id) {
        add_comment_meta($id, 'iro_ip_address', iro_get_ip_address((string) get_comment($id)?->comment_author_IP));
    });
    return '';
}

add_action('comment_post', function ($comment_id) {
    iro_get_comment_ip_address((int) $comment_id);
});
