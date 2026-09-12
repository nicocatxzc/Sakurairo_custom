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

        <!-- 内容区域 -->
        <div class="layout-slot">
            <div class="background-filter"></div>
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
                } elseif (is_archive()) {
                    require_once get_template_directory() . '/frontend/components/page/archive.php';
                } elseif (is_author()) {
                    require_once get_template_directory() . '/frontend/components/page/author.php';
                } elseif (is_search()) {
                    require_once get_template_directory() . '/frontend/components/page/search.php';
                    // } elseif (is_404()) {
                    //     require_once get_template_directory() . '/components/404.php';
                } else {
                    require_once get_template_directory() . '/frontend/components/default.php';
                }
                ?>
                <?php if (!$is_home): ?>
                </section>
            <?php endif; ?>
            <!-- content end -->
            <?php
            get_footer();
            require_once get_template_directory() . '/frontend/components/site/particle.php';
            ?>
        </div>

        <!-- 小组件 -->
        <?php require_once get_template_directory() . '/frontend/components/site/widget.php'; ?>
        <!-- <SiteModels /> -->
    </div>
    <!-- layout end -->
    <script>
        console.log(<?= json_encode(get_option('iro_options'), JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>)
    </script>
</body>

</html>