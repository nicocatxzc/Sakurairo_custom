<?php require_once get_template_directory() . '/frontend/components/post/head_metas.php'; ?>
<div class="page-header with-image">
    <?php if (has_post_thumbnail()) : ?>
        <?= get_the_post_thumbnail(get_the_ID(), 'large', ['class' => 'nuxtpic feature-image']) ?>
    <?php endif; ?>
    <header class="post-header">
        <h1 class="post-title" style="<?= get_post_meta(get_the_ID(), 'title_style', true) ?>"><?= esc_html(get_the_title()) ?></h1>
        <div class="post-metas">
            <?php iro_render_post_head_metas(); ?>
        </div>
    </header>
</div>
