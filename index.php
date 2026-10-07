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
        require_once get_theme_file_path('/frontend/components/page/template/bangumi.php');
    }
    if ($iro_only_template == "bilibili_favlist") {
        require_once get_theme_file_path('/frontend/components/page/template/bilibili_favlist.php');
    }
    if ($iro_only_template == "steam_list") {
        require_once get_theme_file_path('/frontend/components/page/template/steam.php');
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
                // 布局几何常量在 frontend/layout.scss，这里只附加随配置变化的量
                $layout_columns          = min(3, max(1, (int) iro_opt('layout_content_coloumns', 1)));
                $layout_first_position   = iro_opt('layout_first_coloumn_position', 'left') === 'right' ? 'right' : 'left';
                // 固定导航栏会盖住页面上沿，侧栏吸顶位置要在它下方
                $layout_nav_height       = iro_opt('nav_style_select', 'sakura') === 'island' ? '70px' : '3.75rem';
                ?>
                <div class="layout-grid cols-<?= $layout_columns ?> first-<?= $layout_first_position ?>"
                    style="--layout-sticky-top: calc(<?= $layout_nav_height ?> + 0.75rem);">
                    <!-- pjax start -->
                    <div id="pjax-main" class="pjax-main">
                        <?php
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
                            // } elseif (is_404()) {
                            //     require_once get_theme_file_path('/components/404.php');
                        } else {
                            require_once get_theme_file_path('/frontend/components/default.php');
                        }
                        ?>
                    </div>
                    <!-- pjax end -->
                    <?php if ($layout_columns >= 2): ?>
                        <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_first.php'); ?>
                    <?php endif; ?>
                    <?php if ($layout_columns >= 3): ?>
                        <?php require_once get_theme_file_path('/frontend/components/site/widget/side_bar_second.php'); ?>
                    <?php endif; ?>
                </div>
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