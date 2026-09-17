<?php
if (!function_exists('iro_get_friend_links')) {
    function iro_get_friend_links(): array
    {
        // 排序配置
        $sorting = iro_opt('friend_link_sorting_mode', 'name');
        $order   = iro_opt('friend_link_order', 'ASC');

        $query_args = [
            'orderby' => $sorting,
        ];
        // 随机排序时 order 参数无效，跳过
        if ($sorting !== 'rand') {
            $query_args['order'] = $order;
        }

        // 获取所有链接
        $links = get_bookmarks($query_args);
        if (empty($links)) {
            return [];
        }

        // 获取所有分类
        $categories = get_terms([
            'taxonomy'   => 'link_category',
            'hide_empty' => false,
            'meta_key'   => 'term_priority',
            'orderby'    => 'meta_value_num',
            'order'      => 'DESC',
        ]);

        $category_map = [];
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $cat) {
                $category_map[$cat->term_id] = $cat->name;
            }
        }

        // 按分类分组
        $result = [];
        $uncategorized = [];

        foreach ($links as $link) {
            // 获取该链接所属的所有分类
            $terms = wp_get_object_terms($link->link_id, 'link_category');

            if (is_wp_error($terms) || empty($terms)) {
                // 未分类
                $uncategorized[] = $link;
                continue;
            }

            foreach ($terms as $term) {
                $name = $term->name;
                if (!isset($result[$name])) {
                    $result[$name] = [];
                }
                $result[$name][] = $link;
            }
        }

        // 按分类优先级排序
        if (!is_wp_error($categories) && !empty($categories)) {
            $priority_sorted = [];
            foreach ($categories as $cat) {
                if (isset($result[$cat->name])) {
                    $priority_sorted[$cat->name] = $result[$cat->name];
                }
            }
            // 保留未在分类列表中的
            foreach ($result as $name => $items) {
                if (!isset($priority_sorted[$name])) {
                    $priority_sorted[$name] = $items;
                }
            }
            $result = $priority_sorted;
        }

        if (!empty($uncategorized)) {
            $result[__('未分类', 'sakurairo')] = $uncategorized;
        }

        return $result;
    }
}
$iro_friend_links = json_decode(json_encode(iro_get_friend_links()), true);
?>

<script>
    console.log(<?= json_encode(iro_get_friend_links()) ?>)
</script>
<div class="page-links">
    <ol class="categories">
        <?php foreach ($iro_friend_links as $category_name => $links): ?>
            <h3 id="<?= $category_name ?>" class="category-title">
                <?= $category_name ?>
            </h3>
            <div
                class="category">
                <?php foreach ($links as $link): ?>
                    <div
                        class="link">
                        <a
                            href="<?= $link["link_url"] ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="link-content flex-center">
                            <img
                                loading="lazy"
                                class="avatar"
                                src="<?= $link["link_image"] ?>"
                                onerror="_iro.utils.missAvatar(this)"
                                alt="" />

                            <p class="name"><?= $link["link_name"] ?></p>
                            <p class="desc"><?= $link["link_description"] ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </ol>
</div>