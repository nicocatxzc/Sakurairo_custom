<?php
/** 布局容器的内联变量：侧栏吸顶位置要跟着导航高度走 */
function iro_layout_style(): string
{
    $nav_height = iro_opt('nav_style_select', 'sakura') === 'island' ? '70px' : '3.75rem';

    return '--layout-sticky-top: calc(' . $nav_height . ' + 0.75rem);';
}

/** 当前页面按哪个路由取名（由 index.php 的路由分发写入） */
function iro_page_route_key(): string
{
    return (string) ($GLOBALS['iro_page_route_key'] ?? '');
}

/** 当前页面走不走区块化分支：index.php 按路由开关与布局组件是否铺过决定 */
function iro_page_block_mode(): bool
{
    return (bool) ($GLOBALS['iro_page_block_mode'] ?? false);
}

/** 路由的布局组件铺过没有：只看有没有内容，不渲染 */
function iro_has_page_part(string $route_key, string $kind): bool
{
    $template = get_block_template(get_stylesheet() . '//page-' . $route_key . '-' . $kind, 'wp_template_part');

    return $template && trim((string) $template->content) !== '';
}

/** 当前路由的页面组件内容；没开区块化或组件为空都返回空串 */
function iro_page_part_html(string $kind): string
{
    if (!iro_page_block_mode()) {
        return '';
    }
    $route = (string) ($GLOBALS['iro_page_route_key'] ?? '');
    if ($route === '') {
        return '';
    }
    // 文章页页头按有没有特色图分两个组件
    $slug = $route === 'post' && $kind === 'header'
        ? (has_post_thumbnail() ? 'page-post-header-with-image' : 'page-post-header-simple')
        : 'page-' . $route . '-' . $kind;

    return iro_render_block_part($slug);
}
