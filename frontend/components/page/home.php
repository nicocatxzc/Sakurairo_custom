<?php
// 页头区：首页没有页头
$GLOBALS['iro_page_header_html'] = '';

// 正文区：区块化时换成组件里排的正文，外壳（.page-home 容器）不变
ob_start();
$home_page_style_vars = /* css */ "
--block-title-position:" . iro_opt("homepage_component_title_align", "center") . "
";
// 开始容器
iro_content_container_start(['style' => $home_page_style_vars, "class" => "page-home"]);
?>

<?php if (($iro_content_part = iro_page_part_html('content')) !== '') : ?>
    <?= $iro_content_part ?>
<?php else : ?>
    <?php foreach (iro_opt("homepage_components", ["post_list"]) as $component) : ?>
        <?php
        switch ($component):
            case 'show':
        ?>
                <h2 class="block-title flex-center">
                    <i class="<?= iro_opt("homepage_show_title", [])["icon"] ?? "" ?>"></i>
                    <?= iro__((string) (iro_opt("homepage_show_title", [])["text"] ?? "")) ?>
                </h2>
            <?php
                require_once get_theme_file_path('/frontend/components/homepage/show.php');
                break;
            case 'post_list':
            ?>
                <h2 class="block-title flex-center">
                    <i class="<?= iro_opt("homepage_post_list_title", [])["icon"] ?? "" ?>"></i>
                    <?= iro__((string) (iro_opt("homepage_post_list_title", [])["text"] ?? "")) ?>
                </h2>
        <?php
                require_once get_theme_file_path('/frontend/components/post/list.php');
                break;
            case 'static_page':
                if (get_post(iro_opt("homepage_static_page_id"))) {
                    setup_postdata((iro_opt("homepage_static_page_id")));
                    require_once get_theme_file_path('/frontend/components/post/render.php');
                    wp_reset_postdata();
                }
                break;
        endswitch;
        ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php
iro_content_container_end();
$GLOBALS['iro_page_body_html'] = ob_get_clean();

// 区块化分支：页面布局组件接管排版，页头槽与正文槽取上面两个缓冲
if (iro_page_block_mode()) {
    echo iro_render_block_part('page-' . iro_page_route_key() . '-layout');
} else {
    echo $GLOBALS['iro_page_header_html'] . $GLOBALS['iro_page_body_html'];
}
