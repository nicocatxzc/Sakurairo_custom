<?php
//主查询逻辑，类型只能多不能少，主查询通过后模版页查询才能干扰拓展
function customize_query_functions($query)
{
    //只影响前端
    if ($query->is_main_query() && !is_admin()) {
        // 主页显示类型
        if (is_home()) {
            $post_types = array('post', 'shuoshuo');
            $query->set('post_type', $post_types);
        } elseif (is_search()) {
            // 搜索页结果
            $allowed_types = [];

            // 始终允许文章
            $allowed_types[] = 'post';

            if (iro_opt('search_for_shuoshuo')) {
                $allowed_types[] = 'shuoshuo';
            }

            if (iro_opt('search_for_pages')) {
                $pages_admin_only = (bool) iro_opt('search_pages_can_only_admins');
                $is_admin = current_user_can('manage_options');

                if (!$pages_admin_only || $is_admin) {
                    $allowed_types[] = 'page';
                }
            }

            $query_types = isset($_GET['post_type'])
                ? sanitize_text_field($_GET['post_type'])
                : '';

            if ($query_types) {
                $types = array_filter(array_map('trim', explode(',', $query_types)));
                // 与白名单取交集
                $types = array_intersect($types, $allowed_types);
                // 如果全部被过滤掉，回退到全部允许的类型
                $types = !empty($types) ? array_values($types) : $allowed_types;
            } else {
                $types = $allowed_types;
            }

            $query->set('post_type', array_values($types));

            $query->set('post__not_in', array_filter(array_map('trim', explode(',', iro_opt("search_results_custom_exclude", "")))));
            $tax_query = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'name',
                    'terms'    => get_search_query(),
                    'operator' => 'NOT IN'
                )
            );
            $query->set('tax_query', $tax_query);
        } elseif (is_archive() || is_category() || is_author()) {
            // 保持其他页面的原有逻辑
            $query->set('post_type', array('post', 'shuoshuo'));
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
    if (is_search() && !iro_opt('search_for_pinned_posts', true)) {
        return $clauses;
    }

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
