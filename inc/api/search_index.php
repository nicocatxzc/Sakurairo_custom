<?php
function iro_get_search_index()
{
    $query_index = new WP_Query([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $search_index = [];
    foreach ($query_index->posts as $p) {
        $author  = get_userdata($p->post_author);
        $thumbId = get_post_thumbnail_id($p);

        $search_index[] = [
            'author' => [
                'nicename' => $author->user_nicename,
                'slug'     => $author->user_nicename, // 注意：slug 与 nicename 常相同
                'avatar'   => [
                    'url' => get_avatar_url($author->ID),
                ],
            ],
            'content'       => apply_filters('the_content', $p->post_content),
            'featuredImage' => $thumbId ? [
                'node' => [
                    'sourceUrl'   => wp_get_attachment_image_url($thumbId, 'full'),
                    'altText'     => get_post_meta($thumbId, '_wp_attachment_image_alt', true),
                    'caption'     => wp_get_attachment_caption($thumbId),
                    'description' => apply_filters('the_content', get_post($thumbId)->post_content),
                ],
            ] : null,
            'hasPassword'   => ! empty($p->post_password),
            'modifiedGmt'   => $p->post_modified_gmt,
            'commentStatus' => $p->comment_status,          // 'open' | 'closed'
            'commentCount'  => (int) $p->comment_count,
            'title'         => get_the_title($p),
            'excerpt'       => apply_filters('the_excerpt', get_the_excerpt($p)),
            'categories'    => array_map(fn($t) => [
                'name' => $t->name,
                'uri'  => wp_make_link_relative(get_category_link($t)),
            ], wp_get_post_categories($p->ID, ['fields' => 'all'])),
            'tags'          => array_map(fn($t) => [
                'name' => $t->name,
                'uri'  => wp_make_link_relative(get_tag_link($t)),
            ], wp_get_post_tags($p->ID)),
            'uri'           => wp_make_link_relative(get_permalink($p)),
        ];
    }
    return $search_index;
}
