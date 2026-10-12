<?php
// 布局无关的容器：只管外观（圆角、内边距、卡片底色），宽度与排列交给外层布局
?>
<div class="iro-widgets-container<?= ($attributes['preset'] ?? false) ? " iro-widgets-container-cover" : "" ?>"
    style="
        --iro-widgets-radius: <?= (float) ($attributes['radius'] ?? iro_opt('cover_infor_bar_radius', 1)) ?>rem;
        --iro-widgets-padding: <?= (float) ($attributes['padding'] ?? 1) ?>rem;
    ">
    <?= $content ?>
</div>
