<?php
add_filter('paginate_links_output', function ($output) {
    return str_replace('class="page-numbers', 'class="page-numbers no-pjax', $output);
});

function iro_post_pagination()
{
    the_posts_pagination([
        'class'    => 'pagination',
        'mid_size'  => 2,
        'prev_text' => '<',
        'next_text' => '>',
    ]);
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
