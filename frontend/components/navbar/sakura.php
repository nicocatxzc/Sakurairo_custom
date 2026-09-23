<?php
$iro_menu_options = iro_get_navigation();
?>

<header
    class="site-header sakura flex-center">
    <div class="site-branding flex-center">
        <img src="<?= iro_media_optimize_image_url(iro_opt("nav_logo")) ?>" class="nuxtpic logo" alt="site logo">
        <a href="/">
            <span
                class="site-title"
                style=" font-family: <?= iro_opt("nav_title_font") ?> ">
                <?= iro_opt("nav_title") ?>
            </span>
        </a>
    </div>

    <div class="menu-wrapper">
        <nav>
            <ul
                class="menu"
                style="
                        justify-content: <?= iro_opt("navbar_distribution", "right") ?>;
                        font-family: <?= iro_opt("nav_option_font") ?>,
                    ">
                <?php foreach ($iro_menu_options as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <li style="margin: 0 <?= iro_opt("navbar_option_margin", 0.3) ?>rem;">
                            <a href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                            <ul class="sub-menu" style="border-radius: <?= iro_opt("nav_menu_cover_radius", 0.6) ?>;">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="flex-center">
                                        <a href="<?= esc_url($child['url']) ?>"><?= esc_html($child['title']) ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
    <?php if (iro_opt("nav_menu_search_switch", true)): ?>
        <div
            class="button search flex-center">
            <i class="fa-icon-solid fa-search icon"></i>
        </div>
    <?php endif; ?>
    <?php if (iro_opt('nav_user_menu', true)): ?>
        <div class="user">
            <?php if (is_user_logged_in()):
                $current_user = wp_get_current_user();
                $avatar = get_avatar($current_user->ID, 40, '', '用户头像', ['class' => 'avatar']);
            ?>
                <div class="avatar-wrapper">
                    <?= $avatar ?>
                </div>
                <div class="user-menu">
                    <div class="user-menu-info">
                        <span class="name"><?= esc_html($current_user->display_name) ?></span>
                    </div>
                    <div class="user-menu-option">
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
                </div>
            <?php else: ?>
                    <img
                        src="<?= iro_media_optimize_image_url(iro_opt("missing_avatars_placeholder")) ?>"
                        alt="用户头像"
                        class="nuxtpic avatar"
                    />
                <div class="user-menu">
                    <div class="user-menu-info">
                        <span class="name">游客</span>
                    </div>
                    <div class="user-menu-option">
                        <a href="<?= esc_url(wp_login_url()) ?>" aria-label="点击登录">登录</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</header>