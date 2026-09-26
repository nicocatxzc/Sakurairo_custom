<?php
global $iro_only_template;
$bangumi_request = new WP_REST_Request('GET', '/sakura/v1/bangumi/' . iro_opt("bangumi_source"));
$bangumi_request->set_query_params(
    [
        "page" => isset($_GET["location"]) ? $_GET["location"] : 1
    ]
);
$bangumi_response = rest_do_request($bangumi_request);

if ($bangumi_response->is_error()) {
    $bangumi_data = [];
}

$bangumi_data = $bangumi_response->get_data()["data"];
$bangumi_pagination = $bangumi_response->get_data()["pagination"];
?>
<?php if (!$iro_only_template): ?>
    <div class="page-bangumi flex-center">
        <ol class="anime-list">
        <?php endif; ?>
        <?php foreach ($bangumi_data as $item): ?>
            <li
                data-detail="<?= hachimi_encode_data([
                                    "name_cn" => $item["name_cn"],
                                    "tags" => $item["tags"],
                                    "url" => $item["url"],
                                    "date"=>$item["date"]
                                ]) ?>"
                class="anime-item">
                <div
                    class="anime-content">
                    <img
                        class="anime-image"
                        alt="<?= $item["name"] ?>"
                        src="<?= $item["images"] ?>"
                        referrerpolicy="no-referrer"
                        loading="lazy" />
                    <div class="anime-info">
                        <h3 class="anime-title">
                            <?= $item["name"] ?>
                        </h3>
                        <div class="anime-publish-date">
                            <?= __("上映日期：","sakurairo") ?><?= $item["date"] ?>
                        </div>
                        <?php if ($item["progress"] != ""): ?>
                            <div class="bangumi-status">
                                <p class="status-desc"> <?= __("观看进度：","sakurairo") ?></p>
                                <div class="progress"
                                    style="--progress:<?= $item["progress"] ?>%;">
                                    <div class="progress-bar" data-progress="<?= esc_attr($item['progress']) ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="anime-summary">
                            <?= $item["summary"] ?>
                        </div>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if ($bangumi_pagination["total_pages"] > 1): ?>
            <div class="site-pagination flex-center">
                <div class="nav-links">
                    <?= paginate_links(array(
                        'format'    => '?location=%#%',
                        'current'   => $bangumi_pagination["current_page"],
                        'total'     => $bangumi_pagination["total_pages"],
                        'mid_size'  => 2,
                        'end_size'  => 2,
                        'prev_text' => '<',
                        'next_text' => '>',
                        'type'      => 'plain',
                    )); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!$iro_only_template): ?>
        </ol>
        <div class="bangumi-detail"></div>
    </div>
<?php endif; ?>