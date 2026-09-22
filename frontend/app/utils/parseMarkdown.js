let mdInstance = null;
let mdInstanceWithTex = null;
let loadingPromise = null;

async function loadDeps(useKatex) {
    const tasks = [import("markdown-it").then((m) => m.default)];

    if (useKatex) {
        tasks.push(
            import("markdown-it-texmath").then((m) => m.default),
            import("katex").then((m) => m.default),
            import("katex/dist/katex.min.css"),
        );
    }
    return Promise.all(tasks);
}

/**
 * 将Markdown字符串渲染为HTML字符串
 *
 * @function
 * @param {string} text - 需要渲染的Markdown格式字符串
 * @returns {string} 渲染后的HTML字符串
 *
 * @description
 * 该函数将Markdown文本转换为HTML，
 *
 * @warning
 * **注意安全**：
 * 此函数不执行任何HTML清理，直接输出渲染结果
 * 应确保输入的Markdown文本来源可信
 *
 */
export default async function parseMarkdown(text) {
    const useKatex = !!_iro.config.code_katex;

    // 用缓存避免重复实例化 / 重复加载
    const cacheKey = useKatex ? "tex" : "plain";
    if (cacheKey === "tex" && mdInstanceWithTex) {
        return mdInstanceWithTex.render(text);
    }
    if (cacheKey === "plain" && mdInstance) {
        return mdInstance.render(text);
    }

    const [MarkdownIt, texmath, katex] = await loadDeps(useKatex);

    const md = new MarkdownIt({ html: true });

    if (useKatex) {
        md.use(texmath, {
            engine: katex,
            delimiters: "dollars",
            katexOptions: { throwOnError: false },
        });
        mdInstanceWithTex = md;
    } else {
        mdInstance = md;
    }

    return md.render(text);
}
