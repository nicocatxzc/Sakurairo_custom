<?php
// 文章与页面：页头按有没有特色图分两套，正文是文章本体
if (have_posts()) {
    the_post();
}

function iro_render_page_header(): void
{
    require get_theme_file_path(
        has_post_thumbnail()
            ? '/frontend/components/post/header_with_image.php'
            : '/frontend/components/post/header.php'
    );
}

function iro_render_page_content(): void
{
    ?>
    <div class="page-post">
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
    </div>
    <?php
}
