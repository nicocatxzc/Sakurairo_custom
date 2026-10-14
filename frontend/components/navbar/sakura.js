import bus from "../../app/bus";
import markActiveMenu from "../../app/utils/markActiveMenu";

const header = document.querySelector(".site-header.sakura");
bus.on("scroll:update", (data) => {
    if (!header) {
        return;
    }
    const { progress, direction } = data;

    if (progress >= 5 || direction == "down") {
        header.classList.add("bg");
    } else {
        header.classList.remove("bg");
    }
});

_iro.hooks.onPageLoaded(() => {
    // 封面按钮可被设置项关掉，取不到就跳过
    header
        ?.querySelector(".cover-toggle")
        ?.classList.toggle("show", _iro.page.is_home ?? false);

    markActiveMenu(header?.querySelector(".menu"));
});

let activeSubMenu = null;
