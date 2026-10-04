import bus from "../../../app/bus";

// island 页脚：外层是高度占位的容器（不动、裁剪），卡片在里面做 transform 动画 ——
// 页面高度与滚动范围不随状态变化，卡片也永远画不出容器的范围。
// 页脚在 pjax 容器之外，DOM 不随导航重建，监听只挂一次。
const footer = document.querySelector(".site-footer.island");

// 视口底距文档底这个距离内就算到底了，沿用上游的 100px
const SHOW_THRESHOLD = 100;

function atBottom() {
    const scrollPosition = window.scrollY || document.documentElement.scrollTop;

    return scrollPosition + window.innerHeight >= document.body.scrollHeight - SHOW_THRESHOLD;
}

function checkFooterVisibility() {
    const show = atBottom();
    if (footer.classList.contains("show") === show) {
        return;
    }
    footer.classList.toggle("show", show);
}

/**
 * 挂监听并立刻算一次；hide 立刻收起，check 按当前滚动位置重算
 *
 * @param {"init" | "hide" | "check"} action
 */
export default function initFooter(action = "init") {
    if (!footer) {
        return;
    }

    if (action === "hide") {
        footer.classList.remove("show");
        return;
    }

    if (action === "check") {
        checkFooterVisibility();
        return;
    }

    footer.style.height = footer.querySelector(".site-info").getBoundingClientRect().height+"px";

    bus.on("scroll:update", checkFooterVisibility);
    bus.on("resize:update", checkFooterVisibility);
    // pjax 换页后文档高度和滚动位置都变了，得重算（页脚本身不会被换掉）
    _iro.hooks.onPageLoaded(checkFooterVisibility);
    checkFooterVisibility();
}

initFooter();
