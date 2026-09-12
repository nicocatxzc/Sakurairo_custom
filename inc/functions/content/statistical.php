<?php
/**
 * 字数、词数统计
 */
function count_post_words($post_ID)
{
    $post = get_post($post_ID);
    if (!in_array($post->post_type, ['post', 'shuoshuo'])) {
        return;
    }
    $content = $post->post_content;
    $content = strip_tags($content);
    $count = word_stat($content);
    update_post_meta($post_ID, 'post_words_count', $count);
    return $count;
}

add_action('save_post', 'count_post_words');

//归档页信息缓存
function get_archive_info($get_page = false)
{
    // 获取所有文章和说说
    $args = [
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_type' => array('post', 'shuoshuo'),
        'post_status'    => 'publish',
        'suppress_filters' => false // 同时获取文章和说说
    ];
    if ($get_page) {
        $args['post_type'] = array('post', 'shuoshuo', 'page');
    }
    $posts = get_posts($args);
    // 统计
    $years = [];
    $stats = [
        'total' => [
            'posts' => 0,
            'views' => 0,
            'words' => 0,
            'comments' => 0
        ],
        'shuoshuo' => [
            'posts' => 0,
            'views' => 0,
            'words' => 0,
            'comments' => 0
        ],
        'article' => [
            'posts' => 0,
            'views' => 0,
            'words' => 0,
            'comments' => 0
        ],
        'page' => [
            'posts' => 0,
            'views' => 0,
            'words' => 0,
            'comments' => 0
        ]
    ];
    foreach ($posts as $post) {
        $views = get_post_views($post->ID);
        $words = get_meta_words_count($post->ID);
        $comments = get_comments_number($post->ID);

        // 判断页面类型
        if ($post->post_type == 'post') {
            $post_type = 'article';
        } elseif ($post->post_type == 'shuoshuo') {
            $post_type = 'shuoshuo';
        } else {
            $post_type = 'page';
        }

        // 更新统计数据
        $stats[$post_type]['posts']++;
        $stats[$post_type]['views'] += intval($views);
        $stats[$post_type]['words'] += intval($words);
        $stats[$post_type]['comments'] += intval($comments);

        $stats['total']['posts']++;
        $stats['total']['views'] += intval($views);
        $stats['total']['words'] += intval($words);
        $stats['total']['comments'] += intval($comments);

        $year = date('Y', strtotime($post->post_date));
        $month = date('n', strtotime($post->post_date));
        if ($post->post_password != '') {
            $post->post_title = __("It's a secret", 'sakurairo'); // 隐藏受密码保护文章的标题
        }

        $category_ids = wp_get_post_categories($post->ID) ?: [];

        $post = [ //仅保存需要的数据（归档、展示区）
            'post_title'    => $post->post_title,
            'post_author'     => $post->post_author,
            'post_date'     => $post->post_date,
            'post_modified'     => $post->post_modified,
            'comment_count' => $comments,
            'link'          => get_the_permalink($post->ID),
            'categories'    => $category_ids,
            'meta' => [
                'views' => $views,
                'words' => $words,
                'type' => $post_type
            ]
        ];

        if (!isset($years[$year])) $years[$year] = [];
        if (!isset($years[$year][$month])) $years[$year][$month] = [];
        $years[$year][$month][] = $post;
    }

    return $years;
}

//更新文章后更新缓存
add_action('save_post', function () {
    get_archive_info();
});