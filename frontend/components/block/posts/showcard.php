<div class="block-showcard">
    <div class="img">
        <?php if (!empty($data['img'])): ?>
            <picture class="nuxtpic">
                <?= iro_media_optimize_image_formats(
                    $data['img'],
                    ['width' => '12.5rem', 'height' => '12.5rem'],
                    [
                        'alt' => wp_strip_all_tags($data['title']),
                        'loading' => 'lazy',
                    ]
                ) ?>
            </picture>
        <?php endif; ?>

        <?php if (!empty($data['link'])): ?>
            <a
                href="<?= esc_url($data['link']) ?>"
                target="_blank"
                rel="noopener noreferrer">
                <button
                    class="showcard-button"
                    style="color: <?= esc_attr($data['color']) ?>;">
                    <i class="fa-icon-solid fa-angle-right"></i>
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