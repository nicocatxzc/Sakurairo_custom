<?php
$iro_menu_options = iro_get_navigation();

if (!is_array($iro_menu_options)) {
    $iro_menu_options = [];
}
?>

<header class="site-header island flex-center<?= is_home() ? ' is-home' : '' ?>">
    <div class="site-branding flex-center">
        <a href="<?= esc_url(home_url('/')) ?>">
            <img src="<?= iro_media_optimize_image_url(iro_opt("nav_logo"), ["height" => 96]) ?>" class="nuxtpic logo" alt="site logo">
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
                style="font-family: <?= iro_opt("nav_option_font") ?>;">
                <?php foreach ($iro_menu_options as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <li>
                            <a href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                            <ul class="sub-menu">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
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
        <?php // 文章页滚到标题之上时顶替菜单行，开关与动画见 island.scss / island.js。
        // 位置始终渲染：pjax 不重渲染导航栏，从首页切进文章时也得有这个地方，文字由 island.js 补 
        ?>
        <span class="nav-article-title"><?= is_singular() ? esc_html(get_the_title(get_queried_object_id())) : '' ?></span>
        <?php // 搜索与骰子始终渲染：两种状态之间切换的是类，不重建 DOM 
        ?>
        <?php if (iro_opt("nav_menu_search_switch", true)): ?>
            <div class="divider"></div>
            <div class="button search flex-center">
                <i class="fa-icon-solid fa-search icon"></i>
            </div>
        <?php endif; ?>
        <?php if (iro_opt("nav_menu_cover_switch", true)): ?>
            <div
                class="button cover-toggle flex-center"
                title="<?= __("切换封面", 'sakurairo') ?>">
                <i class="fa-icon-solid fa-dice icon"></i>
            </div>
        <?php endif; ?>
    </div>

    <?php if (iro_opt('nav_user_menu', true)): ?>
        <div class="user">
            <div class="avatar-wrapper">
                <?php if (is_user_logged_in()):
                    $current_user = wp_get_current_user();
                ?>
                    <?= get_avatar($current_user->ID, 80, '', __("用户头像", "sakurairo"), ['class' => 'avatar']) ?>
                <?php else: ?>
                    <img
                        src="<?= iro_media_optimize_image_url(iro_opt("missing_avatars_placeholder")) ?>"
                        alt="<?= __("用户头像", 'sakurairo') ?>"
                        class="nuxtpic avatar">
                <?php endif; ?>
                <div class="user-menu">
                    <div class="user-menu-info">
                        <span class="name"><?= is_user_logged_in() ? esc_html($current_user->display_name) : __("游客", 'sakurairo') ?></span>
                    </div>
                    <div class="user-menu-option">
                        <?php if (is_user_logged_in()): ?>
                            <?php if (current_user_can('manage_options')): ?>
                                <a href="<?= esc_url(admin_url()) ?>" target="_blank"><?= __("管理后台", 'sakurairo') ?></a>
                            <?php endif; ?>
                            <?php if (current_user_can('administrator')): ?>
                                <a href="<?= esc_url(admin_url('customize.php')) ?>" target="_blank"><?= __("主题设置", 'sakurairo') ?></a>
                            <?php endif; ?>
                            <?php if (current_user_can('edit_posts')): ?>
                                <a href="<?= esc_url(admin_url('post-new.php')) ?>" target="_blank"><?= __("撰写文章", 'sakurairo') ?></a>
                            <?php endif; ?>
                            <a href="<?= esc_url(wp_logout_url(home_url())) ?>" target="_top"><?= __("退出登录", 'sakurairo') ?></a>
                        <?php else: ?>
                            <a href="<?= esc_url(wp_login_url()) ?>" aria-label="<?= __("点击登录", 'sakurairo') ?>"><?= __("登录", 'sakurairo') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</header>