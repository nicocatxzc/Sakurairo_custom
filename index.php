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
    <?php // 路由在 frontend/route.php：它负责站级布局与页面布局的解析和渲染 ?>
    <?php require_once get_theme_file_path('/frontend/route.php'); ?>
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
                <?php iro_render_page(); // 路由区域?>
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