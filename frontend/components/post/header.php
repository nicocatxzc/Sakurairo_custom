<?php require_once get_template_directory() . '/frontend/components/post/head_metas.php'; ?>
<div class="page-header only-word">
    <?php iro_content_container_start(); ?>
    <header class="post-header">
        <h1 class="post-title" style="<?= get_post_meta(get_the_ID(), 'title_style', true) ?>"><?= esc_html(get_the_title()) ?></h1>
        <div class="post-metas">
            <?php iro_render_post_head_metas(); ?>
        </div>
    </header>
    <?php iro_content_container_end(); ?>
</div>