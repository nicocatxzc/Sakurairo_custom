import bus from "../../app/bus";

_iro.hooks["DOMContentLoaded"].add(() => {
    const scroll = document.querySelector("#scroll-progress");
    const loading = document.querySelector("#load-progress");
    if (scroll) {
        bus.on("scroll:update", (data) => {
            const { progress, direction } = data;

            scroll.style.setProperty("--progress", `${progress}%`);
        });
    }

    if (loading) {
        let timer = null;
        loading.setAttribute("data-stat", "none");
        document.addEventListener("pjax:start", () => {
            loading.setAttribute("data-stat", "start");
            if (timer) {
                clearTimeout(timer);
            }
        });
        document.addEventListener("pjax:success", () => {
            loading.setAttribute("data-stat", "success");
        });
        document.addEventListener("pjax:complete", () => {
            loading.setAttribute("data-stat", "complete");
        });
        document.addEventListener("pjax:end", () => {
            loading.setAttribute("data-stat", "end");
            timer = setTimeout(() => {
                loading.setAttribute("data-stat", "none");
            }, 500);
        });
    }
});
