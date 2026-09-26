<div class="page-header only-word">
    <?php iro_content_container_start(); ?>
    <header class="post-header">
        <h1 class="post-title" style="<?= get_post_meta(get_the_ID(), 'title_style', true) ?>"><?= esc_html(get_the_title()) ?></h1>
        <div class="post-metas">
            <span class="meta-time"><?= __("更新于：",'sakurairo') ?><?= get_the_modified_date('Y' . __("年", "sakurairo") . 'm' . __("月", "sakurairo") . 'd' . __("日", 'sakurairo')) ?></span>
            <a href="<?= esc_url(get_author_posts_url(get_the_author_meta('ID'))) ?>">
                <span class="meta-author">
                    <?= get_avatar(get_the_author_meta('ID'), 24, '', get_the_author(), ['class' => 'avatar-small']) ?>
                    <?= esc_html(get_the_author()) ?>
                </span>
            </a>
        </div>
    </header>
    <?php iro_content_container_end(); ?>
</div>