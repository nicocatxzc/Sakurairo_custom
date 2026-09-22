<?php
if (!iro_opt('article_nextpre', true) || !is_single()) {
    return;
}

// get_adjacent_post() 只保证未登录访客看不到私有文章，登录且有权限时仍可能返回私有文章与密码文章
$iro_public_post_where = fn(string $where) => $where . " AND p.post_status = 'publish' AND p.post_password = ''";
add_filter('get_previous_post_where', $iro_public_post_where);
add_filter('get_next_post_where', $iro_public_post_where);
$iro_prev_post = get_previous_post();
$iro_next_post = get_next_post();
remove_filter('get_previous_post_where', $iro_public_post_where);
remove_filter('get_next_post_where', $iro_public_post_where);
?>
<?php if ($iro_prev_post || $iro_next_post): ?>
    <?php iro_content_container_start() ?>
    <nav class="post-nav">
        <?php if ($iro_prev_post): ?>
            <a href="<?= esc_url(get_permalink($iro_prev_post)) ?>" class="nav-prev">
                <span class="nav-label">← <?= esc_html__('上一篇', 'sakurairo') ?></span>
                <span class="nav-title"><?= esc_html(get_the_title($iro_prev_post)) ?></span>
            </a>
        <?php endif; ?>
        <?php if ($iro_next_post): ?>
            <a href="<?= esc_url(get_permalink($iro_next_post)) ?>" class="nav-next">
                <span class="nav-label"><?= esc_html__('下一篇', 'sakurairo') ?> →</span>
                <span class="nav-title"><?= esc_html(get_the_title($iro_next_post)) ?></span>
            </a>
        <?php endif; ?>
    </nav>
    <?php iro_content_container_end() ?>
<?php endif; ?>