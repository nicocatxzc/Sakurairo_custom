<?php $author = get_queried_object_id(); ?>

<?php iro_content_container_start() ?>
<div class="author-info-container">
    <div class="author-info">
        <div
            class="author-avatar"
            style="--post-count:'<?= count_user_posts($author) ?>'">
            <img
                src="<?= iro_media_optimize_image_url(esc_url(get_avatar_url($author, ['size' => 150]))) ?>"
                alt="avatar of <?= esc_attr(get_the_author_meta('display_name', $author)) ?>"
                class="nuxtpic" />
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