import bus from "../../../app/bus";

const footer = document.querySelector(".site-footer.island");

// 视口底距文档底这个距离内就算到底了
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

    // 占位高度，容器实际在视口图层
    footer.style.height = footer.querySelector(".site-info").getBoundingClientRect().height+"px";

    bus.on("scroll:update", checkFooterVisibility);
    bus.on("resize:update", checkFooterVisibility);
    _iro.hooks.onPageLoaded(checkFooterVisibility);
    checkFooterVisibility();
}

initFooter();
