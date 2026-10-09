<?php

/**
 * 语言隔离：非当前语言的文章版本不进前台查询
 *
 * 用「排除其它语言」而不是「只取当前语言」：后者会把没有任何语言标记的历史内容
 * 一并排除掉，而语言判定一直把「没有标记」当作站点默认语言，两者必须一致。
 *
 * 排除由 `iro_i18n_language_tax_clause()` 统一给出，它也把「未标记」在非默认语言下
 * 一并挡掉——未标记内容属于默认语言，不该在别的语言归档里出现。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 某个语言的收窄条件：看不见的语言标记，以及（非默认语言下的）完全没有标记的内容
 *
 * 「没有标记」在语言判定里等同于默认语言，所以查询侧也必须照此排除，
 * 否则未标记的历史内容会在每一种语言的归档里各出现一次——列表看起来
 * 像没做隔离，实际是它同时属于所有语言。
 *
 * @return array<int|string,mixed>
 */
function iro_i18n_language_tax_clause(string $language): array
{
    $excluded = [];

    foreach (iro_i18n_languages() as $code) {
        if ($code === $language) {
            continue;
        }

        $term = get_term_by('slug', $code, IRO_I18N_LANGUAGE_TAXONOMY);

        if ($term instanceof WP_Term) {
            $excluded[] = (int) $term->term_id;
        }
    }

    if ($excluded === []) {
        return [];
    }

    $clause = [
        'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
        'field'    => 'term_id',
        'terms'    => $excluded,
        'operator' => 'NOT IN',
    ];

    if (iro_i18n_is_default_language($language)) {
        return $clause;
    }

    return [
        'relation' => 'AND',
        $clause,
        [
            'taxonomy' => IRO_I18N_LANGUAGE_TAXONOMY,
            'operator' => 'EXISTS',
        ],
    ];
}

/**
 * 当前语言的收窄条件
 *
 * @return array<int|string,mixed>
 */
function iro_i18n_current_language_tax_clause(): array
{
    return iro_i18n_language_tax_clause(iro_i18n_current_language());
}

/**
 * 判断一次查询是否需要按语言隔离
 *
 * 只认主查询与显式打了标的查询，而不是按 `is_home()` 之类的上下文条件去推断：
 * - 上下文条件是由主查询设好的全局状态，任何一次二次查询（相关文章、推荐位）
 *   都会跟着主查询的上下文一起为真，于是那些本该跨语言的查询会被一并过滤掉；
 * - 主题里有大量 `WP_Query` 直接驱动局部渲染的写法，把它们全部纳入反而更容易出错。
 * 需要纳入的自定义查询显式加上 `iro_i18n_query` 参数即可。
 *
 * 用 `$wp_the_query` 做对象同一性判断是核心 `WP_Query::is_main_query()` 的做法；
 * 钩子回调里不要用全局函数 `is_main_query()`，那比的是 `$wp_query`。
 */
function iro_i18n_query_needs_language(WP_Query $query): bool
{
    global $wp_the_query;

    if (!iro_i18n_is_frontend()) {
        return false;
    }

    if ($wp_the_query instanceof WP_Query && $query === $wp_the_query) {
        return true;
    }

    return (bool) $query->get('iro_i18n_query');
}

function iro_i18n_filter_frontend_query(WP_Query $query): void
{
    if (!iro_i18n_enabled() || !iro_i18n_query_needs_language($query)) {
        return;
    }

    $clause = iro_i18n_current_language_tax_clause();

    if ($clause !== []) {
        $tax_query = (array) $query->get('tax_query');

        // 已经按语言限定过的查询不再叠加（例如主题自身的推荐位查询）
        foreach ($tax_query as $existing) {
            if (is_array($existing) && ($existing['taxonomy'] ?? '') === IRO_I18N_LANGUAGE_TAXONOMY) {
                return;
            }
        }

        $tax_query[] = $clause;

        $query->set('tax_query', $tax_query);
    }

    // 未翻译副本即使是发布状态也不该出现在列表里
    $meta_query = (array) $query->get('meta_query');

    foreach ($meta_query as $existing) {
        if (is_array($existing) && ($existing['key'] ?? '') === IRO_I18N_SKELETON_META) {
            return;
        }
    }

    $meta_query[] = [
        'key'     => IRO_I18N_SKELETON_META,
        'compare' => 'NOT EXISTS',
    ];

    $query->set('meta_query', $meta_query);
}

// 优先级靠后：主题自身的 main query 定制先跑完，这里只做最后一道收口
add_action('pre_get_posts', 'iro_i18n_filter_frontend_query', 99);
