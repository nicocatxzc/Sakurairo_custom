<div class="block-conversation">
    <div
        class="conversations-code"
        style="flex-direction: <?= esc_attr($data['direction']) ?>;">
        <?php if (!empty($data['avatar'])): ?>
            <img
                class="nuxtpic"
                src="<?= esc_url($data['avatar']) ?>"
                alt="<?= esc_attr($data['username']) ?>"
                loading="lazy" />
        <?php endif; ?>
        <div class="conversations-code-text">
            <?= wp_kses_post($data['content']) ?>
        </div>
    </div>
</div>