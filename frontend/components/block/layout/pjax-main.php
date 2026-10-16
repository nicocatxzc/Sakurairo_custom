<?php
// 路由容器：id 是 PJAX 的替换目标（frontend/app/pjax.js），一个页面只应出现一个；
// 页面布局里再铺一层 pjax-main 时这层只当透明容器，页面槽只在最外层补一次
$iro_pjax_nested = !empty($GLOBALS['iro_pjax_main_open']);
$GLOBALS['iro_pjax_main_open'] = true;
?>
<?php if ($iro_pjax_nested) : ?>
    <?= $content ?>
<?php else : ?>
    <div id="pjax-main" class="pjax-main">
        <?= $content ?>
        <?php
        if (isset($GLOBALS['iro_page_slot_renderer']) && is_callable($GLOBALS['iro_page_slot_renderer'])) {
            ($GLOBALS['iro_page_slot_renderer'])();
        }
        ?>
    </div>
<?php endif; ?>
