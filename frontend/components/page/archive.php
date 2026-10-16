<?php
// 分类等归档：页头是分类名与描述，正文是文章列表，外壳是 .page-taxonomy 容器
function iro_render_page_header(): void
{
    $term             = get_queried_object();
    $term_name        = __("未知的分类", "sakurairo");
    $term_description = false;
    if ($term && ! is_wp_error($term)) {
        $term_name        = $term->name;
        $term_description = $term->description;
    }
    ?>
    <header class="page-header taxonomy-header">
        <?php iro_content_container_start() ?>
        <h1><?= $term_name ?></h1>
        <?php if (term_description()): ?>
            <p><?= $term_description ?></p>
        <?php endif; ?>
        <?php iro_content_container_end() ?>
    </header>
    <?php
}

function iro_render_page_content(): void
{
    iro_content_container_start(['class' => 'page-taxonomy']);
    require_once get_theme_file_path('/frontend/components/post/list.php');
    iro_content_container_end();
}
