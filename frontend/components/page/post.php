<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article class="post">
            <?php
            if (has_post_thumbnail()) {
                require get_template_directory() . '/frontend/components/post/header_with_image.php';
            } else {
                require get_template_directory() . '/frontend/components/post/header.php';
            }
            ?>
            <?php iro_content_container_start() ?>
            <div class="post-content">
                <?php require get_template_directory() . '/frontend/components/post/render.php'; ?>
            </div>
            <!-- <ClientOnly>
                <PostToc
                    v-if="
                        (themeConfig?.postTableOfContent &&
                            props.page.type == 'single') ||
                        (themeConfig?.pageTableOfContent &&
                            props.page.type == 'page')
                    "
                    class="toc" />
            </ClientOnly> -->
            <?php iro_content_container_end() ?>
        </article>
<?php endwhile;
endif; ?>
<?php
// 没有文章密码且评论已开启
if (!post_password_required() && get_post_field('comment_status', get_the_ID()) == 'open'):
    iro_content_container_start();
    comments_template();
    iro_content_container_end();
endif; ?>