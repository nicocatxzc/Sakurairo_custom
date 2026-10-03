import { applyReadableOnActive } from "../utils/contrast";

// 深色模式由 app/darkmode.js 换一套 --active-color，主色变了前景要跟着重算；
// 该事件在 class 切换之后广播，回调里读得到新配色
document.addEventListener("darkmode", () => {
    applyReadableOnActive();
});

_iro.hooks.onPageLoaded(() => {
    applyReadableOnActive();
});
