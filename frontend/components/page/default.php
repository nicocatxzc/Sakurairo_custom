<?php
// 兜底页面
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
