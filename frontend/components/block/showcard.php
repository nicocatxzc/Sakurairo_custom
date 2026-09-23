<div class="block-showcard">
    <div class="img">
        <?php if (!empty($data['img'])): ?>
            <img
                class="nuxtpic"
                src="<?= iro_media_optimize_image_url(esc_url($data['img'])) ?>"
                alt="<?= esc_attr(wp_strip_all_tags($data['title'])) ?>"
                loading="lazy" />
        <?php endif; ?>

        <?php if (!empty($data['link'])): ?>
            <a
                href="<?= esc_url($data['link']) ?>"
                target="_blank"
                rel="noopener noreferrer">
                <button
                    class="showcard-button"
                    style="color: <?= esc_attr($data['color']) ?>;">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
            </a>
        <?php endif; ?>
    </div>

    <div class="icon-title">
        <?php if (!empty($data['icon'])): ?>
            <i class="icon <?= esc_attr($data['icon']) ?>"></i>
        <?php endif; ?>
        <span class="title"><?= wp_kses_post($data['title']) ?></span>
    </div>
</div>