<?php

/**
 * 获取友情链接列表
 * @Param: string $sorting_mode 友情链接列表排序模式，name、updated、rating、rand四种模式
 * @Param: string $link_order 友情链接列表排序方法，ASC、DESC（升序或降序）
 * @Param: mixed $id 友情链接ID
 * @Param: string $output HTML格式化输出
 */
function get_the_link_items($id = null)
{
    $sorting_mode = iro_opt('friend_link_sorting_mode');
    $link_order = iro_opt('friend_link_order');
    $bookmarks = get_bookmarks(
        array(
            'orderby' => $sorting_mode,
            'order' => $link_order,
            'category' => $id
        )
    );
    $output = '';
    if (!empty($bookmarks)) {
        $output .= '<ul class="link-items fontSmooth">';
        foreach ($bookmarks as $bookmark) {
            if (empty($bookmark->link_description)) {
                $bookmark->link_description = __('This guy is so lazy ╮(╯▽╰)╭', 'sakurairo');
            }

            if (empty($bookmark->link_image)) {
                $bookmark->link_image = 'https://weavatar.com/avatar/?s=80&d=mm&r=g';
            }

            // 获取链接状态
            $link_status = get_post_meta($bookmark->link_id, '_link_check_status', true);
            $status_class = '';
            if ($link_status === 'success') {
                $status_class = 'link-status-success';
            } elseif ($link_status === 'failure') {
                $status_class = 'link-status-failure';
            }

            $output .= '<li class="link-item ' . $status_class . '"><a class="link-item-inner effect-apollo" href="' . $bookmark->link_url . '" title="' . $bookmark->link_description . '" target="_blank" rel="friend"><div class="link-avatar-wrapper"><img alt="friend_avator" class="lazyload" onerror="imgError(this,1)" data-src="' . $bookmark->link_image . '" src="' . iro_opt('load_in_svg') . '"></div><span class="sitename" style="' . $bookmark->link_notes . '">' . $bookmark->link_name . '</span><div class="linkdes">' . $bookmark->link_description . '</div></a></li>';
        }
        $output .= '</ul>';
    }
    return $output;
}

function get_link_items()
{
    // 获取链接分类并按优先级降序排列
    $linkcats = get_terms(array(
        'taxonomy'   => 'link_category',
        'meta_key'   => 'term_priority', // 优先级字段
        'orderby'    => 'meta_value_num',
        'order'      => 'DESC',
        'hide_empty' => false
    ));

    // 检查是否返回错误或空结果
    if (is_wp_error($linkcats) || empty($linkcats)) {
        return get_the_link_items(); // 友链无分类或出错，直接返回全部列表  
    }

    $result = '';
    $pending_cat_name = __('Pending Links', 'sakurairo'); // 未审核链接分类名称

    foreach ($linkcats as $linkcat) {
        // 跳过未审核链接分类
        if ($linkcat->name === $pending_cat_name) {
            continue;
        }

        $result .= '<h3 class="link-title"><span class="link-fix">' . $linkcat->name . '</span></h3>';
        if ($linkcat->description) {
            $result .= '<div class="link-description">' . $linkcat->description . '</div>';
        }

        $result .= get_the_link_items($linkcat->term_id);
    }
    return $result;
}
