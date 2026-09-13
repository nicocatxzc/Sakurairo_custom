import Swup from "swup";
import SwupProgressPlugin from "@swup/progress-plugin";

const swup = new Swup({
    containers: [".layout-slot", "#iro_page_config"],
    linkSelector: "a[href]:not(.no-pjax):not(* .no-pjax)",
    animationSelector: false,

    plugins: [new SwupProgressPlugin()],

    scrollTo: (event) => {
        const url = event.to.url;

        // 首页或 /page/* 滚动到 #articles
        if (url === "/" || url.startsWith("/page/")) {
            const target = document.querySelector("#articles");
            if (target) {
                return (
                    target.getBoundingClientRect().top + window.pageYOffset - 20
                );
            }
        }

        // 其他页面滚动到顶部
        return 0;
    },
});

swup.hooks.on("visit:start", (visit) => {
    document.dispatchEvent(new CustomEvent("pjax:start", { detail: {} }));
    const url = new URL(visit.to.url, window.location.origin);

    if (url.pathname.startsWith("/page/")) {
        visit.scroll.reset = false;
    }
});

swup.hooks.on("content:replace", () => {
    document.dispatchEvent(new CustomEvent("pjax:success", { detail: {} }));
});

swup.hooks.on("page:view", () => {
    document.dispatchEvent(new CustomEvent("pjax:complete", { detail: {} }));
});

swup.hooks.on("visit:end", () => {
    document.dispatchEvent(new CustomEvent("pjax:end", { detail: {} }));
});

swup.hooks.on("visit:fail", () => {
    document.dispatchEvent(new CustomEvent("pjax:error", { detail: {} }));
});

swup.hooks.on("visit:abort", () => {
    document.dispatchEvent(new CustomEvent("pjax:error", { detail: {} }));
    document.dispatchEvent(new CustomEvent("pjax:complete", { detail: {} }));
    document.dispatchEvent(new CustomEvent("pjax:end", { detail: {} }));
});
