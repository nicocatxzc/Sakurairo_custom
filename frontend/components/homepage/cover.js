import Typed from "typed.js";

let typedInstance = null;
_iro.hooks.onPageLoaded(() => {
    // 封面适时隐藏
    if (!document.querySelector(".page-home")) {
        document.querySelector(".homepage-cover").classList.add("hide");
    } else {
        document.querySelector(".homepage-cover").classList.remove("hide");
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
});
