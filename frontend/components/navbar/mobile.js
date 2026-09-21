import { animate } from "animejs";
import { onClickOutside } from "@vueuse/core";
import bus from "../../app/bus";

// 面板展开/收起时长
const DURATION = 500;
// 面板完全展开时的高度
const OPEN_HEIGHT = "70dvh";
// 动画被打断时的兜底放行延时
const FALLBACK_DELAY = 50;

let header = null;
let scrollProgress = 0;
let scrollDirection = "none";

let currentPanel = ""; // 当前展开的面板："" | "menu" | "user"
let closingPanel = ""; // 正在收起的面板
let sequence = 0; // 时序令牌：新的操作会让在途的旧时序失效

const animations = { menu: null, user: null };

/** 取面板元素 */
function getPanel(name) {
    return header?.querySelector(`[data-panel="${name}"]`) ?? null;
}

/**
 * 执行面板动画
 * @param {string} name 面板名
 * @param {boolean} open 展开 / 收起
 * @returns {Promise<void>} 动画结束（或被打断）后兑现
 */
function animatePanel(name, open) {
    const el = getPanel(name);
    if (!el) return Promise.resolve();

    // 同一面板上的旧动画先取消，避免 maxHeight 动画互相叠加
    animations[name]?.cancel();

    return new Promise((resolve) => {
        let anim = null;
        let settled = false;

        const finish = () => {
            if (settled) return;
            settled = true;
            if (animations[name] === anim) animations[name] = null;
            resolve();
        };

        anim = animate(el, {
            // 从当前高度出发（没有内联值时按收起状态处理），避免打断时跳变
            maxHeight: [el.style.maxHeight || "0dvh", open ? OPEN_HEIGHT : 0],
            duration: DURATION,
            ease: open ? "outQuad" : "inQuad",
            onComplete: finish,
        });
        animations[name] = anim;

        // anime.js v4 的 then() 只在动画自然结束时兑现，
        // 动画被替换 / 取消时不会触发，这里兜底放行，避免时序锁死
        setTimeout(finish, DURATION + FALLBACK_DELAY);
    });
}

/** 同步 header 的背景态：滚动进度 >= 5%、向下滚动、面板展开时显示背景 */
function syncHeader() {
    if (!header) return;

    const expanded = !!(currentPanel || closingPanel);
    header.classList.toggle(
        "bg",
        scrollProgress >= 5 || scrollDirection === "down" || expanded,
    );

    header.querySelectorAll("[data-panel-toggle]").forEach((toggle) => {
        toggle.setAttribute(
            "aria-expanded",
            String(toggle.dataset.panelToggle === currentPanel),
        );
    });
}

/** 切换当前展开的面板 */
function setCurrentPanel(name) {
    currentPanel = name;
    syncHeader();
}

/**
 * 打开/收起面板
 * @param {string} name 面板名
 */
async function toggleMenu(name) {
    const token = ++sequence;

    // 没有任何面板展开，直接展开
    if (!currentPanel && !closingPanel) {
        setCurrentPanel(name);
        await animatePanel(name, true);
        return;
    }

    // 点击已经展开的面板，收起
    if (name === currentPanel) {
        closingPanel = name;
        setCurrentPanel("");
        await animatePanel(name, false);
        if (closingPanel === name) closingPanel = "";
        syncHeader();
        return;
    }

    // 切换到另一个面板，先等旧面板完全收起，再展开新面板
    const previous = currentPanel || closingPanel;
    closingPanel = previous;
    setCurrentPanel("");
    await animatePanel(previous, false);

    // 收起期间又有新的操作，本次展开作废，交给新的时序
    if (token !== sequence) return;

    closingPanel = "";
    setCurrentPanel(name);
    await animatePanel(name, true);
}

// 收起当前展开/正在收起的面板
function collapse() {
    const name = currentPanel || closingPanel;
    if (!name) return;

    sequence++; // 让在途的时序失效
    closingPanel = name;
    setCurrentPanel("");

    animatePanel(name, false).then(() => {
        if (closingPanel === name) closingPanel = "";
        syncHeader();
    });
}

let expandedItem = null; // 当前展开的子菜单下标（同时只展开一个）

/**
 * 展开/收起子菜单
 * @param {number} index 菜单项下标
 */
function toggleSubMenu(index) {
    expandedItem = expandedItem === index ? null : index;

    header?.querySelectorAll(".menu > .item").forEach((item, i) => {
        const expanded = i === expandedItem;
        item.querySelector(".button")?.classList.toggle("expand", expanded);
        item.querySelector(".sub-menu")?.classList.toggle("expand", expanded);
    });
}

/**
 * 初始化：header 由服务端渲染且位于 pjax 容器之外，只需绑定一次
 */
function initPanels() {
    const el = document.querySelector(".site-header.mobile");
    if (!el) return;

    if (el !== header) {
        header = el;

        header.querySelectorAll("[data-panel-toggle]").forEach((toggle) => {
            toggle.addEventListener("click", () =>
                toggleMenu(toggle.dataset.panelToggle),
            );
        });

        header.querySelectorAll(".menu > .item").forEach((item, index) => {
            item.querySelector(".button")?.addEventListener("click", () =>
                toggleSubMenu(index),
            );
        });

        const searchInput = header.querySelector(".search-input");
        searchInput?.addEventListener("keyup", (event) => {
            if (event.key !== "Enter") return;

            const keyword = searchInput.value.trim();
            if (!keyword) return;

            collapse();

            const url = `/?s=${encodeURIComponent(keyword)}`;
            if (typeof _iro.navigate === "function") _iro.navigate(url);
            else window.location.href = url;
        });

        // 点击 header 之外的区域收起面板
        onClickOutside(header, () => collapse());
    }

    // pjax 跳转后同步一次状态
    header.classList.remove("hide");
    syncHeader();
}

bus.on("scroll:update", ({ progress, direction }) => {
    scrollProgress = progress;
    scrollDirection = direction;

    if (!header) return;

    // 向下滚动收起面板并隐藏 header，向上 / 复位时恢复
    if (direction === "down") {
        collapse();
        header.classList.add("hide");
    } else {
        header.classList.remove("hide");
    }

    syncHeader();
});

_iro.hooks.onPageLoaded(() => {
    initPanels();
    collapse();
});

_iro.hooks["pjax:start"].add(() => collapse());
