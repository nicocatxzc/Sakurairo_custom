<?php
$icons = [
    'task'    => 'fa-regular fa-clipboard',
    'warning' => 'fa-solid fa-warning',
    'noway'   => 'fa-solid fa-square-xmark',
    'buy'     => 'fa-solid fa-square-check',
];

$type = $data['type'] ?? 'task';
$icon = $icons[$type] ?? $icons['task'];
?>

<div class="block-notice">
    <i class="icon <?= esc_attr($type) ?> <?= esc_attr($icon) ?>"></i>
    <span><?= wp_kses_post($data['content']) ?></span>
</div>