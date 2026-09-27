import { getColorSync } from "colorthief";

/**
 * 取色后需要覆盖的 CSS 变量。
 * --border-color-shine 存的是 rgb 分量，--widget-shadow-shine-color 存的是完整颜色。
 */
export const THEME_COLOR_VARS = [
    "--active-color",
    "--border-color-sketch",
    "--widget-shadow-shining-color",
];

function isSameOrigin(url) {
    try {
        return new URL(url, window.location.href).origin === window.location.origin;
    } catch (error) {
        return false;
    }
}

function isInlineSource(url) {
    return url.startsWith("blob:") || url.startsWith("data:");
}

function waitForImage(image) {
    return new Promise((resolve, reject) => {
        if (image.complete && image.naturalWidth > 0) {
            resolve(image);
            return;
        }
        const cleanup = () => {
            image.removeEventListener("load", onLoad);
            image.removeEventListener("error", onError);
        };
        const onLoad = () => {
            cleanup();
            resolve(image);
        };
        const onError = () => {
            cleanup();
            reject(new Error("图片加载失败"));
        };
        image.addEventListener("load", onLoad);
        image.addEventListener("error", onError);
    });
}

async function fetchObjectUrl(url) {
    const response = await fetch(url, { mode: "cors", credentials: "omit" });
    if (!response.ok) {
        throw new Error(`请求图片失败：HTTP ${response.status}`);
    }
    return URL.createObjectURL(await response.blob());
}

function pickColor(image) {
    try {
        return getColorSync(image);
    } catch (error) {
        console.warn("[iro] 图片取色失败", error);
        return null;
    }
}

/** 把主色写入目标元素（默认根元素）的内联 CSS 变量 */
export function applyExtractedColor(color, target = document.documentElement) {
    if (!color || !target) {
        return;
    }
    const [r, g, b] = color.array();
    target.style.setProperty("--active-color", color.hex());
    target.style.setProperty("--border-color-sketch", `${r}, ${g}, ${b}`);
    target.style.setProperty("--widget-shadow-shining-color", `rgb(${r}, ${g}, ${b})`);
}

/** 移除目标元素上由取色写入的内联变量，恢复到样式表定义的兜底值 */
export function clearExtractedColor(target = document.documentElement) {
    if (!target) {
        return;
    }
    THEME_COLOR_VARS.forEach((name) => target.style.removeProperty(name));
}

/**
 * 从已渲染的 <img> 读取主色。跨域图片会先取回同源 blob 再替换显示，
 * 确保脚本读取的图片与实际显示的一致，同时避免 canvas 被污染。
 */
export async function extractColorFromImageElement(image) {
    if (!image) {
        return null;
    }
    const src = image.currentSrc || image.src;
    if (!src) {
        return null;
    }
    if (isSameOrigin(src) || isInlineSource(src)) {
        try {
            await waitForImage(image);
            return pickColor(image);
        } catch (error) {
            console.warn("[iro] 图片取色失败", error);
            return null;
        }
    }
    let objectUrl = null;
    try {
        objectUrl = await fetchObjectUrl(src);
        image.removeAttribute("srcset");
        image.src = objectUrl;
        await waitForImage(image);
        return pickColor(image);
    } catch (error) {
        console.warn("[iro] 跨域图片取色失败，沿用兜底色", error);
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        return null;
    }
}

/**
 * 加载远程图片并取主色，返回 { color, objectUrl }。
 * objectUrl 可直接作为同一张图的背景地址使用，保证显示与取色一致。
 */
export async function loadColorFromUrl(url) {
    if (!url) {
        return { color: null, objectUrl: null };
    }
    let objectUrl = null;
    try {
        objectUrl = await fetchObjectUrl(url);
        const image = new Image();
        image.src = objectUrl;
        await waitForImage(image);
        return { color: pickColor(image), objectUrl };
    } catch (error) {
        console.warn("[iro] 封面取色失败，沿用兜底色", error);
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        return { color: null, objectUrl: null };
    }
}
