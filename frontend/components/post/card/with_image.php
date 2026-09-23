<article class="post-card post-card-with-image">
    <div class="post-thumb">
        <a href="<?= esc_url(get_permalink()) ?>">
            <?php if (has_post_thumbnail()) : ?>
                <img
                    src="<?= iro_media_optimize_image_url(esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium_large'))) ?>"
                    alt="<?= esc_attr(sprintf('featured image for post %s', get_the_title())) ?>"
                    loading="lazy">
            <?php else : // 意义不明的兜底？?>
                <img
                    src="<?= iro_media_optimize_image_url(esc_url(get_template_directory_uri() . '/assets/images/default-thumb.jpg')) ?>"
                    alt="<?= esc_attr(sprintf('default image for post %s', get_the_title())) ?>"
                    loading="lazy">
            <?php endif; ?>
        </a>
    </div>

    <div class="post-date">
        <time datetime="<?= esc_attr(get_the_modified_date('Y-m-d\TH:i:s')) ?>">
            更新于:<?= get_the_modified_date('Y年m月d日') ?>
        </time>
        <?php if (is_sticky()) : ?>
            <div class="sticky">&#x2605;&#xFE0E;置顶</div>
        <?php endif; ?>
    </div>

    <div class="post-meta">
        <?php
        $metas = iro_opt('post_card_metas', ['category', 'views']);
        foreach ($metas as $meta) :
            switch ($meta):
                case 'author': ?>
                    <span>
                        <i class="fa-solid fa-feather-pointed"></i>
                        <?= esc_html(get_the_author()) ?>
                    </span>
                    <?php break; ?>
                    <?php
                case 'category':
                    $categories = get_the_category();
                    if (!empty($categories)) : ?>
                        <span>
                            <i class="fa-solid fa-folder-open"></i>
                            <a href="<?= esc_url(get_category_link($categories[0]->term_id)) ?>">
                                <?= esc_html($categories[0]->name) ?>
                            </a>
                        </span>
                    <?php else : ?>
                        <span>
                            <i class="fa-solid fa-folder-open"></i>
                            未分类
                        </span>
                    <?php endif; ?>
                    <?php break; ?>
                <?php
                case 'commentCounts': ?>
                    <span>
                        <i class="fa-solid fa-comment"></i>
                        <?= get_comments_number() ?>
                    </span>
                    <?php break; ?>
                <?php
                case 'views': ?>
                    <span>
                        <i class="fa-solid fa-eye"></i>
                        <?= (int) iro_get_post_views(get_the_ID()) ?>
                    </span>
                    <?php break; ?>
            <?php endswitch; ?>
        <?php endforeach; ?>
    </div>

    <div class="post-title">
        <a href="<?= esc_url(get_permalink()) ?>" aria-label="<?= esc_attr(sprintf('link to post %s', get_the_title())) ?>">
            <h3><?= esc_html(get_the_title()) ?></h3>
        </a>
    </div>
    <div class="post-excerpt">
        <span><?= esc_html(wp_trim_words(get_the_excerpt(), 30, '...')) ?></span>
    </div>
</article>