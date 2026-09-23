<?php
// 加载语言颜色映射表
$language_colors = [];
$colors_file = __DIR__ . '/languageColors.json';

if (file_exists($colors_file)) {
    $decoded = json_decode(file_get_contents($colors_file), true);
    if (is_array($decoded)) {
        $language_colors = $decoded;
    }
}

$lang_color = !empty($data['language']) && isset($language_colors[$data['language']])
    ? $language_colors[$data['language']]
    : '#999';
?>

<div class="block-ghcard">
    <div class="github-card">
        <a href="<?= esc_url($data['url']) ?>" target="_blank" rel="noopener">
            <header class="repo-name">
                <span class="title-text">
                    <i class="icon gh-repo"></i>
                    <?= esc_html($data['name']) ?>
                </span>
            </header>

            <?php if (!empty($data['description'])): ?>
                <div class="repo-desc">
                    <?= esc_html($data['description']) ?>
                </div>
            <?php endif; ?>

            <div class="repo-meta">
                <?php if (!empty($data['language'])): ?>
                    <span class="lang">
                        <span class="dot" style="background-color: <?= esc_attr($lang_color) ?>;"></span>
                        <?= esc_html($data['language']) ?>
                    </span>
                <?php endif; ?>

                <span class="stars">
                    <i class="icon gh-star"></i>
                    <?= (int) $data['stars'] ?>
                </span>

                <span class="forks">
                    <i class="icon gh-fork"></i>
                    <?= (int) $data['forks'] ?>
                </span>

                <?php if (!empty($data['license'])): ?>
                    <span class="license">
                        <i class="icon gh-license"></i>
                        <?= esc_html($data['license']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>