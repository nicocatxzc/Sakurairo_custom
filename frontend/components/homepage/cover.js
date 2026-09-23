import Typed from "typed.js";

let typedInstance = null;
_iro.hooks.onPageLoaded(() => {
    const cover = document.querySelector(".homepage-cover");
    if (!cover) {
        return;
    }
    const isHome = document.querySelector(".page-home");
    // 封面适时隐藏
    if (!isHome) {
        cover.classList.add("hide");
    } else {
        cover.classList.remove("hide");
    }
    // 封面打字机
    const typed_config = _iro?.config?.typed_config;
    if (typedInstance) {
        typedInstance.destroy();
    }
    if (typed_config) {
        const config = JSON.parse(typed_config);
        const typed_el = document.querySelector("#typed");
        if (typed_el) {
            typed_el.innerHTML = "";
            typedInstance = new Typed(typed_el, config);
        }
    }

    const video = document.querySelector(".cover-video");
    if (!isHome) {
        video.pause();
    } else {
        video.play();
    }
});
