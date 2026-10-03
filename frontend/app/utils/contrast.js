/**
 * 主色填充上的文字与图标色。
 *
 * 黑白两色的对比度在背景相对亮度 0.1791 处相等，此时各自约为 4.58:1，
 * 因此在它两侧二选一，恒能满足 WCAG AA 的正文要求，
 * 不需要为了可读性去改动主题配置或封面取色得到的主色本身。
 */
const EQUAL_CONTRAST_LUMINANCE = 0.179129;

const colorProbe = document.createElement("canvas").getContext("2d");

/**
 * 把任意 CSS 颜色解析成 [r, g, b]
 * @param {string} value CSS 颜色
 * @returns {number[] | null} 无法解析时为 null
 */
export function parseCssColor(value) {
    const input = String(value ?? "").trim();
    if (!input || !colorProbe || !CSS.supports("color", input)) {
        return null;
    }
    colorProbe.fillStyle = "#000000";
    colorProbe.fillStyle = input;

    // canvas 会把任何合法颜色归一成 #rrggbb，带透明度时归一成 rgba(...)
    const normalized = colorProbe.fillStyle;
    if (normalized.startsWith("#")) {
        return [1, 3, 5].map((start) => parseInt(normalized.slice(start, start + 2), 16));
    }
    const channels = normalized.match(/[\d.]+/g);
    return channels ? channels.slice(0, 3).map(Number) : null;
}

/**
 * WCAG 相对亮度
 * @param {number[]} rgb
 * @returns {number}
 */
export function relativeLuminance([r, g, b]) {
    const linear = (channel) => {
        const value = channel / 255;
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b);
}

/**
 * 取黑或白中在给定背景上对比度更高者
 * @param {number[]} background
 * @returns {string} "#000000" 或 "#ffffff"
 */
export function readableOn(background) {
    return relativeLuminance(background) > EQUAL_CONTRAST_LUMINANCE ? "#000000" : "#ffffff";
}

/**
 * 依据目标元素上生效的 --active-color 重算 --word-color-on-active。
 * 页面加载、深色模式切换两个来源读计算值；取色时主色刚写入内联样式，
 * 直接把已经解析好的分量传进来，省掉每个卡片一次强制样式重算。
 * @param {Element} [target]
 * @param {number[] | null} [background] 已解析的 [r, g, b]，省略时从 target 读取
 * @returns {void}
 */
export function applyReadableOnActive(target = document.documentElement, background = null) {
    const rgb = background ?? parseCssColor(getComputedStyle(target).getPropertyValue("--active-color"));
    if (!rgb) {
        return;
    }
    target.style.setProperty("--word-color-on-active", readableOn(rgb));
}
