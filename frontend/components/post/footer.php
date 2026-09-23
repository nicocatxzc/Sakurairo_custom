<?php
if (!iro_opt('article_function', true) || !is_single()) {
    return;
}

$license = get_post_meta(get_the_ID(), 'license', true) ?: iro_opt('article_licenses', true);
$reward = iro_opt('article_author_reward', []);
?>
<?php iro_content_container_start() ?>
<footer class="post-footer">
    <?php if ($license && $license !== '0' && $license !== false): ?>
        <?php
        $license_variant = $license === 'cc0' ? 'zero' : substr($license, 3);
        $license_name = $license === 'cc0' ? 'CC0 1.0' : 'CC ' . strtoupper($license_variant) . ' 4.0';
        ?>
        <a
            class="post-license"
            href="https://creativecommons.org/<?= $license === 'cc0' ? 'publicdomain/zero/1.0' : 'licenses/' . $license_variant . '/4.0' ?>/deed.<?= get_user_locale() ?>"
            target="_blank"
            rel="nofollow"
            title="<?= esc_attr(sprintf(__('This article is licensed under %s', 'sakurairo'), $license_name)) ?>">
            <i class="fa-brands fa-creative-commons"></i>
            <?php foreach (explode('-', $license_variant) as $variant): ?>
                <i class="fa-brands fa-creative-commons-<?= $variant ?>"></i>
            <?php endforeach; ?>
        </a>
    <?php endif; ?>

    <?php if (($reward['link'] ?? '') || !empty($reward['image1']) || !empty($reward['image2'])): ?>
        <div class="reward-open">
            <?php if ($reward['link'] ?? ''): ?>
                <a class="reward-button" href="<?= esc_url($reward['link']) ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fa-icon-solid fa-piggy-bank fa-sm"></i>
                </a>
            <?php else: ?>
                <i class="fa-icon-solid fa-piggy-bank fa-sm"></i>
            <?php endif; ?>
            <?php if (!empty($reward['image1']) || !empty($reward['image2'])): ?>
                <div class="reward-main">
                    <ul class="reward-row">
                        <?php foreach (['image1' => 'link1', 'image2' => 'link2'] as $image_key => $link_key): ?>
                            <?php if (empty($reward[$image_key])): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <li class="reward-<?= $image_key ?>">
                                <?php if (!empty($reward[$link_key])): ?>
                                    <a href="<?= esc_url($reward[$link_key]) ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?= iro_media_optimize_image_url(esc_url($reward[$image_key])) ?>" alt="<?= esc_attr('reward_' . $image_key) ?>">
                                    </a>
                                <?php else: ?>
                                    <img src="<?= iro_media_optimize_image_url(esc_url($reward[$image_key])) ?>" alt="<?= esc_attr('reward_' . $image_key) ?>">
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (iro_opt('article_author_avatar', true)): ?>
        <div class="info" itemprop="author" itemscope itemtype="http://schema.org/Person">
            <a class="profile gravatar" href="<?= esc_url(get_author_posts_url(get_the_author_meta('ID'))) ?>">
                <img
                    class="fa-spin"
                    style="--fa-animation-duration: 15s;"
                    src="<?= iro_media_optimize_image_url(esc_url(get_avatar_url(get_the_author_meta('ID'), ['size' => 60]))) ?>"
                    itemprop="image"
                    alt="<?= esc_attr(get_the_author()) ?>"
                    height="30"
                    width="30">
            </a>
        </div>
    <?php endif; ?>

    <?php if (iro_opt('article_author_name', false)): ?>
        <div class="meta">
            <a
                href="<?= esc_url(get_author_posts_url(get_the_author_meta('ID'))) ?>"
                itemprop="url"
                rel="author"><?= esc_html(get_the_author()) ?></a>
        </div>
    <?php endif; ?>

    <?php if (iro_opt('article_author_quote', true)): ?>
        <div class="desc flex-center">
            <i class="fa-icon-solid fa-feather" aria-hidden="true"></i><?= esc_html(get_the_author_meta('description') ?: __('This author has not provided a description.', 'sakurairo')) ?>
        </div>
    <?php endif; ?>

    <?php if (iro_opt('article_modified_time', false)): ?>
        <div class="post-modified-time flex-center">
            <i class="fa-icon-solid fa-calendar-day" aria-hidden="true"></i><?= esc_html__('Last updated on ', 'sakurairo') . get_the_modified_time('Y-m-d') ?>
        </div>
    <?php endif; ?>

    <?php if (iro_opt('article_tag', true) && has_tag()): ?>
        <div class="post-tags flex-center">
            <i class="fa-icon-solid fa-tag" aria-hidden="true"></i>
            <?php the_tags('', ' ', ' '); ?>
        </div>
    <?php endif; ?>
</footer>
<?php iro_content_container_end() ?>