<?php
// 聚焦文字：普通文字与 Mashiro 特效文字共用一套字体/大小/字重/颜色
$text = (string) ($attributes['text'] ?? (iro_opt('cover_title', [])['text'] ?? ''));
$weight = (string) ($attributes['weight'] ?? '');
?>
<h1 class="cover-title<?= ($attributes['effect'] ?? false) ? " glitch" : "" ?>"
    data-text="<?= esc_attr($text) ?>"
    style="
        font-family: <?= esc_attr((string) ($attributes['font'] ?? '')) ?>;
        font-size: <?= (float) ($attributes['size'] ?? 5) ?>rem;
        color: <?= esc_attr((string) ($attributes['color'] ?? '#FFF')) ?>;
        <?= $weight !== '' ? 'font-weight:' . esc_attr($weight) . ';' : '' ?>
    ">
    <?= esc_html($text) ?>
</h1>
