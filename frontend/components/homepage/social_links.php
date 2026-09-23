<?php
$icons_map = [
    "bilibili" => "bilibili",
    "discord" => "discord",
    "tiktok" => "dy",
    "facebook" => "fb",
    "github" => "github",
    "instgram" => "ig",
    "linkedin" => "lk",
    "email" => "mail",
    'netease_music' => "ncm",
    "qq" => "qq",
    "steam" => "st",
    "telegram" => "tg",
    'twitter' => "tw",
    "wechat" => "wechat",
    "sina" => "weibo",
    "xiaohongshu" => "xiaohongshu",
    "youtube" => "youtube",
    "zhihu" => "zhihu",
    "custom" => "custom",
]
?>
<div class="social-links">
    <button class="pagination prev flex-center">
        <i class="fa-icon-solid fa-angle-left icon"></i>
    </button>
    <div class="page-container">
        <?php foreach (iro_opt("cover_social_displays", []) as $item): ?>
            <div
                class="social-item">
                <a
                    href="<?= $item['link'] ?? '#' ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="点击访问<?= $item['title'] ?? '' ?>"
                    title="点击访问<?= $item['title'] ?? '' ?>">
                    <img
                        loading="lazy"
                        src="<?= iro_media_optimize_image_url(iro_opt('vision_resource_basepath', 'https://s.nmxc.ltd/sakurairo_vision/@3.0/') . 'display_icon/' . iro_opt('cover_social_icon') . '/' . $icons_map[$item['select']] . '.webp') ?>"
                        class="social-img nuxtpic"
                        alt="<?= $item['title'] ?? '' ?>" />
                </a>
                <?php if ($item['qrcode']): ?>
                    <div class="qrcode">
                        <img src="<?= $item['qrcode'] ?? '' ?>" alt="qrcode">
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="pagination next flex-center">
        <i class="fa-icon-solid fa-angle-right icon"></i>
    </button>
</div>