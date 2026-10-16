<?php
// 主布局容器：栅格与收缩规则在 frontend/layout.scss（经典路径与区块路径共用一套），
// 这里只输出容器元素，栏目就是 $content 里的栏目容器，主题不再给它们套壳
?>
<div class="layout-grid" style="<?= iro_layout_style() ?>">
    <?= $content ?>
</div>
