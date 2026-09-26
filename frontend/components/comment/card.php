<?php
function iro_comment_render($comment, $args=[], $depth=[])
{
    $GLOBALS['comment'] = $comment;
    $comment_id = get_comment_ID();
    $parent_id  = $comment->comment_parent;
?>
    <li id="comment-<?= $comment_id; ?>" <?php comment_class('comment-card'); ?>>
        <div class="comment">
            <button
                class="reply-button"
                data-commentid="<?= $comment_id; ?>"
                data-commentauthor="<?= esc_attr(get_comment_author()); ?>">
                <?= __("回复",'sakurairo') ?>
            </button>

            <section class="comment-infos">
                <span class="author-avatar">
                    <?= get_avatar($comment, 48, '', '', ['class' => 'nuxtpic']); ?>
                </span>
                <div class="comment-metas flex-center">
                    <span class="author-name"><?php comment_author(); ?></span>
                    <div class="comment-meta-list">
                        <time datetime="<?php comment_date('c'); ?>">
                            <?= __("发布于",'sakurairo') ?> <?php comment_date(); ?>
                        </time>
                    </div>
                </div>
            </section>

            <section class="comment-content">
                <?php if ($parent_id) : ?>
                    <a href="#comment-<?= $parent_id; ?>" class="reply">
                        @<?= esc_html(get_comment_author($parent_id)); ?>
                    </a>
                <?php endif; ?>
                <?php comment_text(); ?>
            </section>
        </div>
    <?php
    // 不写</li>，wp_list_comments 会补
}
