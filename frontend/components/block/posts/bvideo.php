<div class="block-bvideo">
    <?php foreach ($data['av'] as $video): ?>
        <iframe
            class="bvideo"
            scrolling="no"
            sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
            allowfullscreen
            src="https://player.bilibili.com/player.html?avid=<?= esc_attr($video) ?>&page=1&autoplay=0&danmaku=0"
            frameborder="0"></iframe>
    <?php endforeach; ?>

    <?php foreach ($data['bv'] as $video): ?>
        <iframe
            class="bvideo"
            scrolling="no"
            sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
            allowfullscreen
            src="https://player.bilibili.com/player.html?bvid=<?= esc_attr($video) ?>&page=1&autoplay=0&danmaku=0"
            frameborder="0"></iframe>
    <?php endforeach; ?>
</div>