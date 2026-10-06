<?php require_once get_template_directory() . '/frontend/components/post/head_metas.php'; ?>
<div class="page-header with-image">
    <?php if (has_post_thumbnail()) : ?>
        <picture>
            <?= iro_media_optimize_image_formats(
                get_the_post_thumbnail_url(get_the_ID(), 'large'),
                [
                    'width' => 1920,
                    'height' => '25rem',
                    'fit' => 'cover',
                    'sizes' => '100vw',
                ],
                [
                    'class' => 'feature-image wp-post-image',
                    'alt' => (string) get_post_meta(get_post_thumbnail_id(get_the_ID()), '_wp_attachment_image_alt', true),
                    'decoding' => 'async',
                    'fetchpriority' => 'high',
                ]
            ) ?>
        </picture>
    <?php endif; ?>
    <header class="post-header">
        <h1 class="post-title" style="<?= get_post_meta(get_the_ID(), 'title_style', true) ?>"><?= esc_html(get_the_title()) ?></h1>
        <div class="post-metas">
            <?php iro_render_post_head_metas(); ?>
        </div>
    </header>
</div>
