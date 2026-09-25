<article class="post-card post-card-simple">
    <a href="<?= esc_url(get_permalink()) ?>">
        <h3 class="post-title">
            <?php if (is_sticky()) : ?>
                <span class="sticky">&#x2B06;&#xFE0E;</span>
            <?php endif; ?>
            <span><?= esc_html(get_the_title()) ?></span>
        </h3>
    </a>
    <a href="<?= esc_url(get_permalink()) ?>">
        <span class="post-excerpt"><?= esc_html(wp_trim_words(get_the_excerpt(), 120, '...')) ?></span>
    </a>
    <div class="post-metas">
        <div class="post-meta-date">
            <!-- Icon: fa-icon-solid:calendar -->
            <i class="fa-icon-solid fa-calendar icon"></i>
            <time datetime="<?= esc_attr(get_the_modified_date('Y-m-d\TH:i:s')) ?>">
                更新于:<?= get_the_modified_date('Y年m月d日') ?>
            </time>
        </div>
        <div class="post-meta-categories">
            <!-- Icon: fa-icon-solid:folder-open -->
            <i class="fa-icon-solid fa-folder-open"></i>
            <?php
            foreach (get_the_category() as $category) :
            ?>
                <a href="<?= esc_url(get_category_link($category->term_id)) ?>" class="category">
                    <?= esc_html($category->name) ?>
                </a>
            <?php
            endforeach;
            ?>
        </div>
        <div class="post-meta-tags">
            <!-- Icon: fa-icon-solid:tags -->
            <i class="fa-icon-solid fa-tags icon"></i>
            <?php
            foreach (wp_get_post_tags() as $tag) :
            ?>
                <a href="<?= esc_url(get_tag_link($tag->term_id)) ?>" class="tag">
                    #<?= esc_html($tag->name) ?>
                </a>
            <?php
            endforeach;
            ?>
        </div>
        <?php foreach (iro_opt('post_card_metas', ['category', 'views']) as $meta) :
            switch ($meta):
                case 'author': ?>
                    <span>
                        <i class="fa-icon-solid fa-feather-pointed"></i>
                        <?= esc_html(get_the_author()) ?>
                    </span>
                    <?php break; ?>
                <?php
                case 'commentCounts': ?>
                    <span>
                        <i class="fa-icon-solid fa-comment"></i>
                        <?= get_comments_number() ?>
                    </span>
                    <?php break; ?>
                <?php
                case 'views': ?>
                    <span class="flex-center">
                        <i class="fa-icon-solid fa-eye"></i>
                        <?= (int) iro_get_post_views(get_the_ID()) ?>
                    </span>
                    <?php break; ?>
                <?php
                default: ?>
                    <!-- undefined -->
                    <?php break; ?>
            <?php endswitch; ?>
        <?php endforeach; ?>
    </div>
</article>