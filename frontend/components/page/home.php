<?php
$home_page_style_vars = /* css */ "
--block-title-position:" . iro_opt("homepage_component_title_align", "center") . "
";
// 开始容器
iro_content_container_start(['style' => $home_page_style_vars, "class" => "page-home"]);
?>

<?php foreach (iro_opt("homepage_components", ["post_list"]) as $component) : ?>
    <?php
    switch ($component):
        case 'show':
    ?>
            <h2 class="block-title flex-center">
                <i class="<?= iro_opt("homepage_show_title")["icon"] ?>"></i>
                <?= iro_opt("homepage_show_title")["text"] ?>
            </h2>
        <?php
            require_once get_template_directory() . '/frontend/components/homepage/show.php';
            break;
        case 'post_list':
        ?>
            <h2 class="block-title flex-center">
                <i class="<?= iro_opt("homepage_post_list_title")["icon"] ?>"></i>
                <?= iro_opt("homepage_post_list_title")["text"] ?>
            </h2>
    <?php
            require_once get_template_directory() . '/frontend/components/post/list.php';
            break;
        case 'static_page':
            if (get_post(iro_opt("homepage_static_page_id"))) {
                setup_postdata((iro_opt("homepage_static_page_id")));
                require_once get_template_directory() . '/frontend/components/post/render.php';
                wp_reset_postdata();
            }
            break;
    endswitch;
    ?>
<?php endforeach; ?>

<?php iro_content_container_end(); ?>