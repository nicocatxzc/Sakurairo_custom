import { throttle } from "lodash-es";
import bus from "../bus";

function updateResize() {
    bus.emit("resize:update", {
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
