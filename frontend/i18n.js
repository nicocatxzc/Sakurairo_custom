// 前端国际化
// 以中文原文作为 key，值对象里仅提供 zh_TW / en_US / ja 三种语言；
// 简体中文或对应语言不存在时直接返回原文。
// locale 惰性读取 _iro.config.language，因为本模块会在 app/index.ts 解析配置之前执行。

const MESSAGES = {
    // 文章代码块
    复制代码: {
        zh_TW: "複製代碼",
        en_US: "Copy code",
        ja: "コードをコピー",
    },
    "代码已复制到剪贴板！": {
        zh_TW: "代碼已複製到剪貼板！",
        en_US: "Code copied to clipboard!",
        ja: "コードをクリップボードにコピーしました！",
    },
    "复制失败，请检查剪贴板相关权限": {
        zh_TW: "複製失敗，請檢查剪貼板相關權限",
        en_US: "Copy failed. Please check the clipboard permissions.",
        ja: "コピーに失敗しました。クリップボードの権限を確認してください。",
    },

    // 验证码
    点击图片可以刷新: {
        zh_TW: "點擊圖片可以重新整理",
        en_US: "Click the image to refresh",
        ja: "画像をクリックすると更新できます",
    },
    点击显示验证码: {
        zh_TW: "點擊顯示驗證碼",
        en_US: "Click to show the captcha",
        ja: "クリックして認証コードを表示",
    },
    验证码: {
        zh_TW: "驗證碼",
        en_US: "Captcha",
        ja: "認証コード",
    },
    点击刷新: {
        zh_TW: "點擊重新整理",
        en_US: "Click to refresh",
        ja: "クリックして更新",
    },

    // 首次访问的语言询问（语言名本身用各语言的自称，见 lang_switcher.js，不进字典）
    "要切换到「{name}」版本吗？": {
        zh_TW: "要切換到「{name}」版本嗎？",
        en_US: "Switch to the {name} version?",
        ja: "「{name}」版に切り替えますか？",
    },
    是: {
        zh_TW: "是",
        en_US: "Yes",
        ja: "はい",
    },
    否: {
        zh_TW: "否",
        en_US: "No",
        ja: "いいえ",
    },
    // 前导空格在值里，拼接语言名时不必再判断语种
    "（未翻译）": {
        zh_TW: "（未翻譯）",
        en_US: " (Untranslated)",
        ja: "（未翻訳）",
    },
};

/**
 * 将 WordPress / 浏览器的 locale 归一化为字典键
 * @param {string} raw 原始 locale，如 zh_TW、en-US、ja
 * @returns {string} zh_CN / zh_TW / en_US / ja
 */
function normalizeLocale(raw) {
    const value = String(raw || "")
        .trim()
        .toLowerCase();
    if (value.startsWith("ja")) return "ja";
    if (value.startsWith("en")) return "en_US";
    if (value.startsWith("zh")) {
        return /hant|tw|hk|mo/.test(value) ? "zh_TW" : "zh_CN";
    }
    return "zh_CN";
}

function currentLocale() {
    return normalizeLocale(window?._iro?.config?.language);
}

/**
 * 翻译一段文本，支持 {name} 占位符
 * @param {string} text 中文原文
 * @param {Record<string, string | number>} [params] 占位符参数
 * @param {string} [locale] 指定按哪种语言翻，省略时用当前页面语言
 *   （首次访问的询问要用「被问的那个人」的语言说出来，见 lang_switcher.js）
 * @returns {string}
 */
function translate(text, params, locale) {
    if (typeof text !== "string") {
        return text;
    }

    const lang = locale ? normalizeLocale(locale) : currentLocale();
    const entry = Object.prototype.hasOwnProperty.call(MESSAGES, text)
        ? MESSAGES[text]
        : null;

    let result =
        entry && Object.prototype.hasOwnProperty.call(entry, lang)
            ? entry[lang]
            : text;

    if (params) {
        result = result.replace(/\{(\w+)\}/g, (match, key) =>
            Object.prototype.hasOwnProperty.call(params, key)
                ? String(params[key])
                : match,
        );
    }

    return result;
}

// 既可直接调用，也提供 .t() 形式
translate.t = translate;
Object.defineProperty(translate, "locale", {
    get: currentLocale,
});

export default translate;
