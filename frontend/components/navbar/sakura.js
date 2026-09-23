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
});

let activeSubMenu = null;
