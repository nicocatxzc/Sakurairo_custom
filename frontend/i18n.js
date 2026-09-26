// 轻量前端国际化
// 以中文原文作为 key，仅提供 zh_TW / en_US / ja 三份字典；
// 简体中文或字典中不存在的 key 直接返回原文。
// locale 惰性读取 _iro.config.language，因为本模块会在 app/index.ts 解析配置之前执行。

const MESSAGES = {
    zh_TW: {
        // 文章代码块
        "复制代码": "複製代碼",
        "代码已复制到剪贴板！": "代碼已複製到剪貼板！",
        "复制失败，请检查剪贴板相关权限": "複製失敗，請檢查剪貼板相關權限",
        // 验证码
        "点击图片可以刷新": "點擊圖片可以重新整理",
        "点击显示验证码": "點擊顯示驗證碼",
        "验证码": "驗證碼",
        "点击刷新": "點擊重新整理",
    },
    en_US: {
        // 文章代码块
        "复制代码": "Copy code",
        "代码已复制到剪贴板！": "Code copied to clipboard!",
        "复制失败，请检查剪贴板相关权限":
            "Copy failed. Please check the clipboard permissions.",
        // 验证码
        "点击图片可以刷新": "Click the image to refresh",
        "点击显示验证码": "Click to show the captcha",
        "验证码": "Captcha",
        "点击刷新": "Click to refresh",
    },
    ja: {
        // 文章代码块
        "复制代码": "コードをコピー",
        "代码已复制到剪贴板！": "コードをクリップボードにコピーしました！",
        "复制失败，请检查剪贴板相关权限":
            "コピーに失敗しました。クリップボードの権限を確認してください。",
        // 验证码
        "点击图片可以刷新": "画像をクリックすると更新できます",
        "点击显示验证码": "クリックして認証コードを表示",
        "验证码": "認証コード",
        "点击刷新": "クリックして更新",
    },
};

/**
 * 将 WordPress / 浏览器的 locale 归一化为字典键
 * @param {string} raw 原始 locale，如 zh_TW、en-US、ja
 * @returns {string} zh_CN / zh_TW / en_US / ja
 */
function normalizeLocale(raw) {
    const value = String(raw || "").trim().toLowerCase();
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
 * @returns {string}
 */
function translate(text, params) {
    if (typeof text !== "string") {
        return text;
    }

    const dict = MESSAGES[currentLocale()];
    let result =
        dict && Object.prototype.hasOwnProperty.call(dict, text)
            ? dict[text]
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
