<?php

/**
 * 语言分类法：注册 iro_lang，维护语言术语，提供文章语言标记的读写
 */

if (!defined('ABSPATH')) {
    exit;
}

const IRO_I18N_LANGUAGE_TAXONOMY = 'iro_lang';

/** 语言术语上的语言代号，避免依赖可改动的术语别名 */
const IRO_I18N_LANGUAGE_TERM_META = 'iro_i18n_code';

/**
 * 文章语言标记的 meta 键
 *
 * 语言判定以 meta 为准：术语 ID 会随站点重建而变化，代号不会。
 */
const IRO_I18N_POST_LANG_META = '_iro_i18n_lang';

/**
 * 语言标记只支持文章与页面
 *
 * @return string[]
 */
function iro_i18n_supported_post_types(): array
{
    return apply_filters('iro_i18n_supported_post_types', ['post', 'page']);
}

/**
 * 语言标记用独立分类法而不是复用 category：
 * 它必须与内容分类在数据层隔离，否则「缺少语言标记的内容」会被
 * category__not_in 之类的硬排除一并滤掉，且分类下拉、面包屑、结构化数据
 * 都会混进语言项。
 */
function iro_i18n_register_taxonomy(): void
{
    register_taxonomy(IRO_I18N_LANGUAGE_TAXONOMY, iro_i18n_supported_post_types(), [
        'labels' => [
            'name'          => __('语言', 'sakurairo'),
            'singular_name' => __('语言', 'sakurairo'),
            'all_items'     => __('所有语言', 'sakurairo'),
            'edit_item'     => __('编辑语言', 'sakurairo'),
        ],
        'hierarchical'       => true,
        // 术语链接必须可用，否则 get_term_link 报错；但不开放语言归档页
        'public'             => true,
        // 列表页的语言筛选靠 WP 自身的 taxonomy 查询参数，而它要求该分类法可公开查询
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'show_in_nav_menus'  => false,
        // 列表列由 admin.php 自行渲染，交给核心会与自定义列重复
        'show_admin_column'  => false,
        'show_in_rest'       => false,
        // 必须给出查询变量名：为 false 时 WP 不解析 `iro_lang=` 参数，列表筛选会静默失效
        'query_var'          => IRO_I18N_LANGUAGE_TAXONOMY,
        // 前台不开语言归档页（`/iro_lang/en/`），语言只作为前缀与标记存在
        'rewrite'            => false,
    ]);
}

/**
 * 确保每种内置语言都有对应的术语
 *
 * 刻意补齐全部内置语言而不是只补启用项：原内容自身的语言标记不该被
 * 「此刻启用了哪几种语言」限制，否则站长换语言集时已有标记会变成悬空术语。
 * 真正受启用项约束的只有「要不要建某一语言的副本」。
 */
function iro_i18n_ensure_language_terms(): void
{
    foreach (array_keys(iro_i18n_language_definitions()) as $code) {
        $term = get_term_by('slug', $code, IRO_I18N_LANGUAGE_TAXONOMY);

        if ($term instanceof WP_Term) {
            update_term_meta($term->term_id, IRO_I18N_LANGUAGE_TERM_META, $code);
            continue;
        }

        $created = wp_insert_term(iro_i18n_language_name($code), IRO_I18N_LANGUAGE_TAXONOMY, ['slug' => $code]);

        if (!is_wp_error($created)) {
            update_term_meta((int) $created['term_id'], IRO_I18N_LANGUAGE_TERM_META, $code);
        }
    }
}

/**
 * 文章当前的语言标记；未标记的历史内容一律按默认语言看待
 */
function iro_i18n_post_language(int $post_id): string
{
    $stored = (string) get_post_meta($post_id, IRO_I18N_POST_LANG_META, true);

    if (in_array($stored, iro_i18n_languages(), true)) {
        return $stored;
    }

    // 兼容只有术语、没有 meta 的数据（例如手工用术语面板改过语言）
    $terms = get_the_terms($post_id, IRO_I18N_LANGUAGE_TAXONOMY);

    if (!is_array($terms)) {
        return iro_i18n_default_language();
    }

    foreach ($terms as $term) {
        $code = (string) get_term_meta($term->term_id, IRO_I18N_LANGUAGE_TERM_META, true);

        if ($code === '') {
            $code = $term->slug;
        }

        if (in_array($code, iro_i18n_languages(), true)) {
            return $code;
        }
    }

    return iro_i18n_default_language();
}

function iro_i18n_post_has_language(int $post_id): bool
{
    if (in_array((string) get_post_meta($post_id, IRO_I18N_POST_LANG_META, true), iro_i18n_languages(), true)) {
        return true;
    }

    $terms = get_the_terms($post_id, IRO_I18N_LANGUAGE_TAXONOMY);

    return is_array($terms) && $terms !== [];
}

function iro_i18n_set_post_language(int $post_id, string $code): void
{
    if (!in_array($code, iro_i18n_languages(), true)) {
        return;
    }

    update_post_meta($post_id, IRO_I18N_POST_LANG_META, $code);

    $term = get_term_by('slug', $code, IRO_I18N_LANGUAGE_TAXONOMY);

    if (!$term instanceof WP_Term) {
        return;
    }

    wp_set_object_terms($post_id, [(int) $term->term_id], IRO_I18N_LANGUAGE_TAXONOMY, false);
}

if (iro_i18n_enabled()) {
    add_action('init', 'iro_i18n_register_taxonomy', 9);
    // 优先级要排在 inc/theme_init/translation.php 的 set_user_locale(10) 之后：
    // 只影响术语显示名落到 MO 翻译，但顺序对了才拿得到用户语言
    add_action('init', 'iro_i18n_ensure_language_terms', 20);
}
