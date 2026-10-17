<?php
$ua = $comment->comment_agent;
$imgurl = iro_opt('vision_resource_basepath') . 'ua/';
$browser = siren_get_browsers($ua);
$os = siren_get_os($ua);
?>

<span class="comment-ua">
    <img
        alt="browser-icon"
        src="<?= $imgurl . $browser['icon'] ?>.svg" />
    <?= $browser['title'] ?>
    <img
        alt="os-icon"
        src="<?= $imgurl . $os['icon'] ?>.svg" />
    <?= $os['title'] ?>
</span>