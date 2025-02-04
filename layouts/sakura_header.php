<?php
//Sakura样式导航栏
?>

<header class="site-header no-select" role="banner">
	<?php
        // Logo Section - Only process if logo or text is configured
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

		<?php header_user_menu();
		if (iro_opt('nav_menu_search') == '1') { ?>
			<div class="searchbox js-toggle-search"><i class="fa-solid fa-magnifying-glass"></i></div>
		<?php } ?>
		<?php $nav_menu_display = iro_opt('nav_menu_display'); //菜单是否展开 ?>
		<div class="menu-wrapper"> <!-- 菜单容器 -->
			<style>
				.site-top ul {
					justify-content: <?php echo iro_opt('nav_menu_distribution'); ?>;
				}

				.site-top .lower nav {
					<?php if ($nav_menu_display == 'fold') {
							echo "width: 92%;";
						}else{
							echo "width: 100%;";
						};?>
				}
				.site-top ul li {
					margin: 0 <?php echo iro_opt('menu_option_spacing'); ?>px;
				}
			</style>
		<div class="lower">
			<?php if ($nav_menu_display == 'fold') { ?>
				<div id="show-nav" class="showNav">
					<div class="line line1"></div>
					<div class="line line2"></div>
					<div class="line line3"></div>
				</div>
			<?php } ?>
			<?php wp_nav_menu(['depth' => 2, 'theme_location' => 'primary', 'container' => 'nav']); ?>
		</div>
	</div><!-- .menu-wrapper -->
	</div>
</header><!-- #masthead -->