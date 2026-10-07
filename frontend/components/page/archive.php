<?php
$term = get_queried_object();

$term_name = __("未知的分类", "sakurairo");
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

<?php iro_content_container_start(['class' => 'page-taxonomy']) ?>
<?php require_once get_theme_file_path('/frontend/components/post/list.php'); ?>
<?php iro_content_container_end() ?>