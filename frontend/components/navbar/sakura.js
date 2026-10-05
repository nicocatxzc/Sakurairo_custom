import bus from "../../app/bus";

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

    _iro.hooks.onPageLoaded(() => {
        header
            .querySelector(".cover-toggle")
            .classList.toggle("show", _iro.page.is_home ?? false);
    });
});

let activeSubMenu = null;
