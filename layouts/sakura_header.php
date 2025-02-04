<?php
//Sakura样式导航栏
?>
<style>
.site-header {
    width: 100%;
    height: 75px;
	display: flex;
	justify-content: left;
    top: 0;
    left: 0;
    background: 0 0;
    -webkit-transition: all .4s ease;
    transition: all .4s ease;
    position: fixed;
    z-index: 999;
    border-radius: 0px;
}
.site-branding{
	border-radius: 0px;
	background: rgba(0, 0, 0, 0);
	border: 0px;
}
.menu-wrapper{
	width: 100%;
}
.site-branding {
  height: 75px;
  line-height: 75px;

}
.site-title img {
  margin-top: 17px;
}
.site-top .lower {
  margin: 15px 0 0 0;
}
.lower li ul {
  top: 46px;
  right: -24px;
}
.menu-wrapper .menu {
	display: flex;
	justify-content: <?php echo iro_opt('nav_menu_distribution'); ?>;
}

.site-top .lower nav {
<?php if ($nav_menu_display == 'fold') {
	echo "width: 92%;";
}else{
	echo "width: 100%;";
};?>
}
nav ul li {
	margin: 0 <?php echo iro_opt('menu_option_spacing'); ?>px;
}
.header-user-menu {
  right: -11px;
  top: 44px;
}
.searchbox.js-toggle-search{
  margin: 17px 0;
  margin-left: 15px;
}
@media (max-width:860px) {
.site-header {
  height: 60px;
}
}
</style>
<header class="site-header no-select" role="banner">
	<!-- Logo Start -->
	<?php
	$nav_text_logo = iro_opt('nav_text_logo');
        if (iro_opt('iro_logo') || !empty($nav_text_logo['text'])): ?>
            <div class="site-branding">
                <a href="<?= esc_url(home_url('/')); ?>">
                    <?php if (iro_opt('iro_logo')): ?>
                        <div class="site-title-logo">
                            <img alt="<?= esc_attr(get_bloginfo('name')); ?>"
                                src="<?= esc_url(iro_opt('iro_logo')); ?>"
                                width="auto" height="auto"
                                loading="lazy"
                                decoding="async">
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($nav_text_logo['text'])): ?>
                        <div class="site-title">
                            <?= esc_html($nav_text_logo['text']); ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        <?php endif;?>
		<!-- LOGO部分 -->
		<!-- 菜单开始 -->
		<div class="menu-wrapper"> <!-- 菜单容器 -->
			<?php $nav_menu_display = iro_opt('nav_menu_display'); //菜单是否展开 ?>
			<?php if ($nav_menu_display == 'fold') { ?>
				<div id="show-nav" class="showNav">
					<div class="line line1"></div>
					<div class="line line2"></div>
					<div class="line line3"></div>
				</div>
			<?php } ?>
				<?php wp_nav_menu(['depth' => 2, 'theme_location' => 'primary', 'container' => 'nav', 'container_class' => 'sakura_nav']); ?>
		</div>
		<!-- 菜单结束 -->
		<?php
		if (iro_opt('nav_menu_search') == '1') { ?>
			<div class="searchbox js-toggle-search"><i class="fa-solid fa-magnifying-glass"></i></div>
		<?php } ?>
		
		<?php header_user_menu(); ?>
	</div>
</header>