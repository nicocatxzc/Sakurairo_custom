import { createApp } from "vue";
import ElementPlus from "element-plus";
import "element-plus/dist/index.css";
import "@opentiny/tiny-robot/dist/style.css";

import EditorButton from "./components/EditorButton.vue";
import ConfigPanel from "./components/ConfigPanel.vue"

const mounts = [
    ["#iro-ai-config", ConfigPanel],
    ["#iro-ai-editor", EditorButton],
];

const OWN_STYLE = /\/inc\/ai\/|element-plus|tiny-robot/;

function isOwnStyle(node) {
    if (node.tagName === "STYLE") {
        return OWN_STYLE.test(node.dataset.viteDevId ?? "");
    }

    return OWN_STYLE.test(node.getAttribute("href") ?? "");
}

// 复制样式节点
function copyStyles(shadow) {
    shadow
        .querySelectorAll("[data-iro-ai-style]")
        .forEach((node) => node.remove());

    document
        .querySelectorAll('style[data-vite-dev-id], link[rel="stylesheet"]')
        .forEach((node) => {
            if (!isOwnStyle(node)) {
                return;
            }

            const clone = node.cloneNode(true);
            clone.setAttribute("data-iro-ai-style", "");
            shadow.append(clone);
        });
}

const shadows = [];

// dev 下 Vite 靠改 <style> 的内容做样式 HMR，重新复制一份即可
function watchStyles() {
    let pending = false;

    new MutationObserver(() => {
        if (pending) {
            return;
        }

        pending = true;
        requestAnimationFrame(() => {
            pending = false;
            shadows.forEach(copyStyles);
        });
    }).observe(document.head, {
        childList: true,
        subtree: true,
        characterData: true,
    });
}

// 使用shadowDOM屏蔽wordpress样式污染
function mountInShadow(host, component) {
    const shadow = host.attachShadow({ mode: "open" });
    const container = document.createElement("div");

    shadow.append(container);
    copyStyles(shadow);
    shadows.push(shadow);

    createApp(component).use(ElementPlus).mount(container);
}

function mountApps() {
    for (const [selector, component] of mounts) {
        const host = document.querySelector(selector);

        if (host && !host.shadowRoot) {
            mountInShadow(host, component);
        }
    }

    if (shadows.length === 0) {
        return;
    }

    if (!shadows[0].querySelector("[data-iro-ai-style]")) {
        console.warn("[iro-ai] 未复制到面板样式，界面可能缺少样式");
    }

    watchStyles();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountApps, { once: true });
} else {
    mountApps();
}
