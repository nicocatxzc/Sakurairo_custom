<?php
$term = get_queried_object();

$term_name = __("未知的分类", "sakurairo");
$term_description = false;
if ($term && ! is_wp_error($term)) {
    $term_name        = $term->name;
    $term_description = $term->description;
}

// 页头区：区块化时换成组件里排的页头
ob_start();
if (($iro_header_part = iro_page_part_html('header')) !== '') : ?>
    <?= $iro_header_part ?>
<?php else : ?>
    <header class="page-header taxonomy-header">
        <?php iro_content_container_start() ?>
        <h1><?= $term_name ?></h1>
        <?php if (term_description()): ?>
            <p><?= $term_description ?></p>
        <?php endif; ?>
        <?php iro_content_container_end() ?>
    </header>
<?php endif;
$GLOBALS['iro_page_header_html'] = ob_get_clean();

// 正文区：区块化时换成组件里排的正文，外壳（.page-taxonomy 容器）不变
ob_start();
iro_content_container_start(['class' => 'page-taxonomy']);
if (($iro_content_part = iro_page_part_html('content')) !== '') :
    echo $iro_content_part;
else :
    require_once get_theme_file_path('/frontend/components/post/list.php');
endif;
iro_content_container_end();
$GLOBALS['iro_page_body_html'] = ob_get_clean();

// 区块化分支：页面布局组件接管排版，页头槽与正文槽取上面两个缓冲
if (iro_page_block_mode()) {
    echo iro_render_block_part('page-' . iro_page_route_key() . '-layout');
} else {
    echo $GLOBALS['iro_page_header_html'] . $GLOBALS['iro_page_body_html'];
}
