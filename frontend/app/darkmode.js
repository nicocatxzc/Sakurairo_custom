import { ref, watch } from "vue";

const STORAGE_KEY = "darkmode";
const AUTO_CHECK_INTERVAL = 60 * 1000;
const COOKIE_MAX_AGE = 7 * 24 * 60 * 60;

let darkmode = "auto"; // "auto" | "true" | "false"
let darkmodeStat = ref(false); // 当前实际是否为深色

watch(
    () => darkmodeStat.value,
    () => {
        document.documentElement.classList.toggle(
            "dark",
            darkmodeStat.value === true,
        );
    },
);

// 仅在 auto 模式下，按时间自动判断深色（18:00 ~ 次日 6:00）
const check = () => {
    if (darkmode !== "auto") return;

    const hours = new Date().getHours();
    darkmodeStat.value = hours >= 18 || hours < 6;
};

async function readCookie() {
    try {
        if (cookieStore) {
            const entry = await cookieStore.get(STORAGE_KEY);
            return entry?.value ?? null;
        }
    } catch {
        
    }

    const match = document.cookie.match(
        new RegExp(`(?:^|;\\s*)${STORAGE_KEY}=([^;]*)`),
    );
    return match ? decodeURIComponent(match[1]) : null;
}

// 写入 cookie
async function writeCookie(mode) {
    try {
        await cookieStore.set({
            name: STORAGE_KEY,
            value: mode,
            path: "/",
            maxAge: COOKIE_MAX_AGE,
            sameSite: "lax",
        });
    } catch (e) {
        document.cookie =
            `${STORAGE_KEY}=${encodeURIComponent(mode)}; ` +
            `path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax`;
    }
}

// 状态设置
function setState(mode) {
    if (mode === "auto") {
        darkmode = "auto";
        check(); // 立刻按当前时间校正一次
    } else {
        darkmode = mode === "true" ? "true" : "false";
        darkmodeStat.value = darkmode === "true";
    }
    writeCookie(darkmode);
}

const getState = () => darkmode;

// 状态轮换开关
function toggleMode() {
    if (darkmode === "auto") {
        setState("true");
    } else if (darkmodeStat.value) {
        setState("false");
    } else {
        setState("auto");
    }
}

async function init() {
    const saved = await readCookie();

    if (saved === "auto" || saved === "true" || saved === "false") {
        if (saved === "auto") {
            darkmode = "auto";
            check();
        } else {
            darkmode = saved;
            darkmodeStat.value = saved === "true";
        }
    } else {
        // 默认auto
        check();
    }
    setInterval(check, AUTO_CHECK_INTERVAL);
    console.log("深色模式状态：", darkmode);
}

_iro.hooks.DOMContentLoaded.push(init);

export { toggleMode, setState, getState };
