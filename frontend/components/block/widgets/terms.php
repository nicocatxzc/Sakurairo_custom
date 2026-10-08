<?php
// 分类/标签：列表与按钮两种样式共用同一套结构，差异只在样式类上
$terms_taxonomy = ($attributes['taxonomy'] ?? 'category') === 'post_tag' ? 'post_tag' : 'category';
$terms_title = $attributes['title'] ?? '';
$terms_style = ($attributes['style'] ?? 'list') === 'button' ? 'button' : 'list';
$terms_show_count = $attributes['showCount'] ?? true;
$terms_limit = min(100, max(1, (int) ($attributes['limit'] ?? 10)));

$terms = get_terms([
    'taxonomy'   => $terms_taxonomy,
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
    'number'     => $terms_limit,
]);

if (is_wp_error($terms) || empty($terms)) {
    return;
}

// 标题留空时用分类法自己的名称（分类 / 标签）
if ($terms_title === '') {
    $terms_title = get_taxonomy($terms_taxonomy)->labels->name;
}
?>
<section class="iro-sidebar-terms">
    <h2 class="iro-side-bar-title"><?= esc_html($terms_title) ?></h2>

    <ul class="terms-<?= $terms_style ?>">
        <?php foreach ($terms as $term): ?>
            <li>
                <a class="term-item" href="<?= esc_url(get_term_link($term)) ?>">
                    <span class="term-name"><?= esc_html($term->name) ?></span>
                    <?php if ($terms_show_count): ?>
                        <span class="term-count"><?= (int) $term->count ?></span>
                    <?php endif ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</section>
