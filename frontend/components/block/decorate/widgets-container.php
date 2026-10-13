<?php
// 卡片容器：宽度按父容器百分比，圆角与内边距（上下/左右）由变量控制
?>
<div class="iro-widgets-container<?= ($attributes['preset'] ?? false) ? " iro-widgets-container-cover" : "" ?>"
    style="
        --iro-widgets-radius: <?= (float) ($attributes['radius'] ?? iro_opt('cover_infor_bar_radius', 1)) ?>rem;
        --iro-widgets-width: <?= (float) ($attributes['width'] ?? 65) ?>%;
        --iro-widgets-padding-y: <?= (float) ($attributes['paddingY'] ?? 1) ?>rem;
        --iro-widgets-padding-x: <?= (float) ($attributes['paddingX'] ?? 1) ?>rem;
    ">
    <?= $content ?>
</div>
