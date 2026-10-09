<?php

/**
 * 译文时效：原文改过之后，译文自动进入「待同步」并在前台挂出提示
 *
 * 判定方式是版本指纹，不是状态标记：
 * 基准版本（原文）每改一次内容，指纹就变一次；译文记着自己当初对齐的那一枚指纹，
 * 对不上就是待同步。因此它有三个状态，且全部由数据推导，不需要人工解除：
 * - 无版本：该语言还没有版本（由 sync.php 建副本，或译者尚未接手）；
 * - 已同步：译文指纹 = 原文当前指纹；
 * - 待同步：原文在此之后改过，译文与最新原文可能有差异。
 *
 * 刻意不做的事：
 * - 不动译文的状态与标题（早期版本把译文转草稿并加标题前缀，那会让线上译文直接消失）；
 * - 不留修订或内容快照（原文与译文是两份不同的文本，逐段对照看不出该改哪里），
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
 * 把旧实现留下的标题前缀摘掉显示
 *
 * 早期版本在标记待同步时把前缀直接写进了 `post_title`。现在标记不再动标题，
 * 但库里可能还留着，这里只在输出时摘掉，不写库——编辑器读出干净标题后一保存，
 * 前缀自然就没了；若在这里写库又要处理「写库触发钩子」那一圈问题，不值得。
 */
function iro_i18n_strip_legacy_prefix(string $title): string
{
    return strpos($title, IRO_I18N_LEGACY_FUZZY_PREFIX) === 0
        ? ltrim(substr($title, strlen(IRO_I18N_LEGACY_FUZZY_PREFIX)))
        : $title;
}

/**
 * 内容指纹：只取内容字段，忽略 post_modified_gmt 之类的记账字段
 */
function iro_i18n_source_hash(?WP_Post $post = null): string
{
    $post = $post instanceof WP_Post ? $post : get_post();

    if (!$post instanceof WP_Post) {
        return '';
    }

    return md5(implode("\0", [
        (string) $post->post_title,
        (string) $post->post_content,
        (string) $post->post_excerpt,
    ]));
}

/**
 * 让 `$translation` 对齐到 `$source` 当前的内容版本
 *
 * 「原文自身」也会记录一份：面板要靠它显示原文这一版是什么时候改的。
 */
function iro_i18n_mark_synced(WP_Post $translation, WP_Post $source): void
{
    update_post_meta($translation->ID, IRO_I18N_SOURCE_HASH, iro_i18n_source_hash($source));
    update_post_meta($translation->ID, IRO_I18N_SOURCE_AT, (string) $source->post_modified_gmt);
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

    // 原文不在库里（或未发布）就无从比较，按已同步处理，不打扰读者
    if (!$source instanceof WP_Post) {
        return false;
    }

    $recorded = (string) get_post_meta($post_id, IRO_I18N_SOURCE_HASH, true);

    // 缺记录的历史译文：退回按时间判断，原文最后改动晚于译文即为落后
    if ($recorded === '') {
        return $source->post_modified_gmt > (string) get_post_field('post_modified_gmt', $post_id);
    }

    return $recorded !== iro_i18n_source_hash($source);
}

/**
 * 原文改动后，把同组其余语言的对齐记录清掉，使它们转入待同步
 *
 * 直接删记录而不是写新指纹：删掉之后统一按「原文最后改动是否晚于译文最后改动」
 * 比较，对从未记录过的历史译文同样成立。
 *
 * @return array<int,WP_Post> 变为待同步的译文
 */
function iro_i18n_clear_translation_baseline(int $post_id): array
{
    if (!iro_i18n_autofuzzy_enabled() || !iro_i18n_is_base_post($post_id)) {
        return [];
    }

    $path = iro_i18n_get_path($post_id);

    if ($path === '') {
        return [];
    }

    $affected = [];

    foreach (iro_i18n_get_group($path, [get_post_type($post_id) ?: 'post']) as $translation) {
        // 原文自己、回收站里的版本、还没人接手的副本都不算「落后」
        if ($translation->ID === $post_id || $translation->post_status === 'trash' || iro_i18n_is_skeleton($translation->ID)) {
            continue;
        }

        if (get_post_meta($translation->ID, IRO_I18N_SOURCE_HASH, true) === '') {
            continue;
        }

        delete_post_meta($translation->ID, IRO_I18N_SOURCE_HASH);
        delete_post_meta($translation->ID, IRO_I18N_SOURCE_AT);

        $affected[] = $translation;
    }

    if ($affected !== []) {
        iro_i18n_flush_group_cache();
    }

    return $affected;
}

/**
 * 保存时维护指纹
 *
 * 原文发布时把它自己与全部译文重新对齐：草稿阶段的反复修改不该让线上译文
 * 一次次变成待同步，真正算数的是「原文发布出去的这一版」。
 * 译文发布时对齐到原文当前指纹，视为译者已核对过。
 */
function iro_i18n_sync_versions(int $post_id, WP_Post $post): void
{
    if (!iro_i18n_enabled() || !iro_i18n_autofuzzy_enabled()) {
        return;
    }

    if ($post->post_status !== 'publish' || iro_i18n_is_skeleton($post_id)) {
        return;
    }

    $path = iro_i18n_get_path($post_id);

    if ($path === '') {
        return;
    }

    if (!iro_i18n_is_base_post($post_id)) {
        // 译文发布：对齐到它所依据的原文版本
        $source = iro_i18n_group_source($post_id);

        if ($source instanceof WP_Post) {
            iro_i18n_mark_synced($post, $source);
        }

        return;
    }

    iro_i18n_mark_synced($post, $post);

    foreach (iro_i18n_get_group($path, [$post->post_type]) as $translation) {
        if ($translation->ID === $post_id || $translation->post_status === 'trash' || iro_i18n_is_skeleton($translation->ID)) {
            continue;
        }

        iro_i18n_mark_synced($translation, $post);
    }
}

/**
 * 原文内容变更 → 译文进入待同步
 */
function iro_i18n_handle_post_updated(int $post_id, WP_Post $after, WP_Post $before): void
{
    if (!iro_i18n_enabled() || !iro_i18n_autofuzzy_enabled()) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!in_array($after->post_type, iro_i18n_supported_post_types(), true) || $after->post_status === 'trash') {
        return;
    }

    // 内容没变就不算变更，避免插件写 meta 也把全站译文标成待同步
    if (!iro_i18n_content_changed($before, $after)) {
        return;
    }

    // 人一动它就不再是「未翻译」占位：去掉副本标记，前台改挂「可能过期」提示而非「尚未翻译」
    iro_i18n_clear_skeleton($post_id);

    iro_i18n_clear_translation_baseline($post_id);
}

/**
 * 只比较内容字段，忽略 post_modified_gmt 之类的记账字段
 */
function iro_i18n_content_changed(WP_Post $before, WP_Post $after): bool
{
    foreach (['post_title', 'post_content', 'post_excerpt'] as $field) {
        if ($before->$field !== $after->$field) {
            return true;
        }
    }

    return false;
}

if (iro_i18n_enabled()) {
    // 顺序要紧：sync.php 的 save_post(20) 会顺手派生副本，
    // 这里必须在那之后跑，否则标记的是「还没有译文」的那一轮
    add_action('post_updated', 'iro_i18n_handle_post_updated', 20, 3);
    add_action('save_post', 'iro_i18n_sync_versions', 25, 2);
    add_filter('the_title', 'iro_i18n_strip_legacy_prefix');
}
