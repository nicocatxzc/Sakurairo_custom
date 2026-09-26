<?php

if (!defined('ABSPATH')) {
    exit;
}

// WordPress 7.0 以下没有内置 AI Client，接口不可用
if (!function_exists('iro_ai_selftest_report')) {
    return;
}

// 自检：系统信息 + 接口可用性 + 可用模型
function iro_ai_rest_selftest(): WP_REST_Response
{
    return rest_ensure_response(iro_ai_selftest_report());
}

// 可用模型（?refresh=1 强制刷新缓存）
function iro_ai_rest_models(WP_REST_Request $request): WP_REST_Response
{
    return rest_ensure_response([
        'models' => iro_ai_models_list((bool) $request->get_param('refresh')),
    ]);
}

// 测试对话，messages 为 OpenAI 风格消息数组
function iro_ai_rest_chat(WP_REST_Request $request)
{
    @set_time_limit(180);

    $messages = $request->get_param('messages');
    if (!is_array($messages) || $messages === []) {
        return new WP_Error(
            'iro_ai_chat_empty',
            __('对话内容不能为空。', 'sakurairo'),
            ['status' => 400]
        );
    }

    $model = sanitize_text_field((string) $request->get_param('model'));
    $started = microtime(true);

    $reply = iro_ai_chat(
        $messages,
        array_merge(
            $model !== '' ? ['model' => $model] : [],
            ['timeout' => 120]
        )
    );

    if (is_wp_error($reply)) {
        return $reply;
    }

    return rest_ensure_response([
        'reply' => $reply,
        'model' => $model !== '' ? $model : iro_ai_default_model(),
        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
    ]);
}

/* ---------- 内容管理 ---------- */

// 模型输出与人工输入统一清洗：去掉代码块围栏、换行与包裹引号
function iro_ai_clean_text(string $text): string
{
    $text = (string) preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', trim($text));
    $text = (string) preg_replace('/\s+/u', ' ', $text);

    return trim($text, " \t\n\r\0\x0B\"'`“”‘’《》");
}

function iro_ai_clean_summary(string $text, int $max = 200): string
{
    // 去掉“摘要：”“总结：”这类前缀
    $text = (string) preg_replace('/^[\p{Han}\p{L}\p{N}]{1,6}[：:]\s*/u', '', iro_ai_clean_text($text));

    return mb_substr($text, 0, $max);
}

function iro_ai_clean_keywords(string $text, int $max = 5): string
{
    $text = str_replace(
        ['，', '、', '；', ';', '|', '｜', '/', '·'],
        ',',
        (string) preg_replace('/^关键词[：:]\s*/u', '', iro_ai_clean_text($text))
    );

    $keywords = [];

    foreach (explode(',', $text) as $item) {
        // 去掉“1. ”“- ”这类编号
        $item = iro_ai_clean_text((string) preg_replace('/^[\s\-*0-9.、)]+/u', '', $item));

        if ($item === '' || in_array(mb_strtolower($item), array_map('mb_strtolower', $keywords), true)) {
            continue;
        }

        $keywords[] = mb_substr($item, 0, 30);

        if (count($keywords) >= $max) {
            break;
        }
    }

    return implode(', ', $keywords);
}

// 列表查询参数归一化
function iro_ai_posts_args(array $args = []): array
{
    $args = array_merge(
        [
            'type'       => 'all', // all | post | page
            'filter'     => 'all', // all | no_excerpt | no_keywords
            'search'     => '',
            'skip_thin'  => true, // 略过实质性内容过短的模板页面
            'min_length' => 30,
            'page'       => 1,
            'per_page'   => 20,
            'orderby'    => 'date',
            'order'      => 'desc',
        ],
        $args
    );

    $args['type'] = in_array($args['type'], ['post', 'page'], true) ? $args['type'] : 'all';
    $args['filter'] = in_array($args['filter'], ['no_excerpt', 'no_keywords'], true) ? $args['filter'] : 'all';
    $args['search'] = trim((string) $args['search']);
    $args['skip_thin'] = !in_array(strtolower((string) $args['skip_thin']), ['', '0', 'false', 'no', 'off'], true);
    $args['min_length'] = max(1, (int) $args['min_length']);
    $args['page'] = max(1, (int) $args['page']);
    $args['per_page'] = min(100, max(1, (int) $args['per_page']));
    $args['orderby'] = in_array($args['orderby'], ['date', 'modified', 'title', 'ID'], true) ? $args['orderby'] : 'date';
    $args['order'] = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';

    return $args;
}

// 组装并执行筛选查询；post_excerpt 是数据表字段，空值与 NULL 都算“没有摘要”
// 列表接口不按筛选取行（筛选只在行上打 matched 标记），这里只服务各筛选条件的计数
function iro_ai_posts_query(array $args, array $query): WP_Query
{
    $types = $args['type'] === 'all' ? ['post', 'page'] : [$args['type']];

    if ($args['filter'] === 'no_keywords') {
        // 关键词即原生标签：页面这类不支持 post_tag 的类型不存在“无标签”
        $types = array_values(
            array_filter($types, static fn (string $type): bool => is_object_in_taxonomy($type, 'post_tag'))
        );

        $query['tax_query'] = [
            [
                'taxonomy' => 'post_tag',
                'operator' => 'NOT EXISTS',
            ],
        ];
    }

    $query += [
        // 类型都不支持标签时用不存在的类型名表示空集
        'post_type'              => $types !== [] ? $types : ['iro_ai_none'],
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'orderby'                => $args['orderby'],
        'order'                  => $args['order'],
        // 行数据要读标签，由 WP_Query 一次性预热 term 缓存，避免逐行查询
        'update_post_term_cache' => true,
        'update_post_meta_cache' => false,
    ];

    if ($args['search'] !== '') {
        $query['s'] = $args['search'];
    }

    $no_excerpt = $args['filter'] === 'no_excerpt';

    $where = static function (string $clause): string {
        return $clause . " AND (post_excerpt = '' OR post_excerpt IS NULL)";
    };

    if ($no_excerpt) {
        add_filter('posts_where', $where);
    }

    $result = new WP_Query($query);

    if ($no_excerpt) {
        remove_filter('posts_where', $where);
    }

    return $result;
}

// 关键词就是原生标签（post_tag）：只读写系统自带的标签，不引入自定义字段
function iro_ai_post_tags(int $post_id): string
{
    $tags = get_the_tags($post_id);

    return $tags && !is_wp_error($tags) ? implode(', ', wp_list_pluck($tags, 'name')) : '';
}

// 表格行
function iro_ai_posts_row(WP_Post $post): array
{
    $keywords = iro_ai_post_tags($post->ID);
    // 用原始标题：the_title 过滤器会把 & 之类转成实体，表格里会原样显示
    $title = trim((string) $post->post_title);

    return [
        'id'             => $post->ID,
        'type'           => $post->post_type,
        'title'          => $title !== '' ? $title : __('（无标题）', 'sakurairo'),
        'date'           => get_post_time('Y-m-d H:i', false, $post),
        'content_length' => mb_strlen(iro_ai_plain_content($post->post_content)),
        'excerpt'        => $post->post_excerpt,
        'keywords'       => $keywords,
        'has_excerpt'    => trim($post->post_excerpt) !== '',
        'has_keywords'   => $keywords !== '',
        'tags_supported' => is_object_in_taxonomy($post->post_type, 'post_tag'),
        'edit_link'      => (string) get_edit_post_link($post->ID, 'raw'),
        'permalink'      => (string) get_permalink($post),
    ];
}

// 各筛选条件下的数量统计（不含 skip_thin，它要逐行按内容长度判断）
function iro_ai_posts_counts(array $args): array
{
    $count = static function (array $overrides) use ($args): int {
        return (int) iro_ai_posts_query(
            array_merge($args, $overrides),
            [
                'posts_per_page'         => 1,
                'paged'                  => 1,
                'fields'                 => 'ids',
                'update_post_meta_cache' => false,
            ]
        )->found_posts;
    };

    $counts = [
        'all'  => $count(['type' => 'all', 'filter' => 'all']),
        'post' => $count(['type' => 'post', 'filter' => 'all']),
        'page' => $count(['type' => 'page', 'filter' => 'all']),
    ];

    foreach (['no_excerpt', 'no_keywords'] as $filter) {
        $counts[$filter] = $count(['type' => 'all', 'filter' => $filter]);
    }

    return $counts;
}

// 内容筛选不下推给 SQL：列表按类型/搜索/分页取全量行，用 matched 标记该行是否符合筛选，
// 前端把不符合的行屏蔽掉即可 —— 每行都是真实渲染、可勾选可编辑的行，不需要额外的 id 集合
function iro_ai_posts_matched(WP_Post $post, string $filter): bool
{
    if ($filter === 'no_excerpt') {
        return trim($post->post_excerpt) === '';
    }

    if ($filter === 'no_keywords') {
        return is_object_in_taxonomy($post->post_type, 'post_tag') && iro_ai_post_tags($post->ID) === '';
    }

    return true;
}

// 内容管理：枚举全站的 Post 与 Page，筛选结果以每行的 matched 标记返回
function iro_ai_rest_posts(WP_REST_Request $request)
{
    $params = array_filter(
        [
            'type'       => (string) $request->get_param('type'),
            'filter'     => (string) $request->get_param('filter'),
            'search'     => sanitize_text_field((string) $request->get_param('search')),
            'skip_thin'  => $request->get_param('skip_thin'),
            'min_length' => $request->get_param('min_length'),
            'page'       => $request->get_param('page'),
            'per_page'   => $request->get_param('per_page'),
            'orderby'    => (string) $request->get_param('orderby'),
            'order'      => (string) $request->get_param('order'),
        ],
        static fn($value) => $value !== null && $value !== ''
    );

    $args = iro_ai_posts_args($params);

    // 筛选只用于打标记，查询本身始终取全量行
    $result = iro_ai_posts_query(array_merge($args, ['filter' => 'all']), [
        'posts_per_page' => $args['per_page'],
        'paged'          => $args['page'],
    ]);

    $items = [];
    $skipped = 0;

    foreach ($result->posts as $post) {
        $row = iro_ai_posts_row($post);

        // 实质性内容过短的模板页面按选项略过，因此每页条数可能少于 per_page
        if ($args['skip_thin'] && $row['content_length'] < $args['min_length']) {
            $skipped++;
            continue;
        }

        $row['matched'] = iro_ai_posts_matched($post, $args['filter']);
        $items[] = $row;
    }

    return rest_ensure_response([
        'items'    => $items,
        'total'    => (int) $result->found_posts,
        'page'     => $args['page'],
        'per_page' => $args['per_page'],
        'pages'    => (int) $result->max_num_pages,
        'skipped'  => $skipped,
        'counts'   => iro_ai_posts_counts($args),
    ]);
}

/* ---------- 内容管理：单字段生成与写入（GET 生成不落库，PUT 写入） ---------- */

function iro_ai_rest_posts_keyword(WP_REST_Request $request)
{
    return $request->get_method() === 'PUT'
        ? iro_ai_update_field($request, 'keyword')
        : iro_ai_generate_field($request, 'keyword');
}

function iro_ai_rest_posts_description(WP_REST_Request $request)
{
    return $request->get_method() === 'PUT'
        ? iro_ai_update_field($request, 'description')
        : iro_ai_generate_field($request, 'description');
}

function iro_ai_rest_posts_title(WP_REST_Request $request)
{
    return $request->get_method() === 'PUT'
        ? iro_ai_update_field($request, 'title')
        : iro_ai_generate_field($request, 'title');
}

// 生成只回给前端核对与编辑，是否写库由 PUT 决定
function iro_ai_generate_field(WP_REST_Request $request, string $field)
{
    @set_time_limit(180);

    $post_id = (int) $request->get_param('id');
    // get_post(0) 会回退到全局 $post，必须显式挡住
    $post = $post_id > 0 ? get_post($post_id) : null;

    if (!$post) {
        return new WP_Error(
            'iro_ai_post_not_found',
            __('文章不存在。', 'sakurairo'),
            ['status' => 404]
        );
    }

    $started = microtime(true);

    if ($field === 'keyword' && !is_object_in_taxonomy($post->post_type, 'post_tag')) {
        return new WP_Error(
            'iro_ai_tags_unsupported',
            __('该内容类型不支持标签，无法生成关键词。', 'sakurairo'),
            ['status' => 400]
        );
    }

    $result = match ($field) {
        'keyword' => iro_ai_post_keywords($post_id, ['timeout' => 120]),
        'title'   => iro_ai_post_title($post_id, ['timeout' => 120]),
        default   => iro_ai_post_summary($post_id, ['timeout' => 120]),
    };

    if (is_wp_error($result)) {
        return $result;
    }

    // 模型输出统一清洗：关键词按逗号切分，标题与摘要都是去前缀、去换行的单段文本，标题更短
    $value = match ($field) {
        'keyword' => iro_ai_clean_keywords($result),
        'title'   => iro_ai_clean_summary($result, 60),
        default   => iro_ai_clean_summary($result),
    };

    return rest_ensure_response(
        iro_ai_posts_row($post) + [
            'field'      => $field,
            'value'      => $value,
            'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
        ]
    );
}

// 写入单篇单字段，value 为空表示清除
function iro_ai_update_field(WP_REST_Request $request, string $field)
{
    $post_id = (int) $request->get_param('id');
    $post = $post_id > 0 ? get_post($post_id) : null;

    if (!$post || !current_user_can('edit_post', $post_id)) {
        return new WP_Error(
            'iro_ai_post_forbidden',
            __('没有编辑该文章的权限。', 'sakurairo'),
            ['status' => 403]
        );
    }

    $value = (string) $request->get_param('value');

    if ($field === 'keyword') {
        if (!is_object_in_taxonomy($post->post_type, 'post_tag')) {
            return new WP_Error(
                'iro_ai_tags_unsupported',
                __('该内容类型不支持标签，无法写入关键词。', 'sakurairo'),
                ['status' => 400]
            );
        }

        $value = iro_ai_clean_keywords($value);

        // 覆盖写入原生标签：空值即清空；传数组避免按空格再切一次
        $tags = array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($tag) => $tag !== ''));
        $result = wp_set_post_tags($post_id, $tags, false);

        if (is_wp_error($result)) {
            return $result;
        }
    } elseif ($field === 'title') {
        // 标题写 post_title：与摘要同样去前缀、去换行，只是更短
        $value = iro_ai_clean_summary($value, 60);
        $result = wp_update_post(
            [
                'ID'         => $post_id,
                'post_title' => $value,
            ],
            true
        );

        if (is_wp_error($result)) {
            return $result;
        }
    } else {
        // 摘要写 post_excerpt：前台 meta description 与卡片摘要都取它
        $value = iro_ai_clean_summary($value);
        $result = wp_update_post(
            [
                'ID'           => $post_id,
                'post_excerpt' => $value,
            ],
            true
        );

        if (is_wp_error($result)) {
            return $result;
        }
    }

    return rest_ensure_response([
        'id'    => $post_id,
        'field' => $field,
        'value' => $value,
    ]);
}
