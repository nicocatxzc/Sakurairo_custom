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
 * 为一份原文补齐其余语言的未翻译副本
 *
 * 副本的正文按原文逐区块原样复制（区块注释、区块属性都不动），这样官方 AI 之类的
 * 逐区块翻译工具打开副本即可直接对着区块翻译；留空的话区块编辑器里是空文档，
 * 所有区块翻译能力都无从谈起。副本默认留成草稿：不会以未翻译内容出现在前台，
 * 也不会被收录，译者填完内容后改成发布即接手同一路径。
 */
function iro_i18n_sync_translations(int $post_id): void
{
    // 自动复制未翻译副本的总开关
    if (!apply_filters('iro_i18n_autocopy_enabled', true)) {
        return;
    }

    $path = iro_i18n_get_path($post_id);
    $post = get_post($post_id);

    if ($path === '' || !$post instanceof WP_Post) {
        return;
    }

    $post_type = $post->post_type ?: 'post';

    // 副本要接手的分类法：该类型下除语言分类法以外的全部。分类目录必须跟着走——
    // 副本若留在「未分类」，译者接手后按分类浏览和相关文章都会对不上原文
    $taxonomies = array_values(array_filter(
        get_object_taxonomies($post_type),
        static fn(string $taxonomy): bool => $taxonomy !== IRO_I18N_LANGUAGE_TAXONOMY
    ));

    foreach (iro_i18n_languages() as $code) {
        // 默认语言不复制：它自己就是默认语言下的版本，复制一份只会多出一篇同路径的重复内容
        if (iro_i18n_is_default_language($code)) {
            continue;
        }

        // 判定含回收站与草稿，因此译者手上的草稿副本不会被再建一份
        if (iro_i18n_get_translation($path, $code, [$post_type]) instanceof WP_Post) {
            continue;
        }

        /**
         * 副本要接手的编辑器管理字段
         *
         * 这些字段决定区块在前台与编辑器里怎么渲染（页面模板、脚注数据、同步样板状态），
         * 少一个就会让副本的区块行为和原文不一致。刻意采用白名单：
         * 逐条覆盖统计、SEO、主题自身的元数据会污染副本，比漏拷更难排查。
         */
        $meta = [
            IRO_I18N_PATH_META     => $path,
            IRO_I18N_SKELETON_META => '1',
        ];

        foreach ((array) apply_filters('iro_i18n_skeleton_meta_whitelist', [
            '_wp_page_template',
            '_wp_footnotes',
            'wp_pattern_sync_status',
        ]) as $key) {
            $value = get_post_meta($post->ID, (string) $key, true);

            if ($value !== '' && $value !== null) {
                $meta[(string) $key] = $value;
            }
        }

        $created = wp_insert_post([
            'post_type'    => $post_type,
            'post_title'   => sprintf(
                /* translators: 1: 原文标题 2: 语言代号 */
                __('%1$s %2$s 未翻译', 'sakurairo'),
                $post->post_title,
                $code
            ),
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

        $skeleton_id = (int) $created;

        // 术语不能在 wp_insert_post 的同一次调用里给：默认语言术语会覆盖掉译文语言
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);

            if (is_wp_error($terms) || $terms === []) {
                continue;
            }

            $collected = [];

            foreach ($terms as $term_id) {
                $term_id     = (int) $term_id;
                $collected[] = $term_id;

                if (!is_taxonomy_hierarchical($taxonomy)) {
                    continue;
                }

                foreach (get_ancestors($term_id, $taxonomy, 'taxonomy') as $ancestor_id) {
                    $collected[] = (int) $ancestor_id;
                }
            }

            wp_set_object_terms($skeleton_id, array_values(array_unique($collected)), $taxonomy, false);
        }

        // 语言标记放在最后：上面一旦失败也不会把语言写错
        iro_i18n_set_post_language($skeleton_id, $code);

        // 让同一请求里接下来的「该语言是否已有版本」看到这一篇
        iro_i18n_flush_group_cache();

        do_action('iro_i18n_skeleton_created', $skeleton_id, $post_id, $code);
    }
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

if (iro_i18n_enabled()) {
    /**
     * 保存只对该分类法支持的类型生效，且必须跳过自动保存与修订
     *
     * 只对「原始内容」生效：自动建出来的副本不再派生子副本。关联字段任何状态都落库，
     * 但副本只在内容真正发布后才派生，否则一篇写着玩的草稿会在后台留下三份同样没用的草稿副本。
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
            return;
        }

        iro_i18n_ensure_path($post_id);

        if ($post->post_status === 'publish') {
            iro_i18n_sync_translations($post_id);
        }
    }, 20, 3);
}
