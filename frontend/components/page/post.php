<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <?php
        if (has_post_thumbnail()) {
            require get_theme_file_path('/frontend/components/post/header_with_image.php');
        } else {
            require get_theme_file_path('/frontend/components/post/header.php');
        }
        ?>
        <div class="page-post">
            <article class="post">
                <?php iro_content_container_start() ?>
                <article class="post-content">
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
        </div>
<?php endwhile;
endif; ?>