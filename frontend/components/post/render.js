import mediumZoom from "medium-zoom";
import "medium-zoom/dist/style.css";
import hljs from "highlight.js";

// 全局存储当前 zoom 实例
let zoomInstance = null;

// 初始化灯箱
function initLightbox() {
    // 销毁旧实例
    if (zoomInstance) {
        zoomInstance.detach();
        zoomInstance = null;
    }

    // 查找所有符合条件的图片
    const container = document.querySelector(".post-content");
    if (!container) return;

    const images = [];
    const links = container.querySelectorAll("a");

    links.forEach((a) => {
        const img = a.querySelector("img");
        if (img) {
            images.push(img);
            // 阻止 a 标签的默认跳转行为
            a.addEventListener("click", (e) => {
                e.preventDefault();
            });
            // 阻止swup跳转
            a.classList.add("no-pjax");
            // 可添加标记以便 CSS 或调试
            img.dataset.zoomable = "";
        }
    });

    // 初始化 medium-zoom
    if (images.length) {
        zoomInstance = mediumZoom(images, {
            background: "rgba(0, 0, 0, 0.85)", // 深色背景
            margin: 24,
            scrollOffset: 0,
        });
    }
}

const COPY_ICON_SVG = /* html */ `
<i class="fa-icon-regular fa-clipboard"></i>
`;

// 处理pre标签
function enhanceCodeBlock(pre) {
    // 防止重复处理
    if (pre.dataset.enhanced === "1") return;
    pre.dataset.enhanced = "1";

    const code = pre.querySelector("code");
    if (!code) return;

    //高亮
    if (!code.dataset.highlighted) {
        const langMatch = code.className.match(/language-([\w-]+)/);
        const lang = langMatch ? langMatch[1] : "";

        try {
            if (lang && hljs.getLanguage(lang)) {
                code.innerHTML = hljs.highlight(code.textContent, {
                    language: lang,
                }).value;
            } else {
                // 为空自动识别
                code.innerHTML = hljs.highlightAuto(code.textContent).value;
            }
            code.dataset.highlighted = "1";
        } catch (e) {
            console.warn("代码高亮过程中出现异常:", e);
        }
    }

    // 类名和复制按钮
    pre.classList.add("code");

    if (pre.querySelector(".copy-button")) return;

    const button = document.createElement("button");
    button.className = "copy-button";
    button.type = "button";
    button.setAttribute("aria-label", "复制代码");
    button.innerHTML = COPY_ICON_SVG;

    button.addEventListener("click", async () => {
        try {
            await navigator.clipboard.writeText(code.textContent);
            _iro.message?.("代码已复制到剪贴板！", "success");
            button.classList.add("copied");
            setTimeout(() => button.classList.remove("copied"), 1500);
        } catch (err) {
            console.error("剪贴板写入失败:", err);
            _iro.message?.("复制失败，请检查剪贴板相关权限", "error");
        }
    });

    pre.insertBefore(button, pre.firstChild);
}

function initCodeHighlight() {
    const container = document.querySelector(".post-content");
    if (!container) return;

    container.querySelectorAll("pre").forEach(enhanceCodeBlock);
}

_iro.hooks.onPageLoaded(() => {
    if (_iro.config.lightbox == "medium_zoom") {
        initLightbox();
    }
    if (_iro.config.code_highlight) {
        initCodeHighlight();
    }
});
