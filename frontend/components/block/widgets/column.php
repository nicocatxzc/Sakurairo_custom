<?php
// 栏目容器：只负责把若干块分成一栏，
// 背景与圆角按这一栏自己的设置给前台渲染拿到的是 InnerBlocks 渲染结果（动态块），所以这里只补容器元素
?>
<div class="iro-widget-tools-column<?= ($attributes['sticky'] ?? false) ? " sticky" : "" ?><?= ($attributes['background'] ?? true) ? " with-background" : "" ?>" style="--iro-column-radius: <?= (float) ($attributes['radius'] ?? 0.6) ?>rem;">
    <?= $content ?>
</div>
