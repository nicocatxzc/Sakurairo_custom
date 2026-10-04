import bus from "../../../app/bus";

_iro.hooks.DOMContentLoaded.add(() => {
    const footer = document.querySelector(".site-footer.island");

    if (!footer) {
        return;
    }

    // 视口底距文档底这个距离内就算到底了
    const SHOW_THRESHOLD = 100;

    function atBottom() {
        const scrollPosition =
            window.scrollY || document.documentElement.scrollTop;

        return (
            scrollPosition + window.innerHeight >=
            document.body.scrollHeight - SHOW_THRESHOLD
        );
    }

    function checkFooterVisibility() {
        const show = atBottom();
        if (footer.classList.contains("show") === show) {
            return;
        }
        footer.classList.toggle("show", show);
    }

    // 占位高度，容器实际在视口图层
    footer.style.height =
        footer.querySelector(".site-info").getBoundingClientRect().height +
        "px";

    bus.on("scroll:update", checkFooterVisibility);
    bus.on("resize:update", checkFooterVisibility);
    _iro.hooks.onPageLoaded(checkFooterVisibility);
    checkFooterVisibility();
});
