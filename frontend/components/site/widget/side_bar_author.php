<?php
// 博主信息：名称/简介/头像留空时回退站点信息与站点图标
$author_title = $attributes['title'] ?? '';
$author_name = $attributes['name'] ?? '';
$author_description = $attributes['description'] ?? '';
$author_avatar = $attributes['avatar'] ?? '';
$author_show_stats = $attributes['showStats'] ?? true;

if ($author_name === '') {
    $author_name = get_bloginfo('name');
}

if ($author_description === '') {
    $author_description = get_bloginfo('description');
}

if ($author_avatar === '') {
    $author_avatar = get_site_icon_url() ?: iro_opt('missing_avatars_placeholder', '');
}

$author_post_count = (int) wp_count_posts('post')->publish;
$author_category_count = wp_count_terms(['taxonomy' => 'category', 'hide_empty' => true]);
$author_tag_count = wp_count_terms(['taxonomy' => 'post_tag', 'hide_empty' => true]);

// 分类/标签计数在分类法不存在时返回 WP_Error，按 0 处理
$author_category_count = is_wp_error($author_category_count) ? 0 : (int) $author_category_count;
$author_tag_count = is_wp_error($author_tag_count) ? 0 : (int) $author_tag_count;
?>
<section class="iro-sidebar-author">
    <?php if ($author_title): ?>
        <h2 class="iro-side-bar-title"><?= esc_html($author_title) ?></h2>
    <?php endif ?>

    <picture class="author-avatar">
        <?= iro_media_optimize_image_formats(
            $author_avatar,
            ["width" => 100, "height" => 100],
            ["class" => "avatar", "alt" => $author_name]
        ) ?>
    </picture>

    <h6 class="author-name"><?= esc_html($author_name) ?></h6>

    <?php if ($author_description): ?>
        <h6 class="author-description"><?= esc_html($author_description) ?></h6>
    <?php endif ?>

    <?php if ($author_show_stats): ?>
        <nav class="site-state">
            <div class="site-state-item">
                <span class="site-state-item-count"><?= $author_post_count ?></span>
                <span class="site-state-item-name"><?= esc_html__("文章", "sakurairo") ?></span>
            </div>
            <div class="site-state-item">
                <span class="site-state-item-count"><?= $author_category_count ?></span>
                <span class="site-state-item-name"><?= esc_html__("分类", "sakurairo") ?></span>
            </div>
            <div class="site-state-item">
                <span class="site-state-item-count"><?= $author_tag_count ?></span>
                <span class="site-state-item-name"><?= esc_html__("标签", "sakurairo") ?></span>
            </div>
        </nav>
    <?php endif ?>
</section>