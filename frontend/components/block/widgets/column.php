<?php
// 栏目容器：只负责把若干块分成一栏，本身不画卡片
// 前台渲染拿到的是 InnerBlocks 渲染结果（动态块），所以这里只补容器元素
?>
<div class="iro-widget-tools-column<?= ($attributes['sticky'] ?? false) ? " sticky" : "" ?>">
    <?= $content ?>
</div>
