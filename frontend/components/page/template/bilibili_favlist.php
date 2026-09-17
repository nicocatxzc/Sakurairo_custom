<?php
global $iro_only_template;

// 获取所有分类
$favlist_request  = new WP_REST_Request('GET', '/sakura/v1/favlist/all');
$favlist_response = rest_do_request($favlist_request);

$favlist_categories = [];
if (!$favlist_response->is_error()) {
    $favlist_categories = $favlist_response->get_data()['list'] ?? [];
}

// 当前分类
$current_category = isset($_GET['category']) ? (int) $_GET['category'] : 0;
if (!$current_category && !empty($favlist_categories)) {
    $current_category = (int) $favlist_categories[0]['id'];
}

// 当前页码
$current_page = isset($_GET['location']) ? max(1, (int) $_GET['location']) : 1;

// 获取当前分类详情
$favlist_items      = [];
$favlist_pagination = [
    'current_page' => $current_page,
    'total_pages'  => 0,
    'total_items'  => 0,
    'per_page'     => 20,
];

if ($current_category) {
    $detail_request = new WP_REST_Request('GET', '/sakura/v1/favlist/detail');
    $detail_request->set_query_params([
        'favId' => $current_category,
        'page'  => $current_page,
    ]);
    $detail_response = rest_do_request($detail_request);

    if (!$detail_response->is_error()) {
        $detail_data        = $detail_response->get_data();
        $favlist_items      = $detail_data['medias'] ?? [];
        $favlist_pagination = $detail_data['pagination'] ?? $favlist_pagination;
    }
}
?>

<script>
    console.log(<?= json_encode($favlist_categories) ?>)
    console.log(<?= json_encode($favlist_items) ?>)
    console.log(<?= json_encode($favlist_pagination) ?>)
</script>
<?php if (!$iro_only_template): ?>
    <div class="page-favlist flex-center">
    <?php endif; ?>
    <!-- 分类切换 -->
    <div class="categories flex-center">
        <?php foreach ($favlist_categories as $category): ?>
            <?php
            $is_active    = (int) $category['id'] == $current_category;
            $category_url = add_query_arg([
                'category' => $category['id'],
                'location' => 1, // 切换分类时回到第 1 页
            ]);
            ?>
            <button
                href="<?= esc_url($category_url) ?>"
                class="category <?= $is_active ? 'active' : '' ?>"
                data-id="<?= esc_attr($category['id']) ?>">
                <?= esc_html($category['title']) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="fav-content">
        <?php foreach ($favlist_items as $item): ?>
            <div
                class="fav-item">
                <a
                    href="`https://www.bilibili.com/video/<?= $item["bvid"] ?>`"
                    target="_blank"
                    rel="noopener noreferrer">
                    <div class="cover">
                        <img
                            class="cover-img"
                            src="<?= $item["cover"] ?>"
                            referrerpolicy="no-referrer"
                            loading="lazy"
                            alt="<?= $item["title"] ?>" />
                        <div class="cover-title">
                            <h3 class="title" title="<?= $item["title"] ?>">
                                <?= $item["title"] ?>
                            </h3>
                        </div>
                        <div class="cover-upper">
                            <span class="name">UP: <?= $item["upper"]["name"] ?></span>
                        </div>
                        <div class="cover-play flex-center">
                            <div class="btn flex-center">
                            </div>
                        </div>
                    </div>
                    <div class="fav-desc">
                        <span class="desc" :title="item.intro">
                            <?= $item["intro"] ?? "暂无简介" ?>
                        </span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (($favlist_pagination['total_pages'] ?? 0) > 1): ?>
        <div class="site-pagination flex-center">
            <div class="nav-links">
                <?= paginate_links([
                    'format'    => '?location=%#%',
                    'current'   => $favlist_pagination['current_page'],
                    'total'     => $favlist_pagination['total_pages'],
                    'mid_size'  => 2,
                    'end_size'  => 2,
                    'prev_text' => '<',
                    'next_text' => '>',
                    'type'      => 'plain',
                    'add_args'  => ['category' => $current_category],
                ]); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!$iro_only_template): ?>
    </div>
<?php endif; ?>