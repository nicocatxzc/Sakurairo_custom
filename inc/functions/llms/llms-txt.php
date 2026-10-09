<?php

/**
 * llms.txt —— 文档模型、采集与 markdown 格式
 *
 * 规范见 https://llmstxt.org/
 */

const IRO_LLMS_POST_LIMIT = 20;

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
 * 文档模型
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
 * 引用块：手写的 llms.txt 站点描述优先，没写就沿用 SEO 的描述回退链
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
 * 分类法归档的 md 形态会渲染该归档的文章列表，所以这里直接给 md 地址
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
 */
function iro_llms_escape(string $text): string
{
    $text = iro_clean_text($text);

    if ($text === '') {
        return '';
    }

    $text = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $text);

    // iro_clean_text() 已经去过首尾空白，这里只需判断第一个字符；
    // 转义掉会识别为标题的字符
    if (str_starts_with($text, '#')) {
        $text = '\\' . $text;
    }

    return $text;
}
