<?php
$term = get_queried_object();

$term_name = __("未知的分类", "sakurairo");
$term_description = false;
if ($term && ! is_wp_error($term)) {
    $term_name        = $term->name;
    $term_description = $term->description;
}
?>

<div class="page-taxonomy">
    <?php iro_content_container_start() ?>
    <header class="taxonomy-header">
        <h1><?= $term_name ?></h1>
        <?php if (term_description()): ?>
            <p><?= $term_description ?></p>
        <?php endif; ?>
    </header>
    <?php require_once get_template_directory() . '/frontend/components/post/list.php'; ?>
    <?php iro_content_container_end() ?>
</div>