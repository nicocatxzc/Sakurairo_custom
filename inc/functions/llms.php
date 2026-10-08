<?php

/**
 * llms.txt
 *
 * 规范见 https://llmstxt.org/
 */

if (!defined('ABSPATH')) {
    exit;
}

const IRO_LLMS_ROUTE_VAR  = 'iro_llms';
const IRO_LLMS_CACHE_KEY  = 'iro_llms_cache';
const IRO_LLMS_POST_LIMIT = 20;

if (iro_opt('iro_llms_txt', false)) {
    // 必须早于 WP 解析请求
    add_action('init', 'iro_llms_normalize_md_request', 0);
    add_action('init', 'iro_llms_register_routes', 10);
    add_filter('query_vars', 'iro_llms_query_vars');
    add_action('template_redirect', 'iro_llms_dispatch', 1);
    add_action('wp_head', 'iro_llms_head_links', 99);
    add_action('deleted_post', 'iro_llms_flush_cache', 10, 0);
    add_action('edited_term', 'iro_llms_flush_cache', 10, 0);
    add_action('delete_term', 'iro_llms_flush_cache', 10, 0);
}

// 保存设置那一刻 iro_opt() 读到的还是旧值，只靠上面那批钩子会让刚打开的路由一直缺失，
// 所以这里直接读库里的新值补注册一次，再重建规则
add_action('update_option_iro_options', function (): void {
    if (!empty(get_option('iro_options')['iro_llms_txt'])) {
        iro_llms_register_routes();
        iro_llms_flush_cache();
    }

    flush_rewrite_rules();
});

add_action('after_switch_theme', function (): void {
    flush_rewrite_rules();
});

function iro_llms_query_vars(array $vars): array
{
    $vars[] = IRO_LLMS_ROUTE_VAR;

    return $vars;
}

function iro_llms_register_routes(): void
{
    add_rewrite_rule('^llms\.txt$', 'index.php?' . IRO_LLMS_ROUTE_VAR . '=txt', 'top');
}

/**
 * 把 .md 形态的请求路径摘掉后缀，再交给 WP 自己去解析
 *
 * 这样 /category/foo.md 与 /category/foo/ 会落到完全相同的主查询上，不必自己实现
 * 「路径 → 查询参数」的映射，模板里的 have_posts() / is_singular() 也就都能直接用。
 * index.md 是「无文件名地址」的 md 形态（站点根就是 /index.md），摘掉整个文件名。
 *
 * WP::parse_request() 在 init 之后才跑，所以这里改 REQUEST_URI 还来得及。
 */
function iro_llms_normalize_md_request(): void
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

    if (preg_match('#index\.md(?=\?|$)#', $uri)) {
        $normalized = (string) preg_replace('#index\.md(?=\?|$)#', '', $uri, 1);
    } elseif (preg_match('#\.md(?=\?|$)#', $uri)) {
        $normalized = (string) preg_replace('#\.md(?=\?|$)#', '', $uri, 1);
    } else {
        return;
    }

    $_SERVER['REQUEST_URI'] = $normalized;
    // 与 $iro_only_template 同类：置位后页面里的组件可以据此改用「只取内容」的形态
    $GLOBALS['iro_is_md_template'] = true;
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

function iro_llms_dispatch(): void
{
    if (get_query_var(IRO_LLMS_ROUTE_VAR) === 'txt') {
        iro_llms_render_txt();
        exit;
    }

    if (!iro_llms_md_request()) {
        return;
    }

    // 查询参数形态在这里才认出来，同样在劫持时置位
    $GLOBALS['iro_is_md_template'] = true;

    // 正文：文章与页面
    if (is_singular()) {
        iro_llms_render_markdown(get_queried_object_id());
        exit;
    }

    // 列表：首页与分类、标签归档。判定顺序与 index.php 的分发保持一致，
    // 静态首页也按首页处理（本站首页由 homepage_components 决定）
    if (is_home() || is_front_page() || is_category() || is_tag()) {
        iro_llms_render_list();
        exit;
    }

    // 其余类型（订阅、搜索、作者页等）没有「只取内容」的形态，回落到正常地址
    wp_safe_redirect(iro_llms_proper_url(), 302);
    exit;
}

function iro_llms_flush_cache(): void
{
    delete_transient(IRO_LLMS_CACHE_KEY);
}

/**
 * 用标准 link relation 让客户端发现 llms.txt 与当前页的「只取内容」版本
 *
 * 规范同时接受 HTML link 与 HTTP Link: 头两种投放形式，这里只出 HTML，
 * 免得每个响应都多一个头（需要的话可以在 CDN 层补）
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

function iro_llms_render_txt(): void
{
    $body = get_transient(IRO_LLMS_CACHE_KEY);

    if (!is_string($body) || $body === '') {
        $body = iro_llms_render_document(iro_llms_document());

        if ($body === '') {
            status_header(404);

            return;
        }

        set_transient(IRO_LLMS_CACHE_KEY, $body, DAY_IN_SECONDS);
    }

    header('Content-Type: text/markdown; charset=UTF-8');
    echo $body;
}

/**
 * 渲染文章正文
 */
function iro_llms_render_markdown(int $post_id): void
{
    $post = get_post($post_id);

    if (!$post || $post->post_status !== 'publish' || post_password_required($post)) {
        status_header(404);

        return;
    }

    // setup_postdata() 只补 $id / $pages 等全局量，不负责 $post，
    // 而模板里的 get_the_ID()、the_content() 都读 $post，必须一并赋值
    $GLOBALS['post'] = $post;
    setup_postdata($post);

    header('Content-Type: text/markdown; charset=UTF-8');

    ob_start();

    if (has_post_thumbnail($post)) {
        require get_theme_file_path('/frontend/components/post/header_with_image.php');
    } else {
        require get_theme_file_path('/frontend/components/post/header.php');
    }

    require get_theme_file_path('/frontend/components/post/render.php');

    echo ob_get_clean();

    wp_reset_postdata();
}

/**
 * 渲染文章列表
 * 
 * 主查询已移除md标记，这边可以正常载入
 */
function iro_llms_render_list(): void
{
    $GLOBALS['iro_only_template'] = true;

    require_once get_theme_file_path('/frontend/components/slots/pagination.php');

    header('Content-Type: text/markdown; charset=UTF-8');

    ob_start();
    require get_theme_file_path('/frontend/components/post/list.php');
    echo ob_get_clean();
}

/**
 * 文档模型：采集层只产出这个形状，格式由渲染层统一强制
 *
 * @return array{
 *     title: string,
 *     summary: string,
 *     details: list<string>,
 *     sections: list<array{heading: string, items: list<array{title: string, url: string, note?: string}>}>,
 *     optional: array{heading: string, items: list<array{title: string, url: string, note?: string}>}
 * }
 */
function iro_llms_document(): array
{
    $doc = [
        'title'    => (string) get_bloginfo('name'),
        'summary'  => iro_llms_summary(),
        'details'  => iro_llms_details(),
        'sections' => [
            [
                'heading' => __('文章', 'sakurairo'),
                'items'   => iro_llms_post_items(),
            ],
            [
                'heading' => __('页面', 'sakurairo'),
                'items'   => iro_llms_page_items(),
            ],
            [
                'heading' => __('分类', 'sakurairo'),
                'items'   => iro_llms_term_items('category'),
            ],
            [
                'heading' => __('标签', 'sakurairo'),
                'items'   => iro_llms_term_items('post_tag'),
            ],
        ],
        // 段名是规范里的固定约定，不参与翻译
        'optional' => [
            'heading' => 'Optional',
            'items'   => iro_llms_optional_items(),
        ],
    ];

    return (array) apply_filters('iro_llms_document', $doc);
}

/**
 * 引用块之后的补充说明：交代站点语言、正文取法与各列表的口径
 *
 * @return list<string>
 */
function iro_llms_details(): array
{
    $mode = iro_llms_recommend_mode();

    $details = [
        sprintf(__('站点语言：%s', 'sakurairo'), get_bloginfo('language')),
        __('链接结尾补上 .md（首页是 /index.md）或加 ?md 查询参数即可只取该页内容：文章页返回正文，列表页返回文章列表。', 'sakurairo'),
    ];

    if ($mode === 'custom') {
        $details[] = __('「文章」一节由站长指定。', 'sakurairo');
    } else {
        $details[] = sprintf(
            __('「文章」一节最多列出 %d 篇，按%s排列；页面、分类、标签为全量。', 'sakurairo'),
            iro_llms_post_limit(),
            $mode === 'views' ? __('浏览量', 'sakurairo') : __('发布时间倒序', 'sakurairo')
        );
    }

    return $details;
}

/**
 * 引用块：站长手写的 llms.txt 站点描述优先，没写就沿用 SEO 的描述回退链
 */
function iro_llms_summary(): string
{
    $desc = iro_clean_text((string) iro_opt('iro_llms_desc', ''));

    return $desc !== '' ? $desc : iro_clean_text((string) iro_get_description_text(240));
}

function iro_llms_recommend_mode(): string
{
    $mode = (string) iro_opt('iro_llms_recommend_mode', 'latest');

    return in_array($mode, ['latest', 'views', 'custom'], true) ? $mode : 'latest';
}

function iro_llms_post_limit(): int
{
    return max(1, (int) iro_opt('iro_llms_post_limit', IRO_LLMS_POST_LIMIT));
}

/**
 * 文章列表：来源与排序由设置项决定
 *
 * @return list<array{title: string, url: string, note: string}>
 */
function iro_llms_post_items(): array
{
    $mode = iro_llms_recommend_mode();

    if ($mode === 'custom') {
        return iro_llms_custom_items();
    }

    $args = [
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => iro_llms_post_limit(),
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ];

    if ($mode === 'views') {
        $args['meta_query'] = [
            // 浏览量字段可能缺失，用 OR 把这类文章一并纳入，
            // 否则「按浏览量」只会列出有字段的少数文章
            'relation'     => 'OR',
            'iro_views'    => [
                'key'     => 'views',
                'type'    => 'NUMERIC',
                'compare' => 'EXISTS',
            ],
            'iro_no_views' => [
                'key'     => 'views',
                'compare' => 'NOT EXISTS',
            ],
        ];
        $args['orderby'] = [
            'iro_views' => 'DESC',
            'date'      => 'DESC',
        ];
    } else {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    $items = [];

    foreach (get_posts($args) as $post) {
        if (post_password_required($post)) {
            continue;
        }

        $items[] = iro_llms_item($post);
    }

    return $items;
}

/**
 * 站长自定义列表，失效或加密的文章自动跳过
 *
 * @return list<array{title: string, url: string, note: string}>
 */
function iro_llms_custom_items(): array
{
    $items = [];

    foreach ((array) iro_opt('iro_llms_recommend_items', []) as $row) {
        $post = get_post((int) ($row['post'] ?? 0));

        if (!$post || $post->post_status !== 'publish' || post_password_required($post)) {
            continue;
        }

        $items[] = iro_llms_item($post);
    }

    return $items;
}

/**
 * @return list<array{title: string, url: string, note: string}>
 */
function iro_llms_page_items(): array
{
    $items = [];

    // numberposts 不写会沿用 get_posts() 的默认值 5
    foreach (
        get_posts([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
        ]) as $post
    ) {
        if (post_password_required($post)) {
            continue;
        }

        $items[] = iro_llms_item($post);
    }

    return $items;
}

/**
 * 分类法归档的 md 形态会渲染该归档的文章列表
 *
 * @return list<array{title: string, url: string, note: string}>
 */
function iro_llms_term_items(string $taxonomy): array
{
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        // 空归档没有索引价值，与站点地图一致
        'hide_empty' => true,
    ]);

    if (is_wp_error($terms)) {
        return [];
    }

    $items = [];

    foreach ($terms as $term) {
        $link = get_term_link($term);

        if (is_wp_error($link)) {
            continue;
        }

        $items[] = [
            'title' => $term->name,
            'url'   => iro_llms_markdown_url($link),
            'note'  => iro_clean_text((string) $term->description),
        ];
    }

    return $items;
}

/**
 * 次要链接：上下文紧张时 agent 可以跳过
 *
 * @return list<array{title: string, url: string, note?: string}>
 */
function iro_llms_optional_items(): array
{
    $items = [
        [
            'title' => __('RSS 订阅', 'sakurairo'),
            'url'   => (string) get_feed_link(),
        ],
    ];

    // 站点地图由主题提供且可以关闭，关掉时不该给出死链
    if (iro_opt('iro_sitemap', false)) {
        $items[] = [
            'title' => __('站点地图', 'sakurairo'),
            'url'   => home_url('/sitemap.xml'),
        ];
    }

    return $items;
}

/**
 * 列表项：链接补成「只取正文」的形态，说明直接复用摘要
 *
 * @return array{title: string, url: string, note: string}
 */
function iro_llms_item(WP_Post $post): array
{
    return [
        'title' => get_the_title($post),
        'url'   => iro_llms_markdown_url((string) get_permalink($post)),
        'note'  => iro_clean_text((string) $post->post_excerpt),
    ];
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
 * 渲染成规范要求的文档，H1 为空则整份不产出
 */
function iro_llms_render_document(array $doc): string
{
    $title = iro_llms_escape((string) ($doc['title'] ?? ''));

    if ($title === '') {
        return '';
    }

    $summary = iro_llms_escape((string) ($doc['summary'] ?? ''));
    $details = array_filter(array_map(
        static fn($detail): string => iro_llms_escape((string) $detail),
        (array) ($doc['details'] ?? [])
    ));

    $sections = (array) ($doc['sections'] ?? []);

    // Optional 按约定排在最后
    if (!empty($doc['optional']['items'])) {
        $sections[] = $doc['optional'];
    }

    $blocks = ['# ' . $title];

    if ($summary !== '') {
        $blocks[] = '> ' . $summary;
    }

    foreach ($details as $detail) {
        $blocks[] = $detail;
    }

    foreach ($sections as $section) {
        $markdown = iro_llms_section_markdown((array) $section);

        if ($markdown !== '') {
            $blocks[] = $markdown;
        }
    }

    // 段落之间的空行由拼装保证，不靠模板源码里的空行：
    // PHP 会吞掉 PHP 结束标签之后的第一个换行，模板里写一行空行只会剩下一个换行，段落会黏在一起
    return implode("\n\n", $blocks) . "\n";
}

/**
 * 一个文件列表段落；没有可用条目时返回空串，避免留下空的 H2
 */
function iro_llms_section_markdown(array $section): string
{
    $heading = iro_llms_escape((string) ($section['heading'] ?? ''));
    $lines   = iro_llms_item_lines((array) ($section['items'] ?? []));

    if ($heading === '' || $lines === []) {
        return '';
    }

    return '## ' . $heading . "\n\n" . implode("\n", $lines);
}

/**
 * 列表项：必需 [名称](url)，可选 `: 说明`；同一地址只保留一次
 *
 * @return list<string>
 */
function iro_llms_item_lines(array $items): array
{
    $lines = [];

    foreach ($items as $item) {
        $title = iro_llms_escape((string) ($item['title'] ?? ''));
        $url   = (string) ($item['url'] ?? '');

        if ($title === '' || $url === '') {
            continue;
        }

        // 圆括号会提前终止 markdown 的链接目标
        $line = '- [' . $title . '](' . str_replace(['(', ')'], ['%28', '%29'], esc_url_raw($url)) . ')';
        $note = iro_llms_escape((string) ($item['note'] ?? ''));

        if ($note !== '') {
            $line .= ': ' . $note;
        }

        $lines[$url] = $line;
    }

    return array_values($lines);
}

/**
 * 转义会破坏 markdown 语法的字符
 *
 * 标题与说明都来自数据库，直接插进 [名称](url) 或 `: 说明` 会截断列表项；
 * 行首的 # 还会让这一行被当成标题，而规范要求引用块之后不得出现标题
 */
function iro_llms_escape(string $text): string
{
    $text = iro_clean_text($text);

    if ($text === '') {
        return '';
    }

    $text = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $text);

    // iro_clean_text() 已经去过首尾空白，这里只需判断第一个字符；
    // 转义而不是删掉，标题原文才不会被改动
    if (str_starts_with($text, '#')) {
        $text = '\\' . $text;
    }

    return $text;
}
