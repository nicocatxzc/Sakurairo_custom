import bus from '../bus'
import { throttle } from "lodash-es";

export const emitter = bus;

let progress = 0;
let direction = "none";
let lastScrollY = 0;
let isListening = false;
let updateThrottled = null;

// 更新滚动进度
function updateScroll() {
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const winHeight = window.innerHeight;
    const docHeight = document.documentElement.scrollHeight;
    const maxScroll = docHeight - winHeight;

    let newProgress = 0;
    if (maxScroll <= 0) {
        newProgress = 100;
    } else {
        newProgress = Math.min(100, Math.max(0, (scrollY / maxScroll) * 100));
        newProgress = Number(newProgress.toFixed(2));
    }

    let newDirection = direction;
    if (Math.abs(scrollY - lastScrollY) > 5) {
        newDirection = scrollY > lastScrollY ? "down" : "up";
    }

    // 更新内部状态
    progress = newProgress;
    direction = newDirection;
    lastScrollY = scrollY;

    // 广播事件
    emitter.emit("scroll:update", {
        progress,
        direction,
    });
}

function startListening() {
    if (isListening) return;
    if (typeof window === "undefined") return;

    updateThrottled = throttle(updateScroll, 60, {
        leading: true,
        trailing: true,
    });

    window.addEventListener("scroll", updateThrottled, { passive: true });
    window.addEventListener("resize", updateThrottled, { passive: true });
    isListening = true;

    updateScroll();
}

function stopListening() {
    if (!isListening) return;
    if (updateThrottled) {
        window.removeEventListener("scroll", updateThrottled);
        window.removeEventListener("resize", updateThrottled);
        updateThrottled.cancel();
        updateThrottled = null;
    }
    isListening = false;
}

function resetState() {
    progress = 0;
    direction = "none";
    lastScrollY = 0;
    emitter.emit("scroll:update", { progress: 0, direction: "none" });
}

function handlePjaxStart() {
    stopListening();
    resetState();
}

function handlePjaxEnd() {
    setTimeout(() => {
        startListening();
        updateScroll();
    }, 0);
}

if (typeof window !== "undefined") {
    const $ = window.jQuery;

    if ($ && $.fn && $.fn.pjax) {
        $(document).on("pjax:start", handlePjaxStart);
        $(document).on("pjax:end", handlePjaxEnd);
    }

    startListening();

    window.addEventListener("beforeunload", () => {
        stopListening();
        if ($ && $.fn && $.fn.pjax) {
            $(document).off("pjax:start", handlePjaxStart);
            $(document).off("pjax:end", handlePjaxEnd);
        }
    });
}
