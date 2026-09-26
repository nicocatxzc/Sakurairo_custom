<?php
global $wp_query;

function iro_get_search_type_filters(): array
{
    // 支持的类型
    $all_types = [
        'post'     => __('文章', 'sakurairo'),
    ];

    if (iro_opt("search_for_shuoshuo")) {
        $all_types['shuoshuo'] = __('说说', 'sakurairo');
    }
    if (iro_opt('search_for_pages')) {
        $only_admins = (bool) iro_opt('search_pages_can_only_admins');
        $can_see_pages = !$only_admins || current_user_can('manage_options');

        if ($can_see_pages) {
            $all_types['page'] = __('页面', 'sakurairo');
        }
    }
    // 当前搜索关键词
    $keyword = get_search_query();

    // 当前选中的类型
    $current_raw = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
    $current = $current_raw
        ? array_filter(array_map('trim', explode(',', $current_raw)))
        : array_keys($all_types); // 空 = 全选

    $current = array_intersect($current, array_keys($all_types));

    // 生成每个类型的反选链接
    $filters = [];
    foreach ($all_types as $type => $label) {
        // 判断该类型当前是否选中
        $is_active = in_array($type, $current, true);

        if ($is_active) {
            // 移除该类型
            $new_types = array_diff($current, [$type]);
        } else {
            // 加入该类型
            $new_types = array_unique(array_merge($current, [$type]));
        }

        $new_types = array_values($new_types);
        sort($new_types);

        // 构造 URL
        $args = ['s' => $keyword];
        if (!empty($new_types)) {
            $args['post_type'] = implode(',', $new_types);
        }

        $filters[$type] = [
            'label'     => $label,
            'active'    => $is_active,
            'url'       => add_query_arg($args, home_url('/')),
            'new_types' => $new_types,
        ];
    }

    return [
        'all_types' => $all_types,
        'current'   => $current,
        'filters'   => $filters,
        'keyword'   => $keyword,
    ];
}
$search_filters = iro_get_search_type_filters();
?>
<div class="page-search">
    <?php iro_content_container_start() ?>
    <header class="search-header flex-center">
        <div class="search-box flex-center">
            <i class="fa-icon-solid fa-search search-icon"></i>

            <input
                type="text"
                class="search-input"
                placeholder="<?= __("搜索文章/标题/摘要",'sakurairo') ?>"
                @keyup.enter="gotoSearch" />

            <button
                class="search-button">
                <?= __("搜索",'sakurairo') ?>
            </button>
        </div>
    </header>
    <?php if (iro_opt('search_filter') && count($search_filters['filters']) > 1) : ?>
        <!-- 筛选器部分 -->
        <div id="filter-container">
            <div class="filter-count">
                <?= $wp_query->found_posts ?> <?= __('results found', 'sakurairo'); ?>
            </div>

            <div id="search-filter-form" action="" method="GET">
                <?php foreach ($search_filters['filters'] as $type => $filter): ?>
                    <a
                        href="<?= esc_url($filter['url']) ?>"
                        class="type-filter <?= $filter['active'] ? 'active' : '' ?>">
                        <?= esc_html($filter['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php require_once get_template_directory() . '/frontend/components/post/list.php'; ?>
    <?php iro_content_container_end() ?>
</div>