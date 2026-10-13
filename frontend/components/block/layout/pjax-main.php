<?php
// 主内容容器：id 是 PJAX 的替换目标（frontend/app/pjax.js），一个页面只应出现一个；
// display:contents 由 frontend/layout.scss 给出，所以里面的块直接参与布局容器的排列
?>
<div id="pjax-main" class="pjax-main">
    <?= $content ?>
</div>
