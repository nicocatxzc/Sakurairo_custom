<?php

/**
 * 多语言内容总览的 REST 接口，供 inc/ai 的「文章翻译」面板使用
 *
 * 面板本体是 Vue 应用，这里只做数据组装与批量维护动作的入口：分组、状态判定、
 * 补齐与归位的实现都在 inc/functions/i18n 下，与编辑页的语言面板共用同一套。
 *
 * 只回代号不回文案：状态与语言名由面板自己的字典翻译（inc/ai/i18n.js），
 * 与「文章管理」面板把内容类型代号交给前端翻译的做法一致。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 面板的列定义：语言代号、字典键、URL 前缀与是否为默认语言
 *
 * `name` 取 `iro_i18n_language_name()` 的中文名，它同时是面板字典里的键。
 *
 * @return array<int,array{code:string,name:string,prefix:string,is_default:bool}>
 */
function iro_i18n_rest_languages(): array
{
    $default = iro_i18n_default_language();
    $rows    = [];

    foreach (iro_i18n_languages() as $code) {
        $rows[] = [
            'code'       => $code,
            'name'       => iro_i18n_language_name($code),
            'prefix'     => iro_i18n_prefix($code),
            'is_default' => $code === $default,
        ];
    }

    return $rows;
}

/**
 * 一行的各语言状态，只留面板要用的三样
 *
 * @param array<string,WP_Post> $map 语言代号 => 该语言的版本
 * @return array<int,array{code:string,state:string,url:string}>
 */
function iro_i18n_rest_statuses(array $map): array
{
    return array_map(
        static fn(array $row): array => [
            'code'  => $row['code'],
            'state' => $row['state'],
            'url'   => $row['url'],
        ],
        iro_i18n_language_statuses($map)
    );
}

/**
 * 总览列表：已建立语言关联的已发布内容，连同各语言的版本状态
 *
 * 只列已关联的内容：没有关联标识的内容既没有译文也没有对外路径，
 * 它们该先经由批量补齐落成关联，而不是在这里逐行展示。
 */
function iro_i18n_rest_overview(WP_REST_Request $request): WP_REST_Response
{
    $post_types = iro_i18n_supported_post_types();
    $page       = max(1, (int) $request->get_param('page'));
    $per_page   = (int) $request->get_param('per_page');
    $per_page   = $per_page > 0 ? min(100, $per_page) : 50;
    $search     = sanitize_text_field((string) $request->get_param('search'));

    $args = [
        'post_type'           => $post_types,
        'post_status'         => 'publish',
        'posts_per_page'      => $per_page,
        'paged'               => $page,
        'orderby'             => 'ID',
        'order'               => 'DESC',
        // 置顶会把无关内容插到第一页最前面（它的关联标识往往还是空的），这不是信息流
        'ignore_sticky_posts' => true,
        'meta_query'          => [
            [
                'key'     => IRO_I18N_PATH_META,
                'compare' => 'EXISTS',
            ],
        ],
    ];

    if ($search !== '') {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);

    // 本页涉及的全部关联标识一次性取回版本归属，避免逐行查询
    $paths = [];

    foreach ($query->posts as $post) {
        $path = iro_i18n_get_path($post->ID);

        if ($path !== '') {
            $paths[$path] = true;
        }
    }

    $translations = iro_i18n_paths_translations(array_keys($paths), $post_types);

    $items = [];

    foreach ($query->posts as $post) {
        $path = iro_i18n_get_path($post->ID);
        // 用原始标题：the_title 过滤器会把 & 之类转成实体，表格里会原样显示
        $title = trim((string) $post->post_title);

        $items[] = [
            'id'         => $post->ID,
            'title'      => $title !== '' ? $title : __('（无标题）', 'sakurairo'),
            'type'       => $post->post_type,
            'path'       => $path,
            'edit_link'  => (string) get_edit_post_link($post->ID, 'raw'),
            'statuses'   => iro_i18n_rest_statuses($translations[$path] ?? []),
        ];
    }

    return rest_ensure_response([
        'items'     => $items,
        'languages' => iro_i18n_rest_languages(),
        // 默认语言是站点语言推出来的，面板用它拼那句「默认语言是…」的说明
        'locale'    => iro_i18n_site_locale(),
        'autofuzzy' => iro_i18n_autofuzzy_enabled(),
        'total'     => (int) $query->found_posts,
        'page'      => $page,
        'per_page'  => $per_page,
        'pages'     => (int) $query->max_num_pages,
    ]);
}

// 为全部已发布内容补齐关联与未翻译副本
function iro_i18n_rest_backfill(): WP_REST_Response
{
    return rest_ensure_response(['count' => iro_i18n_backfill_translations()]);
}

// 把原文的语言标记归位到默认语言
function iro_i18n_rest_repair_language(): WP_REST_Response
{
    $count = iro_i18n_repair_default_language();

    // 与原后台动作一致：手动归位同样落下哨兵，免得 admin_init 再全表扫一遍
    update_option('iro_i18n_default_repair', $count);

    return rest_ensure_response(['count' => $count]);
}

// 规范化译文别名（原文别名 + 语言代号）
function iro_i18n_rest_rename_slugs(): WP_REST_Response
{
    return rest_ensure_response(['count' => iro_i18n_rename_translation_slugs()]);
}
