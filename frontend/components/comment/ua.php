<?php
$imgurl = iro_opt('vision_resource_basepath', BASIC_VISION_RESOURCE_PATH) . 'ua/';
$ua = (string) $comment->comment_agent;
$browser = siren_get_browsers($ua);
$os = siren_get_os($ua);
$ip_address = iro_opt('comment_ip_address', false) ? iro_get_comment_ip_address((int) $comment->comment_ID) : '';
?>
<?php if (iro_opt('comment_useragent', false)): ?>
    <span class="comment-ua">
        <img
            alt="browser-icon"
            src="<?= esc_url($imgurl . $browser['icon'] . '.svg') ?>" />
        <?= esc_html($browser['title']) ?>
        <img
            alt="os-icon"
            src="<?= esc_url($imgurl . $os['icon'] . '.svg') ?>" />
        <?= esc_html($os['title']) ?>
    </span>
<?php endif ?>
<?php if ($ip_address !== ''): ?>
    <span class="comment-ip-address">
        <?= __('来自：', 'sakurairo') ?><?= esc_html($ip_address) ?>
    </span>
<?php endif ?>
