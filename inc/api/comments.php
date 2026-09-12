<?php
function iro_get_comments($request)
{
    $page = intval($request->get_param('page')) ?: 1;
    $per_page = intval($request->get_param('per_page')) ?: 10;
    $post_id = intval($request->get_param('post_id'));

    if ($post_id <= 0) {
        return new WP_Error('missing_post_id', '缺少文章 ID', ['status' => 400]);
    }

    $offset = ($page - 1) * $per_page;

    $args = [
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => $per_page,
        'offset' => $offset,
        'order' => 'DESC',
        'orderby' => 'comment_date_gmt',
    ];
    $comments = get_comments($args);

    // 获取父评论
    $parent_ids = array_filter(array_column($comments, 'comment_parent'));
    $parent_comments = [];
    if (!empty($parent_ids)) {
        $parent_comments = get_comments([
            'comment__in' => $parent_ids,
            'status' => 'approve',
            'number' => 0,
        ]);
        $parent_comments = array_combine(array_column($parent_comments, 'comment_ID'), $parent_comments);
    }

    $nodes = array_map(function ($comment) use ($parent_comments) {
        $data = [
            'databaseId' => $comment->comment_ID,
            'author' => [
                'name' => $comment->comment_author,
                'avatar' => ['url' => get_avatar_url($comment->comment_author_email, ['size' => 48])],
            ],
            'date' => $comment->comment_date,
            'content' => apply_filters('comment_text', $comment->comment_content),
            'parent' => null,
        ];
        if ($comment->comment_parent > 0 && isset($parent_comments[$comment->comment_parent])) {
            $parent = $parent_comments[$comment->comment_parent];
            $data['parent'] = [
                'databaseId' => $parent->comment_ID,
                'author' => ['name' => $parent->comment_author],
            ];
        }
        return $data;
    }, $comments);

    $total = get_comments([
        'post_id' => $post_id,
        'status' => 'approve',
        'count' => true,
    ]);

    $total_pages = ceil($total / $per_page);
    $has_next_page = $page < $total_pages;

    return [
        'totalComments' => $total,
        'totalPages' => $total_pages,
        'currentPage' => $page,
        'comments' => $nodes,
        'hasNextPage' => $has_next_page,
    ];
}
