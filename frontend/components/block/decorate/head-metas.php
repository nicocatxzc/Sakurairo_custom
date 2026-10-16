<?php
// 文章头信息：装饰占位块，不搬实现，直接用文章组件里那份渲染
require_once get_theme_file_path('/frontend/components/post/head_metas.php');

iro_render_post_head_metas();
