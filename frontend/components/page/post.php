<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <?php
        // 页头区：区块化时换成组件里排的页头，否则用主题原来的两套页头
        ob_start();
        if (($iro_header_part = iro_page_part_html('header')) !== '') {
            echo $iro_header_part;
        } elseif (has_post_thumbnail()) {
            require get_theme_file_path('/frontend/components/post/header_with_image.php');
        } else {
            require get_theme_file_path('/frontend/components/post/header.php');
        }
        $GLOBALS['iro_page_header_html'] = ob_get_clean();

        // 正文区：区块化时换成组件里排的正文，外壳（.page-post）不变
        ob_start();
        ?>
        <div class="page-post">
            <?php if (($iro_content_part = iro_page_part_html('content')) !== '') : ?>
                <?= $iro_content_part ?>
            <?php else : ?>
                <article class="post">
                    <?php iro_content_container_start() ?>
                    <article class="post-content">
                        <?php if (iro_i18n_enabled()): ?>
                            <?php require get_theme_file_path('/frontend/components/post/translation_notice.php'); ?>
                        <?php endif; ?>
                        <?php require get_theme_file_path('/frontend/components/post/render.php'); ?>
                    </article>
                    <?php // 仅限单栏，多栏时目录交给侧栏的「目录容器」块
                    if ((int) iro_opt("layout_content_coloumns", 1) === 1): ?>
                        <?php if ((is_single() && iro_opt("page_post_toc", true)) || (is_page() && iro_opt("page_page_toc", false))): ?>
                            <div class="toc-container toc">
                                <div id="toc"></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php iro_content_container_end() ?>
                    <?php require get_theme_file_path('/frontend/components/post/footer.php'); ?>
                </article>
                <?php require get_theme_file_path('/frontend/components/post/navigator.php'); ?>
                <?php
                // 没有文章密码且评论已开启
                if (!post_password_required() && get_post_field('comment_status', get_the_ID()) == 'open'):
                    iro_content_container_start();
                    comments_template();
                    iro_content_container_end();
                endif; ?>
            <?php endif; ?>
        </div>
        <?php
        $GLOBALS['iro_page_body_html'] = ob_get_clean();

        // 区块化分支：页面布局组件接管排版，页头槽与正文槽取上面两个缓冲
        if (iro_page_block_mode()) {
            echo iro_render_block_part('page-' . iro_page_route_key() . '-layout');
        } else {
            echo $GLOBALS['iro_page_header_html'] . $GLOBALS['iro_page_body_html'];
        }
        ?>
<?php endwhile;
endif; ?>
