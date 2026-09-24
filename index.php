<?php
$iro_only_template = $_SERVER['HTTP_X_TEMPLATE_PART'] ?? '';
global $iro_only_template;
?>
<?php if ($iro_only_template): ?>
    <?php
    if (is_home() || is_archive() || is_author() || is_search()) {
        require_once get_template_directory() . '/frontend/components/post/list.php';
    }
    if ($iro_only_template == "comment_list") {
        // 没有文章密码且评论已开启
        if (!post_password_required() && get_post_field('comment_status', get_the_ID()) == 'open'):
            comments_template();
        endif;
    }
    if ($iro_only_template == "bangumi_list") {
        require_once get_template_directory() . '/frontend/components/page/template/bangumi.php';
    }
    if ($iro_only_template == "bilibili_favlist") {
        require_once get_template_directory() . '/frontend/components/page/template/bilibili_favlist.php';
    }
    if ($iro_only_template == "steam_list") {
        require_once get_template_directory() . '/frontend/components/page/template/steam.php';
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
            <?php require_once get_template_directory() . '/frontend/components/site/progress_bar.php'; ?>
            <?php require_once get_template_directory() . '/frontend/components/navbar/sakura.php'; ?>
            <?php require_once get_template_directory() . '/frontend/components/navbar/mobile.php'; ?>

            <!-- 主页封面 -->
            <?php require_once get_template_directory() . '/frontend/components/homepage/cover.php'; ?>

            <div class="layout-slot">
                <div class="background-filter"></div>
                <!-- pjax start -->
                <div id="pjax-main">
                    <?php $is_home = is_home() || is_front_page() ?>
                    <!-- content start -->
                    <?php if (!$is_home): ?>
                        <section class="main-container">
                        <?php endif; ?>
                        <?php
                        if ($is_home) {
                            require_once get_template_directory() . '/frontend/components/page/home.php';
                        } elseif (is_single() || is_page()) {
                            require_once get_template_directory() . '/frontend/components/page/post.php';
                        } elseif (is_search()) {
                            require_once get_template_directory() . '/frontend/components/page/search.php';
                        } elseif (is_author()) {
                            require_once get_template_directory() . '/frontend/components/page/author.php';
                        } elseif (is_archive()) {
                            require_once get_template_directory() . '/frontend/components/page/archive.php';
                            // } elseif (is_404()) {
                            //     require_once get_template_directory() . '/components/404.php';
                        } else {
                            require_once get_template_directory() . '/frontend/components/default.php';
                        }
                        ?>
                        <?php if (!$is_home): ?>
                        </section>
                        <!-- content end -->
                    <?php endif; ?>
                </div>
                <!-- pjax end -->
                <?php
                get_footer();
                require_once get_template_directory() . '/frontend/components/site/particle.php';
                ?>
            </div>

            <!-- 小组件 -->
            <?php require_once get_template_directory() . '/frontend/components/site/widget.php'; ?>
            <!-- model start -->
            <?php if (iro_opt("nav_menu_search_switch", true)): ?>
                <?php require_once get_template_directory() . '/frontend/components/site/search_form.php'; ?>
            <?php endif; ?>
            <div id="site-model-slot" class="flex-center"></div>
            <!-- model end -->
        </div>
        <!-- layout end -->
    </body>

    </html>
<?php endif; ?>