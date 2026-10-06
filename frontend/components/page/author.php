<?php $author = get_queried_object_id(); ?>

<?php iro_content_container_start() ?>
<div class="author-info-container">
    <div class="author-info">
        <div
            class="author-avatar"
            style="--post-count:'<?= count_user_posts($author) ?>'">
            <picture class="nuxtpic">
                <?= iro_media_optimize_image_formats(
                    get_avatar_url($author, ['size' => 150]),
                    ['width' => '4.6rem', 'height' => '4.6rem'],
                    ['alt' => sprintf('avatar of %s', get_the_author_meta('display_name', $author))]
                ) ?>
            </picture>
        </div>
        <div class="author-desc">
            <h3 class="name"><?= esc_html(get_the_author_meta('display_name', $author)) ?></h3>
            <div class="description">
                <?= wp_kses_post(get_the_author_meta('description', $author)) ?>
            </div>
        </div>
    </div>
</div>
<?php require_once get_template_directory() . '/frontend/components/post/list.php'; ?>
<?php iro_content_container_end() ?>