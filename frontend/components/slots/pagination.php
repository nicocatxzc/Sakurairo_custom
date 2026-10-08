<?php
add_filter('paginate_links_output', function ($output) {
    return str_replace('class="page-numbers', 'class="page-numbers no-pjax widget-button', $output);
});

/**
 * 给翻页地址补上只取内容标记
 *
 * 只能用查询参数形式：paginate_links() 会拿 get_pagenum_link() 的返回当 base，继续加查询参数会损坏链接
 */
function iro_pagination_md_link(string $link): string
{
    return add_query_arg('md', '', $link);
}

function iro_post_pagination()
{
    global $wp_query, $iro_is_md_template;

    // 主题被以 md 形态请求时，翻页链接也保持该形态，
    // agent 顺着往后翻就不用回到整页 HTML
    if (!empty($iro_is_md_template)) {
        add_filter('get_pagenum_link', 'iro_pagination_md_link');
    }

    if (iro_opt("pagination_mode", "pagination") == "pagination"):
        the_posts_pagination([
            'class'    => 'site-pagination',
            'mid_size'  => 2,
            'prev_text' => '<',
            'next_text' => '>',
        ]);
    else:
        $paged     = max(1, (int) get_query_var('paged'));
        $max_pages = (int) $wp_query->max_num_pages;
        if ($paged < $max_pages): ?>
            <div class="site-pagination flex-center">
                <a
                    class="ajax-pagination no-pjax"
                    href="<?= esc_url(get_pagenum_link($paged + 1)) ?>"
                    data-next-page="<?= $paged + 1 ?>"
                    data-max-pages="<?= $max_pages ?>">
                    <?= esc_html__("加载更多", "sakurairo") ?>
                </a>
            </div>
        <?php else: ?>
            <div class="site-pagination flex-center">
                <?= esc_html__("已经到头啦", "sakurairo") ?>
            </div>
<?php endif;
    endif;
}

function iro_comment_pagination()
{
    the_comments_pagination([
        'class'    => 'site-pagination',
        'mid_size'  => 2,
        'prev_text' => '<',
        'next_text' => '>',
    ]);
}
