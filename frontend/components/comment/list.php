<?php
global $iro_only_template;
if (!$iro_only_template): ?>
    <h3 class="comment-list-title">
        Comments
        <span class="comment-count"><?= number_format_i18n(get_comments_number()) ?>条评论</span>
    </h3>
    <ol class="comment-list">
    <?php endif ?>
    <?php if (have_comments()) : ?>
        <?php
        require_once get_template_directory() . "/frontend/components/comment/card.php";
        wp_list_comments([
            'style'      => 'ol',
            'short_ping' => true,
            'avatar_size' => 48,
            'callback'    => 'iro_comment_render',
        ]);
        ?>
        <?php
        // 评论分页
        iro_comment_pagination();
        ?>
    <?php endif; ?>
    <?php if (!$iro_only_template): ?>
    </ol>
<?php endif; ?>