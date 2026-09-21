import axios from "axios";

const DWELL_TIME = 3000;

let timer = null;

function cancelReport() {
    if (timer === null) {
        return;
    }

    clearTimeout(timer);
    timer = null;
}

function reportViews(postId) {
    axios
        .post(
            `${_iro.config.iro_api}/post/views`,
            { post_id: postId },
            { headers: { "X-WP-Nonce": _iro.config.nonce } },
        )
        .catch((error) => {
            console.error("阅读量上报失败", error);
        });
}

_iro.hooks.onPageLoaded(() => {
    cancelReport();

    // 列表页的 post_id 指向循环中的文章，只有单篇文章页才计入
    if (_iro.page?.is_home || !_iro.page?.is_singular || !_iro.page?.post_id) {
        return;
    }

    const postId = Number(_iro.page.post_id);

    timer = setTimeout(() => {
        timer = null;
        reportViews(postId);
    }, DWELL_TIME);
});

// 驻留时间内离开当前页面则不计入
_iro.hooks["pjax:start"].push(cancelReport);
window.addEventListener("pagehide", cancelReport);
