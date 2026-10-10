<?php

/**
 * 多语言内容总览的 REST 接口，供 inc/ai 的「文章翻译」面板使用
 *
 * 面板本体是 Vue 应用，这里只做数据组装与批量维护动作的入口：分组、状态判定与
 * 归位的实现在 inc/functions/i18n 下，与编辑页的语言面板共用同一套；只有这里
 * 独有的批量动作（补齐、规范化别名）就地实现，因为它们只有这一个调用方。
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

    $translations = [];

    if ($paths !== []) {
        $grouped = new WP_Query([
            'post_type'              => $post_types,
            'post_status'            => iro_i18n_existing_statuses(),
            'posts_per_page'         => -1,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'     => IRO_I18N_PATH_META,
                    'value'   => array_keys($paths),
                    'compare' => 'IN',
                ],
            ],
        ]);

        if ($grouped->posts !== []) {
            // 语言判定依赖术语缓存，这里一次性预热，避免每篇各查一次
            update_object_term_cache(wp_list_pluck($grouped->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
        }

        foreach ($grouped->posts as $grouped_post) {
            $group_path = iro_i18n_get_path($grouped_post->ID);

            if ($group_path !== '') {
                $translations[$group_path][iro_i18n_post_language($grouped_post->ID)] = $grouped_post;
            }
        }
    }

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

/**
 * 为全部已发布内容补齐关联与未翻译副本
 *
 * 分两轮：先给「还没有语言标记」的内容认领默认语言——语言隔离靠分类法查询实现，
 * 不补上术语它们就会在任何按语言收窄的列表里消失；再给已发布内容补关联字段并派生
 * 未翻译副本。补副本很重（每篇要建 n-1 份草稿），因此只对确有关联字段缺失的内容做。
 */
function iro_i18n_rest_backfill(): WP_REST_Response
{
    $count = 0;

    $query = new WP_Query([
        'post_type'              => iro_i18n_supported_post_types(),
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'tax_query'              => [
            [
                'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
                'operator' => 'NOT EXISTS',
            ],
        ],
    ]);

    foreach ($query->posts as $post) {
        iro_i18n_adopt_default_language($post->ID);
        $count++;
    }

    $query = new WP_Query([
        'post_type'              => iro_i18n_supported_post_types(),
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        // 保留 meta 缓存：下面逐篇判关联与副本标记，关掉会退化成逐篇查库
        'meta_query'             => [
            'relation' => 'OR',
            [
                'key'     => IRO_I18N_PATH_META,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => IRO_I18N_PATH_META,
                'value'   => '',
                'compare' => '=',
            ],
        ],
    ]);

    foreach ($query->posts as $post) {
        if (iro_i18n_is_skeleton($post->ID)) {
            continue;
        }

        if (iro_i18n_ensure_path($post->ID) === '') {
            continue;
        }

        iro_i18n_sync_translations($post->ID);
        $count++;
    }

    return rest_ensure_response(['count' => $count]);
}

// 把原文的语言标记归位到默认语言
function iro_i18n_rest_repair_language(): WP_REST_Response
{
    $count = iro_i18n_repair_default_language();

    // 与原后台动作一致：手动归位同样落下哨兵，免得 admin_init 再全表扫一遍
    update_option('iro_i18n_default_repair', $count);

    return rest_ensure_response(['count' => $count]);
}

/**
 * 规范化译文别名（原文别名 + 语言代号）
 *
 * 早期副本与原文同名，被 WordPress 自动接上了 `-2`、`-3`；那串数字既不稳定也
 * 看不出属于哪种语言。这里按现在的规则重命名，让别名与语言一一对应。
 * 只动别名，不动关联字段与语言标记，因此分组关系不受影响。
 */
function iro_i18n_rest_rename_slugs(): WP_REST_Response
{
    $renamed = 0;

    foreach (iro_i18n_languages() as $code) {
        if (iro_i18n_is_default_language($code)) {
            continue;
        }

        $posts = get_posts([
            'post_type'        => iro_i18n_supported_post_types(),
            'post_status'      => iro_i18n_existing_statuses(),
            'posts_per_page'   => -1,
            'orderby'          => 'ID',
            'order'            => 'ASC',
            'suppress_filters' => false,
            'no_found_rows'    => true,
            'meta_query'       => [
                [
                    'key'   => IRO_I18N_POST_LANG_META,
                    'value' => $code,
                ],
            ],
        ]);

        foreach ($posts as $post) {
            $path = iro_i18n_get_path($post->ID);

            if ($path === '' || iro_i18n_is_base_post($post->ID)) {
                continue;
            }

            $expected = iro_i18n_skeleton_post_name($path, $code);

            if ($post->post_name === $expected) {
                continue;
            }

            wp_update_post([
                'ID'        => $post->ID,
                'post_name' => $expected,
            ]);

            $renamed++;
        }
    }

    return rest_ensure_response(['count' => $renamed]);
}
