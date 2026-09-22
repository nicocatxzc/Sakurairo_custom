let MarkdownIt;
let texmath;
let katex;

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
    if (!MarkdownIt || !texmath) {
        MarkdownIt = (await import("markdown-it")).default;
        texmath = (await import("markdown-it-texmath")).default;
    }

    let tex;
    if (_iro.config.code_katex) {
        if (!katex) {
            katex = (await import("katex")).default;
            import("katex/dist/katex.min.css");
        }
        tex = {
            engine: katex,
        };
    }
    const md = new MarkdownIt({ html: true }).use(texmath, {
        ...tex,
        delimiters: "dollars", // $...$ 和 $$...$$
        katexOptions: { throwOnError: false },
    });

    const html = md.render(text);
    return html;
}
