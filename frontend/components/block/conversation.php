<div class="block-conversation">
    <div
        class="conversations-code"
        style="flex-direction: <?= esc_attr($data['direction']) ?>;">
        <?php if (!empty($data['avatar'])): ?>
            <picture class="nuxtpic">
                <?= iro_media_optimize_image_formats(
                    $data['avatar'],
                    ['width' => '2.5rem', 'height' => '2.5rem'],
                    [
                        'alt' => $data['username'],
                        'loading' => 'lazy',
                    ]
                ) ?>
            </picture>
        <?php endif; ?>
        <div class="conversations-code-text">
            <?= wp_kses_post($data['content']) ?>
        </div>
    </div>
</div>