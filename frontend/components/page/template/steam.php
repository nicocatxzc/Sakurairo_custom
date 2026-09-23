<?php
global $iro_only_template;

$steam_request = new WP_REST_Request('GET', '/sakura/v1/steam');
$steam_request->set_query_params([
    'page' => max(1, (int) ($_GET['location'] ?? 1)),
]);
$steam_response = rest_do_request($steam_request);
$steam_result   = $steam_response->is_error() ? [] : $steam_response->get_data();

$steam_data       = $steam_result['data'] ?? [];
$steam_pagination = $steam_result['pagination'] ?? [];
?>
<?php if (!$iro_only_template): ?>
    <div class="page-steam flex-center">
        <div class="steam-list">
        <?php endif; ?>
        <?php if (empty($steam_data)): ?>
            <div class="steam-empty">
                <?= esc_html($steam_result['message'] ?? __('暂无游戏数据', 'sakurairo')) ?>
            </div>
        <?php endif; ?>
        <?php foreach ($steam_data as $game): ?>
            <a
                class="steam-card"
                href="<?= esc_url($game['url']) ?>"
                target="_blank"
                rel="nofollow noopener noreferrer">
                <div class="steam-card-image">
                    <img
                        src="<?= esc_url($game['images']) ?>"
                        alt="<?= esc_attr($game['name']) ?>"
                        referrerpolicy="no-referrer"
                        loading="lazy" />
                    <div class="steam-title-overlay">
                        <h3 class="steam-title" title="<?= esc_attr($game['name']) ?>">
                            <?= esc_html($game['name']) ?>
                        </h3>
                    </div>
                </div>
                <div class="steam-info">
                    <div class="steam-stat">
                        <i class="fa-icon-solid fa-gamepad"></i>
                        <span><?= esc_html($game['playtime']) ?></span>
                    </div>
                    <?php if ($game['last_played']): ?>
                        <div class="steam-stat">
                            <i class="fa-icon-regular fa-clock"></i>
                            <span><?= esc_html($game['last_played']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (($steam_pagination['total_pages'] ?? 0) > 1): ?>
            <div class="site-pagination flex-center">
                <div class="nav-links">
                    <?= paginate_links([
                        'format'    => '?location=%#%',
                        'current'   => $steam_pagination['current_page'],
                        'total'     => $steam_pagination['total_pages'],
                        'mid_size'  => 2,
                        'end_size'  => 2,
                        'prev_text' => '<',
                        'next_text' => '>',
                        'type'      => 'plain',
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!$iro_only_template): ?>
        </div>
    </div>
<?php endif; ?>

