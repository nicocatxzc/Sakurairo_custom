<?php
$iro_menu_options = iro_get_navigation();

if (!is_array($iro_menu_options)) {
    $iro_menu_options = [];
}
?>

<header class="site-header mobile flex-center">
    <button
        type="button"
        class="menu-toggle flex-center"
        data-panel-toggle="menu"
        aria-expanded="false"
        aria-label="打开菜单">
        <i class="fa-solid fa-bars icon"></i>
    </button>

    <div class="site-branding flex-center">
        <img src="<?= iro_media_optimize_image_url(iro_opt("nav_logo")) ?>" class="nuxtpic" alt="site logo">
        <a href="<?= esc_url(home_url('/')) ?>">
            <span
                class="site-title"
                style="font-family: <?= iro_opt("nav_title_font") ?>">
                <?= iro_opt("nav_title") ?>
            </span>
        </a>
    </div>

    <?php if (iro_opt('nav_user_menu', true)): ?>
        <button
            type="button"
            class="user-toggle flex-center"
            data-panel-toggle="user"
            aria-expanded="false"
            aria-label="打开用户菜单">
            <i class="fa-regular fa-bookmark icon"></i>
        </button>
    <?php endif; ?>

    <nav class="menu-wrapper" data-panel="menu">
        <?php if (iro_opt("nav_menu_search_switch", true)): ?>
            <div class="search-form flex-center">
                <i class="fa-solid fa-search icon"></i>
                <input
                    class="search-input"
                    type="text"
                    inputmode="search"
                    autocomplete="off"
                    placeholder="想找点什么呢?">
            </div>
        <?php endif; ?>
        <ul
            class="menu"
            style="font-family: <?= iro_opt("nav_option_font") ?>">
            <?php foreach ($iro_menu_options as $item): ?>
                <?php $has_children = !empty($item['children']); ?>
                <li class="item">
                    <div class="item-head">
                        <a class="link" href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                        <?php if ($has_children): ?>
                            <i class="fa-solid fa-angle-right button" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <?php if ($has_children): ?>
                        <ul class="sub-menu">
                            <?php foreach ($item['children'] as $child): ?>
                                <li>
                                    <a href="<?= esc_url($child['url']) ?>"><?= esc_html($child['title']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if (iro_opt('nav_user_menu', true)): ?>
        <div class="user-wrapper" data-panel="user">
            <div class="user-menu-container">
                <div class="user-menu flex-center">
                    <?php if (is_user_logged_in()):
                        $current_user = wp_get_current_user();
                    ?>
                        <?= get_avatar($current_user->ID, 80, '', '用户头像', ['class' => 'avatar']) ?>
                        <div class="user-info">
                            <span class="name"><?= esc_html($current_user->display_name) ?></span>
                        </div>
                    <?php else: ?>
                        <img
                            src="<?= iro_media_optimize_image_url(iro_opt("missing_avatars_placeholder")) ?>"
                            alt="用户头像"
                            class="avatar">
                        <div class="user-info">
                            <span class="name">游客</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (is_user_logged_in()): ?>
                    <div class="user-option">
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?= esc_url(admin_url()) ?>" target="_blank">管理后台</a>
                        <?php endif; ?>
                        <?php if (current_user_can('administrator')): ?>
                            <a href="<?= esc_url(admin_url('customize.php')) ?>" target="_blank">主题设置</a>
                        <?php endif; ?>
                        <?php if (current_user_can('edit_posts')): ?>
                            <a href="<?= esc_url(admin_url('post-new.php')) ?>" target="_blank">撰写文章</a>
                        <?php endif; ?>
                        <a href="<?= esc_url(wp_logout_url(home_url())) ?>" target="_top">退出登录</a>
                    </div>
                <?php else: ?>
                    <div class="visitor-option flex-center">
                        <a href="<?= esc_url(wp_login_url()) ?>" aria-label="点击登录">登录</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</header>
