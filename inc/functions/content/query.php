<?php
//主查询逻辑，类型只能多不能少，主查询通过后模版页查询才能干扰拓展
function customize_query_functions($query)
{
    //只影响前端
    if ($query->is_main_query() && !is_admin()) {
        //主页可以显示文章和说说
        if (is_home()) {
            //index引用content-thumb，其中根据设置项决定是否在主页排除说说
            $post_types = array('post', 'shuoshuo');
            $query->set('post_type', $post_types);
        } elseif (is_archive() || is_category() || is_author()) {
            // 保持其他页面的原有逻辑
            $query->set('post_type', array('post', 'shuoshuo'));
        }

        // 在搜索页面中排除分类页和特定类别
        if ($query->is_search) {
            $post_types = array('post', 'link', 'shuoshuo', 'page');
            $query->set('post_type', $post_types);
            $tax_query = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'name',
                    'terms'    => get_search_query(),
                    'operator' => 'NOT IN'
                )
            );
            $query->set('tax_query', $tax_query);
        }
    }
}

add_action('pre_get_posts', 'customize_query_functions');

add_filter(
    'posts_clauses',
    'iro_sticky_posts_first',
    10,
    2
);

function iro_sticky_posts_first($clauses, WP_Query $query)
{
    if (is_admin() || ! $query->is_main_query()) {
        return $clauses;
    }

    if (
        ! $query->is_home()
        && ! $query->is_archive()
        && ! $query->is_search()
    ) {
        return $clauses;
    }

    if ($query->get('ignore_sticky_posts')) {
        return $clauses;
    }

    $sticky_ids = get_option('sticky_posts', []);

    if (empty($sticky_ids)) {
        return $clauses;
    }

    $sticky_ids = array_map('intval', $sticky_ids);

    global $wpdb;

    // 置顶查询结果中的所有sticky文章
    $sticky_order = sprintf(
        'CASE WHEN %s.ID IN (%s) THEN 0 ELSE 1 END',
        $wpdb->posts,
        implode(',', $sticky_ids)
    );

    $clauses['orderby'] = $sticky_order . ', ' . $clauses['orderby'];

    return $clauses;
}
