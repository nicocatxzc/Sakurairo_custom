import bus from "../../app/bus";
import { toggleMode, getState } from "../../app/darkmode";
import iconMoonLoop from "../icons/line-md-moon-loop.svg?raw";
import iconSunRisingLoop from "../icons/line-md-sun-rising-loop.svg?raw";
import iconThemeLightDark from "../icons/mdi-theme-light-dark.svg?raw";

const siteWidget = document.querySelector(".site-widget");

// 控制面板
const control = siteWidget.querySelector(".control");
bus.on("scroll:update", (data) => {
    const { progress, direction } = data;

    if (progress >= 5 || direction == "down") {
        control.classList.remove("hide");
    } else {
        control.classList.add("hide");
    }
});

const goToTop = siteWidget.querySelector("#goToTop");
goToTop.addEventListener("click", () => {
    scrollTo(0, 0);
});

const widgetToggle = siteWidget.querySelector("#widgetToggle");
const panel = siteWidget.querySelector(".panel");
widgetToggle.addEventListener("click", () => {
    panel.classList.toggle("hide");
});

// 深色模式
const darkmode = siteWidget.querySelector(".darkmode-toggle");
function darkmodeIcon() {
    switch (getState()) {
        case "false":
            darkmode.innerHTML = iconSunRisingLoop;
            break;
        case "true":
            darkmode.innerHTML = iconMoonLoop;
            break;
        case "auto":
            darkmode.innerHTML = iconThemeLightDark;
            break;
    }
}
_iro.hooks.DOMContentLoaded.push(darkmodeIcon);
darkmode.addEventListener("click", () => {
    toggleMode();
    darkmodeIcon();
});

// 字体
const STORAGE_KEY = "iro_global_font";

// 应用字体
function applyFont(name) {
    if (name) {
        document.documentElement.style.setProperty(
            "--global-font-family",
            name,
        );
    } else {
        document.documentElement.style.removeProperty("--global-font-family");
    }
}

// 同步按钮高亮状态
function setActive(name) {
    siteWidget
        .querySelectorAll(".font-controls button[data-name]")
        .forEach(function (btn) {
            btn.classList.toggle("active", btn.dataset.name === name);
        });
}

siteWidget.addEventListener("click", function (e) {
    const btn = e.target.closest(".font-controls button[data-name]");
    if (!btn) return;

    e.preventDefault();

    const name = btn.dataset.name;
    const current = localStorage.getItem(STORAGE_KEY);

    if (current === name) {
        // 反选
        localStorage.removeItem(STORAGE_KEY);
        applyFont(null);
        setActive(null);
    } else {
        // 选中新的，同时取消旧的
        localStorage.setItem(STORAGE_KEY, name);
        applyFont(name);
        setActive(name);
    }
});

_iro.hooks.onPageLoaded(function () {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved) {
        applyFont(saved);
        setActive(saved);
    } else {
        // 默认不选中
        applyFont(null);
        setActive(null);
    }
});
