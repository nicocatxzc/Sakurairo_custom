<?php

/**
 * 语言关联字段：把同一份内容在各语言下的版本串成一组
 *
 * 字段值是「默认语言下的基准路径」（不含语言前缀，如 about），因此它同时承担两件事：
 * 1. 翻译关联：同组 = 同一基准路径，查同组某语言的版本只需一次 meta 查询；
 * 2. 路由基准：前缀路由可以拿「语言 + 基准路径」直接定位版本，不必依赖副本的 post_name。
 */

if (!defined('ABSPATH')) {
    exit;
}

const IRO_I18N_PATH_META = '_iro_i18n_path';

/** 标记「自动建出来的未翻译副本」，与基准路径配合区分原文与副本 */
const IRO_I18N_SKELETON_META = '_iro_i18n_skeleton';

/**
 * 译文所依据的原文版本：原文内容指纹（见 fuzzy.php 的 iro_i18n_source_hash）
 *
 * 原文每改一次指纹就变，同组的译文指纹对不上即为「待同步」。
 */
const IRO_I18N_SOURCE_HASH = '_iro_i18n_source_hash';

/** 原文改动的时间，仅供后台显示「原文最后改动于…」 */
const IRO_I18N_SOURCE_AT = '_iro_i18n_source_at';

function iro_i18n_is_skeleton(int $post_id): bool
{
    return get_post_meta($post_id, IRO_I18N_SKELETON_META, true) === '1';
}

/**
 * 读取文章的语言关联字段
 */
function iro_i18n_get_path(int $post_id): string
{
    return (string) get_post_meta($post_id, IRO_I18N_PATH_META, true);
}

/**
 * 由文章自身推导一个稳定的基准路径
 *
 * 优先用别名，别名被站长改过也只影响它自己所在的组，不会牵连同组其它版本。
 */
function iro_i18n_compute_path(int $post_id): string
{
    $post = get_post($post_id);

    if (!$post instanceof WP_Post) {
        return '';
    }

    $base = $post->post_name !== '' ? $post->post_name : (string) $post->ID;

    return (string) apply_filters('iro_i18n_compute_path', $base, $post);
}

/**
 * 保障文章已有关联字段
 *
 * 只在缺失时写入：同组各版本共享同一个基准路径，重复写入会把已有分组改断。
 * 例外是早期版本在别名尚未生成时退化写入的 ID 路径——那会让「基准版本」
 * 判定永久失效，一旦别名可用就迁移回别名。
 */
function iro_i18n_ensure_path(int $post_id): string
{
    $path = iro_i18n_get_path($post_id);
    $name = (string) get_post_field('post_name', $post_id);

    if ($path !== '' && !($name !== '' && $path === (string) $post_id)) {
        return $path;
    }

    $path = iro_i18n_compute_path($post_id);

    if ($path !== '') {
        update_post_meta($post_id, IRO_I18N_PATH_META, $path);
    }

    return $path;
}

/**
 * 是否为组的基准版本（原文）
 *
 * 基准版本的关联字段等于它自己的别名（或 ID），副本则继承原文的别名；
 * 有了它才能让「原文改动 → 其余语言失效」只朝一个方向传播，
 * 否则改一份译文会把原文也标成待同步。
 */
function iro_i18n_is_base_post(int $post_id): bool
{
    $path = iro_i18n_get_path($post_id);

    if ($path === '' || iro_i18n_is_skeleton($post_id)) {
        return false;
    }

    return $path === iro_i18n_compute_path($post_id);
}

/**
 * 按基准路径取同组的全部版本，没有则返回空数组
 *
 * 术语缓存刻意保持开启：取回后每篇都要判语言，关掉会退化成逐篇查库。
 * 组结果按请求缓存：一次渲染里语言切换器、hreflang、面板会反复问同一组。
 * 组只在保存流程里变化，那个流程由 save_post 触发，早于任何前台渲染；
 * 保存流程内部自己增删版本时用 `iro_i18n_flush_group_cache()` 显式失效。
 *
 * **未命中也缓存**：路径匹配会按段数与后缀反复试探，不缓存否定结果的话
 * 一次请求光路由就要多跑十几条查询。
 *
 * 第三个参数是内部的缓存失效开关，调用方一律不要传。
 */
function iro_i18n_get_group(string $path, array $post_types = [], bool $flush = false): array
{
    static $cache = [];

    if ($flush) {
        $cache = [];

        return [];
    }

    if ($path === '') {
        return [];
    }

    $post_types = $post_types === [] ? iro_i18n_supported_post_types() : array_values($post_types);
    $key        = $path . '|' . implode(',', $post_types);

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $query = new WP_Query([
        'post_type'           => $post_types,
        'post_status'         => iro_i18n_existing_statuses(),
        'posts_per_page'      => -1,
        'orderby'             => 'ID',
        'order'               => 'ASC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'meta_query'          => [
            [
                'key'   => IRO_I18N_PATH_META,
                'value' => $path,
            ],
        ],
    ]);

    if ($query->posts !== []) {
        update_object_term_cache(wp_list_pluck($query->posts, 'ID'), IRO_I18N_LANGUAGE_TAXONOMY);
    }

    $cache[$key] = $query->posts;

    return $cache[$key];
}

/**
 * 丢弃组查询的请求级缓存
 *
 * 组缓存按请求保存，而派生副本是在同一请求里逐个建出来的：
 * 不清掉的话，`iro_i18n_sync_translations()` 的第一轮循环结束前，
 * 后续的「该语言是否已有版本」都还在读第一次的旧结果。
 */
function iro_i18n_flush_group_cache(): void
{
    iro_i18n_get_group('', [], true);
}

/**
 * 按基准路径取某语言的版本
 *
 * `$prefer_base` 为真时优先返回基准版本（原文）：默认语言的「这一篇」就是原文，
 * 同组里可能同时存在早期版本留下的同名副本，不能随手取第一篇。
 */
function iro_i18n_get_translation(string $path, string $code, array $post_types = [], bool $prefer_base = false): ?WP_Post
{
    $fallback = null;

    foreach (iro_i18n_get_group($path, $post_types) as $post) {
        if (iro_i18n_post_language($post->ID) !== $code) {
            continue;
        }

        if (iro_i18n_is_base_post($post->ID)) {
            if ($prefer_base) {
                return $post;
            }

            $fallback = $fallback ?? $post;
            continue;
        }

        if (!$prefer_base) {
            return $post;
        }

        $fallback = $fallback ?? $post;
    }

    return $fallback;
}

/**
 * 路由入口：请求「某语言下的某基准路径」时该给哪一篇
 *
 * 默认语言直接落到原文（默认语言不复制副本），其余语言取该语言的版本。
 * 只看已发布：草稿状态的副本是给译者预备的落点，不该被访客走到。
 *
 * @param string[] $post_types
 */
function iro_i18n_translation_post(string $path, string $code, array $post_types = []): ?WP_Post
{
    if (iro_i18n_is_default_language($code)) {
        return iro_i18n_base_post($path, $post_types);
    }

    $translation = iro_i18n_get_translation($path, $code, $post_types);

    return $translation instanceof WP_Post && $translation->post_status === 'publish' ? $translation : null;
}

/**
 * 某基准路径在指定类型下的原文（基准版本）
 *
 * @param string[] $post_types
 */
function iro_i18n_base_post(string $path, array $post_types = []): ?WP_Post
{
    foreach (iro_i18n_get_group($path, $post_types) as $post) {
        if ($post->post_status === 'publish' && iro_i18n_is_base_post($post->ID)) {
            return $post;
        }
    }

    return null;
}

/**
 * 同组各语言版本的对照表，供面板与后续的 hreflang 输出复用
 *
 * @return array<string,WP_Post>
 */
function iro_i18n_group_map(int $post_id): array
{
    $map  = [];
    $path = iro_i18n_get_path($post_id);

    if ($path === '') {
        $post = get_post($post_id);

        return $post instanceof WP_Post ? [iro_i18n_post_language($post_id) => $post] : [];
    }

    foreach (iro_i18n_get_group($path, [get_post_type($post_id) ?: 'post']) as $post) {
        $map[iro_i18n_post_language($post->ID)] = $post;
    }

    return $map;
}

/**
 * 未翻译副本的别名：原文别名 + 语言代号
 *
 * WordPress 要求同一 post_type 下别名唯一，副本没法与原文同名（同名时
 * `wp_unique_post_slug` 会给它接一个 `-2`、`-3`，既不稳定也看不出是哪种语言）。
 * 因此显式接上代号，路由侧再把它剥掉匹配回组的基准路径。
 *
 * 代号而不是 URL 前缀：前缀只是对外形态，别名属于内容标识，用代号才一致。
 */
function iro_i18n_skeleton_post_name(string $path, string $code): string
{
    $slug = (string) basename($path);

    if ($slug === '') {
        $slug = $path;
    }

    return (string) apply_filters('iro_i18n_skeleton_post_name', $slug . '-' . $code, $path, $code);
}
