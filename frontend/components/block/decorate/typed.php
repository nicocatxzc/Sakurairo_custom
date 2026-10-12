<?php
// 打字机：区块自带配置时内联给前台，否则前台回退到全局 typed_config
$typed_config = json_decode(trim((string) ($attributes['config'] ?? '')), true);
$mark = $attributes['mark'] ?? false;
$placeholder = (string) ($attributes['placeholder'] ?? iro_opt('cover_typedjs_placeholder', ''));
?>
<div class="typed-container"<?= is_array($typed_config) && $typed_config ? ' data-typed="' . hachimi_encode_data(['config' => $typed_config]) . '"' : '' ?>>
    <?php if ($mark) : ?>
        <i class="fa-icon-solid fa-quote-left"></i>
    <?php endif; ?>
    <span class="typed"><?= esc_html($placeholder) ?></span>
    <?php if ($mark) : ?>
        <i class="fa-icon-solid fa-quote-right"></i>
    <?php endif; ?>
</div>
