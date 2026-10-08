<?php
$icons = [
    'task'    => 'fa-icon-regular fa-clipboard',
    'warning' => 'fa-icon-solid fa-warning',
    'noway'   => 'fa-icon-solid fa-square-xmark',
    'buy'     => 'fa-icon-solid fa-square-check',
];

$type = $data['type'] ?? 'task';
$icon = $icons[$type] ?? $icons['task'];
?>

<div class="block-notice">
    <i class="icon <?= esc_attr($type) ?> <?= esc_attr($icon) ?>"></i>
    <span><?= wp_kses_post($data['content']) ?></span>
</div>