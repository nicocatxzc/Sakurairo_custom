_iro.hooks.onPageLoaded(() => {
    const list = document.querySelector("#archive-list");
    if (!list) return;

    // 防止重复初始化
    if (list.dataset.animated === "1") return;
    list.dataset.animated = "1";

    const items = list.querySelectorAll(".item");
    const total = items.length;
    if (total === 0) return;

    let showCount = 0;
    let timer = null;

    const INTERVAL = 50;
    let lastTime = 0;

    function tick(now) {
        if (now - lastTime >= INTERVAL) {
            lastTime = now;
            items[showCount].classList.add("show");
            showCount++;

            if (showCount >= total) {
                timer = null;
                return;
            }
        }
        timer = requestAnimationFrame(tick);
    }

    timer = requestAnimationFrame(tick);
});
