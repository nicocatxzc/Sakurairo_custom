<div class="block-bvideo">
    <iframe
        class="bvideo"
        scrolling="no"
        sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
        allowfullscreen
        v-for="video in videos.av"
        :src="`https://player.bilibili.com/player.html?avid=${video}&page=1&autoplay=0&danmaku=0`"
        frameborder="0"
        :key="video"></iframe>
    <iframe
        class="bvideo"
        scrolling="no"
        sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts"
        allowfullscreen
        v-for="video in videos.bv"
        :src="`https://player.bilibili.com/player.html?bvid=${video}&page=1&autoplay=0&danmaku=0`"
        frameborder="0"
        :key="video"></iframe>
</div>