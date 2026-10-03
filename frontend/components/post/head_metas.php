<?php
/**
 * 文章头信息条目
 *
 * 与上游 tpl/entry-census.php 对应：显示哪些条目、以什么顺序显示，都由设置项 post_head_metas 决定。
 * header.php 与 header_with_image.php 共用同一份渲染，配色等差异由各自 .post-metas 上下文决定。
 */
function iro_render_post_head_metas(): void
{
    $metas = iro_opt('post_head_metas', ['update_time', 'author', 'views']);
    if (!is_array($metas)) {
        $metas = ['update_time', 'author', 'views'];
    }
    foreach ($metas as $meta) :
        switch ($meta):
            case 'author': ?>
                <a href="<?= esc_url(get_author_posts_url(get_the_author_meta('ID'))) ?>">
                    <span class="meta-author">
                        <?= get_avatar(get_the_author_meta('ID'), 24, '', get_the_author(), ['class' => 'avatar-small']) ?>
                        <?= esc_html(get_the_author()) ?>
                    </span>
                </a>
            <?php break; ?>
            <?php
            case 'category':
                $categories = get_the_category();
                if ($categories) : ?>
                    <span>
                        <a href="<?= esc_url(get_category_link($categories[0])) ?>"><?= esc_html($categories[0]->name) ?></a>
                    </span>
                <?php else : ?>
                    <span><?= esc_html__('未分类', 'sakurairo') ?></span>
                <?php endif; ?>
                <?php break; ?>
            <?php
            case 'comment_count': ?>
                <span class="comments-number">
                    <a href="#comments"><?= esc_html(number_format_i18n(get_comments_number())) . ' ' . esc_html__('条评论', 'sakurairo') ?></a>
                </span>
                <?php break; ?>
            <?php
            case 'views': ?>
                <span><?= esc_html(iro_get_post_views(get_the_ID())) . ' ' . esc_html__('次阅读', 'sakurairo') ?></span>
                <?php break; ?>
            <?php
            case 'words_count':
                $words_count = iro_get_post_words(get_the_ID());
                if ($words_count) : ?>
                    <span><?= esc_html($words_count) . ' ' . esc_html__('字', 'sakurairo') ?></span>
                <?php endif; ?>
                <?php break; ?>
            <?php
            case 'reading_time':
                $reading_time = iro_get_reading_time(get_the_ID());
                if ($reading_time) : ?>
                    <span><?= esc_html__('预计阅读时间', 'sakurairo') . ': ' . esc_html($reading_time) ?></span>
                <?php endif; ?>
                <?php break; ?>
            <?php
            case 'publish_time': ?>
                <span><?= esc_html__('发布于', 'sakurairo') . ' ' . get_the_date('Y-m-d') ?></span>
                <?php break; ?>
            <?php
            case 'update_time': ?>
                <span><?= esc_html__('最后更新于', 'sakurairo') . ' ' . get_the_modified_time('Y-m-d') ?></span>
                <?php break; ?>
            <?php
            case 'editor_link':
                // get_edit_post_link() 默认按 display 上下文返回，URL 已转义，不能再套一次 esc_url
                $edit_url = get_edit_post_link();
                if ($edit_url) : ?>
                    <a href="<?= $edit_url ?>"><?= esc_html__('编辑', 'sakurairo') ?></a>
                <?php endif; ?>
                <?php break; ?>
        <?php endswitch; ?>
    <?php endforeach;
}
