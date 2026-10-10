<?php

/**
 * 译文时效：原文改过之后，译文自动进入「待同步」并在前台挂出提示
 *
 * 判定方式是版本号，不做内容比较：译文记着自己当初对齐的那一版原文的版本号
 * （post_modified_gmt），与原文当前那一枚对不上就是待同步。因此它有三个状态，
 * 且全部由数据推导，不需要人工解除：
 * - 无版本：该语言还没有版本（由 sync.php 建副本，或译者尚未接手）；
 * - 已同步：译文对齐的版本号 = 原文当前的版本号；
 * - 待同步：原文在此之后又认可过新版，译文与最新原文可能有差异。
 *
 * 只有脱离草稿态的保存才算认可一版：原文还在草稿里时不参与比较，草稿阶段的反复修改
 * 不该让线上译文一次次变成待同步；原文一发布，它的版本号就前进，译文随之变成待同步。
 *
 * 刻意不做的事：
 * - 不比内容：自动建出的占位副本正文与原文逐字相同，按内容去猜「这一版算不算数」
 *   只会把占位副本和真正的译文认成同一版；版本号是 WordPress 自己的记账事实。
 * - 不动译文的状态与标题（保持在线状态）；
 * - 不留修订或内容快照（diff没有意义），
 *   改由前台提示 + 一键跳回原文最新版承担。
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 旧实现写进标题里的待同步前缀，仅用于给已有数据收尾 */
const IRO_I18N_LEGACY_FUZZY_PREFIX = '【待同步】';

function iro_i18n_autofuzzy_enabled(): bool
{
    return (bool) iro_opt('iro_i18n_autofuzzy', true);
}

/**
 * 原文当前的版本号
 *
 * 按库里的当前值读，而不是手上对象的属性：同一次请求里那个对象可能是早先取回并缓存进
 * 组查询的旧快照，直接读它会把刚发布出去的原文看成还没改过。
 */
function iro_i18n_source_stamp(WP_Post $source): string
{
    return (string) get_post_field('post_modified_gmt', $source->ID);
}

/**
 * 让 `$translation` 对齐到 `$source` 当前的版本号
 *
 * 「原文自身」也会记录一份：面板要靠它显示原文这一版是什么时候改的。
 */
function iro_i18n_mark_synced(WP_Post $translation, WP_Post $source): void
{
    update_post_meta($translation->ID, IRO_I18N_SOURCE_AT, iro_i18n_source_stamp($source));
}

/**
 * 同组的原文（基准版本）
 */
function iro_i18n_group_source(int $post_id): ?WP_Post
{
    $path = iro_i18n_get_path($post_id);

    if ($path === '') {
        return null;
    }

    return iro_i18n_base_post($path, [get_post_type($post_id) ?: 'post']);
}

/**
 * 展示用的时间戳：该篇所记录的那一版原文的时间
 *
 * 原文自己记录的是它自己的最后改动时间，译文记录的是它对齐到的那一版时间，
 * 因此同一个函数既能回答「原文这一版是什么时候改的」，也能回答「译文对齐到哪一版」。
 * 库里存的是 GMT，按站点时区格式化后再展示。
 */
function iro_i18n_source_version(int $post_id): string
{
    $stored = (string) get_post_meta($post_id, IRO_I18N_SOURCE_AT, true);
    $format = (string) get_option('date_format') . ' ' . (string) get_option('time_format');

    if ($stored === '') {
        return (string) get_post_modified_time($format, false, $post_id, true);
    }

    return (string) wp_date($format, (int) strtotime($stored . ' UTC'));
}

/**
 * 译文是否落后于原文
 *
 * 只比版本号：原文当前的版本号 vs 译文对齐到的那一版。
 * 未翻译副本不算：它是「无版本」状态的载体，另有单独提示。
 */
function iro_i18n_translation_outdated(int $post_id): bool
{
    if (!iro_i18n_enabled() || !iro_i18n_autofuzzy_enabled() || iro_i18n_is_skeleton($post_id)) {
        return false;
    }

    if (iro_i18n_get_path($post_id) === '' || iro_i18n_is_base_post($post_id)) {
        return false;
    }

    $source = iro_i18n_group_source($post_id);

    // 原文不在库里（或还没发布）就无从比较，按已同步处理，不打扰读者。
    // 原文还在草稿阶段的那些修改正落在这一支，因此不会惊动线上译文。
    if (!$source instanceof WP_Post) {
        return false;
    }

    $recorded = (string) get_post_meta($post_id, IRO_I18N_SOURCE_AT, true);
    $current  = iro_i18n_source_stamp($source);

    // 缺记录的历史译文：退回比它自己的最后改动时间，原文这一版更晚即为落后
    if ($recorded === '') {
        return $current > (string) get_post_field('post_modified_gmt', $post_id);
    }

    return $recorded !== $current;
}

if (iro_i18n_enabled()) {
    /**
     * 保存时维护版本号
     *
     * 只有脱离草稿态的那一次保存才算认可一版：原文由此有了新的版本号，译文记下它对齐的是哪一版。
     * 原文认可新版时刻意不去同步译文——译文正是该变成待同步的那一方。
     */
    add_action('save_post', function (int $post_id, WP_Post $post): void {
        if (!iro_i18n_enabled() || !iro_i18n_autofuzzy_enabled()) {
            return;
        }

        if (!in_array($post->post_status, iro_i18n_accepted_statuses(), true) || iro_i18n_is_skeleton($post_id)) {
            return;
        }

        $path = iro_i18n_get_path($post_id);

        if ($path === '') {
            return;
        }

        // 译文脱离草稿态：认作译者已核对过，对齐到原文当前的版本号
        if (!iro_i18n_is_base_post($post_id)) {
            $source = iro_i18n_group_source($post_id);

            if ($source instanceof WP_Post) {
                iro_i18n_mark_synced($post, $source);
            }

            return;
        }

        iro_i18n_mark_synced($post, $post);
    }, 25, 2);

    /**
     * 把旧实现留下的标题前缀摘掉显示
     *
     * 早期版本在标记待同步时把前缀直接写进了 `post_title`。现在标记不再动标题，
     * 但库里可能还留着，这里只在输出时摘掉，不写库——编辑器读出干净标题后一保存，
     * 前缀自然就没了；若在这里写库又要处理「写库触发钩子」那一圈问题，不值得。
     */
    add_filter('the_title', function (string $title): string {
        return strpos($title, IRO_I18N_LEGACY_FUZZY_PREFIX) === 0
            ? ltrim(substr($title, strlen(IRO_I18N_LEGACY_FUZZY_PREFIX)))
            : $title;
    });
}
