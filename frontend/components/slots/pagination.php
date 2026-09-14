<?php
add_filter('paginate_links_output', function ($output) {
    return str_replace('class="page-numbers', 'class="page-numbers no-pjax', $output);
});

function iro_post_pagination()
{
    global $wp_query;
    if (iro_opt("pagination_mode", "pagination") == "pagination"):
        the_posts_pagination([
            'class'    => 'pagination',
            'mid_size'  => 2,
            'prev_text' => '<',
            'next_text' => '>',
        ]);
    else:
        $paged     = max(1, (int) get_query_var('paged'));
        $max_pages = (int) $wp_query->max_num_pages;
        if ($paged < $max_pages): ?>
            <div class="pagination flex-center">
                <a
                    class="ajax-pagination no-pjax"
                    href="<?= esc_url(get_pagenum_link($paged + 1)) ?>"
                    data-next-page="<?= $paged + 1 ?>"
                    data-max-pages="<?= $max_pages ?>">
                    <?= esc_html__("加载更多", "sakurairo") ?>
                </a>
            </div>
        <?php else: ?>
            <div class="pagination flex-center">
                <?= esc_html__("已经到头啦", "sakurairo") ?>
            </div>
<?php endif;
    endif;
}

function iro_comment_pagination()
{
    the_comments_pagination([
        'class'    => 'pagination',
        'mid_size'  => 2,
        'prev_text' => '<',
        'next_text' => '>',
    ]);
}
