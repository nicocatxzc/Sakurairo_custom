<?php
// 目录容器：目录内容由 tocbot（frontend/components/page/post.js）扫正文标题生成
$toc_title = $attributes['title'] ?? '';
?>
<div class="iro-widget-tools iro-widget-tools-toc">
    <?php if ($toc_title): ?>
        <h2 class="iro-widget-tools-title"><?= esc_html($toc_title) ?></h2>
    <?php endif ?>
    <div id="toc"></div>
</div>
