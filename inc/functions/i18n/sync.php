<?php

/**
 * 保存时的语言同步：补关联字段、补语言标记、自动建未翻译副本
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 「已存在该语言版本」的判定范围，含回收站
 *
 * 不含回收站的话，删掉一份副本后下次保存又会重建一份，回收站里的那份则永远留着。
 */
function iro_i18n_existing_statuses(): array
{
    return ['publish', 'draft', 'pending', 'private', 'future', 'trash'];
}

/**
 * 「已脱离草稿态」的内容状态
 *
 * 到了这些状态就说明作者或译者已经把这一版交出去了，版本号从此刻起生效；
 * 草稿阶段的反复保存不算新版，否则线上译文会跟着草稿一次次变成待同步。
 */
function iro_i18n_accepted_statuses(): array
{
    return ['publish', 'future', 'pending', 'private'];
}

/**
 * 副本要接手的分类法：该类型下除语言分类法以外的全部
 *
 * 分类目录必须跟着走——副本若留在「未分类」，译者接手后按分类浏览与相关文章
 * 都会对不上原文。
 *
 * @return string[]
 */
function iro_i18n_copyable_taxonomies(string $post_type): array
{
    return array_values(array_filter(
        get_object_taxonomies($post_type),
        static fn(string $taxonomy): bool => $taxonomy !== IRO_I18N_LANGUAGE_TAXONOMY
    ));
}

/**
 * 某篇内容的术语 ID，层级分类法连祖先一起收
 *
 * 祖先必须显式带上：`wp_set_object_terms()` 只认传进去的那几个，只给子项会让副本
 * 在按父分类浏览的列表里漏掉。
 *
 * @return int[]
 */
function iro_i18n_term_ids(int $post_id, string $taxonomy): array
{
    $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);

    if (is_wp_error($terms) || $terms === []) {
        return [];
    }

    $ids = [];

    foreach ($terms as $term_id) {
        $term_id = (int) $term_id;
        $ids[]   = $term_id;

        if (!is_taxonomy_hierarchical($taxonomy)) {
            continue;
        }

        foreach (get_ancestors($term_id, $taxonomy, 'taxonomy') as $ancestor_id) {
            $ids[] = (int) $ancestor_id;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * 副本要接手的编辑器管理字段
 *
 * 这些字段决定内容在前台与编辑器里怎么呈现（特色图片、页面模板、脚注数据、同步样板
 * 状态），少一个副本就和原文不一样。刻意采用白名单：统计、SEO、主题自身的元数据逐条
 * 覆盖会污染副本，比漏拷更难排查。
 *
 * @return string[]
 */
function iro_i18n_copyable_meta_keys(): array
{
    return array_values(array_filter(array_map('strval', (array) apply_filters(
        'iro_i18n_skeleton_meta_whitelist',
        [
            '_thumbnail_id',
            '_wp_page_template',
            '_wp_footnotes',
            'wp_pattern_sync_status',
        ]
    ))));
}

/**
 * 上列字段的当前值，只留非空的（建副本时用：副本本来就没有这些字段）
 *
 * @return array<string,mixed>
 */
function iro_i18n_copyable_meta(int $post_id): array
{
    $values = [];

    foreach (iro_i18n_copyable_meta_keys() as $key) {
        $value = get_post_meta($post_id, $key, true);

        if ($value !== '' && $value !== null) {
            $values[$key] = $value;
        }
    }

    return $values;
}

/**
 * 把原文的分类、标签与编辑器管理字段同步给同组其它语言版本
 *
 * 这些属性只有原文一个来源：副本是原文派生出来的，它自己不会长出新分类，原文调整
 * 之后必须再同步一次，否则译者接手时看到的分类、特色图片和原文对不上。
 *
 * 区块编辑器先保存文章、后写分类与特色图片，派生副本的那一刻它们还没落库，因此派生
 * 路径上这条同步在请求结束前还要再跑一次（见下面的 `rest_after_insert_*`）。
 *
 * @param bool $skeletons_only 只同步「未翻译副本」。自动同步用它：译者自己写过的版本
 *                             已脱离副本身份，该在编辑页面板上按需拉取，
 *                             不该被原文的每次保存悄悄覆盖。
 * @return int 被同步的版本数
 */
function iro_i18n_sync_attributes(int $post_id, bool $skeletons_only = false): int
{
    $post = get_post($post_id);
    $path = iro_i18n_get_path($post_id);

    if (!$post instanceof WP_Post || $path === '') {
        return 0;
    }

    // 属性一律取自原文：在译文上点同步也是把原文的属性拉过来，不是反向推回去
    $source = iro_i18n_group_source($post_id);
    $source = $source instanceof WP_Post ? $source : $post;

    $terms = [];

    foreach (iro_i18n_copyable_taxonomies($post->post_type) as $taxonomy) {
        $terms[$taxonomy] = iro_i18n_term_ids($source->ID, $taxonomy);
    }

    $synced = 0;

    foreach (iro_i18n_get_group($path, [$post->post_type]) as $version) {
        if ((int) $version->ID === (int) $source->ID) {
            continue;
        }

        if ($skeletons_only && !iro_i18n_is_skeleton($version->ID)) {
            continue;
        }

        foreach ($terms as $taxonomy => $term_ids) {
            wp_set_object_terms($version->ID, $term_ids, $taxonomy, false);
        }

        // 原文没有的字段要清掉：手动同步是「让副本和原文一致」，
        // 留下原文已经撤掉的特色图片比不同步更难解释
        foreach (iro_i18n_copyable_meta_keys() as $key) {
            $value = get_post_meta($source->ID, $key, true);

            if ($value === '' || $value === null) {
                delete_post_meta($version->ID, $key);
                continue;
            }

            update_post_meta($version->ID, $key, $value);
        }

        $synced++;
    }

    return $synced;
}

/**
 * 把全站内容的属性同步给各自的语言版本
 *
 * 一行 = 一组内容，属性一律取自本组的原文：同组只跑一次，否则一组四篇会把同一份属性
 * 反反复复写四遍。内容来源优先取站点语言那一版（原文的正身），它不在时才退到组里
 * 最早的一篇——原文的别名被改过之后就不再满足「基准版本」的判定。
 *
 * @return array{groups:int,synced:int} 核对过的组数与被同步的版本数
 */
function iro_i18n_sync_all_attributes(): array
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
        'meta_query'             => [
            [
                'key'     => IRO_I18N_PATH_META,
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    // 语言判定逐篇要用术语，缓存先一次性预热
    if ($query->posts !== []) {
        update_object_term_cache(wp_list_pluck($query->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
    }

    $donors = [];

    foreach ($query->posts as $post) {
        if (iro_i18n_is_skeleton($post->ID)) {
            continue;
        }

        $path = iro_i18n_get_path($post->ID);

        if ($path === '') {
            continue;
        }

        $key = $post->post_type . '|' . $path;

        if (!isset($donors[$key]) || iro_i18n_post_language($post->ID) === $default) {
            $donors[$key] = (int) $post->ID;
        }
    }

    $synced = 0;

    foreach ($donors as $post_id) {
        $synced += iro_i18n_sync_attributes($post_id);
    }

    return ['groups' => count($donors), 'synced' => $synced];
}

/**
 * 为一份原文补齐其余语言的未翻译副本
 *
 * 副本的正文按原文逐区块原样复制（区块注释、区块属性都不动），这样官方 AI 之类的
 * 逐区块翻译工具打开副本即可直接对着区块翻译；留空的话区块编辑器里是空文档，
 * 所有区块翻译能力都无从谈起。副本默认留成草稿：不会以未翻译内容出现在前台，
 * 也不会被收录，译者填完内容后改成发布即接手同一路径。分类、标签与特色图片等属性
 * 也一并接手，见 `iro_i18n_sync_attributes()`。
 *
 * 幂等：已有版本（含草稿与回收站）的语言一律跳过，因此可以反复调用，
 * 面板的批量补齐也靠它把后来新增语言、或被永久删掉的副本补回来。
 *
 * @return int 这次新建的副本份数
 */
function iro_i18n_sync_translations(int $post_id): int
{
    // 自动复制未翻译副本的总开关
    if (!apply_filters('iro_i18n_autocopy_enabled', true)) {
        return 0;
    }

    $path = iro_i18n_get_path($post_id);
    $post = get_post($post_id);

    if ($path === '' || !$post instanceof WP_Post) {
        return 0;
    }

    $copies = 0;

    $post_type = $post->post_type ?: 'post';

    foreach (iro_i18n_languages() as $code) {
        // 默认语言不复制：它自己就是默认语言下的版本，复制一份只会多出一篇同路径的重复内容
        if (iro_i18n_is_default_language($code)) {
            continue;
        }

        // 判定含回收站与草稿，因此译者手上的草稿副本不会被再建一份
        if (iro_i18n_get_translation($path, $code, [$post_type]) instanceof WP_Post) {
            continue;
        }

        $meta = [
            IRO_I18N_PATH_META     => $path,
            IRO_I18N_SKELETON_META => '1',
        ] + iro_i18n_copyable_meta($post->ID);

        $created = wp_insert_post([
            'post_type'    => $post_type,
            // 标题就是原文标题：副本的身份由语言标记与别名后缀承担，标题里再挂
            // 「未翻译」既是冗余（列表有语言列），又要译者手动删一次
            'post_title'   => $post->post_title,
            'post_name'    => iro_i18n_skeleton_post_name($path, $code),
            // 副本是为译者准备的落点，默认留成草稿
            'post_status'  => (string) apply_filters('iro_i18n_skeleton_status', 'draft'),
            // 用库里的原始 post_content，不经 the_content：区块标记与属性必须逐字保留
            'post_content' => (string) apply_filters('iro_i18n_skeleton_content', (string) $post->post_content, $post),
            'post_excerpt' => (string) $post->post_excerpt,
            'post_author'  => $post->post_author,
            'post_parent'  => $post->post_parent,
            // 标记必须与插入同批写入：插入本身会触发 save_post，
            // 若标记晚一步落库，那次嵌套同步会把副本当成原文再派一轮副本
            'meta_input'   => $meta + [IRO_I18N_POST_LANG_META => $code],
        ], true);

        if (is_wp_error($created)) {
            continue;
        }

        $copies++;
        $skeleton_id = (int) $created;

        // 语言标记放在最后：上面一旦失败也不会把语言写错
        iro_i18n_set_post_language($skeleton_id, $code);

        // 让同一请求里接下来的「该语言是否已有版本」看到这一篇
        iro_i18n_flush_group_cache();

        do_action('iro_i18n_skeleton_created', $skeleton_id, $post_id, $code);
    }

    // 分类、标签与特色图片在副本都建好之后统一搬一次。术语不能在 wp_insert_post 的
    // 同一次调用里给：那次插入会先落一颗默认语言（分类法的默认术语、分类的「未分类」），
    // 与正文同步派生副本的顺序纠缠在一起
    if ($copies > 0) {
        iro_i18n_sync_attributes($post_id, true);
    }

    return $copies;
}

/**
 * 给尚无语言标记的内容认领默认语言
 *
 * 语言隔离靠分类法查询实现，因此「未标记」必须在数据侧落实成默认语言术语；
 * 否则新写的文章不带任何语言术语，一开语言筛选就会发现它从列表里消失了。
 * 历史内容由「文章翻译」面板里的批量补齐统一处理。
 */
function iro_i18n_adopt_default_language(int $post_id): void
{
    if (iro_i18n_is_skeleton($post_id) || iro_i18n_post_has_language($post_id)) {
        return;
    }

    $post = get_post($post_id);

    if (!$post instanceof WP_Post || !in_array($post->post_type, iro_i18n_supported_post_types(), true)) {
        return;
    }

    if (in_array($post->post_status, ['auto-draft', 'inherit', 'trash'], true)) {
        return;
    }

    iro_i18n_set_post_language($post_id, iro_i18n_default_language());
}

/**
 * 区块编辑器写完分类与特色图片之后再同步一次
 *
 * REST 请求的顺序是「保存文章 → 写分类与特色图片」，派生副本的 save_post 那一刻它们
 * 还没落库，副本拿到的是「未分类」与空特色图片；落库之后必须再同步一次，否则新文章的
 * 副本永远停在未分类。保存流程里那次同步对传统编辑器（属性随文章一起提交）已经足够。
 */
function iro_i18n_sync_attributes_after_rest(WP_Post $post): void
{
    if (iro_i18n_is_skeleton($post->ID) || wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
        return;
    }

    iro_i18n_sync_attributes($post->ID, true);
}

/**
 * 特色图片单独挂一条：任何编辑器都是先保存文章、后写特色图片关联
 *
 * 区块编辑器由 `rest_after_insert_*` 兜住，传统编辑器与直接调用 `set_post_thumbnail()`
 * 的路径只有这一条。值没变时 WordPress 不会触发钩子，因此同步写回副本的那几次不会
 * 变成来回递归。
 *
 * @param int    $meta_id  无关，钩子签名要求
 * @param int    $post_id  关联的那篇内容
 * @param string $meta_key 被写的字段名
 */
function iro_i18n_sync_attributes_after_thumbnail(int $meta_id, int $post_id, string $meta_key): void
{
    if ($meta_key !== '_thumbnail_id') {
        return;
    }

    $post = get_post($post_id);

    if (!$post instanceof WP_Post || !in_array($post->post_type, iro_i18n_supported_post_types(), true)) {
        return;
    }

    iro_i18n_sync_attributes($post_id, true);
}

if (iro_i18n_enabled()) {
    /**
     * 保存只对该分类法支持的类型生效，且必须跳过自动保存与修订
     *
     * 只对「原始内容」生效：自动建出来的副本不再派生子副本。关联字段任何状态都落库，
     * 原文只在发布后才新建多语言副本，防止测试文章生成无效数据。
     */
    add_action('save_post', function (int $post_id, ?WP_Post $post = null): void {
        if (!iro_i18n_enabled()) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if (!$post instanceof WP_Post || !in_array($post->post_type, iro_i18n_supported_post_types(), true)) {
            return;
        }

        // 自动草稿与继承记录不是内容本身，其余状态（含回收站）都让关联字段落库
        if (in_array($post->post_status, ['auto-draft', 'inherit', 'trash'], true)) {
            return;
        }

        iro_i18n_adopt_default_language($post_id);

        if (iro_i18n_is_skeleton($post_id)) {
            // 副本脱离草稿态就算被认可
            if (in_array($post->post_status, iro_i18n_accepted_statuses(), true)) {
                delete_post_meta($post_id, IRO_I18N_SKELETON_META);
            }

            return;
        }

        iro_i18n_ensure_path($post_id);

        if ($post->post_status === 'publish') {
            iro_i18n_sync_translations($post_id);
        }
    }, 20, 3);

    foreach (iro_i18n_supported_post_types() as $post_type) {
        add_action("rest_after_insert_{$post_type}", 'iro_i18n_sync_attributes_after_rest', 20);
    }

    add_action('added_post_meta', 'iro_i18n_sync_attributes_after_thumbnail', 20, 3);
    add_action('updated_post_meta', 'iro_i18n_sync_attributes_after_thumbnail', 20, 3);
}
