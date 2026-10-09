<?php
if (!defined('ABSPATH')) {
    exit;
}

const IRO_LLMS_ROUTE_VAR = 'iro_llms';
const IRO_LLMS_CACHE_KEY = 'iro_llms_cache';

require_once __DIR__ . '/llms-txt.php';
require_once __DIR__ . '/list.php';
require_once __DIR__ . '/post.php';

if (iro_opt('iro_llms_txt', false)) {
    // 把 .md 形态的请求路径摘掉后缀，让wp主查询完成解析，必须早于 WP 解析请求
    // 摘掉后核心自动映射，模板里的 have_posts() 都能直接用。
    // WP::parse_request() 在 init 之后才跑，所以这里改 REQUEST_URI 还来得及。
    add_action('init', static function (): void {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        // 主页
        if (preg_match('#index\.md(?=\?|$)#', $uri)) {
            $uri = (string) preg_replace('#index\.md(?=\?|$)#', '', $uri, 1);
        // 其他
        } elseif (preg_match('#\.md(?=\?|$)#', $uri)) {
            $uri = (string) preg_replace('#\.md(?=\?|$)#', '', $uri, 1);
        } else {
            return;
        }

        $_SERVER['REQUEST_URI'] = $uri;
        // 全局生命本次渲染只需要正文
        $GLOBALS['iro_is_md_template'] = true;
    }, 0);

    add_action('init', 'iro_llms_register_routes', 10);
    add_action('template_redirect', 'iro_llms_dispatch', 1);
    add_action('wp_head', 'iro_llms_head_links', 99);

    add_filter('query_vars', static function (array $vars): array {
        $vars[] = IRO_LLMS_ROUTE_VAR;

        return $vars;
    });

    foreach (['deleted_post', 'edited_term', 'delete_term'] as $hook) {
        add_action($hook, 'iro_llms_flush_cache', 10, 0);
    }
}

function iro_llms_register_routes(): void
{
    add_rewrite_rule('^llms\.txt$', 'index.php?' . IRO_LLMS_ROUTE_VAR . '=txt', 'top');
}

function iro_llms_flush_cache(): void
{
    delete_transient(IRO_LLMS_CACHE_KEY);
}

// 保存设置那一刻 iro_opt() 读到的还是旧值，只靠上面那批钩子会让刚打开的路由一直缺失，
// 所以这里直接读库里的新值补注册一次，再重建规则
add_action('update_option_iro_options', static function (): void {
    if (!empty(get_option('iro_options')['iro_llms_txt'])) {
        iro_llms_register_routes();
        iro_llms_flush_cache();
    }

    flush_rewrite_rules();
});

add_action('after_switch_theme', static function (): void {
    flush_rewrite_rules();
});

function iro_llms_dispatch(): void
{
    // llms.txt
    if (get_query_var(IRO_LLMS_ROUTE_VAR) === 'txt') {
        iro_llms_render_txt();
        exit;
    }

    if (!iro_llms_md_request()) {
        return;
    }

    // 查询参数形态在这里才认出来，同样在劫持时置位
    $GLOBALS['iro_is_md_template'] = true;

    // 文章和页面
    if (is_singular()) {
        iro_llms_render_markdown(get_queried_object_id());
        exit;
    }

    // 首页/分类和标签
    if (is_home() || is_front_page() || is_category() || is_tag()) {
        iro_llms_render_list();
        exit;
    }

    // 其余未制作独立模板的页面类型
    wp_safe_redirect(iro_llms_proper_url(), 302);
    exit;
}

/**
 * 本次请求是否要求「只取内容」的形态
 *
 * .md 后缀在 init 就被摘掉了，所以靠规范化时置位的 $iro_is_md_template 判断；
 * ?md 是标记参数，空值（?md）也算数，因此只能判断存在
 */
function iro_llms_md_request(): bool
{
    return !empty($GLOBALS['iro_is_md_template']) || isset($_GET['md']);
}

/**
 * 用标准 link relation 让客户端发现 llms.txt 与当前页的「只取内容」版本
 *
 * 规范同时接受 HTML link 与 HTTP Link: 头两种投放形式，这里只出 HTML，
 * 免得每个响应都多一个头
 */
function iro_llms_head_links(): void
{
    echo '<link rel="describedby" type="text/markdown" href="' . esc_url(home_url('/llms.txt')) . '">' . "\n";

    $markdown_url = iro_llms_current_md_url();

    if ($markdown_url === '') {
        return;
    }

    echo '<link rel="alternate" type="text/markdown" href="' . esc_url($markdown_url) . '">' . "\n";
}

/**
 * 当前页面对应的「只取内容」地址；没有该形态时返回空串
 */
function iro_llms_current_md_url(): string
{
    if (is_singular()) {
        return iro_llms_markdown_url((string) get_permalink());
    }

    if (is_home() || is_front_page()) {
        return iro_llms_markdown_url(home_url('/'));
    }

    if (is_category() || is_tag()) {
        $link = get_term_link(get_queried_object());

        return is_wp_error($link) ? '' : iro_llms_markdown_url($link);
    }

    return '';
}

/**
 * 只取内容的链接形态：去掉结尾斜杠后补 .md
 *
 * 站点根（含子目录安装）没有可加后缀的路径段，按规范落到 index.md；
 * 未开启伪静态时固定链接带查询串（?p=123），也没有可加后缀的路径，改用等价的 ?md 参数
 */
function iro_llms_markdown_url(string $url): string
{
    if ($url === '') {
        return '';
    }

    if (str_contains($url, '?')) {
        return $url . '&md';
    }

    $path = rtrim((string) parse_url($url, PHP_URL_PATH), '/');
    $home = rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ($path === $home) {
        return rtrim($url, '/') . '/index.md';
    }

    return rtrim($url, '/') . '.md';
}

/**
 * 当前请求对应的正常地址：回落目标里不该再带着 md 标记
 */
function iro_llms_proper_url(): string
{
    $request = trim((string) ($GLOBALS['wp']->request ?? ''), '/');
    $url     = $request === '' ? home_url('/') : home_url('/' . $request);

    // 搜索这类靠查询串的地址要把参数带走
    $args = wp_unslash($_GET);
    unset($args['md']);

    return $args === [] ? $url : add_query_arg($args, $url);
}
