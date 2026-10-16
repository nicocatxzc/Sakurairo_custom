<?php
// 页面路由：站级布局从 layout-grid 开始解析，遇到 #pjax-main 就转到页面部分。
// 页面模板统一声明 iro_render_page_header() 与 iro_render_page_content()，
// 这里只做两件事：按请求载入页面模板，把两个槽的输出塞进解析出来的结构里。

/** 页面布局没铺过，或者铺的不是从 pjax-main 开始，就用这套默认布局 */
const IRO_DEFAULT_PAGE_LAYOUT = '<!-- wp:sakurairo/pjax-main -->
<!-- wp:sakurairo/route-header /-->

<!-- wp:sakurairo/content-layout -->
<!-- wp:sakurairo/route-content /-->
<!-- /wp:sakurairo/content-layout -->
<!-- /wp:sakurairo/pjax-main -->';

// 路由分发：定下这一页的路由名，页面模板与页面组件都用它取名
switch (true) {
    case is_home() || is_front_page():
        define('IRO_PAGE_ROUTE', 'home');
        break;
    case is_single() || is_page():
        define('IRO_PAGE_ROUTE', 'post');
        break;
    case is_search():
        define('IRO_PAGE_ROUTE', 'search');
        break;
    case is_author():
        define('IRO_PAGE_ROUTE', 'author');
        break;
    case is_archive():
        define('IRO_PAGE_ROUTE', 'archive');
        break;
    default:
        define('IRO_PAGE_ROUTE', 'default');
}

require_once get_theme_file_path('/frontend/components/page/' . IRO_PAGE_ROUTE . '.php');

/**
 * 站级布局：开了区块化且铺过站级布局组件就解析它，否则用主题预设栅格
 */
function iro_render_page(): void
{
    if (iro_opt('layout_block_global', false) && ($layout = iro_get_part_content('site-layout')) !== '') {
        iro_render_blocks(parse_blocks($layout));

        return;
    }

    $columns        = min(3, max(1, (int) iro_opt('layout_content_coloumns', 1)));
    $first_position = iro_opt('layout_first_coloumn_position', 'left') === 'right' ? 'right' : 'left';
?>
    <div class="layout-grid" style="<?= iro_layout_style() ?>">
        <?php // 左右由 DOM 顺序决定：写在 #pjax-main 之前的在左，之后的在右 
        ?>
        <?php if ($columns >= 2 && $first_position === 'left') : ?>
            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_first.php'); ?>
        <?php endif; ?>
        <?php if ($columns >= 3 && $first_position === 'right') : ?>
            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_second.php'); ?>
        <?php endif; ?>

        <?php iro_render_blocks(parse_blocks(IRO_DEFAULT_PAGE_LAYOUT)) ?>

        <?php if ($columns >= 3 && $first_position === 'left') : ?>
            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_second.php'); ?>
        <?php endif; ?>
        <?php if ($columns >= 2 && $first_position === 'right') : ?>
            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_first.php'); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 站级布局的块：layout 渲染成栅格，pjax-main 是路由槽——不看里面的内容，
 * 直接交到页面部分，其余块照常解析
 */
function iro_render_blocks(array $blocks): void
{
    foreach ($blocks as $block) {
        switch ($block['blockName']) {
            case 'sakurairo/layout':
    ?>
                <div class="layout-grid" style="<?= iro_layout_style() ?>">
                    <?php iro_render_blocks($block['innerBlocks']) ?>
                </div>
            <?php
                break;
            case 'sakurairo/pjax-main':
                iro_render_page_blocks(iro_page_layout_blocks());
                break;
            default:
                echo render_block($block);
        }
    }
}

/**
 * 页面部分的块：pjax-main 渲染成路由容器，content-layout 只按顺序平铺里面的内容，
 * 页头与正文两个槽跳到页面模板的函数，其余块照常解析
 */
function iro_render_page_blocks(array $blocks): void
{
    foreach ($blocks as $block) {
        switch ($block['blockName']) {
            case 'sakurairo/pjax-main':
            ?>
                <div id="pjax-main" class="pjax-main">
                    <?php iro_render_page_blocks($block['innerBlocks']) ?>
                </div>
<?php
                break;
            case 'sakurairo/content-layout':
                iro_render_page_blocks($block['innerBlocks']);
                break;
            case 'sakurairo/route-header':
                iro_render_page_header();
                break;
            case 'sakurairo/route-content':
                iro_render_page_content();
                break;
            default:
                echo render_block($block);
        }
    }
}

/**
 * 页面布局：开了区块化且铺的组件从 pjax-main 开始才认，否则视为放弃布局
 */
function iro_page_layout_blocks(): array
{
    $switches = [
        'home'    => 'layout_block_homepage',
        'post'    => 'layout_block_post',
        'archive' => 'layout_block_archive',
    ];
    $content = isset($switches[IRO_PAGE_ROUTE]) && (iro_opt($switches[IRO_PAGE_ROUTE], ["layout" => false])["layout"])
        ? iro_get_part_content('page-' . IRO_PAGE_ROUTE . '-layout')
        : '';
    $blocks = $content !== '' ? parse_blocks($content) : [];

    return ($blocks[0]['blockName'] ?? '') === 'sakurairo/pjax-main'
        ? $blocks
        : parse_blocks(IRO_DEFAULT_PAGE_LAYOUT);
}

/** 模板组件的原始内容：站级布局与页面布局都从这里取 */
function iro_get_part_content(string $slug): string
{
    $template = get_block_template(get_stylesheet() . '//' . $slug, 'wp_template_part');

    return $template ? trim((string) $template->content) : '';
}

/** 布局容器的内联变量：侧栏吸顶位置要跟着导航高度走 */
function iro_layout_style(): string
{
    $nav_height = iro_opt('nav_style_select', 'sakura') === 'island' ? '70px' : '3.75rem';

    return '--layout-sticky-top: calc(' . $nav_height . ' + 0.75rem);';
}
