<?php
// 兜底页面：没有专属模板的请求（含 404），页头与正文给一份与经典形态相近的占位
function iro_render_page_header(): void
{
    ?>
    <header class="page-header only-word">
        <?php iro_content_container_start() ?>
        <h1><?php esc_html_e('页面不存在', 'sakurairo'); ?></h1>
        <?php iro_content_container_end() ?>
    </header>
<?php
}

function iro_render_page_content(): void
{
    iro_content_container_start(['class' => 'page-default']);
    ?>
    <p><?php esc_html_e('你访问的页面可能已被删除，或者链接有误。', 'sakurairo'); ?></p>
<?php
    iro_content_container_end();
}
