import { throttle } from "lodash-es";

// 视口尺寸的「唯一真源」：全站只留这一个 window resize 监听，其余模块订阅 resize:update 即可，
// 免得各处自己挂监听、各自防抖。总线不 import，直接取 _iro.bus（由 app/bus.js 挂到全局）
function updateResize() {
    _iro.bus.emit("resize:update", {
        width: window.innerWidth,
        height: window.innerHeight,
    });
}

if (typeof window !== "undefined" && !_iro.isBackend) {
    window.addEventListener(
        "resize",
        throttle(updateResize, 60, { leading: true, trailing: true }),
        { passive: true },
    );
}
