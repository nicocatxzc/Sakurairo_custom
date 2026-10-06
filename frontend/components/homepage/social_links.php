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
    <button class="pagination prev flex-center hide">
        <i class="fa-icon-solid fa-angle-left icon"></i>
    </button>
    <div class="page-container">
        <?php if (iro_opt("cover_social_displays", [])): ?>
            <?php foreach (iro_opt("cover_social_displays", []) as $item): ?>
                <?php
                $is_email = ($item['select'] ?? '') === 'email';
                $email_href = $item['link'] ?? '#';
                // 统一规范成 mailto: 形式
                if ($is_email && $email_href && $email_href !== '#') {
                    if (stripos($email_href, 'mailto:') !== 0) {
                        // 纯邮箱或带参数的邮箱，补上 mailto:
                        $email_href = 'mailto:' . $email_href;
                    }
                }
                // 混淆：base64 -> 每字符 +1 -> 再 base64
                $obfuscated = '';
                if ($is_email && $email_href && $email_href !== '#') {
                    $b64 = base64_encode($email_href);
                    $shifted = '';
                    for ($i = 0; $i < strlen($b64); $i++) {
                        $shifted .= chr(ord($b64[$i]) + 1);
                    }
                    $obfuscated = base64_encode($shifted);
                    $href = '#';
                } else {
                    $href = $email_href;
                }
                ?>
                <div
                    class="social-item">
                    <a
                        href="<?= esc_attr($href) ?>"
                        <?php if ($is_email && $obfuscated): ?>
                        data-href="<?= esc_attr($obfuscated) ?>"
                        onclick="(function(el,ev){ev.preventDefault();var s=atob(el.dataset.href),r='';for(var i=0;i<s.length;i++){r+=String.fromCharCode(s.charCodeAt(i)-1);}var u=atob(r);el.href=u;el.removeAttribute('data-href');el.removeAttribute('onclick');window.location.href=u;})(this,event);return false;"
                        <?php else: ?>
                        target="_blank"
                        rel="noopener noreferrer"
                        <?php endif; ?>
                        aria-label="<?= esc_attr(__("点击访问", 'sakurairo') . ($item['title'] ?? '')) ?>"
                        title="<?= esc_attr(__("点击访问", 'sakurairo') . ($item['title'] ?? '')) ?>">
                        <picture class="nuxtpic social-img">
                            <?= iro_media_optimize_image_formats(
                                iro_opt('vision_resource_basepath', BASIC_VISION_RESOURCE_PATH) . 'display_icon/' . iro_opt('cover_social_icon') . '/' . $icons_map[$item['select']] . '.webp',
                                ['height' => '2.2rem'],
                                [
                                    'alt' => $item['title'] ?? '',
                                    'loading' => 'lazy',
                                ]
                            ) ?>
                        </picture>
                    </a>
                    <?php if ($item['qrcode']): ?>
                        <div class="qrcode">
                            <img
                                src="<?= esc_url(iro_media_optimize_image_url($item['qrcode'], ['width' => '12.5rem'])) ?>"
                                alt="qrcode">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button class="pagination next flex-center hide">
        <i class="fa-icon-solid fa-angle-right icon"></i>
    </button>
</div>