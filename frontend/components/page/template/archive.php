<?php
$archive_posts = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
]);
?>
<div class="page-archive page-template">
    <ol class="list" id="archive-list">
        <?php foreach ($archive_posts as $post) : ?>
            <li
                class="item"
                data-date-gmt="<?= esc_attr($post->post_date_gmt) ?>">
                <a href="<?= esc_url(get_permalink($post)) ?>">
                    <?= esc_html(get_the_title($post)) ?>
                </a>
                <time datetime="<?= esc_attr($post->post_date_gmt) ?>">
                    <?= esc_html(get_the_date(iro_i18n_date_format(), $post)) ?>
                </time>
            </li>
        <?php endforeach; ?>
    </ol>
</div>