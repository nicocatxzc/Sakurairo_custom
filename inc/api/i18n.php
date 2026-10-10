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
 * 总览列表：一行 = 一组内容（同一内容类型下的同一关联标识）
 *
 * 同一篇内容的多个语言版本都已发布时，逐篇罗列会让同一篇文章在列表里出现多次，
 * 因此先按「内容类型 + 关联标识」归并成组、再对组分页，每行的内容列取站点语言的
 * 那一版（它不在时退到组里最新的一篇），各语言列指向该语言版本的编辑页。
 *
 * 分页只能发生在归并之后：一页有多少组取决于全站有多少组，而 SQL 里没有「按关联标识
 * 去重」这种分页口径，所以这里先取回全部已建立关联的已发布内容，再在 PHP 里切页。
 * 面板每页最多 100 条，这个代价值得。
 */
function iro_i18n_rest_overview(WP_REST_Request $request): WP_REST_Response
{
    $post_types = iro_i18n_supported_post_types();
    $default    = iro_i18n_default_language();
    $page       = max(1, (int) $request->get_param('page'));
    $per_page   = (int) $request->get_param('per_page');
    $per_page   = $per_page > 0 ? min(100, $per_page) : 50;
    $search     = sanitize_text_field((string) $request->get_param('search'));

    $args = [
        'post_type'              => $post_types,
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'DESC',
        // 置顶会把无关内容插到第一页最前面（它的关联标识往往还是空的），这不是信息流
        'ignore_sticky_posts'    => true,
        // 组数与总数都在下面自己算，核心的 found_posts 用不上
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'meta_query'             => [
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

    // 语言判定依赖术语缓存，这里一次性预热，避免每篇各查一次
    if ($query->posts !== []) {
        update_object_term_cache(wp_list_pluck($query->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
    }

    // 组键带内容类型：同一别名下的文章与页面分属两组，与路由、编辑页面板的口径一致。
    // 数组顺序即「组里最新一篇」的先后，也就是列表从新到旧的顺序
    $published = [];

    foreach ($query->posts as $post) {
        $path = iro_i18n_get_path($post->ID);

        if ($path !== '') {
            $published[$post->post_type . '|' . $path][] = (int) $post->ID;
        }
    }

    $total = count($published);
    $keys  = array_slice(array_keys($published), ($page - 1) * $per_page, $per_page);
    $paths = [];

    foreach ($keys as $key) {
        $paths[] = substr($key, strpos($key, '|') + 1);
    }

    // 本页涉及的全部关联标识一次性取回版本归属，避免逐行查询
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
                    'value'   => array_values(array_unique($paths)),
                    'compare' => 'IN',
                ],
            ],
        ]);

        if ($grouped->posts !== []) {
            update_object_term_cache(wp_list_pluck($grouped->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
        }

        foreach ($grouped->posts as $grouped_post) {
            $group_path = iro_i18n_get_path($grouped_post->ID);

            if ($group_path !== '') {
                $translations[$grouped_post->post_type . '|' . $group_path][iro_i18n_post_language($grouped_post->ID)] = $grouped_post;
            }
        }
    }

    $items = [];

    foreach ($keys as $key) {
        $group = $translations[$key] ?? [];
        // 内容列显示站点语言版本；它不在（或已进回收站）时才退到组里最新的一篇，
        // 那一版的代号一并回给面板，好让它标明这一行显示的不是站点语言
        $representative = $group[$default] ?? null;

        if (!$representative instanceof WP_Post || $representative->post_status === 'trash') {
            $representative = get_post($published[$key][0] ?? 0);
        }

        if (!$representative instanceof WP_Post) {
            continue;
        }

        // 用原始标题：the_title 过滤器会把 & 之类转成实体，表格里会原样显示
        $title = trim((string) $representative->post_title);

        $items[] = [
            'id'         => $representative->ID,
            'title'      => $title !== '' ? $title : __('（无标题）', 'sakurairo'),
            'type'       => $representative->post_type,
            'path'       => substr($key, strpos($key, '|') + 1),
            'lang'       => iro_i18n_post_language($representative->ID),
            'edit_link'  => (string) get_edit_post_link($representative->ID, 'raw'),
            'statuses'   => iro_i18n_rest_statuses($group),
        ];
    }

    return rest_ensure_response([
        'items'     => $items,
        'languages' => iro_i18n_rest_languages(),
        // 默认语言是站点语言推出来的，面板用它拼那句「默认语言是…」的说明
        'locale'    => iro_i18n_site_locale(),
        'autofuzzy' => iro_i18n_autofuzzy_enabled(),
        'total'     => $total,
        'page'      => $page,
        'per_page'  => $per_page,
        'pages'     => (int) max(1, (int) ceil($total / $per_page)),
    ]);
}

/**
 * 为全部已发布内容补齐关联与未翻译副本
 *
 * 逐个核对每一条已发布内容，缺什么补什么，都在才算「无需改动」：
 * 1. 没有语言标记的认领默认语言——语言隔离靠分类法查询实现，不补上术语它们就会在
 *    任何按语言收窄的列表里消失；
 * 2. 没有关联标识的按自身别名补上，它同时是分组与路由的依据；
 * 3. 由每组的内容来源派生缺失语言的未翻译副本——只补缺的：
 *    `iro_i18n_sync_translations()` 会跳过已有版本（含草稿与回收站），因此重复执行
 *    不会重复建，也不会动到译者已经写过的副本。
 *
 * 第 3 步刻意不限定在「刚刚补上关联标识的内容」上：给内容加一个语言、或永久删掉
 * 一份副本之后，原文早就有标识了，只看标识缺失的话这类缺口永远补不回来。
 *
 * 内容来源优先取该组的默认语言版本（原文的正身）：原文的别名被改过之后它就不再满足
 * 「关联标识等于自身别名」，按基准版本找会漏掉它；若该组确实没有已发布的默认语言版本
 * （原文还是草稿、译文先发布），才退到组里已发布的那一篇，好过留下一个补不上的
 * 「无版本」。
 *
 * 返回分项条数而不是一个总数：全部为 0 时面板要说清「核对过多少条、为什么没改」，
 * 否则一个 0 看起来就像按钮没生效。
 */
function iro_i18n_rest_backfill(): WP_REST_Response
{
    $default = iro_i18n_default_language();

    $query = new WP_Query([
        'post_type'              => iro_i18n_supported_post_types(),
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ]);

    // 下面逐篇判语言，术语缓存先一次性预热
    if ($query->posts !== []) {
        update_object_term_cache(wp_list_pluck($query->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
    }

    $checked = 0;
    $marked  = 0;
    $linked  = 0;
    $copies  = 0;
    $donors  = [];

    foreach ($query->posts as $post) {
        // 未翻译副本不是原文，不给它派生子副本
        if (iro_i18n_is_skeleton($post->ID)) {
            continue;
        }

        $checked++;

        if (!iro_i18n_post_has_language($post->ID)) {
            iro_i18n_adopt_default_language($post->ID);

            if (iro_i18n_post_has_language($post->ID)) {
                $marked++;
            }
        }

        if (iro_i18n_get_path($post->ID) === '' && iro_i18n_ensure_path($post->ID) !== '') {
            $linked++;
        }

        $path = iro_i18n_get_path($post->ID);

        if ($path === '') {
            continue;
        }

        // 组键带内容类型，与总览、路由的口径一致；同组只留一个内容来源
        $key = $post->post_type . '|' . $path;

        if (!isset($donors[$key]) || iro_i18n_post_language($post->ID) === $default) {
            $donors[$key] = $post;
        }
    }

    // 派生放到最后：一轮核对把所有语言标记与关联标识都补齐之后，派生的判定才准
    foreach ($donors as $donor) {
        $copies += iro_i18n_sync_translations($donor->ID);
    }

    return rest_ensure_response([
        'checked' => $checked,
        'marked'  => $marked,
        'linked'  => $linked,
        'copies'  => $copies,
    ]);
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
