<?php
$iro_only_template = $_SERVER['HTTP_X_TEMPLATE_PART'] ?? '';
global $iro_only_template;
?>
<?php if ($iro_only_template): ?>
    <?php
    if (is_home() || is_archive() || is_author() || is_search()) {
        require_once get_theme_file_path('/frontend/components/post/list.php');
    }
    if ($iro_only_template == "comment_list") {
        // 没有文章密码且评论已开启
        if (!post_password_required() && get_post_field('comment_status', get_the_ID()) == 'open'):
            comments_template();
        endif;
    }
    if ($iro_only_template == "bangumi_list") {
        require_once get_theme_file_path('/frontend/components/block/pages/bangumi.php');
    }
    if ($iro_only_template == "bilibili_favlist") {
        require_once get_theme_file_path('/frontend/components/block/pages/bilibili_favlist.php');
    }
    if ($iro_only_template == "steam_list") {
        require_once get_theme_file_path('/frontend/components/block/pages/steam.php');
    }
    ?>
<?php else: ?>
    <!DOCTYPE html>
    <!-- 
            ◢＼　 ☆　　 ／◣
           ∕　　﹨　╰╮∕　　﹨
           ▏　　～～′′～～ 　｜
           ﹨／　　　　　　 　＼∕
           ∕ 　　●　　　 ●　＼
        ＝＝　○　∴·╰╯　∴　○　＝＝
           ╭──╮　　　　　╭──╮
  ╔═ ∪∪∪═Mashiro&Hitomi═∪∪∪═╗
-->
    <html <?php language_attributes(); ?>>

    <?php
    get_header();
    ?>

    <body>
        <!-- layout start -->
        <div class="background">
            <!-- 导航区域 -->
            <?php require_once get_theme_file_path('/frontend/components/site/progress_bar.php'); ?>
            <?php if (iro_opt('nav_style_select', 'sakura') === 'island'): ?>
                <?php require_once get_theme_file_path('/frontend/components/navbar/island.php'); ?>
            <?php else: ?>
                <?php require_once get_theme_file_path('/frontend/components/navbar/sakura.php'); ?>
            <?php endif; ?>
            <?php require_once get_theme_file_path('/frontend/components/navbar/mobile.php'); ?>

            <!-- 主页封面 -->
            <?php require_once get_theme_file_path('/frontend/components/homepage/cover.php'); ?>

            <div class="layout-slot">
                <div class="background-filter"></div>
                <?php
                // 路由分发只在这一处：页面模板自己攒页头与正文，区块化时改由页面布局组件排
                $iro_route_key = is_home() || is_front_page()
                    ? 'home'
                    : (is_single() || is_page() ? 'post' : (is_archive() ? 'archive' : ''));
                $iro_route_switches = [
                    'home'    => 'layout_block_homepage',
                    'post'    => 'layout_block_post',
                    'archive' => 'layout_block_archive',
                ];
                $GLOBALS['iro_page_route_key'] = $iro_route_key;
                // 这一页走不走区块化分支：开关打开且该路由的布局组件铺过
                $GLOBALS['iro_page_block_mode'] = isset($iro_route_switches[$iro_route_key])
                    && (bool) iro_opt($iro_route_switches[$iro_route_key], false)
                    && iro_has_page_part($iro_route_key, 'layout');
                $GLOBALS['iro_page_slot_renderer'] = function () {
                    if (is_home() || is_front_page()) {
                        require_once get_theme_file_path('/frontend/components/page/home.php');
                    } elseif (is_single() || is_page()) {
                        require_once get_theme_file_path('/frontend/components/page/post.php');
                    } elseif (is_search()) {
                        require_once get_theme_file_path('/frontend/components/page/search.php');
                    } elseif (is_author()) {
                        require_once get_theme_file_path('/frontend/components/page/author.php');
                    } elseif (is_archive()) {
                        require_once get_theme_file_path('/frontend/components/page/archive.php');
                    }
                };
                ?>
                <?php // 站级布局组件：全站底层布局，自己带 .layout-grid 与 #pjax-main 槽 
                ?>
                <?php if (iro_page_block_mode() && ($iro_site_layout = iro_render_block_part('site-layout')) !== ''): ?>
                    <?= $iro_site_layout ?>
                <?php else: ?>
                    <?php
                    // 布局几何常量在 frontend/layout.scss，这里只附加随配置变化的量
                    $layout_columns          = min(3, max(1, (int) iro_opt('layout_content_coloumns', 1)));
                    $layout_first_position   = iro_opt('layout_first_coloumn_position', 'left') === 'right' ? 'right' : 'left';
                    ?>
                    <div class="layout-grid" style="<?= iro_layout_style() ?>">
                        <?php // 左右由 DOM 顺序决定：写在 #pjax-main 之前的在左，之后的在右 
                        ?>
                        <?php if ($layout_columns >= 2 && $layout_first_position === 'left'): ?>
                            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_first.php'); ?>
                        <?php endif; ?>
                        <?php if ($layout_columns >= 3 && $layout_first_position === 'right'): ?>
                            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_second.php'); ?>
                        <?php endif; ?>
                        <!-- pjax start -->
                        <div id="pjax-main" class="pjax-main">
                            <?php
                            $GLOBALS['iro_pjax_main_open'] = true;
                            ($GLOBALS['iro_page_slot_renderer'])();
                            ?>
                        </div>
                        <!-- pjax end -->
                        <?php if ($layout_columns >= 3 && $layout_first_position === 'left'): ?>
                            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_second.php'); ?>
                        <?php endif; ?>
                        <?php if ($layout_columns >= 2 && $layout_first_position === 'right'): ?>
                            <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_first.php'); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php
                get_footer();
                require_once get_theme_file_path('/frontend/components/site/particle.php');
                ?>
            </div>

            <!-- 小组件 -->
            <?php require_once get_theme_file_path('/frontend/components/site/widget/toolbar.php'); ?>
            <!-- model start -->
            <?php if (iro_opt("nav_menu_search_switch", true)): ?>
                <?php require_once get_theme_file_path('/frontend/components/site/search_form.php'); ?>
            <?php endif; ?>
            <div id="site-model-slot" class="flex-center"></div>
            <!-- model end -->
        </div>
        <!-- layout end -->
    </body>

    </html>
<?php endif; ?>