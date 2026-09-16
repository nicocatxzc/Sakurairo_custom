/**
 * 规范化语言代码
 * @param {string} [lang] - 原始语言代码（可选）
 * @returns {string} 规范化的语言代码
 */
const normalizeLang = (lang) => (lang || "en").replace("_", "-").toLowerCase();

/**
 * 检测当前语言环境
 * @returns {string} 检测到的语言代码
 */
const detectLang = () => {
    const raw =
        window.iroBlockEditor?.language || window.navigator.language || "en";

    const lang = normalizeLang(raw);

    if (lang.startsWith("zh-cn") || lang.startsWith("zh-hans")) return "zh-CN";
    if (
        lang.startsWith("zh-tw") ||
        lang.startsWith("zh-hk") ||
        lang.startsWith("zh-mo")
    )
        return "zh-TW";
    if (lang.startsWith("ja")) return "ja";

    return "en";
};

/**
 * 当前语言代码
 * @type {string}
 */
export const currentLang = detectLang();

/**
 * @typedef {Object.<string, string>} Dict 字典类型
 */

/**
 * 创建国际化对象
 * @template {Object.<string, Dict>} T
 * @param {T} dict - 语言字典对象
 * @param {keyof T} [fallback='en'] - 回退语言键
 * @returns {Dict} 对应语言的字典
 */
export default function createI18n(dict, fallback = "en") {
    return dict[currentLang] || dict[fallback] || {};
}
